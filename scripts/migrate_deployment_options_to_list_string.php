<?php

/**
 * @file
 * DESTRUCTIVE migration: convert field_deployment_options (string) → list_string.
 *
 * --------------------------------------------------------------------------
 *   READ THE PLANNING DOC FIRST:
 *     docs/migrations/FIELD_DEPLOYMENT_OPTIONS_LIST_STRING.md
 * --------------------------------------------------------------------------
 *
 * This script is safe-to-have-checked-in:
 *   - Default mode is a DRY RUN. Nothing is written without --apply.
 *   - --export mode is read-only; useful for a fresh pre-flight survey.
 *   - --apply requires --i-have-a-backup to proceed.
 *   - --apply refuses to run if any data exists in
 *     node__field_deployment_options or node_revision__field_deployment_options.
 *     The "no data to migrate" path is the only one currently implemented; if
 *     data is present, the script aborts and the operator must extend this
 *     script with a data-mapping step before re-running.
 *
 * What --apply does (and why it is destructive):
 *   Drupal does not allow changing a field storage's `type`. The only path
 *   from `string` to `list_string` is delete + purge + recreate. That means:
 *     1. Verify both data tables are empty (abort otherwise).
 *     2. Delete bundle FieldConfig on product + service.
 *     3. Delete the shared FieldStorageConfig.
 *     4. Run field_purge_batch() until the deleted field is fully reaped.
 *     5. Create a new FieldStorageConfig (list_string, cardinality -1,
 *        translatable, allowed_values from WL_ALLOWED_DEPLOYMENT_OPTIONS).
 *     6. Create FieldConfig on product + service with their original labels
 *        and descriptions, translatable, not required.
 *     7. Switch form display widget to options_buttons; view display formatter
 *        to list_default. Both bundles, default form/view modes.
 *     8. Cache rebuild via drupal_flush_all_caches().
 *
 * Usage:
 *   # Pre-flight survey — read-only
 *   ddev drush scr scripts/migrate_deployment_options_to_list_string.php -- --export
 *
 *   # Dry run — prints plan, mutates nothing
 *   ddev drush scr scripts/migrate_deployment_options_to_list_string.php
 *
 *   # Real run
 *   ddev drush scr scripts/migrate_deployment_options_to_list_string.php -- \
 *       --apply --i-have-a-backup
 *
 * Rollback:
 *   See planning doc. Short version: restore the DB backup and `drush cim -y`
 *   to put the old field.storage YAML back.
 */

declare(strict_types=1);

use Drupal\Core\Database\Database;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

// -----------------------------------------------------------------------------
// Configuration — single source of truth.
// -----------------------------------------------------------------------------

/**
 * Canonical allowed_values for the new list_string field.
 *
 * Keys are the storage values that will be persisted; values are the
 * human-readable labels shown in the admin dropdown / checkboxes.
 *
 * Mirrored into config/sync/field.storage.node.field_deployment_options.yml
 * by `drush cex` after --apply runs successfully.
 */
const WL_ALLOWED_DEPLOYMENT_OPTIONS = [
  'aws_govcloud'          => 'AWS GovCloud',
  'azure_government'      => 'Azure Government',
  'gcp_assured_workloads' => 'Google Cloud GCP (Assured Workloads)',
  'on_premises'           => 'On-Premises',
  'hybrid'                => 'Hybrid',
  'il5'                   => 'IL5',
  'il6'                   => 'IL6',
];

/**
 * Per-bundle FieldConfig metadata, preserved from the pre-migration YAMLs.
 */
const WL_BUNDLE_FIELD_META = [
  'product' => [
    'label'       => 'Deployment Options',
    'description' => 'E.g., on-premises, private cloud, hybrid, air-gapped',
  ],
  'service' => [
    'label'       => 'Deployment Options',
    'description' => '',
  ],
];

const WL_FIELD_NAME = 'field_deployment_options';
const WL_BUNDLES    = ['product', 'service'];

// -----------------------------------------------------------------------------
// Arg parsing.
// -----------------------------------------------------------------------------

$argv_in = $extra ?? ($GLOBALS['argv'] ?? []);
$flags = [
  'apply'           => in_array('--apply', $argv_in, TRUE),
  'export'          => in_array('--export', $argv_in, TRUE),
  'i_have_a_backup' => in_array('--i-have-a-backup', $argv_in, TRUE),
];

wl_log('Mode: ' . wl_describe_mode($flags));

// -----------------------------------------------------------------------------
// Snapshot current data (always; cheap; needed by every mode).
// -----------------------------------------------------------------------------

$rows = wl_snapshot_current_rows();
$revision_count = wl_snapshot_revision_count();
wl_log(sprintf(
  'Loaded %d rows from node__%s; revision table has %d rows.',
  count($rows),
  WL_FIELD_NAME,
  $revision_count
));

