<?php
/**
 * Rename platform nodes to canonical names + update path aliases.
 * Also creates the Coquina Software Factory Platform node.
 *
 * Run AFTER config:import (Drupal knows the 'platform' bundle).
 *
 * Run:
 *   ddev drush scr scripts/rename_platform_nodes.php
 *   ddev drush scr scripts/rename_platform_nodes.php -- --dry-run
 */

use Drupal\node\Entity\Node;
use Drupal\path_alias\Entity\PathAlias;

$wl_argv = isset($extra) && is_array($extra) ? $extra : array_slice($_SERVER['argv'] ?? [], 1);
$dry_run = in_array('--dry-run', $wl_argv, true);

echo "=== Rename Platform Nodes to Canonical Names ===\n";
echo 'Mode: ' . ($dry_run ? 'DRY-RUN' : 'LIVE') . "\n\n";

// ---------------------------------------------------------------------------
// Step 1: Rename existing platform nodes
// ---------------------------------------------------------------------------

echo "--- Step 1: Rename platform nodes ---\n";

// Map current DB titles → canonical names + aliases
$renames = [
  'Sovereign Infrastructure Platform'     => ['title' => 'Sabal Infrastructure Platform',              'alias' => '/platforms/sabal'],
  'Liberty Headless CMS Platform'          => ['title' => 'Keel CMS Platform',                          'alias' => '/platforms/keel'],
  'Enterprise Search Platform'             => ['title' => 'Alidade Search Platform',                    'alias' => '/platforms/alidade'],
  'Fortis Zero-Trust Identity Platform'    => ['title' => 'Squawk Zero-Trust Identity Platform',        'alias' => '/platforms/squawk'],
  'Apex Secure Data Platform'              => ['title' => 'Manifest Data Platform',                     'alias' => '/platforms/manifest'],
  'Vigilance Mission Observability Suite'   => ['title' => 'Lighthouse Observability Platform',           'alias' => '/platforms/lighthouse'],
];

$alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');

foreach ($renames as $old_title => $new) {
  $nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
    ->condition('type', 'platform')
    ->condition('title', $old_title)
    ->accessCheck(FALSE)
    ->range(0, 1)
    ->execute();

  if (empty($nids)) {
    echo "  [!] Not found: \"{$old_title}\" — skipping\n";
    continue;
  }

  $nid = reset($nids);

  if ($dry_run) {
    echo "  [?] nid={$nid} \"{$old_title}\" → \"{$new['title']}\" alias={$new['alias']}\n";
    continue;
  }

  $node = Node::load($nid);
  $node->setTitle($new['title']);
  $node->save();

  // Delete existing path aliases for this node.
  $existing_aliases = $alias_storage->loadByProperties([
    'path' => '/node/' . $nid,
  ]);
  foreach ($existing_aliases as $alias_entity) {
    $alias_entity->delete();
  }

  // Create new path alias.
  $new_alias = PathAlias::create([
    'path' => '/node/' . $nid,
    'alias' => $new['alias'],
    'langcode' => 'en',
  ]);
  $new_alias->save();

  echo "  [+] nid={$nid} \"{$old_title}\" → \"{$new['title']}\" alias={$new['alias']}\n";
}

// ---------------------------------------------------------------------------
// Step 2: Create Coquina Software Factory Platform (draft)
// ---------------------------------------------------------------------------

echo "\n--- Step 2: Create Coquina Software Factory Platform ---\n";

$coquina_title = 'Coquina Software Factory Platform';
$coquina_alias = '/platforms/coquina';

$existing = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
  ->condition('type', 'platform')
  ->condition('title', $coquina_title)
  ->accessCheck(FALSE)
  ->range(0, 1)
  ->execute();

if (!empty($existing)) {
  $nid = reset($existing);
  echo "  [=] Already exists: nid={$nid} — skipping\n";
}
elseif ($dry_run) {
  echo "  [?] Would create: \"{$coquina_title}\" alias={$coquina_alias}\n";
}
else {
  $node = Node::create([
    'type' => 'platform',
    'title' => $coquina_title,
    'status' => 0,
    'moderation_state' => 'draft',
    'body' => [
      'value' => '<p>Placeholder — content to be entered in a separate content session.</p>',
      'format' => 'headless_safe',
    ],
    'field_summary' => 'Full-lifecycle software factory for sovereign environments.',
    'path' => ['alias' => $coquina_alias],
  ]);
  $node->save();
  echo "  [+] Created: nid=" . $node->id() . " \"{$coquina_title}\" alias={$coquina_alias} (draft)\n";
}

echo "\n=== Done ===\n";
if ($dry_run) {
  echo "Dry run: no changes were made.\n";
}
