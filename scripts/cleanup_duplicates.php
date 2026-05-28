<?php
/**
 * Definitive node cleanup — alias-authority strategy.
 *
 * Philosophy
 * ----------
 * The canonical alias lists below are the SINGLE source of truth.
 * Every node in Drupal must have exactly ONE alias that appears in those
 * lists, or it gets deleted.
 *
 * KEEP  — exactly one node per canonical alias.
 *         Tie-breaking priority: (1) published, (2) uid=1 (admin author),
 *         (3) lowest nid.
 * DELETE — any node whose alias is NOT in the canonical lists, including:
 *         - old combined-service nodes (e.g. "Custom Software Development
 *           and Middleware Engineering")
 *         - solution nodes with verbose/renamed titles that got a different
 *           alias (e.g. /solutions/outpost-defense-tech-modernization)
 *         - article duplicates seeded without pathauto firing
 *         - any other orphan/artifact nodes
 * NO-ALIAS nodes — always deleted; canonical nodes always have an alias.
 *
 * Modes
 * -----
 *   (default)  DRY-RUN — full report, nothing written.
 *   --apply    Deletes every non-canonical and excess duplicate node.
 *
 * Run:
 *   ddev drush scr scripts/cleanup_duplicates.php
 *   ddev drush scr scripts/cleanup_duplicates.php -- --apply
 *
 * After running --apply, always follow up with:
 *   ddev drush scr scripts/audit_content.php -- --apply
 */

use Drupal\node\Entity\Node;

// ---------------------------------------------------------------------------
// CLI flags
// ---------------------------------------------------------------------------

$wl_argv  = isset($extra) && is_array($extra) ? $extra : array_slice($_SERVER['argv'] ?? [], 1);
$WL_APPLY = in_array('--apply', $wl_argv, true);

$mode = $WL_APPLY
  ? 'APPLY (non-canonical and duplicate nodes will be DELETED)'
  : 'DRY-RUN (no changes — re-run with --apply to delete)';

echo "=== Definitive node cleanup (alias-authority) ===\n";
echo "Mode: {$mode}\n\n";

// ---------------------------------------------------------------------------
// Canonical alias sets  (keep in sync with audit_content.php)
// ---------------------------------------------------------------------------

$WL_CANONICAL = array_flip(array_merge(
  [
    // Platforms (7)
    '/platforms/sabal',
    '/platforms/keel',
    '/platforms/alidade',
    '/platforms/squawk',
    '/platforms/manifest',
    '/platforms/lighthouse',
    '/platforms/coquina',
    // Services (11)
    '/services/private-infrastructure-engineering',
    '/services/zero-trust-identity-consulting',
    '/services/defense-technology-integration',
    '/services/headless-cms-implementation',
    '/services/enterprise-search-architecture',
    '/services/ai-integration',
    '/services/digital-modernization',
    '/services/custom-software-development',
    '/services/integration-engineering',
    '/services/digital-asset-solutions',
    '/services/intelligence-actionable-insights',
    // Solutions — canonical (8)
    '/solutions/dotedu',
    '/solutions/accord',
    '/solutions/palisade',
    '/solutions/bulkhead',
    '/solutions/dotgov',
    '/solutions/gazette',
    '/solutions/outpost',
    '/solutions/software-factory',
    // Articles (3)
    '/articles/drupal-headless-cms-federal-agencies',
    '/articles/what-is-sovereignty-in-federal-it',
    '/articles/iac-driven-infrastructure-for-government',
    // Basic pages
    '/about',
    '/federal',
    '/partners',
    '/team/jeremy-michael-cerda',
    // Legal pages
    '/legal/privacy-policy',
    '/legal/terms-of-service',
    '/legal/cookie-policy',
    '/legal/accessibility-statement',
    // Hub landing pages — Drupal landing_page nodes for intro/hero sections.
    '/platforms',
    '/services',
    '/solutions',
    '/resources',
    '/resources/downloads-guides',
    '/contact',
    // /press /case-studies /articles are pure Next.js dynamic-index routes
    // with no Drupal node — intentionally excluded.
    // Homepage
    '/home',
  ],
  [
    // Case studies — canonical draft
    '/case-studies/hhs-cms-web-platform',
    '/case-studies/usps-oig-drupal-distribution',
    '/case-studies/pandemicoversight-gov',
  ]
));

// ---------------------------------------------------------------------------
// Load all nodes + build alias map
// ---------------------------------------------------------------------------

$nids = \Drupal::entityQuery('node')
  ->accessCheck(false)
  ->execute();

if (!$nids) {
  echo "No nodes found.\n";
  return;
}

/** @var Node[] $nodes */
$nodes = Node::loadMultiple($nids);

$alias_entities = \Drupal::entityTypeManager()
  ->getStorage('path_alias')
  ->loadByProperties(['langcode' => 'en']);

$alias_map = [];  // /node/N → alias
foreach ($alias_entities as $pa) {
  $alias_map[$pa->getPath()] = $pa->getAlias();
}

// ---------------------------------------------------------------------------
// Categorise every node
// ---------------------------------------------------------------------------

// canonical_alias → [ nid => Node ]  (only nodes whose alias IS canonical)
$canonical_groups = [];

// nid → [ 'node' => Node, 'alias' => string|null, 'reason' => string ]
$delete_candidates = [];

foreach ($nodes as $node) {
  $nid   = (int) $node->id();
  $alias = $alias_map['/node/' . $nid] ?? null;

  if ($alias === null) {
    $delete_candidates[$nid] = [
      'node'   => $node,
      'alias'  => null,
      'reason' => 'no path alias',
    ];
  } elseif (isset($WL_CANONICAL[$alias])) {
    $canonical_groups[$alias][$nid] = $node;
  } else {
    $delete_candidates[$nid] = [
      'node'   => $node,
      'alias'  => $alias,
      'reason' => 'alias not in canonical list',
    ];
  }
}

