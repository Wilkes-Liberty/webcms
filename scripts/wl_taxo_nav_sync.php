<?php
/**
 * One-off utility: Sync top-level Capabilities/Industries terms with field_show_in_nav
 * into the Main menu. Safe to re-run. No need to enable wl_taxo_nav.
 * Usage: ddev drush scr scripts/wl_taxo_nav_sync.php
 */

use Drupal\taxonomy\Entity\Term;
use Drupal\menu_link_content\Entity\MenuLinkContent;

$allowed = ['capabilities','industries'];

function find_link_for_tid(int $tid): ?MenuLinkContent {
  $ids = \Drupal::entityQuery('menu_link_content')->accessCheck(FALSE)->condition('menu_name','main')->execute();
  if (!$ids) return NULL;
  foreach (MenuLinkContent::loadMultiple($ids) as $link) {
    $item = $link->get('link')->first(); if (!$item) continue;
    $options = $item->get('options')->getValue();
    $opts = is_array($options) && isset($options[0]['value']) ? $options[0]['value'] : [];
    if ((int) ($opts['wl_taxo_nav_tid'] ?? 0) === $tid) return $link;
  }
  return NULL;
}

$count = 0; $removed = 0;
foreach ($allowed as $vid) {
  $tids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid',$vid)->execute();
  if (!$tids) continue;
  foreach (Term::loadMultiple($tids) as $term) {
    // Only top-level terms with show_in_nav checked.
    $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($term->id());
    if ($parents) continue;
    $show = (bool) ($term->get('field_show_in_nav')->value ?? FALSE);
    $existing = find_link_for_tid((int)$term->id());
    if ($show) {
      if (!$existing) {
        $existing = MenuLinkContent::create([
          'title' => $term->label(),
          'menu_name' => 'main',
          'link' => [
            'uri' => 'route:entity.taxonomy_term.canonical',
            'title' => $term->label(),
            'options' => [
              'route_parameters' => ['taxonomy_term' => (int) $term->id()],
              'wl_taxo_nav_tid' => (int) $term->id(),
            ],
          ],
          'enabled' => TRUE,
          'weight' => 0,
        ]);
      } else {
        $existing->set('title', $term->label());
        $existing->set('link', [
          'uri' => 'route:entity.taxonomy_term.canonical',
          'title' => $term->label(),
          'options' => [
            'route_parameters' => ['taxonomy_term' => (int) $term->id()],
            'wl_taxo_nav_tid' => (int) $term->id(),
          ],
        ]);
        $existing->set('enabled', TRUE);
      }
      $existing->save();
      $count++;
    } else {
      if ($existing) { $existing->delete(); $removed++; }
    }
  }
}
print "Created/updated links: $count, removed: $removed\n";
