<?php
/**
 * Content audit — inventory, status correction, and author normalisation.
 *
 * What this script does
 * ---------------------
 * 1. Lists every node in the site with its bundle, status, author, and alias.
 * 2. Sets the author (uid) to 1 (admin) on ALL nodes.
 * 3. Unpublishes legacy "Sovereign-*" solution nodes and any other nodes whose
 *    path aliases are not in the canonical site map.
 * 4. Publishes nodes that belong to the canonical site map but are currently
 *    unpublished (except case_study — those stay draft intentionally).
 *
 * Canonical URL lists
 * -------------------
 * These are the ONLY paths that should be published. Everything else gets
 * unpublished (but not deleted — use Drupal admin UI to delete if desired).
 *
 * Modes
 * -----
 *   (default)  DRY-RUN — prints what would change, writes nothing.
 *   --apply    Applies all changes (status + author).
 *
 * Run:
 *   ddev drush scr scripts/audit_content.php
 *   ddev drush scr scripts/audit_content.php -- --apply
 *
 * On production:
 *   docker compose exec drupal drush scr /var/www/html/scripts/audit_content.php -- --apply
 */

use Drupal\node\Entity\Node;

// ---------------------------------------------------------------------------
// CLI flags
// ---------------------------------------------------------------------------

$wl_argv  = isset($extra) && is_array($extra) ? $extra : array_slice($_SERVER['argv'] ?? [], 1);
$WL_APPLY = in_array('--apply', $wl_argv, true);

// ---------------------------------------------------------------------------
// Canonical published paths
// ---------------------------------------------------------------------------
// Every path listed here should be status=published after the run.
// case_study paths are intentionally ABSENT — they stay draft.
// Paths NOT in either list will be unpublished.

const WL_CANONICAL_PUBLISHED = [
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
  '/about/jeremy-cerda',
  // Legal pages
  '/privacy-policy',
  '/terms-of-service',
  '/cookie-policy',
  '/accessibility-statement',
  // Hub/listing pages (if seeded as nodes rather than views)
  '/solutions',
  '/platforms',
  '/services',
  '/resources',
  '/press',
  '/case-studies',
  '/articles',
  // Homepage
  '/home',
];