if ($flags['export']) {
  $path = '/tmp/dep_opts_export_' . date('Ymd_His') . '.csv';
  wl_write_csv($path, $rows);
  $distinct = wl_distinct_value_counts($rows);
  wl_log("Wrote: $path");
  wl_log('Distinct values + counts:');
  if (empty($distinct)) {
    wl_log('  (none — table empty)');
  }
  else {
    foreach ($distinct as $value => $count) {
      wl_log(sprintf('  %6d  %s', $count, var_export($value, TRUE)));
    }
  }
  return;
}

// -----------------------------------------------------------------------------
// Dry-run plan output.
// -----------------------------------------------------------------------------

wl_log('Planned destructive sequence (informational):');
wl_log('  1. Verify node__field_deployment_options + revision table both empty');
wl_log('  2. Delete FieldConfig on bundles: ' . implode(', ', WL_BUNDLES));
wl_log('  3. Delete FieldStorageConfig node.' . WL_FIELD_NAME);
wl_log('  4. Run field_purge_batch() until reaped');
wl_log('  5. Create new FieldStorageConfig (type=list_string, cardinality=-1, translatable)');
wl_log('     allowed_values:');
foreach (WL_ALLOWED_DEPLOYMENT_OPTIONS as $k => $v) {
  wl_log("       $k => $v");
}
wl_log('  6. Create FieldConfig on bundles with preserved labels/descriptions');
wl_log('  7. Switch form display widget → options_buttons; view formatter → list_default');
wl_log('  8. Re-enable field in graphql_compose.settings on both bundles');
wl_log('  9. drupal_flush_all_caches()');

if (!$flags['apply']) {
  wl_log('Dry run complete. Re-run with --apply --i-have-a-backup to execute.');
  return;
}

if (!$flags['i_have_a_backup']) {
  wl_log('ABORT: --apply requires --i-have-a-backup. See planning doc §Rollback.');
  return;
}

// -----------------------------------------------------------------------------
// Pre-flight guard: zero-row policy.
// -----------------------------------------------------------------------------

if (count($rows) !== 0 || $revision_count !== 0) {
  wl_log(sprintf(
    'ABORT: non-zero data found (current=%d, revision=%d).',
    count($rows),
    $revision_count
  ));
  wl_log('This script only supports the zero-data path. Extend with mapping');
  wl_log('logic before re-running, or restore the planned mapping step.');
  return;
}

// -----------------------------------------------------------------------------
// APPLY.
// -----------------------------------------------------------------------------

wl_log('--apply mode entered. Beginning destructive sequence.');
wl_apply_destructive();
wl_log('Migration complete.');

// =============================================================================
// Helpers.
// =============================================================================

function wl_log(string $msg): void {
  fwrite(STDOUT, '[deploy-opts-migrate] ' . $msg . PHP_EOL);
}

function wl_describe_mode(array $flags): string {
  if ($flags['export']) {
    return 'EXPORT (read-only survey)';
  }
  if ($flags['apply'] && $flags['i_have_a_backup']) {
    return 'APPLY (destructive)';
  }
  if ($flags['apply']) {
    return 'APPLY requested but missing --i-have-a-backup';
  }
  return 'DRY RUN (default)';
}

/**
 * @return array<int, array{entity_id:int, bundle:string, delta:int, langcode:string, value:string}>
 */
function wl_snapshot_current_rows(): array {
  $conn = Database::getConnection();
  $sql = "
    SELECT entity_id, bundle, delta, langcode, field_deployment_options_value AS value
    FROM {node__field_deployment_options}
    ORDER BY entity_id, langcode, delta
  ";
  $result = $conn->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
  return array_map(fn($r) => [
    'entity_id' => (int) $r['entity_id'],
    'bundle'    => (string) $r['bundle'],
    'delta'     => (int) $r['delta'],
    'langcode'  => (string) $r['langcode'],
    'value'     => (string) $r['value'],
  ], $result);
}

function wl_snapshot_revision_count(): int {
  $conn = Database::getConnection();
  $sql = "SELECT COUNT(*) FROM {node_revision__field_deployment_options}";
  return (int) $conn->query($sql)->fetchField();
}

function wl_write_csv(string $path, array $rows): void {
  $fh = fopen($path, 'w');
  fputcsv($fh, ['entity_id', 'bundle', 'delta', 'langcode', 'value']);
  foreach ($rows as $r) {
    fputcsv($fh, [$r['entity_id'], $r['bundle'], $r['delta'], $r['langcode'], $r['value']]);
  }
  fclose($fh);
}

function wl_distinct_value_counts(array $rows): array {
  $counts = [];
  foreach ($rows as $r) {
    $counts[$r['value']] = ($counts[$r['value']] ?? 0) + 1;
  }
  arsort($counts);
  return $counts;
}

/**
 * The destructive path. Caller has already verified zero-row state.
 */
