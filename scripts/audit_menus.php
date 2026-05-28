<?php
/**
 * Menu audit — compare DB state against seed_menu.php definitions.
 *
 * Reports:
 *   1. Everything currently in the main and footer menus (DB state).
 *   2. Items defined in seed_menu.php that are MISSING from the DB.
 *   3. Items in the DB that are NOT defined in seed_menu.php (orphans).
 *   4. Items with mismatched URLs (DB differs from seed definition).
 *
 * Read-only — makes no changes.
 *
 * Run:
 *   ddev drush scr scripts/audit_menus.php
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

// ---------------------------------------------------------------------------
// Canonical definitions (kept in sync with seed_menu.php)
// ---------------------------------------------------------------------------

$WL_MAIN_TOP = [
  'Solutions' => '/solutions',
  'Platforms' => '/platforms',
  'Services'  => '/services',
  'Resources' => 'internal:/resources',
  'About'     => '/about',
  'Federal'   => '/federal',
  'Contact'   => '/contact',
];

$WL_MAIN_CHILDREN = [
  'Solutions' => [
    'DotEDU — Higher Education'       => '/solutions/dotedu',
    'Accord — Nonprofit'              => '/solutions/accord',
    'Palisade — Privacy SaaS'         => '/solutions/palisade',
    'Bulkhead — Regulated Industries' => '/solutions/bulkhead',
    'DotGov — Federal Civilian'       => '/solutions/dotgov',
    'Gazette — IG Platforms'          => '/solutions/gazette',
    'Outpost — Defense Tech'          => '/solutions/outpost',
    'Software Factory'                => '/solutions/software-factory',
  ],
  'Platforms' => [
    'Sabal Infrastructure Platform'        => '/platforms/sabal',
    'Keel CMS Platform'                    => '/platforms/keel',
    'Alidade Search Platform'              => '/platforms/alidade',
    'Squawk Zero-Trust Identity Platform'  => '/platforms/squawk',
    'Manifest Data Platform'               => '/platforms/manifest',
    'Lighthouse Observability Platform'    => '/platforms/lighthouse',
    'Coquina Software Factory Platform'    => '/platforms/coquina',
  ],
  'Services' => [
    'Private Infrastructure Engineering' => '/services/private-infrastructure-engineering',
    'Zero-Trust Identity Consulting'     => '/services/zero-trust-identity-consulting',
    'Defense Technology Integration'     => '/services/defense-technology-integration',
    'Headless CMS Implementation'        => '/services/headless-cms-implementation',
    'Enterprise Search Architecture'     => '/services/enterprise-search-architecture',
    'AI Integration'                     => '/services/ai-integration',
    'Digital Modernization'              => '/services/digital-modernization',
    'Custom Software Development'        => '/services/custom-software-development',
    'Integration Engineering'            => '/services/integration-engineering',
    'Digital Asset Solutions'            => '/services/digital-asset-solutions',
    'Intelligence & Actionable Insights' => '/services/intelligence-actionable-insights',
  ],
  'Resources' => [
    'Case Studies'        => '/case-studies',
    'Articles & Insights' => '/articles',
    'Downloads & Guides'  => '/resources/downloads-guides',
    'Press'               => '/press',
  ],
];

$WL_FOOTER_TOP = [
  'Platforms' => '/platforms',
  'Services'  => '/services',
  'Solutions' => '/solutions',
  'Company'   => '/about',
  'Resources' => 'internal:/resources',
  'Legal'     => '/legal/privacy-policy',
];

$WL_FOOTER_CHILDREN = [
  'Platforms' => [
    'Sabal Infrastructure Platform'        => '/platforms/sabal',
    'Keel CMS Platform'                    => '/platforms/keel',
    'Alidade Search Platform'              => '/platforms/alidade',
    'Squawk Zero-Trust Identity Platform'  => '/platforms/squawk',
    'Manifest Data Platform'               => '/platforms/manifest',
    'Lighthouse Observability Platform'    => '/platforms/lighthouse',
    'Coquina Software Factory Platform'    => '/platforms/coquina',
  ],
  'Services' => [
    'Private Infrastructure Engineering' => '/services/private-infrastructure-engineering',
    'Zero-Trust Identity Consulting'     => '/services/zero-trust-identity-consulting',
    'Defense Technology Integration'     => '/services/defense-technology-integration',
    'Headless CMS Implementation'        => '/services/headless-cms-implementation',
    'Enterprise Search Architecture'     => '/services/enterprise-search-architecture',
    'AI Integration'                     => '/services/ai-integration',
    'Digital Modernization'              => '/services/digital-modernization',
    'Custom Software Development'        => '/services/custom-software-development',
    'Integration Engineering'            => '/services/integration-engineering',
    'Digital Asset Solutions'            => '/services/digital-asset-solutions',
    'Intelligence & Actionable Insights' => '/services/intelligence-actionable-insights',
  ],
  'Solutions' => [
    'DotEDU — Higher Education'       => '/solutions/dotedu',
    'Accord — Nonprofit'              => '/solutions/accord',
    'Palisade — Privacy SaaS'         => '/solutions/palisade',
    'Bulkhead — Regulated Industries' => '/solutions/bulkhead',
    'DotGov — Federal Civilian'       => '/solutions/dotgov',
    'Gazette — IG Platforms'          => '/solutions/gazette',
    'Outpost — Defense Tech'          => '/solutions/outpost',
    'Software Factory'                => '/solutions/software-factory',
  ],
  'Company' => [
    'About'    => '/about',
    'Contact'  => '/contact',
    'Federal'  => '/federal',
    'Partners' => '/partners',
  ],
  'Resources' => [
    'Articles & Insights' => '/articles',
    'Case Studies'        => '/case-studies',
    'Downloads & Guides'  => '/resources/downloads-guides',
    'Press'               => '/press',
  ],
  'Legal' => [
    'Privacy Policy'          => '/legal/privacy-policy',
    'Terms of Service'        => '/legal/terms-of-service',
    'Cookie Policy'           => '/legal/cookie-policy',
    'Accessibility Statement' => '/legal/accessibility-statement',
  ],
];

// ---------------------------------------------------------------------------
// Load DB state
// ---------------------------------------------------------------------------

/**
 * Load all menu_link_content for a given menu, keyed by title.
 * Returns [ title => ['uri' => ..., 'parent' => ..., 'uuid' => ..., 'enabled' => bool] ]
 *
 * Note: storage is fetched inline rather than via a global variable because
 * drush scr includes the script inside a method scope, meaning script-level
 * variables are NOT in PHP's global scope and global $var would be null.
 */
