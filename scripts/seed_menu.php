<?php
/**
 * Seed main and footer navigation menu_link_content entities.
 *
 * Main menu hierarchy:
 *   - 7 top-level items (Solutions, Platforms, Services, Resources, About, Federal, Contact)
 *   - 8 children under Solutions
 *   - 7 children under Platforms
 *   - 11 children under Services (3 Infrastructure & Security + 8 Modernization & Integration)
 *   - 4 children under Resources
 *
 * Footer menu hierarchy (menu_name = 'footer') — 6 columns:
 *   - Platforms  (7 platform links)
 *   - Services   (11 service links)
 *   - Solutions  (8 solution links)
 *   - Company    (About, Contact, Federal, Partners)
 *   - Resources  (Articles & Insights, Case Studies, Downloads & Guides, Press)
 *   - Legal      (Privacy Policy, Terms of Service, Cookie Policy, Accessibility Statement)
 *
 * Idempotent — safe to re-run. Matches existing items by title + menu name and,
 * by default, skips them. Will NOT overwrite manual changes made through the
 * admin UI.
 *
 * Modes:
 *   (default)   skip-if-exists. Create missing items; leave existing ones alone.
 *   --dry-run   report what would happen without writing anything to the DB.
 *               Overrides --update.
 *   --update    when an item already exists, overwrite its link, weight, and
 *               expanded flag with the values defined in this script. Title and
 *               parent are never changed in update mode.
 *   --prune     delete any menu_link_content entity whose title is NOT defined
 *               in this script. Use to remove stale items from old seeding runs.
 *               Combine with --dry-run to preview before deleting.
 *
 * Run:
 *   ddev drush scr scripts/seed_menu.php
 *   ddev drush scr scripts/seed_menu.php -- --dry-run
 *   ddev drush scr scripts/seed_menu.php -- --update
 *   ddev drush scr scripts/seed_menu.php -- --dry-run --update
 *   ddev drush scr scripts/seed_menu.php -- --dry-run --prune
 *   ddev drush scr scripts/seed_menu.php -- --prune
 *
 * On production:
 *   docker compose exec drupal drush scr /var/www/html/scripts/seed_menu.php -- --update
 *
 * The `--` separator is required so Drush passes the flags through to the
 * script as $extra instead of trying to parse them itself.
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

// ---------------------------------------------------------------------------
// CLI flag parsing
// ---------------------------------------------------------------------------

$wl_argv = isset($extra) && is_array($extra) ? $extra : array_slice($_SERVER['argv'] ?? [], 1);
$WL_DRY_RUN = in_array('--dry-run', $wl_argv, true);
$WL_UPDATE  = in_array('--update', $wl_argv, true);
$WL_PRUNE   = in_array('--prune',  $wl_argv, true);

// ---------------------------------------------------------------------------
// Menu structure definition
// ---------------------------------------------------------------------------

/**
 * Top-level items. Keys used internally to wire up parent references.
 * Format: [ 'key' => [...fields...], ... ]
 */
$WL_TOP_LEVEL = [
  'solutions' => [
    'title'    => 'Solutions',
    'url'      => '/solutions',
    'weight'   => 0,
    'expanded' => true,
  ],
  'platforms' => [
    'title'    => 'Platforms',
    'url'      => '/platforms',
    'weight'   => 10,
    'expanded' => true,
  ],
  'services' => [
    'title'    => 'Services',
    'url'      => '/services',
    'weight'   => 20,
    'expanded' => true,
  ],
  'resources' => [
    'title'    => 'Resources',
    'url'      => 'internal:/resources',
    'weight'   => 30,
    'expanded' => true,
  ],
  'about' => [
    'title'    => 'About',
    'url'      => '/about',
    'weight'   => 40,
    'expanded' => false,
  ],
  'federal' => [
    'title'    => 'Federal',
    'url'      => '/federal',
    'weight'   => 50,
    'expanded' => false,
  ],
  'contact' => [
    'title'    => 'Contact',
    'url'      => '/contact',
    'weight'   => 60,
    'expanded' => false,
  ],
];

/**
 * Child items keyed by parent key (matching $WL_TOP_LEVEL keys above).
 */