// Case studies should be DRAFT (status=0, moderation_state=draft), not published.
// They are present in the site map but intentionally unpublished pending [VERIFY] review.
const WL_CANONICAL_DRAFT = [
  '/case-studies/hhs-cms-web-platform',
  '/case-studies/usps-oig-drupal-distribution',
  '/case-studies/pandemicoversight-gov',
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function wla_all_aliases(): array {
  // Returns a map of /node/{nid} → alias.
  $result = [];
  /** @var \Drupal\path_alias\AliasRepositoryInterface $repo */
  $aliases = \Drupal::entityTypeManager()
    ->getStorage('path_alias')
    ->loadByProperties(['langcode' => 'en']);
  foreach ($aliases as $pa) {
    $result[$pa->getPath()] = $pa->getAlias();
  }
  return $result;
}

function wla_set_moderation(Node $node, string $state): void {
  // Valid content moderation states in this workflow: published, draft, archived.
  // 'unpublished' is not a valid state — use 'archived' to take content offline.
  if ($node->hasField('moderation_state')) {
    $node->set('moderation_state', $state);
  }
  $node->set('status', $state === 'published' ? 1 : 0);
}

// ---------------------------------------------------------------------------
// Run
// ---------------------------------------------------------------------------

$mode = $WL_APPLY ? 'APPLY (changes will be written)' : 'DRY-RUN (no changes written — re-run with --apply to apply)';
echo "=== Content audit ===\n";
echo "Mode: {$mode}\n\n";

// Load all nodes.
$nids = \Drupal::entityQuery('node')
  ->accessCheck(false)
  ->execute();

if (!$nids) {
  echo "No nodes found.\n";
  return;
}

/** @var Node[] $nodes */
$nodes = Node::loadMultiple($nids);

// Build alias map.
$alias_map = wla_all_aliases(); // /node/N → /alias

$canonical_published_set = array_flip(WL_CANONICAL_PUBLISHED);
$canonical_draft_set     = array_flip(WL_CANONICAL_DRAFT);

// Counters.
$counts = [
  'total'        => 0,
  'author_fixed' => 0,
  'published'    => 0,
  'unpublished'  => 0,
  'draft_kept'   => 0,
  'already_ok'   => 0,
];

$inventory = [];

foreach ($nodes as $node) {
  $nid    = (int) $node->id();
  $bundle = $node->bundle();
  $title  = $node->getTitle();
  $status = $node->isPublished();
  $uid    = (int) $node->getOwnerId();
  $alias  = $alias_map['/node/' . $nid] ?? '/node/' . $nid;
  $mod    = $node->hasField('moderation_state')
    ? $node->get('moderation_state')->value
    : ($status ? 'published' : 'unpublished');

  $inventory[] = [
    'nid'    => $nid,
    'bundle' => $bundle,
    'status' => $status ? 'pub' : 'DRAFT',
    'mod'    => $mod,
    'uid'    => $uid,
    'alias'  => $alias,
    'title'  => $title,
  ];
  $counts['total']++;
}

// Sort by bundle then alias for readable output.
usort($inventory, static function (array $a, array $b): int {
  return $a['bundle'] <=> $b['bundle'] ?: $a['alias'] <=> $b['alias'];
});

// ---------------------------------------------------------------------------
// Print inventory and determine actions
// ---------------------------------------------------------------------------

echo "--- Current inventory ---\n";
printf("%-5s %-14s %-9s %-10s %-4s  %s\n", 'NID', 'BUNDLE', 'STATUS', 'MOD', 'UID', 'ALIAS / TITLE');
printf("%s\n", str_repeat('-', 100));

foreach ($inventory as $row) {
  printf("%-5d %-14s %-9s %-10s %-4d  %-45s %s\n",
    $row['nid'],
    $row['bundle'],
    $row['status'],
    $row['mod'],
    $row['uid'],
    $row['alias'],
    mb_substr($row['title'], 0, 40)
  );
}

// ---------------------------------------------------------------------------
// Determine and apply changes
// ---------------------------------------------------------------------------

echo "\n--- Actions ---\n";

foreach ($nodes as $node) {
  $nid   = (int) $node->id();
  $alias = $alias_map['/node/' . $nid] ?? null;
  $uid   = (int) $node->getOwnerId();
  $bundle = $node->bundle();
  $changed = false;
  $actions = [];

  // 1. Author → admin (uid=1).
  if ($uid !== 1) {
    $actions[] = "author {$uid}→1";
    if ($WL_APPLY) {
      $node->setOwnerId(1);
    }
    $counts['author_fixed']++;
    $changed = true;
  }

  // 2. Status decisions.
  if ($alias !== null) {
    if (isset($canonical_published_set[$alias])) {
      // Should be published.
      if (!$node->isPublished()) {
        $actions[] = 'PUBLISH';
        if ($WL_APPLY) {
          wla_set_moderation($node, 'published');
        }
        $counts['published']++;
        $changed = true;
      }
      else {
        $counts['already_ok']++;
      }
    }
    elseif (isset($canonical_draft_set[$alias])) {
      // Should stay draft.
      if ($node->isPublished()) {
        $actions[] = 'ARCHIVE→draft';
        if ($WL_APPLY) {
          wla_set_moderation($node, 'draft');
        }
        $counts['unpublished']++;
        $changed = true;
      }
      else {
        $actions[] = 'keep draft ✓';
        $counts['draft_kept']++;
      }
    }
    else {
      // Not in either canonical list — unpublish.
      if ($node->isPublished()) {
        $actions[] = 'UNPUBLISH (legacy/unlisted)';
        if ($WL_APPLY) {
          wla_set_moderation($node, 'archived');
        }
        $counts['unpublished']++;
        $changed = true;
      }
      else {
        $actions[] = 'already unpub ✓';
        $counts['already_ok']++;
      }
    }
  }
  else {
    // No alias at all — unpublish.
    if ($node->isPublished()) {
      $actions[] = 'UNPUBLISH (no alias)';
      if ($WL_APPLY) {
        wla_set_moderation($node, 'archived');
      }
      $counts['unpublished']++;
      $changed = true;
    }
    else {
      $actions[] = 'already unpub ✓';
      $counts['already_ok']++;
    }
  }

  if ($changed && $WL_APPLY) {
    $node->save();
  }

  $label = $alias ?? '/node/' . $nid;
  printf("  nid=%-4d %-45s %s\n", $nid, $label, implode(' | ', $actions));
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

echo "\n=== Summary ===\n";
printf("  Total nodes:     %d\n", $counts['total']);
printf("  Author fixed:    %d (uid→1)\n", $counts['author_fixed']);
printf("  Published:       %d\n", $counts['published']);
printf("  Unpublished:     %d\n", $counts['unpublished']);
printf("  Draft kept:      %d\n", $counts['draft_kept']);
printf("  Already correct: %d\n", $counts['already_ok']);

if (!$WL_APPLY) {
  echo "\nNo changes written. Re-run with -- --apply to apply.\n";
}
else {
  echo "\nAll changes saved. Review at /admin/content.\n";
}