function wla_load_menu(string $menu_name): array {
  $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  $ids = $storage->getQuery()
    ->condition('menu_name', $menu_name)
    ->accessCheck(false)
    ->execute();
  $result = [];
  foreach ($storage->loadMultiple($ids) as $item) {
    $title  = $item->getTitle();
    $uri    = $item->link->uri ?? '';
    $parent = $item->get('parent')->value ?? '';
    // Resolve parent UUID to title for display.
    $parent_label = '';
    if ($parent && preg_match('/menu_link_content:(.+)/', $parent, $m)) {
      $parent_items = $storage->loadByProperties(['uuid' => $m[1]]);
      if ($parent_items) {
        $parent_label = reset($parent_items)->getTitle();
      }
    }
    $result[$title] = [
      'uri'          => $uri,
      'parent'       => $parent,
      'parent_label' => $parent_label,
      'uuid'         => $item->uuid(),
      'enabled'      => $item->isEnabled(),
      'weight'       => (int) $item->get('weight')->value,
    ];
  }
  return $result;
}

/** Normalise URI for comparison: strip 'internal:' prefix, lowercase. */
function wla_norm(string $uri): string {
  return strtolower(str_replace('internal:', '', $uri));
}

// ---------------------------------------------------------------------------
// Section 1: DB inventory
// ---------------------------------------------------------------------------

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║              MENU AUDIT — DB INVENTORY                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

foreach (['main', 'footer'] as $menu_name) {
  $db_items = wla_load_menu($menu_name);

  // Split into top-level and children.
  $top      = [];
  $children = [];
  foreach ($db_items as $title => $info) {
    if ($info['parent'] === '' || $info['parent'] === null) {
      $top[$title] = $info;
    } else {
      $children[$info['parent_label']][$title] = $info;
    }
  }

  uasort($top, fn($a, $b) => $a['weight'] <=> $b['weight']);

  $label = strtoupper($menu_name);
  echo "┌─ {$label} MENU ({$menu_name}) ── " . count($db_items) . " total items ─────────────────\n";

  if (empty($db_items)) {
    echo "│  (empty — nothing seeded yet)\n";
    echo "└──────────────────────────────────────────────────────────────\n\n";
    continue;
  }

  foreach ($top as $title => $info) {
    $status  = $info['enabled'] ? '✓' : '✗';
    $uri_disp = str_replace('internal:', '', $info['uri']);
    printf("│  [%s] w=%-3d %-42s %s\n", $status, $info['weight'], $title, $uri_disp);

    if (!empty($children[$title])) {
      uasort($children[$title], fn($a, $b) => $a['weight'] <=> $b['weight']);
      foreach ($children[$title] as $ctitle => $cinfo) {
        $cstatus  = $cinfo['enabled'] ? '✓' : '✗';
        $curi     = str_replace('internal:', '', $cinfo['uri']);
        printf("│       [%s] w=%-3d %-38s %s\n", $cstatus, $cinfo['weight'], $ctitle, $curi);
      }
    }
  }
  echo "└──────────────────────────────────────────────────────────────\n\n";
}

