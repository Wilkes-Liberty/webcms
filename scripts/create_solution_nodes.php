<?php
/**
 * Create the 8 canonical solution nodes and handle seeded placeholders.
 *
 * Run AFTER migrate_product_to_platform.php so the platform bundle exists.
 *
 * Seeded placeholders (nid 21/22/23) are reviewed first:
 *   - nid 21 (Sovereign Mission Edge) → Retired (unpublished)
 *   - nid 22 (Sovereign AI Command Fabric) → Retired (unpublished)
 *   - nid 23 (Sovereign Digital Modernization Platform) → Retired (unpublished)
 *
 * Then creates 8 fresh canonical solution nodes in Draft status.
 *
 * Run:
 *   ddev drush scr scripts/create_solution_nodes.php
 *   ddev drush scr scripts/create_solution_nodes.php -- --dry-run
 */

use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;

$wl_argv = isset($extra) && is_array($extra) ? $extra : array_slice($_SERVER['argv'] ?? [], 1);
$dry_run = in_array('--dry-run', $wl_argv, true);

echo "=== Create Canonical Solution Nodes ===\n";
echo 'Mode: ' . ($dry_run ? 'DRY-RUN' : 'LIVE') . "\n\n";

// ---------------------------------------------------------------------------
// Step 1: Retire seeded placeholders
// ---------------------------------------------------------------------------

echo "--- Step 1: Retire seeded placeholder solutions ---\n";

$retire_nids = [21, 22, 23];
foreach ($retire_nids as $nid) {
  $node = Node::load($nid);
  if (!$node) {
    echo "  [!] nid={$nid} not found — skipping\n";
    continue;
  }
  if ($node->bundle() !== 'solution') {
    echo "  [!] nid={$nid} \"{$node->getTitle()}\" is bundle={$node->bundle()}, not solution — skipping\n";
    continue;
  }
  $title = $node->getTitle();
  if (!$node->isPublished()) {
    echo "  [=] nid={$nid} \"{$title}\" already unpublished — skipping\n";
    continue;
  }
  if ($dry_run) {
    echo "  [?] nid={$nid} \"{$title}\" — would unpublish\n";
    continue;
  }
  $node->setUnpublished();
  $node->set('moderation_state', 'archived');
  $node->save();
  echo "  [+] nid={$nid} \"{$title}\" — unpublished (archived)\n";
}

// ---------------------------------------------------------------------------
// Step 2: Create 8 canonical solution nodes
// ---------------------------------------------------------------------------

echo "\n--- Step 2: Create canonical solution nodes ---\n";

$solutions = [
  [
    'title' => 'DotEDU',
    'display' => 'DotEDU — Higher Education',
    'alias' => '/solutions/dotedu',
    'summary' => 'Higher education digital platform built on Keel CMS and Alidade Search.',
  ],
  [
    'title' => 'Accord',
    'display' => 'Accord — Nonprofit',
    'alias' => '/solutions/accord',
    'summary' => 'Nonprofit digital platform built on Keel CMS.',
  ],
  [
    'title' => 'Palisade',
    'display' => 'Palisade — Privacy SaaS',
    'alias' => '/solutions/palisade',
    'summary' => 'Privacy SaaS solution built on Manifest Data and Squawk Identity platforms.',
  ],
  [
    'title' => 'Bulkhead',
    'display' => 'Bulkhead — Regulated Industries',
    'alias' => '/solutions/bulkhead',
    'summary' => 'Regulated industries solution built on Sabal Infrastructure, Squawk Identity, and Manifest Data platforms.',
  ],
  [
    'title' => 'DotGov',
    'display' => 'DotGov — Federal Civilian',
    'alias' => '/solutions/dotgov',
    'summary' => 'Federal civilian solution built on Keel CMS, Alidade Search, and Squawk Identity platforms.',
  ],
  [
    'title' => 'Gazette',
    'display' => 'Gazette — IG Platforms',
    'alias' => '/solutions/gazette',
    'summary' => 'Inspector General platform built on Keel CMS and Manifest Data platforms.',
  ],
  [
    'title' => 'Outpost',
    'display' => 'Outpost — Defense Tech',
    'alias' => '/solutions/outpost',
    'summary' => 'Defense technology solution built on Sabal Infrastructure and Coquina Software Factory platforms.',
  ],
  [
    'title' => 'Software Factory',
    'display' => 'Software Factory',
    'alias' => '/solutions/software-factory',
    'summary' => 'Full-lifecycle software factory built on the Coquina Software Factory Platform.',
  ],
];

$alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');

foreach ($solutions as $sol) {
  // Check if a solution with this title already exists.
  $existing = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
    ->condition('type', 'solution')
    ->condition('title', $sol['title'])
    ->accessCheck(FALSE)
    ->range(0, 1)
    ->execute();

  if (!empty($existing)) {
    $nid = reset($existing);
    echo "  [=] \"{$sol['title']}\" already exists (nid={$nid}) — skipping\n";
    continue;
  }

  // Also check by alias.
  $alias_exists = $alias_storage->loadByProperties(['alias' => $sol['alias']]);
  if (!empty($alias_exists)) {
    echo "  [=] Alias {$sol['alias']} already in use — skipping \"{$sol['title']}\"\n";
    continue;
  }

  if ($dry_run) {
    echo "  [?] Would create: \"{$sol['title']}\" alias={$sol['alias']}\n";
    continue;
  }

  $node = Node::create([
    'type' => 'solution',
    'title' => $sol['title'],
    'status' => 0,
    'moderation_state' => 'draft',
    'body' => [
      'value' => '<p>' . htmlspecialchars($sol['display'], ENT_QUOTES | ENT_HTML5) . ' — placeholder content to be entered in a separate content session.</p>',
      'format' => 'headless_safe',
    ],
    'field_summary' => $sol['summary'],
    'field_seo_title' => $sol['display'],
    'field_meta_description' => $sol['summary'],
    'path' => ['alias' => $sol['alias']],
  ]);
  $node->save();
  echo "  [+] Created: nid=" . $node->id() . " \"{$sol['title']}\" alias={$sol['alias']} (draft)\n";
}

echo "\n=== Done ===\n";
if ($dry_run) {
  echo "Dry run: no changes were made.\n";
}
else {
  echo "All 8 canonical solutions created in Draft status.\n";
  echo "Note: Outpost (Defense Tech) is flagged for attorney USPTO review before any public use.\n";
}