function wl_apply_destructive(): void {
  // Step 2: Delete bundle FieldConfig.
  foreach (WL_BUNDLES as $bundle) {
    $fc = FieldConfig::loadByName('node', $bundle, WL_FIELD_NAME);
    if ($fc) {
      $fc->delete();
      wl_log("  Deleted FieldConfig: node.$bundle." . WL_FIELD_NAME);
    }
    else {
      wl_log("  (FieldConfig already absent: node.$bundle." . WL_FIELD_NAME . ')');
    }
  }

  // Step 3: Delete FieldStorageConfig.
  $storage = FieldStorageConfig::loadByName('node', WL_FIELD_NAME);
  if ($storage) {
    $storage->delete();
    wl_log('  Deleted FieldStorageConfig: node.' . WL_FIELD_NAME);
  }
  else {
    wl_log('  (FieldStorageConfig already absent: node.' . WL_FIELD_NAME . ')');
  }

  // Step 4: Purge loop. Bounded; abort if we cannot reap within budget.
  $deleted_fields_repo = \Drupal::service('entity_field.deleted_fields_repository');
  $max_iterations = 50;
  for ($i = 0; $i < $max_iterations; $i++) {
    $pending = FALSE;
    foreach ($deleted_fields_repo->getFieldStorageDefinitions() as $def) {
      if ($def->getName() === WL_FIELD_NAME) {
        $pending = TRUE;
        break;
      }
    }
    if (!$pending) {
      // Also check field definitions (per-bundle records).
      foreach ($deleted_fields_repo->getFieldDefinitions() as $def) {
        if ($def->getName() === WL_FIELD_NAME) {
          $pending = TRUE;
          break;
        }
      }
    }
    if (!$pending) {
      wl_log("  Purge complete after $i iteration(s).");
      break;
    }
    field_purge_batch(50);
  }
  if ($i === $max_iterations) {
    throw new \RuntimeException("Purge did not complete in $max_iterations iterations.");
  }

  // Step 5: Create new FieldStorageConfig.
  // NOTE: at runtime the entity API expects allowed_values in the SIMPLE
  // [key => label] form; Drupal converts it to the structured
  // [{value, label}] form on save via storageSettingsToConfigData().
  // Passing the structured form here would get double-converted and trip
  // the config schema validator with "label.0 doesn't exist".
  FieldStorageConfig::create([
    'field_name'   => WL_FIELD_NAME,
    'entity_type'  => 'node',
    'type'         => 'list_string',
    'cardinality'  => -1,
    'translatable' => TRUE,
    'settings'     => [
      'allowed_values'          => WL_ALLOWED_DEPLOYMENT_OPTIONS,
      'allowed_values_function' => '',
    ],
  ])->save();
  wl_log('  Created FieldStorageConfig (list_string).');

  // Step 6: Create bundle FieldConfig.
  foreach (WL_BUNDLES as $bundle) {
    $meta = WL_BUNDLE_FIELD_META[$bundle];
    FieldConfig::create([
      'field_name'   => WL_FIELD_NAME,
      'entity_type'  => 'node',
      'bundle'       => $bundle,
      'label'        => $meta['label'],
      'description'  => $meta['description'],
      'required'     => FALSE,
      'translatable' => TRUE,
    ])->save();
    wl_log("  Created FieldConfig: node.$bundle." . WL_FIELD_NAME);
  }

  // Step 7: Update form + view displays.
  $display_repo = \Drupal::service('entity_display.repository');
  foreach (WL_BUNDLES as $bundle) {
    $form = $display_repo->getFormDisplay('node', $bundle, 'default');
    $form->setComponent(WL_FIELD_NAME, [
      'type'                 => 'options_buttons',
      'weight'               => 6,
      'region'               => 'content',
      'settings'             => [],
      'third_party_settings' => [],
    ])->save();
    wl_log("  Form display updated: node.$bundle.default (options_buttons).");

    $view = $display_repo->getViewDisplay('node', $bundle, 'default');
    $view->setComponent(WL_FIELD_NAME, [
      'type'                 => 'list_default',
      'label'                => 'above',
      'weight'               => 6,
      'region'               => 'content',
      'settings'             => [],
      'third_party_settings' => [],
    ])->save();
    wl_log("  View display updated: node.$bundle.default (list_default).");
  }

  // Step 8: Re-enable the field in graphql_compose.settings on both bundles.
  // graphql_compose removes the per-field entry when the FieldConfig is
  // deleted; recreating the field does NOT auto-restore it (default for
  // new fields is unset/disabled). Restore explicitly so the GraphQL
  // schema continues to expose deploymentOptions on NodeProduct/NodeService.
  $gc = \Drupal::configFactory()->getEditable('graphql_compose.settings');
  foreach (WL_BUNDLES as $bundle) {
    $gc->set("field_config.node.$bundle." . WL_FIELD_NAME . '.enabled', TRUE);
  }
  $gc->save();
  wl_log('  graphql_compose: re-enabled ' . WL_FIELD_NAME . ' on ' . implode(', ', WL_BUNDLES) . '.');

  // Step 9: Cache rebuild.
  drupal_flush_all_caches();
  wl_log('  Caches rebuilt.');
}
