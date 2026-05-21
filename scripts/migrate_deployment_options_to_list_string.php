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
 * This script is intentionally safe-to-have-checked-in:
 *   - Default mode is a DRY RUN. Nothing is written without --apply.
 *   - --export mode is read-only; useful for the pre-flight survey.
 *   - --apply requires --i-have-a-backup to proceed; verifies allowed-value
 *     coverage before touching anything; aborts on any unmapped value.
 *
 * What --apply does (and why it is destructive):
 *   Drupal does not allow changing a field storage's `type`. The only path
 *   from `string` to `list_string` is delete + purge + recreate. That means:
 *     1. Snapshot current data into a CSV at /tmp/dep_opts_pre_<TIMESTAMP>.csv
 *     2. Delete field.field.node.product.field_deployment_options
 *        AND   field.field.node.service.field_deployment_options
 *        (the storage is shared across both bundles)
 *     3. Delete field.storage.node.field_deployment_options
 *     4. Run field_purge_batch() until the deleted field is fully removed
 *     5. Import the new field.storage YAML (list_string + allowed_values)
 *        and the two re-created field.field YAMLs from config/sync
 *     6. Re-attach mapped values to each node revision, preserving:
 *          - entity_id, langcode, delta order
 *          - translation rows (the field is translatable)
 *     7. Re-snapshot to /tmp/dep_opts_post_<TIMESTAMP>.csv for diff
 *
 * Usage:
 *   # Pre-flight survey — read-only, dumps distinct values + per-value counts
 *   ddev drush scr scripts/migrate_deployment_options_to_list_string.php -- --export
 *
 *   # Dry run — validates mapping, prints plan, mutates nothing
 *   ddev drush scr scripts/migrate_deployment_options_to_list_string.php
 *
 *   # Real run — requires explicit acknowledgement of backup
 *   ddev drush scr scripts/migrate_deployment_options_to_list_string.php -- \
 *       --apply --i-have-a-backup
 *
 * Rollback:
 *   See planning doc. Short version: restore the 02:00 nightly DB backup and
 *   `ddev drush cim -y` to put the old field.storage YAML back.
 */

declare(strict_types=1);

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;

// -----------------------------------------------------------------------------
// Configuration — single source of truth for the mapping.
// -----------------------------------------------------------------------------

/**
 * Canonical allowed_values for the new list_string field.
 *
 * Keys are the storage values that will be persisted; values are the
 * human-readable labels shown in the admin dropdown.
 *
 * Mirror this exactly into:
 *   config/sync/field.storage.node.field_deployment_options.yml
 *
 * IMPORTANT: changing keys after deploy is itself a breaking migration.
 */
const WL_ALLOWED_DEPLOYMENT_OPTIONS = [
  'aws_govcloud'           => 'AWS GovCloud',
  'azure_government'       => 'Azure Government',
  'gcp_assured_workloads'  => 'Google Cloud GCP (Assured Workloads)',
  'on_premises'            => 'On-Premises',
  'hybrid'                 => 'Hybrid',
  'il5'                    => 'IL5',
  'il6'                    => 'IL6',
  // If editors need an escape hatch, uncomment and mirror into YAML:
  // 'other'               => 'Other',
];

/**
 * Free-text → canonical mapping.
 *
 * Lookup is case-insensitive and whitespace-normalised (see normalize_key()).
 * Populate this from the --export survey output before running --apply.
 * Add an entry for every observed variant; if anything is missing, --apply
 * aborts loudly rather than silently dropping data.
 *
 * Example entries (replace once survey is in):
 *   'aws govcloud'                 => 'aws_govcloud',
 *   'aws gov cloud'                => 'aws_govcloud',
 *   'azure government'             => 'azure_government',
 *   'google cloud (assured workloads)' => 'gcp_assured_workloads',
 *   'on-premises'                  => 'on_premises',
 *   'on premises'                  => 'on_premises',
 *   'on-prem'                      => 'on_premises',
 *   'hybrid'                       => 'hybrid',
 *   'il5'                          => 'il5',
 *   'il6'                          => 'il6',
 */
