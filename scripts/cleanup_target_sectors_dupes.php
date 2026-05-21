<?php
/**
 * Idempotent cleanup of duplicate target_sectors taxonomy terms.
 *
 * Production data (restored from backup) carried legacy terms that overlap
 * with the canonical terms seeded by scripts/taxonomy_setup.php:
 *
 *   Defense        -> Department of Defense   (canonical matches BRAND_VOICE.md persona, more specific)
 *   State & Local  -> State and Local         (canonical avoids ampersand; cleaner URLs/SEO)
 *
 * What this does, per pair:
 *   1. Resolve tids by name in vocabulary "target_sectors".
 *   2. For every entity already referencing BOTH the deprecated AND canonical tid,
 *      drop the deprecated row (dedupe; multi-cardinality safe).
 *   3. Re-point remaining deprecated references to the canonical tid in both the
 *      data table (node__field_target_sectors) and the revisions table
 *      (node_revision__field_target_sectors).
 *   4. Delete the deprecated term once no references remain.
 *   5. Invalidate node + taxonomy cache tags so the change is visible immediately.
 *
 * Safe to re-run. A pair where the deprecated term is already gone is a no-op.
 *
 * Run:
 *   drush scr scripts/cleanup_target_sectors_dupes.php
 *
 * Note:
 *   Taxonomy terms are content, not configuration. Do NOT run `drush cex`
 *   afterward — there is nothing to export.
 */

use Drupal\taxonomy\Entity\Term;

const WL_VID        = 'target_sectors';
const WL_DATA_TABLE = 'node__field_target_sectors';
const WL_REVS_TABLE = 'node_revision__field_target_sectors';
const WL_VALUE_COL  = 'field_target_sectors_target_id';

/**
 * deprecated_name => canonical_name. Keep this list authoritative.
 */
$merges = [
  'Defense'       => 'Department of Defense',
  'State & Local' => 'State and Local',
];

/**
 * Load the first term matching a name within a vocabulary, or NULL.
 */
$load_term_by_name = function (string $vid, string $name): ?Term {
  $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
  $ids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('vid', $vid)
    ->condition('name', $name)
    ->execute();
  if (!$ids) {
    return NULL;
  }
  return $storage->load(reset($ids));
};

$db = \Drupal::database();
$total_reassigned = 0;
$total_deduped    = 0;
$rows             = [];
$affected_nids    = [];