$WL_CHILDREN = [
  'solutions' => [
    ['title' => 'DotEDU — Higher Education',    'url' => '/solutions/dotedu',   'weight' => 0],
    ['title' => 'Accord — Nonprofit',           'url' => '/solutions/accord',   'weight' => 10],
    ['title' => 'Palisade — Privacy SaaS',      'url' => '/solutions/palisade', 'weight' => 20],
    ['title' => 'Bulkhead — Regulated Industries', 'url' => '/solutions/bulkhead', 'weight' => 30],
    ['title' => 'DotGov — Federal Civilian',    'url' => '/solutions/dotgov',   'weight' => 40],
    ['title' => 'Gazette — IG Platforms',       'url' => '/solutions/gazette',  'weight' => 50],
    ['title' => 'Outpost — Defense Tech',       'url' => '/solutions/outpost',        'weight' => 60],
    ['title' => 'Software Factory',             'url' => '/solutions/software-factory', 'weight' => 70],
  ],
  'platforms' => [
    ['title' => 'Sabal Infrastructure Platform',        'url' => '/platforms/sabal',      'weight' => 0],
    ['title' => 'Keel CMS Platform',                    'url' => '/platforms/keel',       'weight' => 10],
    ['title' => 'Alidade Search Platform',              'url' => '/platforms/alidade',    'weight' => 20],
    ['title' => 'Squawk Zero-Trust Identity Platform',  'url' => '/platforms/squawk',     'weight' => 30],
    ['title' => 'Manifest Data Platform',               'url' => '/platforms/manifest',   'weight' => 40],
    ['title' => 'Lighthouse Observability Platform',    'url' => '/platforms/lighthouse', 'weight' => 50],
    ['title' => 'Coquina Software Factory Platform',    'url' => '/platforms/coquina',    'weight' => 60],
  ],
  'services' => [
    // ── Infrastructure & Security ───────────────────────────────────────────
    ['title' => 'Private Infrastructure Engineering', 'url' => '/services/private-infrastructure-engineering', 'weight' => 0],
    ['title' => 'Zero-Trust Identity Consulting',     'url' => '/services/zero-trust-identity-consulting',     'weight' => 10],
    ['title' => 'Defense Technology Integration',     'url' => '/services/defense-technology-integration',     'weight' => 20],
    // ── Modernization & Integration ─────────────────────────────────────────
    ['title' => 'Headless CMS Implementation',        'url' => '/services/headless-cms-implementation',        'weight' => 30],
    ['title' => 'Enterprise Search Architecture',     'url' => '/services/enterprise-search-architecture',     'weight' => 40],
    ['title' => 'AI Integration',                     'url' => '/services/ai-integration',                     'weight' => 50],
    ['title' => 'Digital Modernization',              'url' => '/services/digital-modernization',              'weight' => 60],
    ['title' => 'Custom Software Development',        'url' => '/services/custom-software-development',        'weight' => 70],
    ['title' => 'Integration Engineering',            'url' => '/services/integration-engineering',            'weight' => 80],
    ['title' => 'Digital Asset Solutions',            'url' => '/services/digital-asset-solutions',            'weight' => 90],
    ['title' => 'Intelligence & Actionable Insights', 'url' => '/services/intelligence-actionable-insights',   'weight' => 100],
  ],
  'resources' => [
    ['title' => 'Case Studies',        'url' => '/case-studies', 'weight' => 0],
    ['title' => 'Articles & Insights', 'url' => '/articles',     'weight' => 10],
    ['title' => 'Downloads & Guides',  'url' => '/resources/downloads-guides', 'weight' => 20],
    ['title' => 'Press',               'url' => '/press',        'weight' => 30],
  ],
];

// ---------------------------------------------------------------------------
// Footer menu structure definition
// ---------------------------------------------------------------------------

/**
 * Footer top-level section items (menu_name = 'footer').
 * Six columns: Platforms, Services, Solutions, Company, Resources, Legal.
 */