const WL_VALUE_MAP = [
  // POPULATE FROM --export SURVEY BEFORE RUNNING --apply.
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
// Step 1: Snapshot current data (always; cheap; needed by every mode).
// -----------------------------------------------------------------------------

$rows = wl_snapshot_current_rows();
wl_log(sprintf('Loaded %d rows from node__%s.', count($rows), WL_FIELD_NAME));

if ($flags['export']) {
  $path = '/tmp/dep_opts_export_' . date('Ymd_His') . '.csv';
  wl_write_csv($path, $rows);
  $distinct = wl_distinct_value_counts($rows);
  wl_log("Wrote: $path");
  wl_log('Distinct values + counts:');
  foreach ($distinct as $value => $count) {
    wl_log(sprintf('  %6d  %s', $count, var_export($value, TRUE)));
  }
  return;
}

// -----------------------------------------------------------------------------
// Step 2: Validate mapping coverage. Abort on any unmapped value.
// -----------------------------------------------------------------------------

$unmapped = wl_unmapped_values($rows);
if (!empty($unmapped)) {
  wl_log('ABORT: unmapped values present. Add these to WL_VALUE_MAP:');
  foreach ($unmapped as $value => $count) {
    wl_log(sprintf('  %6d  %s', $count, var_export($value, TRUE)));
  }
  wl_log('No changes made.');
  return;
}
wl_log('Mapping coverage OK — every current value maps to a canonical key.');

// -----------------------------------------------------------------------------
// Step 3: Plan the rewrites. Always shown.
// -----------------------------------------------------------------------------

$rewrites = wl_plan_rewrites($rows);
wl_log(sprintf('Planned rewrites: %d node/delta/langcode rows.', count($rewrites)));
$summary = wl_rewrite_summary($rewrites);
foreach ($summary as $from => $to_counts) {
  foreach ($to_counts as $to => $count) {
    wl_log(sprintf('  %6d  %s  ->  %s', $count, $from, $to));
  }
}

// -----------------------------------------------------------------------------
// Step 4: Dry-run exit. --apply gated behind --i-have-a-backup.
// -----------------------------------------------------------------------------

if (!$flags['apply']) {
  wl_log('Dry run complete. Re-run with --apply --i-have-a-backup to execute.');
  return;
}

if (!$flags['i_have_a_backup']) {
  wl_log('ABORT: --apply requires --i-have-a-backup. See planning doc §Rollback.');
  return;
}

// -----------------------------------------------------------------------------
// Step 5: APPLY. The destructive path.
// -----------------------------------------------------------------------------
//
// NOTE: This block is the skeleton of the destructive path. It is intentionally
// left as TODO + assertions until Jeremy explicitly approves the run. The real
// implementation needs the field-purge dance described at the top of the file
// and must be exercised against a copy of prod first.

wl_log('--apply mode entered. Beginning destructive sequence.');

// 5a. Persist a pre-snapshot CSV.
$pre_path = '/tmp/dep_opts_pre_' . date('Ymd_His') . '.csv';
wl_write_csv($pre_path, $rows);
wl_log("Pre-snapshot written: $pre_path");

// 5b. Delete bundle-level field configs (product + service).
// TODO(apply): foreach (WL_BUNDLES as $bundle) load field_config and ->delete()

// 5c. Delete field storage.
// TODO(apply): load field_storage_config and ->delete()

// 5d. Run field_purge_batch until empty.
// TODO(apply): loop field_purge_batch(50) while
// \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('node')
// still contains a deleted field with this name.

// 5e. Import new storage + field configs from config/sync.
// TODO(apply): use ConfigImporter on the three YAMLs, or drush cim --partial
// scoped to just those config names. The new field.storage YAML must declare
// type: list_string and allowed_values matching WL_ALLOWED_DEPLOYMENT_OPTIONS.

// 5f. Re-attach mapped values via entity API so hooks/translations run.
// TODO(apply): for each entity_id in $rewrites:
//   - Node::load(), iterate translations, set field values to mapped canonicals
//     respecting delta order, then ->save() on each translation.

// 5g. Post-snapshot for diff + count parity check.
$post_path = '/tmp/dep_opts_post_' . date('Ymd_His') . '.csv';
// TODO(apply): wl_write_csv($post_path, wl_snapshot_current_rows());
wl_log("Post-snapshot target: $post_path");

wl_log('Skeleton complete. Real --apply implementation is gated until plan approval.');

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
 * Lowercase, collapse whitespace. Used to make WL_VALUE_MAP forgiving.
 */
function wl_normalize_key(string $value): string {
  return preg_replace('/\s+/', ' ', strtolower(trim($value)));
}

/**
 * @return array<string,int> Unmapped raw value → occurrence count.
 */
function wl_unmapped_values(array $rows): array {
  $map = [];
  foreach (WL_VALUE_MAP as $k => $v) {
    $map[wl_normalize_key($k)] = $v;
  }
  $unmapped = [];
  foreach ($rows as $r) {
    $key = wl_normalize_key($r['value']);
    if (!array_key_exists($key, $map)) {
      $unmapped[$r['value']] = ($unmapped[$r['value']] ?? 0) + 1;
    }
  }
  return $unmapped;
}

/**
 * @return array<int, array{entity_id:int, bundle:string, delta:int, langcode:string, from:string, to:string}>
 */
function wl_plan_rewrites(array $rows): array {
  $map = [];
  foreach (WL_VALUE_MAP as $k => $v) {
    $map[wl_normalize_key($k)] = $v;
  }
  $out = [];
  foreach ($rows as $r) {
    $key = wl_normalize_key($r['value']);
    $canonical = $map[$key] ?? NULL;
    if ($canonical === NULL) {
      continue;
    }
    if (!array_key_exists($canonical, WL_ALLOWED_DEPLOYMENT_OPTIONS)) {
      throw new \RuntimeException(
        "Mapping points to unknown canonical key: $canonical (from '{$r['value']}')"
      );
    }
    $out[] = $r + ['from' => $r['value'], 'to' => $canonical];
  }
  return $out;
}

/**
 * @return array<string, array<string,int>> raw-value => [canonical => count]
 */
function wl_rewrite_summary(array $rewrites): array {
  $sum = [];
  foreach ($rewrites as $r) {
    $sum[$r['from']][$r['to']] = ($sum[$r['from']][$r['to']] ?? 0) + 1;
  }
  return $sum;
}
