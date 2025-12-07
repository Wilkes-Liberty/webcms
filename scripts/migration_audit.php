<?php
/**
 * Audit migrated content vs exported JSON (terms/pages/menu/redirects).
 * Prints any missing items. Exit code 0 if all matched, 1 otherwise.
 * Usage: ddev drush scr scripts/migration_audit.php
 */

use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\redirect\Entity\Redirect;

$base = DRUPAL_ROOT . '/modules/custom/wl_hugo_migrate/data';
$terms = json_decode(file_get_contents($base . '/terms.json'), true)['items'] ?? [];
$pages = json_decode(file_get_contents($base . '/pages.json'), true)['items'] ?? [];
$menu  = json_decode(file_get_contents($base . '/menu.json'), true)['items'] ?? [];
$redirects = json_decode(file_get_contents($base . '/redirects.json'), true)['items'] ?? [];

$aliasManager = \Drupal::service('path_alias.manager');

$missing = [];

$norm = function(string $p): string {
  $p = trim($p);
  if ($p === '') return '/';
  if ($p[0] !== '/') $p = '/' . $p;
  $p = rtrim($p, '/');
  return $p === '' ? '/' : $p;
};

// 1) Terms: ensure term exists in vocab; check alias or redirect from original path.
foreach ($terms as $t) {
  $vid = $t['vid']; $name = $t['name']; $origPath = $t['path'];
  $ids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid',$vid)->condition('name',$name)->execute();
  if (!$ids) { $missing[] = "TERM missing: $vid :: $name"; continue; }
  $tid = reset($ids); $internal = '/taxonomy/term/'.$tid;
$alias = $aliasManager->getAliasByPath($internal, 'en');
  $aliasN = $norm($alias);
  $origN  = $norm($origPath);
if ($vid === 'capabilities') {
    // We switched to /capabilities/*; ensure a redirect exists from original path.
    if ($origPath && $origN !== $aliasN) {
      $rid = \Drupal::entityQuery('redirect')->accessCheck(FALSE)->condition('redirect_source__path', ltrim($origN,'/'))->range(0,1)->execute();
      if (!$rid) { $missing[] = "REDIRECT missing for capabilities: from $origN to $aliasN"; }
    }
  } else {
    if ($origPath && $aliasN !== $origN) {
      $missing[] = "ALIAS mismatch: $vid :: $name expected $origN got $aliasN";
    }
  }
}

// 2) Pages: ensure node exists with alias path.
foreach ($pages as $p) {
$alias = $p['path'];
  $aliasN = $norm($alias);
  // Reverse lookup alias -> internal path (try with and without trailing slash)
  $repo = \Drupal::service('path_alias.repository');
  $res = $repo->lookupByAlias($aliasN, 'en') ?: $repo->lookupByAlias($aliasN . '/', 'en');
  if (!$res) { $missing[] = "PAGE alias missing: $aliasN"; continue; }
  $path = is_array($res) ? ($res['path'] ?? '') : $res;
  if (!is_string($path) || strpos($path, '/node/') !== 0) { $missing[] = "PAGE alias not pointing to node: $aliasN -> ".print_r($res, true); continue; }
}

// 3) Menu: ensure each URL in exported menu exists in Main.
$allMainIds = \Drupal::entityQuery('menu_link_content')->accessCheck(FALSE)->condition('menu_name','main')->execute();
$byUri = [];
if ($allMainIds) {
  foreach (MenuLinkContent::loadMultiple($allMainIds) as $ml) {
    $item = $ml->get('link')->first(); if (!$item) continue;
    $uri = (string) $item->get('uri')->getString();
    $byUri[$uri] = true;
  }
}
foreach ($menu as $m) {
  $url = $m['url'] ?? '/';
  if ($url === '/services/') { continue; } // intentionally removed
  $expect = 'internal:' . rtrim($url, '/');
  $expect2 = 'internal:' . rtrim($url, '/') . '/';
  if (empty($byUri[$expect]) && empty($byUri[$expect2])) {
    $missing[] = "MENU link missing: {$m['title']} -> {$url}";
  }
}

// 4) Redirects: ensure each exists.
foreach ($redirects as $r) {
  $fromNorm = ltrim($norm((string)$r['from']), '/');
  // Skip self-redirects (alias equals canonical path)
  $aliasLookup = \Drupal::service('path_alias.repository')->lookupByAlias('/' . $fromNorm, 'en');
  if ($aliasLookup && $aliasLookup === '/' . $fromNorm) { continue; }
  $rid = \Drupal::entityQuery('redirect')->accessCheck(FALSE)->condition('redirect_source__path',$fromNorm)->range(0,1)->execute();
  if (!$rid) { $missing[] = "REDIRECT missing: from /{$fromNorm}"; }
}

if ($missing) {
  print "\nAUDIT RESULT: FAIL\n";
  foreach ($missing as $m) print " - $m\n";
  exit(1);
}
print "\nAUDIT RESULT: PASS (terms: ".count($terms).", pages: ".count($pages).", menu links: ".count($menu).", redirects: ".count($redirects).")\n";
