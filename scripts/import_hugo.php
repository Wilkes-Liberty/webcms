<?php
/**
 * Drush script: Import Hugo JSON into Drupal (terms, pages, menu, redirects).
 * Usage: ddev drush scr scripts/import_hugo.php
 */

use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\redirect\Entity\Redirect;

$modulePath = DRUPAL_ROOT . '/modules/custom/wl_hugo_migrate/data';
$terms = json_decode(file_get_contents($modulePath . '/terms.json'), true)['items'] ?? [];
$pages = json_decode(file_get_contents($modulePath . '/pages.json'), true)['items'] ?? [];
$menu  = json_decode(file_get_contents($modulePath . '/menu.json'), true)['items'] ?? [];
$redirects = json_decode(file_get_contents($modulePath . '/redirects.json'), true)['items'] ?? [];

$aliasStorage = \Drupal::entityTypeManager()->getStorage('path_alias');
$saveAlias = function(string $path, string $alias) use ($aliasStorage): void {
  // Remove existing aliases for this path/lang, then create
  $existing = $aliasStorage->loadByProperties(['path' => $path, 'langcode' => 'en']);
  if ($existing) { foreach ($existing as $e) { $e->delete(); } }
  $aliasStorage->create(['path' => $path, 'alias' => $alias, 'langcode' => 'en'])->save();
};

// 1) Terms (create or update). Resolve parents by looping until stable.
$truncate255 = function($s){ return mb_substr((string)$s, 0, 255); };
$termIdMap = []; // term_id => tid
$remaining = $terms;
for ($pass = 0; $pass < 5 && count($remaining) > 0; $pass++) {
  $next = [];
  foreach ($remaining as $t) {
    $vid = $t['vid'];
    $name = $t['name'];
    $parentTermId = $t['parent_id'] ?? null;
    if ($parentTermId && !isset($termIdMap[$parentTermId])) { $next[] = $t; continue; }
    // Try to find existing by vid+name
    $existing = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => $vid, 'name' => $name]);
    /** @var \Drupal\taxonomy\Entity\Term|null $term */
    $term = $existing ? reset($existing) : null;
    if (!$term) { $term = Term::create(['vid' => $vid, 'name' => $name]); }
    // Parent
    if ($parentTermId) { $term->set('parent', [['target_id' => $termIdMap[$parentTermId]]]); }
    // Description
    if (!empty($t['description_html'])) {
      $term->set('description', ['value' => $t['description_html'], 'format' => 'basic_html']);
    }
    // SEO fields if present
foreach (['field_seo_title' => 'seo_title', 'field_meta_description' => 'meta_description'] as $field => $src) {
      if ($term->hasField($field) && isset($t[$src])) {
        $val = $t[$src];
        if (in_array($field, ['field_seo_title','field_meta_description'], true)) { $val = $truncate255($val); }
        $term->set($field, $val);
      }
    }
    $term->save();
    $tid = (int) $term->id();
    $termIdMap[$t['term_id']] = $tid;
    // Path alias
    if (!empty($t['path'])) {
$saveAlias("/taxonomy/term/$tid", $t['path']);
    }
  }
  $remaining = $next;
}
print "Imported terms: " . count($termIdMap) . "\n";

// 2) Pages (basic_page nodes)
$nodeCount = 0;
foreach ($pages as $p) {
  $path = $p['path'];
  $title = $p['title'] ?? $path;
  // Find existing by alias
  $nids = \Drupal::entityQuery('node')->accessCheck(FALSE)->condition('type','basic_page')->condition('title',$title)->execute();
  $node = $nids ? Node::load(reset($nids)) : Node::create(['type' => 'basic_page']);
  $node->setTitle($title);
  if (!empty($p['body_html'])) { $node->set('body', ['value' => $p['body_html'], 'format' => 'basic_html']); }
foreach (['field_seo_title' => 'seo_title', 'field_meta_description' => 'meta_description'] as $field => $src) {
    if ($node->hasField($field) && isset($p[$src])) {
      $val = $p[$src];
      if (in_array($field, ['field_seo_title','field_meta_description'], true)) { $val = $truncate255($val); }
      $node->set($field, $val);
    }
  }
  $node->set('status', 1);
  $node->save();
$saveAlias('/node/' . $node->id(), $path);
  $nodeCount++;
}
print "Imported pages: $nodeCount\n";

// 3) Menu (Main) - build map of uuid to link for parent assignment
$menuName = 'main';
$linkCount = 0;
$uuidToLink = [];
foreach ($menu as $m) {
  // Try to find existing link by uuid third-party setting or by URL+title
  $existingId = \Drupal::entityQuery('menu_link_content')->accessCheck(FALSE)->condition('menu_name', $menuName)->condition('link__uri', 'internal:' . $m['url'])->condition('title', $m['title'])->execute();
  $ml = $existingId ? MenuLinkContent::load(reset($existingId)) : MenuLinkContent::create(['menu_name' => $menuName]);
  $ml->set('title', $m['title']);
  $ml->set('link', ['uri' => 'internal:' . $m['url'], 'title' => $m['title']]);
  $ml->set('enabled', 1);
  if (isset($m['weight'])) { $ml->set('weight', (int) $m['weight']); }
  $ml->save();
  $uuidToLink[$m['uuid']] = $ml;
  $linkCount++;
}
// Second pass: set parents now that all exist
foreach ($menu as $m) {
  if (!empty($m['parent_uuid']) && isset($uuidToLink[$m['uuid']]) && isset($uuidToLink[$m['parent_uuid']])) {
    $child = $uuidToLink[$m['uuid']];
    $parent = $uuidToLink[$m['parent_uuid']];
    $child->set('parent', 'menu_link_content:' . $parent->uuid());
    $child->save();
  }
}
print "Imported menu links: $linkCount\n";

// 4) Redirects
$redirCount = 0;
foreach ($redirects as $r) {
  $from = trim($r['from'], '/');
  $to = $r['to'];
  if (!$from || !$to) continue;
  // Check if redirect exists
  $ids = \Drupal::entityQuery('redirect')->accessCheck(FALSE)->condition('redirect_source__path', $from)->execute();
  $redir = $ids ? Redirect::load(reset($ids)) : Redirect::create();
  $redir->setSource($from);
$redir->setRedirect('internal:' . $to);
  $redir->setStatusCode(301);
  $redir->save();
  $redirCount++;
}
print "Imported redirects: $redirCount\n";