$WL_FOOTER_TOP_LEVEL = [
  'footer_platforms' => [
    'title'    => 'Platforms',
    'url'      => '/platforms',
    'weight'   => 0,
    'expanded' => true,
  ],
  'footer_services' => [
    'title'    => 'Services',
    'url'      => '/services',
    'weight'   => 10,
    'expanded' => true,
  ],
  'footer_solutions' => [
    'title'    => 'Solutions',
    'url'      => '/solutions',
    'weight'   => 20,
    'expanded' => true,
  ],
  'footer_company' => [
    'title'    => 'Company',
    'url'      => '/about',
    'weight'   => 30,
    'expanded' => true,
  ],
  'footer_resources' => [
    'title'    => 'Resources',
    'url'      => 'internal:/resources',
    'weight'   => 40,
    'expanded' => true,
  ],
  'footer_legal' => [
    'title'    => 'Legal',
    'url'      => '/legal/privacy-policy',
    'weight'   => 50,
    'expanded' => true,
  ],
];

/**
 * Footer child items keyed by parent key.
 */
$WL_FOOTER_CHILDREN = [
  'footer_platforms' => [
    ['title' => 'Sabal Infrastructure Platform',       'url' => '/platforms/sabal',      'weight' => 0],
    ['title' => 'Keel CMS Platform',                   'url' => '/platforms/keel',       'weight' => 10],
    ['title' => 'Alidade Search Platform',             'url' => '/platforms/alidade',    'weight' => 20],
    ['title' => 'Squawk Zero-Trust Identity Platform', 'url' => '/platforms/squawk',     'weight' => 30],
    ['title' => 'Manifest Data Platform',              'url' => '/platforms/manifest',   'weight' => 40],
    ['title' => 'Lighthouse Observability Platform',   'url' => '/platforms/lighthouse', 'weight' => 50],
    ['title' => 'Coquina Software Factory Platform',   'url' => '/platforms/coquina',    'weight' => 60],
  ],
  'footer_services' => [
    ['title' => 'Private Infrastructure Engineering', 'url' => '/services/private-infrastructure-engineering', 'weight' => 0],
    ['title' => 'Zero-Trust Identity Consulting',     'url' => '/services/zero-trust-identity-consulting',     'weight' => 10],
    ['title' => 'Defense Technology Integration',     'url' => '/services/defense-technology-integration',     'weight' => 20],
    ['title' => 'Headless CMS Implementation',        'url' => '/services/headless-cms-implementation',        'weight' => 30],
    ['title' => 'Enterprise Search Architecture',     'url' => '/services/enterprise-search-architecture',     'weight' => 40],
    ['title' => 'AI Integration',                     'url' => '/services/ai-integration',                     'weight' => 50],
    ['title' => 'Digital Modernization',              'url' => '/services/digital-modernization',              'weight' => 60],
    ['title' => 'Custom Software Development',        'url' => '/services/custom-software-development',        'weight' => 70],
    ['title' => 'Integration Engineering',            'url' => '/services/integration-engineering',            'weight' => 80],
    ['title' => 'Digital Asset Solutions',            'url' => '/services/digital-asset-solutions',            'weight' => 90],
    ['title' => 'Intelligence & Actionable Insights', 'url' => '/services/intelligence-actionable-insights',   'weight' => 100],
  ],
  'footer_solutions' => [
    ['title' => 'DotEDU — Higher Education',      'url' => '/solutions/dotedu',           'weight' => 0],
    ['title' => 'Accord — Nonprofit',             'url' => '/solutions/accord',           'weight' => 10],
    ['title' => 'Palisade — Privacy SaaS',        'url' => '/solutions/palisade',         'weight' => 20],
    ['title' => 'Bulkhead — Regulated Industries','url' => '/solutions/bulkhead',         'weight' => 30],
    ['title' => 'DotGov — Federal Civilian',      'url' => '/solutions/dotgov',           'weight' => 40],
    ['title' => 'Gazette — IG Platforms',         'url' => '/solutions/gazette',          'weight' => 50],
    ['title' => 'Outpost — Defense Tech',         'url' => '/solutions/outpost',          'weight' => 60],
    ['title' => 'Software Factory',               'url' => '/solutions/software-factory', 'weight' => 70],
  ],
  'footer_company' => [
    ['title' => 'About',    'url' => '/about',    'weight' => 0],
    ['title' => 'Contact',  'url' => '/contact',  'weight' => 10],
    ['title' => 'Federal',  'url' => '/federal',  'weight' => 20],
    ['title' => 'Partners', 'url' => '/partners', 'weight' => 30],
  ],
  'footer_resources' => [
    ['title' => 'Articles & Insights', 'url' => '/articles',                   'weight' => 0],
    ['title' => 'Case Studies',        'url' => '/case-studies',               'weight' => 10],
    ['title' => 'Downloads & Guides',  'url' => '/resources/downloads-guides', 'weight' => 20],
    ['title' => 'Press',               'url' => '/press',                      'weight' => 30],
  ],
  'footer_legal' => [
    ['title' => 'Privacy Policy',          'url' => '/legal/privacy-policy',          'weight' => 0],
    ['title' => 'Terms of Service',        'url' => '/legal/terms-of-service',        'weight' => 10],
    ['title' => 'Cookie Policy',           'url' => '/legal/cookie-policy',           'weight' => 20],
    ['title' => 'Accessibility Statement', 'url' => '/legal/accessibility-statement', 'weight' => 30],
  ],
];