// ---------------------------------------------------------------------------
// Section 2: Gap analysis — main menu
// ---------------------------------------------------------------------------

echo "┌─ MAIN MENU — GAP ANALYSIS ───────────────────────────────────\n";

$db_main = wla_load_menu('main');
$db_main_titles = array_keys($db_main);

$missing_main  = [];
$mismatch_main = [];
$orphan_main   = [];

// Expected top-level.
foreach ($WL_MAIN_TOP as $title => $expected_url) {
  if (!isset($db_main[$title])) {
    $missing_main[] = "TOP  {$title}  ({$expected_url})";
  } elseif (wla_norm($db_main[$title]['uri']) !== wla_norm($expected_url)) {
    $mismatch_main[] = "TOP  {$title}  DB='{$db_main[$title]['uri']}'  EXPECTED='{$expected_url}'";
  }
}

// Expected children.
foreach ($WL_MAIN_CHILDREN as $parent => $items) {
  foreach ($items as $title => $expected_url) {
    if (!isset($db_main[$title])) {
      $missing_main[] = "  ↳ [{$parent}]  {$title}  ({$expected_url})";
    } elseif (wla_norm($db_main[$title]['uri']) !== wla_norm($expected_url)) {
      $mismatch_main[] = "  ↳ [{$parent}]  {$title}  DB='{$db_main[$title]['uri']}'  EXPECTED='{$expected_url}'";
    }
  }
}

// Expected titles flat list.
$expected_main_titles = array_keys($WL_MAIN_TOP);
foreach ($WL_MAIN_CHILDREN as $items) {
  $expected_main_titles = array_merge($expected_main_titles, array_keys($items));
}
foreach ($db_main_titles as $title) {
  if (!in_array($title, $expected_main_titles)) {
    $orphan_main[] = "{$title}  ({$db_main[$title]['uri']})";
  }
}