// ---------------------------------------------------------------------------
// Within canonical groups: resolve ties, queue excess copies for deletion
// ---------------------------------------------------------------------------

$delete_queue = [];  // nids to delete
$keep_map     = [];  // canonical alias → nid being kept

foreach ($canonical_groups as $alias => $group) {
  if (count($group) === 1) {
    $keep_map[$alias] = array_key_first($group);
    continue;
  }

  // Tie-break: prefer published → prefer uid=1 → prefer lowest nid.
  $best_nid   = null;
  $best_score = -1;

  foreach ($group as $nid => $node) {
    $score = 0;
    if ($node->isPublished())        { $score += 4; }
    if ((int)$node->getOwnerId() === 1) { $score += 2; }
    // Prefer lower nid for stability (subtract tiny fraction).
    $score -= $nid / 1_000_000;

    if ($score > $best_score) {
      $best_score = $score;
      $best_nid   = $nid;
    }
  }

  $keep_map[$alias] = $best_nid;

  foreach ($group as $nid => $node) {
    if ($nid !== $best_nid) {
      $delete_candidates[$nid] = [
        'node'   => $node,
        'alias'  => $alias,
        'reason' => "duplicate of canonical nid={$best_nid}",
      ];
    }
  }
}

// Build final delete queue (exclude anything we decided to keep above).
foreach ($delete_candidates as $nid => $info) {
  if (!in_array($nid, $keep_map, true)) {
    $delete_queue[$nid] = $info;
  }
}

// ---------------------------------------------------------------------------
// Report: what will be kept
// ---------------------------------------------------------------------------

echo "--- Canonical nodes to KEEP (" . count($keep_map) . " nodes) ---\n\n";
ksort($keep_map);
foreach ($keep_map as $alias => $nid) {
  $node   = $nodes[$nid];
  $status = $node->isPublished() ? 'pub' : 'DRAFT';
  $uid    = (int) $node->getOwnerId();
  $title  = mb_substr($node->getTitle(), 0, 55);
  printf("  nid=%-5d uid=%-3d %-6s  %-40s  %s\n", $nid, $uid, $status, $alias, $title);
}

// ---------------------------------------------------------------------------
// Report: what will be deleted
// ---------------------------------------------------------------------------

echo "\n--- Nodes to DELETE (" . count($delete_queue) . " nodes) ---\n\n";

if (empty($delete_queue)) {
  echo "  Nothing to delete.\n\n";
} else {
  // Group by reason for readability.
  $by_reason = [];
  foreach ($delete_queue as $nid => $info) {
    $by_reason[$info['reason']][$nid] = $info;
  }
  ksort($by_reason);

  foreach ($by_reason as $reason => $entries) {
    echo "  Reason: {$reason}\n";
    printf("  %-5s %-14s %-6s %-4s  %-45s  %s\n",
      'NID', 'BUNDLE', 'STATUS', 'UID', 'ALIAS', 'TITLE');
    printf("  %s\n", str_repeat('-', 110));
    foreach ($entries as $nid => $info) {
      $node   = $info['node'];
      $status = $node->isPublished() ? 'pub' : 'DRAFT';
      $uid    = (int) $node->getOwnerId();
      $alias  = $info['alias'] ?? '(none)';
      $bundle = $node->bundle();
      $title  = mb_substr($node->getTitle(), 0, 40);
      printf("  %-5d %-14s %-6s %-4d  %-45s  %s\n",
        $nid, $bundle, $status, $uid, $alias, $title);
    }
    echo "\n";
  }
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "--- Summary ---\n";
printf("  Total nodes in DB:   %d\n", count($nodes));
printf("  Canonical to keep:   %d\n", count($keep_map));
printf("  Canonical aliases:   %d (entries in canonical list)\n", count($WL_CANONICAL));
printf("  Missing from DB:     %d (canonical aliases with no node yet)\n",
  count($WL_CANONICAL) - count($keep_map));
printf("  To delete:           %d\n\n", count($delete_queue));

// Show which canonical aliases have no node yet.
$missing = array_diff_key($WL_CANONICAL, array_flip(array_keys($canonical_groups)));
if (!empty($missing)) {
  echo "  Canonical aliases with NO node in DB (will need seeding):\n";
  foreach (array_keys($missing) as $alias) {
    echo "    {$alias}\n";
  }
  echo "\n";
}

if (empty($delete_queue)) {
  echo "Nothing to delete.\n";
  if (!$WL_APPLY) {
    echo "Dry-run complete.\n";
  }
  return;
}

if (!$WL_APPLY) {
  echo "Dry-run complete. Re-run with -- --apply to delete the nodes listed above.\n";
  return;
}

// ---------------------------------------------------------------------------
// Apply: delete non-canonical and duplicate nodes
// ---------------------------------------------------------------------------

echo "Deleting nodes...\n";
$deleted = 0;

foreach ($delete_queue as $nid => $info) {
  $node = $nodes[$nid] ?? Node::load($nid);
  if ($node) {
    $title = $node->getTitle();
    $node->delete();
    printf("  Deleted nid=%-5d  %s  (%s)\n", $nid, $title, $info['reason']);
    $deleted++;
  } else {
    printf("  [!] nid=%-5d  not found (already deleted?)\n", $nid);
  }
}

echo "\nDone. Deleted {$deleted} node(s).\n";
echo "Run next: ddev drush scr scripts/audit_content.php -- --apply\n";
