<?php
/**
 * Migrate content type product → platform (database layer).
 *
 * Run BEFORE config:import. This script updates the database so that when
 * Drupal deletes the old 'product' field instances during cim, the purge
 * queries find nothing (bundle is already 'platform'), preserving field data.
 *
 * Steps:
 *   1. Update type/bundle column in core node tables
 *   2. Update bundle column in ALL field data tables (node__* and node_revision__*)
 *   3. Update the content_moderation_state tables
 *
 * After running this script, run: ddev drush cim -y
 * Then run the node-rename and Coquina-creation steps separately.
 *
 * Run:
 *   ddev drush scr scripts/migrate_product_to_platform.php
 *   ddev drush scr scripts/migrate_product_to_platform.php -- --dry-run
 */

$wl_argv = isset($extra) && is_array($extra) ? $extra : array_slice($_SERVER['argv'] ?? [], 1);
$dry_run = in_array('--dry-run', $wl_argv, true);

echo "=== Product → Platform Database Migration ===\n";
echo 'Mode: ' . ($dry_run ? 'DRY-RUN (no DB writes)' : 'LIVE') . "\n\n";

$db = \Drupal::database();

// ---------------------------------------------------------------------------
// Step 1: Core node tables (type column)
// ---------------------------------------------------------------------------

echo "--- Step 1: Core node tables (type column) ---\n";

// Only node and node_field_data have a 'type' column.
// node_revision and node_field_revision reference the node by nid only.
$core_tables = ['node', 'node_field_data'];

foreach ($core_tables as $table) {
  $count = $db->select($table, 't')
    ->condition('t.type', 'product')
    ->countQuery()
    ->execute()
    ->fetchField();

  if ($dry_run) {
    echo "  [?] {$table}: {$count} rows would be updated\n";
  }
  elseif ($count > 0) {
    $updated = $db->update($table)
      ->fields(['type' => 'platform'])
      ->condition('type', 'product')
      ->execute();
    echo "  [+] {$table}: {$updated} rows updated\n";
  }
  else {
    echo "  [=] {$table}: 0 rows (already migrated or empty)\n";
  }
}

// ---------------------------------------------------------------------------
// Step 2: Field data tables (bundle column)
// ---------------------------------------------------------------------------

echo "\n--- Step 2: Field data tables (bundle column) ---\n";

// Find all tables with a 'bundle' column that start with node__ or node_revision__
$field_tables = [];
$result = $db->query("
  SELECT table_name FROM information_schema.columns
  WHERE column_name = 'bundle'
    AND table_schema = current_schema()
    AND (table_name LIKE 'node\\_\\_%' OR table_name LIKE 'node\\_revision\\_\\_%')
  ORDER BY table_name
");

foreach ($result as $row) {
  $field_tables[] = $row->table_name;
}

$total_updated = 0;
foreach ($field_tables as $table) {
  $count = $db->select($table, 't')
    ->condition('t.bundle', 'product')
    ->countQuery()
    ->execute()
    ->fetchField();

  if ($count == 0) {
    continue; // Skip tables with no product rows (most fields aren't used by product)
  }

  if ($dry_run) {
    echo "  [?] {$table}: {$count} rows would be updated\n";
  }
  else {
    $updated = $db->update($table)
      ->fields(['bundle' => 'platform'])
      ->condition('bundle', 'product')
      ->execute();
    echo "  [+] {$table}: {$updated} rows updated\n";
    $total_updated += $updated;
  }
}

if (!$dry_run) {
  echo "  Total field data rows updated: {$total_updated}\n";
}

// ---------------------------------------------------------------------------
// Step 3: Content moderation state tables
// ---------------------------------------------------------------------------

echo "\n--- Step 3: Content moderation state tables ---\n";

$mod_tables = ['content_moderation_state_field_data', 'content_moderation_state_field_revision'];
foreach ($mod_tables as $table) {
  // Check if table exists first
  if (!$db->schema()->tableExists($table)) {
    echo "  [=] {$table}: table does not exist — skipping\n";
    continue;
  }

  // content_moderation_state uses 'content_entity_type_id' and 'content_entity_id'
  // but the bundle filter is on the moderated entity. Check for a 'bundle' or
  // 'workflow' column pattern.
  $has_bundle = $db->schema()->fieldExists($table, 'content_entity_type_id');
  if (!$has_bundle) {
    echo "  [=] {$table}: no content_entity_type_id column — skipping\n";
    continue;
  }

  // Content moderation doesn't store bundle directly in its tables; it references
  // the entity. The bundle change in node tables is sufficient.
  echo "  [=] {$table}: handled via node table bundle update\n";
}

echo "\n=== Database migration complete ===\n";
if ($dry_run) {
  echo "Dry run: no changes were made. Re-run without --dry-run to apply.\n";
}
else {
  echo "Next steps:\n";
  echo "  1. ddev drush cim -y    (import renamed config)\n";
  echo "  2. ddev drush cr        (rebuild caches)\n";
}