foreach ($merges as $deprecated_name => $canonical_name) {
  $deprecated = $load_term_by_name(WL_VID, $deprecated_name);
  $canonical  = $load_term_by_name(WL_VID, $canonical_name);

  if (!$canonical) {
    printf("[!] ABORT pair: canonical term '%s' not found in vocabulary '%s'. Run scripts/taxonomy_setup.php first.\n",
      $canonical_name, WL_VID);
    continue;
  }

  if (!$deprecated) {
    printf("[=] No-op: deprecated term '%s' not present (already merged).\n", $deprecated_name);
    $rows[] = [$deprecated_name, '(absent)', $canonical_name, (int) $canonical->id(), 0, 0];
    continue;
  }

  $from_tid = (int) $deprecated->id();
  $to_tid   = (int) $canonical->id();

  if ($from_tid === $to_tid) {
    printf("[=] No-op: '%s' resolved to same tid as canonical (%d).\n", $deprecated_name, $to_tid);
    continue;
  }

  printf("[~] Merge: '%s' (tid=%d) -> '%s' (tid=%d)\n",
    $deprecated_name, $from_tid, $canonical_name, $to_tid);

  // Capture entity ids that currently reference the deprecated tid so we can
  // invalidate their cache tags after the merge — raw SQL bypasses Drupal's
  // entity cache, so without this nodes can render stale until `drush cr`.
  $touched = $db->query(
    "SELECT DISTINCT entity_id FROM " . WL_DATA_TABLE . " WHERE " . WL_VALUE_COL . " = :tid",
    [':tid' => $from_tid]
  )->fetchCol();
  foreach ($touched as $nid) {
    $affected_nids[(int) $nid] = TRUE;
  }

  $pair_reassigned = 0;
  $pair_deduped    = 0;

  foreach ([WL_DATA_TABLE, WL_REVS_TABLE] as $table) {
    // 1. Dedupe: drop rows holding the deprecated tid when the same
    //    (entity, revision, langcode, deleted) tuple already holds the canonical.
    //    Use prepareStatement so we can read rowCount() on a raw DELETE.
    $dedup_sql = "
      DELETE FROM {$table} a
      WHERE a." . WL_VALUE_COL . " = :from
        AND EXISTS (
          SELECT 1 FROM {$table} b
          WHERE b.entity_id    = a.entity_id
            AND b.revision_id  = a.revision_id
            AND b.langcode     = a.langcode
            AND b.deleted      = a.deleted
            AND b." . WL_VALUE_COL . " = :to
        )
    ";
    $stmt = $db->prepareStatement($dedup_sql, [], TRUE);
    $stmt->execute([':from' => $from_tid, ':to' => $to_tid]);
    $deduped = $stmt->rowCount();

    // 2. Reassign remaining deprecated references to the canonical tid.
    $reassigned = $db->update($table)
      ->fields([WL_VALUE_COL => $to_tid])
      ->condition(WL_VALUE_COL, $from_tid)
      ->execute();

    printf("    %s: deduped=%d, reassigned=%d\n", $table, (int) $deduped, (int) $reassigned);

    if ($table === WL_DATA_TABLE) {
      $pair_deduped    = (int) $deduped;
      $pair_reassigned = (int) $reassigned;
    }
  }

  $total_deduped    += $pair_deduped;
  $total_reassigned += $pair_reassigned;

  // 3. Verify zero references remain (both tables) before deleting the term.
  $remaining_data = (int) $db->query(
    "SELECT COUNT(*) FROM " . WL_DATA_TABLE . " WHERE " . WL_VALUE_COL . " = :tid",
    [':tid' => $from_tid]
  )->fetchField();
  $remaining_revs = (int) $db->query(
    "SELECT COUNT(*) FROM " . WL_REVS_TABLE . " WHERE " . WL_VALUE_COL . " = :tid",
    [':tid' => $from_tid]
  )->fetchField();

  if ($remaining_data === 0 && $remaining_revs === 0) {
    $deprecated->delete();
    printf("[+] Deleted deprecated term '%s' (tid=%d)\n", $deprecated_name, $from_tid);
  }
  else {
    printf("[!] Refusing to delete tid=%d: still %d data + %d revision rows reference it.\n",
      $from_tid, $remaining_data, $remaining_revs);
  }

  $rows[] = [$deprecated_name, $from_tid, $canonical_name, $to_tid, $pair_reassigned, $pair_deduped];
}

// Invalidate caches so reassigned nodes pick up the new tid downstream.
// Raw SQL writes don't fire entity hooks, so we explicitly invalidate the
// per-node cache tags plus list tags, and reset the entity storage caches.
if ($affected_nids) {
  $tags = ['node_list', 'taxonomy_term_list'];
  foreach (array_keys($affected_nids) as $nid) {
    $tags[] = 'node:' . $nid;
  }
  \Drupal\Core\Cache\Cache::invalidateTags($tags);

  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type_manager->getStorage('node')->resetCache(array_keys($affected_nids));
  $entity_type_manager->getStorage('taxonomy_term')->resetCache();
}
else {
  \Drupal\Core\Cache\Cache::invalidateTags(['node_list', 'taxonomy_term_list']);
}

print "\n=== Summary ===\n";
printf("%-16s %-8s %-26s %-8s %-12s %-8s\n", 'Deprecated', 'from_tid', 'Canonical', 'to_tid', 'Reassigned', 'Deduped');
foreach ($rows as [$dn, $ft, $cn, $tt, $r, $d]) {
  printf("%-16s %-8s %-26s %-8s %-12d %-8d\n", $dn, (string) $ft, $cn, (string) $tt, $r, $d);
}
printf("Totals: reassigned=%d, deduped=%d\n", $total_reassigned, $total_deduped);