// ---------------------------------------------------------------------------
// Drupal helpers
// ---------------------------------------------------------------------------

/**
 * Find an existing menu_link_content entity by title and menu name.
 * Returns the first match or null.
 */
function wlm_find_by_title(string $title, string $menu_name = 'main'): ?MenuLinkContent {
  $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  $ids = $storage->getQuery()
    ->condition('menu_name', $menu_name)
    ->condition('title', $title)
    ->accessCheck(false)
    ->range(0, 1)
    ->execute();
  if (!$ids) {
    return null;
  }
  return $storage->load(reset($ids));
}

/**
 * Normalise a URL string to a Drupal link field value.
 *
 * Paths beginning with '/' are treated as internal: URIs. Strings that
 * already start with 'internal:' or a scheme ('http:', 'https:', etc.) are
 * passed through unchanged.
 */
function wlm_uri(string $url): string {
  if (str_starts_with($url, 'internal:') || preg_match('/^[a-z][a-z0-9+\-.]*:/', $url)) {
    return $url;
  }
  return 'internal:' . $url;
}

/**
 * Create a new menu_link_content entity and save it.
 *
 * @param string      $title      Link text.
 * @param string      $url        URL (absolute or /path or internal:/path).
 * @param int         $weight     Menu ordering weight.
 * @param bool        $expanded   Whether the item is expanded by default.
 * @param string|null $parent     Parent value in "menu_link_content:UUID" form, or null.
 * @param string      $menu_name  The machine name of the menu (default: 'main').
 *
 * @return MenuLinkContent
 */
function wlm_create(string $title, string $url, int $weight, bool $expanded, ?string $parent, string $menu_name = 'main'): MenuLinkContent {
  $values = [
    'title'     => $title,
    'link'      => ['uri' => wlm_uri($url)],
    'menu_name' => $menu_name,
    'weight'    => $weight,
    'expanded'  => $expanded,
    'enabled'   => true,
  ];
  if ($parent !== null) {
    $values['parent'] = $parent;
  }
  $entity = MenuLinkContent::create($values);
  $entity->save();
  return $entity;
}

/**
 * Update an existing entity's link, weight, and expanded flag.
 */
function wlm_update(MenuLinkContent $entity, string $url, int $weight, bool $expanded): void {
  $entity->set('link', ['uri' => wlm_uri($url)]);
  $entity->set('weight', $weight);
  $entity->set('expanded', $expanded);
  $entity->set('enabled', true);
  $entity->save();
}

// ---------------------------------------------------------------------------
// Seed one item
// ---------------------------------------------------------------------------

/**
 * Seed a single menu link. Returns a result array describing what happened.
 *
 * @param array       $item       Associative array with keys: title, url, weight, expanded (optional).
 * @param string|null $parent     Parent plugin ID ("menu_link_content:UUID") or null.
 * @param bool        $dry_run
 * @param bool        $update
 * @param string      $menu_name  The machine name of the menu (default: 'main').
 *
 * @return array{status: string, title: string, uuid?: string, parent?: string}
 */