if (empty($missing_main) && empty($mismatch_main) && empty($orphan_main)) {
  echo "│  ✓ Main menu is fully in sync with seed_menu.php\n";
} else {
  if ($missing_main) {
    echo "│  MISSING (" . count($missing_main) . "):\n";
    foreach ($missing_main as $m) { echo "│    [+] {$m}\n"; }
  }
  if ($mismatch_main) {
    echo "│  URL MISMATCH (" . count($mismatch_main) . "):\n";
    foreach ($mismatch_main as $m) { echo "│    [~] {$m}\n"; }
  }
  if ($orphan_main) {
    echo "│  ORPHAN (in DB, not in seed definition) (" . count($orphan_main) . "):\n";
    foreach ($orphan_main as $m) { echo "│    [?] {$m}\n"; }
  }
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ---------------------------------------------------------------------------
// Section 3: Gap analysis — footer menu
// ---------------------------------------------------------------------------

echo "┌─ FOOTER MENU — GAP ANALYSIS ─────────────────────────────────\n";

$db_footer = wla_load_menu('footer');
$db_footer_titles = array_keys($db_footer);

$missing_footer  = [];
$mismatch_footer = [];
$orphan_footer   = [];

foreach ($WL_FOOTER_TOP as $title => $expected_url) {
  if (!isset($db_footer[$title])) {
    $missing_footer[] = "TOP  {$title}  ({$expected_url})";
  } elseif (wla_norm($db_footer[$title]['uri']) !== wla_norm($expected_url)) {
    $mismatch_footer[] = "TOP  {$title}  DB='{$db_footer[$title]['uri']}'  EXPECTED='{$expected_url}'";
  }
}

foreach ($WL_FOOTER_CHILDREN as $parent => $items) {
  foreach ($items as $title => $expected_url) {
    if (!isset($db_footer[$title])) {
      $missing_footer[] = "  ↳ [{$parent}]  {$title}  ({$expected_url})";
    } elseif (wla_norm($db_footer[$title]['uri']) !== wla_norm($expected_url)) {
      $mismatch_footer[] = "  ↳ [{$parent}]  {$title}  DB='{$db_footer[$title]['uri']}'  EXPECTED='{$expected_url}'";
    }
  }
}

$expected_footer_titles = array_keys($WL_FOOTER_TOP);
foreach ($WL_FOOTER_CHILDREN as $items) {
  $expected_footer_titles = array_merge($expected_footer_titles, array_keys($items));
}
// Footer shares some titles with main (e.g. "Platforms", "Services") — scope orphan check to footer DB only.
foreach ($db_footer_titles as $title) {
  if (!in_array($title, $expected_footer_titles)) {
    $orphan_footer[] = "{$title}  ({$db_footer[$title]['uri']})";
  }
}

if (empty($missing_footer) && empty($mismatch_footer) && empty($orphan_footer)) {
  echo "│  ✓ Footer menu is fully in sync with seed_menu.php\n";
} else {
  if ($missing_footer) {
    echo "│  MISSING (" . count($missing_footer) . "):\n";
    foreach ($missing_footer as $m) { echo "│    [+] {$m}\n"; }
  }
  if ($mismatch_footer) {
    echo "│  URL MISMATCH (" . count($mismatch_footer) . "):\n";
    foreach ($mismatch_footer as $m) { echo "│    [~] {$m}\n"; }
  }
  if ($orphan_footer) {
    echo "│  ORPHAN (" . count($orphan_footer) . "):\n";
    foreach ($orphan_footer as $m) { echo "│    [?] {$m}\n"; }
  }
}
echo "└──────────────────────────────────────────────────────────────\n\n";

// ---------------------------------------------------------------------------
// Section 4: Cross-menu comparison
// ---------------------------------------------------------------------------

echo "┌─ CROSS-MENU COMPARISON ──────────────────────────────────────\n";
echo "│  Items in main but NOT footer (expected omissions noted):\n";

// Items expected ONLY in main (top-level like About which isn't a footer section header).
$main_only_expected = ['About'];  // About is in footer under Company, not as top-level.
// array_values() strips string keys before spread to avoid PHP 8 named-parameter error.
$main_titles_flat = array_merge(array_keys($WL_MAIN_TOP), ...array_values(array_map('array_keys', $WL_MAIN_CHILDREN)));
$footer_titles_flat = array_merge(array_keys($WL_FOOTER_TOP), ...array_values(array_map('array_keys', $WL_FOOTER_CHILDREN)));

$in_main_not_footer = array_diff($main_titles_flat, $footer_titles_flat);
$in_footer_not_main = array_diff($footer_titles_flat, $main_titles_flat);

if ($in_main_not_footer) {
  foreach ($in_main_not_footer as $t) {
    $note = in_array($t, ['About', 'Resources', 'Solutions', 'Platforms', 'Services']) ? ' (top-level differs in footer)' : '';
    echo "│    {$t}{$note}\n";
  }
} else {
  echo "│    (none — all main items also appear in footer)\n";
}

echo "│\n│  Items in footer but NOT main:\n";
if ($in_footer_not_main) {
  foreach ($in_footer_not_main as $t) {
    echo "│    {$t}\n";
  }
} else {
  echo "│    (none)\n";
}

echo "└──────────────────────────────────────────────────────────────\n\n";

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

$total_missing  = count($missing_main)  + count($missing_footer);
$total_mismatch = count($mismatch_main) + count($mismatch_footer);
$total_orphan   = count($orphan_main)   + count($orphan_footer);

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  SUMMARY                                                     ║\n";
echo "╠══════════════════════════════════════════════════════════════╣\n";
printf("║  Main menu items in DB:    %-4d                             ║\n", count($db_main));
printf("║  Footer menu items in DB:  %-4d                             ║\n", count($db_footer));
printf("║  Missing (need seeding):   %-4d                             ║\n", $total_missing);
printf("║  URL mismatches:           %-4d                             ║\n", $total_mismatch);
printf("║  Orphans (not in seed):    %-4d                             ║\n", $total_orphan);
echo "╠══════════════════════════════════════════════════════════════╣\n";
if ($total_missing + $total_mismatch + $total_orphan === 0) {
  echo "║  ✓  Both menus are fully in sync.                           ║\n";
} else {
  echo "║  ✗  Run: ddev drush scr scripts/seed_menu.php -- --update   ║\n";
}
echo "╚══════════════════════════════════════════════════════════════╝\n";