function wlm_seed_item(array $item, ?string $parent, bool $dry_run, bool $update, string $menu_name = 'main'): array {
  $title    = $item['title'];
  $url      = $item['url'];
  $weight   = (int) ($item['weight'] ?? 0);
  $expanded = (bool) ($item['expanded'] ?? false);

  $existing = wlm_find_by_title($title, $menu_name);

  if ($existing) {
    if (!$update) {
      return [
        'status' => $dry_run ? 'would-skip' : 'skipped',
        'title'  => $title,
        'uuid'   => $existing->uuid(),
      ];
    }

    if ($dry_run) {
      return [
        'status' => 'would-update',
        'title'  => $title,
        'uuid'   => $existing->uuid(),
      ];
    }

    wlm_update($existing, $url, $weight, $expanded);
    return [
      'status' => 'updated',
      'title'  => $title,
      'uuid'   => $existing->uuid(),
    ];
  }

  // Item does not exist yet.
  if ($dry_run) {
    return [
      'status' => 'would-create',
      'title'  => $title,
    ];
  }

  $entity = wlm_create($title, $url, $weight, $expanded, $parent, $menu_name);
  return [
    'status' => 'created',
    'title'  => $title,
    'uuid'   => $entity->uuid(),
  ];
}

// ---------------------------------------------------------------------------
// Output helpers
// ---------------------------------------------------------------------------

function wlm_render(array $r, string $indent = ''): void {
  switch ($r['status']) {
    case 'created':
      echo sprintf("%s[+] %s  uuid=%s\n", $indent, $r['title'], $r['uuid'] ?? '');
      break;
    case 'updated':
      echo sprintf("%s[~] %s  uuid=%s\n", $indent, $r['title'], $r['uuid'] ?? '');
      break;
    case 'skipped':
      echo sprintf("%s[=] %s  (exists, uuid=%s) — skipped\n", $indent, $r['title'], $r['uuid'] ?? '');
      break;
    case 'would-create':
      echo sprintf("%s[+?] %s — WOULD CREATE\n", $indent, $r['title']);
      break;
    case 'would-update':
      echo sprintf("%s[~?] %s  uuid=%s — WOULD UPDATE\n", $indent, $r['title'], $r['uuid'] ?? '');
      break;
    case 'would-skip':
      echo sprintf("%s[=?] %s  (exists, uuid=%s) — WOULD SKIP\n", $indent, $r['title'], $r['uuid'] ?? '');
      break;
    default:
      echo sprintf("%s[?] %s  status=%s\n", $indent, $r['title'], $r['status']);
  }
}

// ---------------------------------------------------------------------------
// Run
// ---------------------------------------------------------------------------

$mode_label = $WL_DRY_RUN
  ? ($WL_UPDATE ? 'DRY-RUN + UPDATE (no DB writes)' : 'DRY-RUN (no DB writes)')
  : ($WL_UPDATE ? 'UPDATE (existing items will be overwritten)' : 'SKIP-IF-EXISTS (default)');

echo "=== Seeding main menu ===\n";
echo "Mode: {$mode_label}\n\n";

$status_keys = ['created', 'skipped', 'updated', 'would-create', 'would-skip', 'would-update'];
$summary = array_fill_keys($status_keys, 0);

// Track UUIDs of top-level items so children can reference them.
// Key: $WL_TOP_LEVEL key  →  value: "menu_link_content:UUID" or null (dry-run).
$parent_plugin_ids = [];

echo "--- Top-level items ---\n";
foreach ($WL_TOP_LEVEL as $key => $item) {
  $r = wlm_seed_item($item, null, $WL_DRY_RUN, $WL_UPDATE);
  $summary[$r['status']]++;
  wlm_render($r, '  ');

  // Resolve the plugin ID for children.
  if (isset($r['uuid'])) {
    // Real UUID available (created, updated, skipped).
    $parent_plugin_ids[$key] = 'menu_link_content:' . $r['uuid'];
  }
  elseif ($r['status'] === 'would-create') {
    // Dry-run create — no UUID yet; children will also report would-create
    // using a null parent (still accurate for dry-run output).
    $parent_plugin_ids[$key] = null;
  }
}

echo "\n--- Children ---\n";
foreach ($WL_CHILDREN as $parent_key => $children) {
  $parent_title = $WL_TOP_LEVEL[$parent_key]['title'];
  $parent_plugin_id = $parent_plugin_ids[$parent_key] ?? null;
  echo "  [{$parent_title}]\n";

  foreach ($children as $child) {
    $child['expanded'] = $child['expanded'] ?? false;
    $r = wlm_seed_item($child, $parent_plugin_id, $WL_DRY_RUN, $WL_UPDATE);
    $summary[$r['status']]++;
    wlm_render($r, '    ');
  }
}

// ---------------------------------------------------------------------------
// Footer menu
// ---------------------------------------------------------------------------

echo "\n--- Footer top-level items (footer menu) ---\n";

$footer_parent_plugin_ids = [];

foreach ($WL_FOOTER_TOP_LEVEL as $key => $item) {
  $r = wlm_seed_item($item, null, $WL_DRY_RUN, $WL_UPDATE, 'footer');
  $summary[$r['status']]++;
  wlm_render($r, '  ');

  if (isset($r['uuid'])) {
    $footer_parent_plugin_ids[$key] = 'menu_link_content:' . $r['uuid'];
  } elseif ($r['status'] === 'would-create') {
    $footer_parent_plugin_ids[$key] = null;
  }
}

echo "\n--- Footer children ---\n";
foreach ($WL_FOOTER_CHILDREN as $parent_key => $children) {
  $parent_title     = $WL_FOOTER_TOP_LEVEL[$parent_key]['title'];
  $parent_plugin_id = $footer_parent_plugin_ids[$parent_key] ?? null;
  echo "  [{$parent_title}]\n";

  foreach ($children as $child) {
    $child['expanded'] = $child['expanded'] ?? false;
    $r = wlm_seed_item($child, $parent_plugin_id, $WL_DRY_RUN, $WL_UPDATE, 'footer');
    $summary[$r['status']]++;
    wlm_render($r, '    ');
  }
}

// ---------------------------------------------------------------------------
// Prune — remove DB items not defined in this script (--prune only)
// ---------------------------------------------------------------------------

if ($WL_PRUNE) {
  echo "\n--- Pruning orphan menu items ---\n";

  // Build flat sets of canonical titles per menu.
  $canonical_main = [];
  foreach ($WL_TOP_LEVEL as $item) {
    $canonical_main[] = $item['title'];
  }
  foreach ($WL_CHILDREN as $children) {
    foreach ($children as $child) {
      $canonical_main[] = $child['title'];
    }
  }

  $canonical_footer = [];
  foreach ($WL_FOOTER_TOP_LEVEL as $item) {
    $canonical_footer[] = $item['title'];
  }
  foreach ($WL_FOOTER_CHILDREN as $children) {
    foreach ($children as $child) {
      $canonical_footer[] = $child['title'];
    }
  }

  $to_prune = [
    'main'   => $canonical_main,
    'footer' => $canonical_footer,
  ];

  $pruned = 0;
  foreach ($to_prune as $menu_name => $canonical_titles) {
    $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
    $ids = $storage->getQuery()
      ->condition('menu_name', $menu_name)
      ->accessCheck(false)
      ->execute();
    $items = $storage->loadMultiple($ids);

    foreach ($items as $item) {
      $title = $item->getTitle();
      if (!in_array($title, $canonical_titles, true)) {
        $uri = $item->link->uri ?? '(no url)';
        if ($WL_DRY_RUN) {
          printf("  [dry-run] WOULD DELETE  [%s] \"%s\"  %s\n", $menu_name, $title, $uri);
        }
        else {
          $item->delete();
          printf("  [pruned]  DELETED       [%s] \"%s\"  %s\n", $menu_name, $title, $uri);
          $pruned++;
        }
      }
    }
  }

  if (!$WL_DRY_RUN) {
    echo "  Pruned {$pruned} orphan item(s).\n";
  }
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "\n=== Summary ===\n";
$parts = [];
foreach ($summary as $k => $v) {
  if ($v > 0) {
    $parts[] = "{$k}={$v}";
  }
}
echo '  ' . ($parts ? implode('  ', $parts) : '(nothing to do)') . "\n";

if ($WL_DRY_RUN) {
  echo "\nDry run: no menu items were created, updated, or deleted. Re-run without --dry-run to apply.\n";
}
else {
  echo "\nMain menu: /admin/structure/menu/manage/main\n";
  echo "Footer menu: /admin/structure/menu/manage/footer\n";
}
