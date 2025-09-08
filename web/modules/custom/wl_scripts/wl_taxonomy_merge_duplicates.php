<?php

declare(strict_types=1);

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Merge duplicate taxonomy terms (same name within a vocabulary).
 *
 * Rules:
 * - Normalize names (lowercase + trimmed + collapse whitespace) per vocabulary.
 * - Keep the lowest TID as canonical; remap all references to it; delete the rest.
 * - Updates any entity reference field (including term parents) that targets taxonomy_term.
 *
 * Safety:
 * - Set DRY_RUN=1 to preview; DRY_RUN=0 (default) performs the merge.
 */

$DRY_RUN = getenv('DRY_RUN') === '1' || in_array('--dry-run', $_SERVER['argv'] ?? [], true);

$etm = \Drupal::entityTypeManager();
$efm = \Drupal::service('entity_field.manager');
$bundleInfo = \Drupal::service('entity_type.bundle.info');
$term_storage = $etm->getStorage('taxonomy_term');

// 1) Build duplicate map per vocabulary.
$tid_map = [];               // dup_tid => canonical_tid
$dup_groups_by_vid = [];     // vid => [ norm => ['canonical'=>tid, 'dupes'=>[tid,...]] ]

$vocabs = Vocabulary::loadMultiple();
foreach ($vocabs as $vid => $vocab) {
  $tids = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', $vid)
    ->accessCheck(FALSE)
    ->execute();
  if (!$tids) { continue; }

  $terms = $term_storage->loadMultiple($tids);
  $groups = [];
  foreach ($terms as $term) {
    $name = (string) $term->getName();
    $norm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    $groups[$norm][] = (int) $term->id();
  }
  $dup_groups = [];
  foreach ($groups as $norm => $ids) {
    if (count($ids) > 1) {
      sort($ids, SORT_NUMERIC);
      $canonical = array_shift($ids);
      $dup_groups[$norm] = ['canonical' => $canonical, 'dupes' => $ids];
      foreach ($ids as $dup) { $tid_map[$dup] = $canonical; }
    }
  }
  if ($dup_groups) {
    $dup_groups_by_vid[$vid] = $dup_groups;
  }
}

if (empty($tid_map)) {
  print "No duplicate terms found.\n";
  return;
}

$dup_total = count($tid_map);
print "Found $dup_total duplicate term IDs to merge.\n";
foreach ($dup_groups_by_vid as $vid => $groups) {
  $g = count($groups);
  $dups = array_sum(array_map(fn($x) => count($x['dupes']), $groups));
  print "- [$vid] duplicate groups: $g, dup terms: $dups\n";
}
if ($DRY_RUN) {
  print "\nDRY RUN — Planned merges (first 50 lines):\n";
  $printed = 0;
  foreach ($dup_groups_by_vid as $vid => $groups) {
    foreach ($groups as $norm => $data) {
      $printed++;
      printf("  [%s] '%s' => keep %d, delete %s\n", $vid, $norm, $data['canonical'], implode(',', $data['dupes']));
      if ($printed >= 50) { break 2; }
    }
  }
  return;
}

// 2) Update references in all fieldable entities for fields that target taxonomy_term.
$entity_type_defs = $etm->getDefinitions();
$dup_tids = array_keys($tid_map);
$total_entities_changed = 0;

foreach ($entity_type_defs as $entity_type_id => $def) {
  $class = $def->getClass();
  if (!is_subclass_of($class, FieldableEntityInterface::class)) {
    continue; // skip non-fieldable entity types
  }

  $bundles = array_keys($bundleInfo->getBundleInfo($entity_type_id) ?? []);
  if (!$bundles) { $bundles = [$entity_type_id]; }

  $bundle_key = $def->getKey('bundle');
  $storage = $etm->getStorage($entity_type_id);

  foreach ($bundles as $bundle) {
    $field_defs = $efm->getFieldDefinitions($entity_type_id, $bundle);
    // Filter for entity reference fields to taxonomy_term
    $ref_fields = [];
    foreach ($field_defs as $field_name => $field_def) {
      $type = $field_def->getType();
      if (!in_array($type, ['entity_reference', 'entity_reference_revisions'], true)) { continue; }
      $target = $field_def->getSetting('target_type');
      if ($target !== 'taxonomy_term') { continue; }
      $ref_fields[] = $field_name;
    }
    if (!$ref_fields) { continue; }

    foreach ($ref_fields as $field_name) {
      $q = \Drupal::entityQuery($entity_type_id)->accessCheck(FALSE);
      if ($bundle_key) { $q->condition($bundle_key, $bundle); }
      $q->condition($field_name . '.target_id', $dup_tids, 'IN');
      $ids = $q->execute();
      if (!$ids) { continue; }

      $changed_here = 0;
      foreach (array_chunk($ids, 50) as $chunk) {
        $entities = $storage->loadMultiple($chunk);
        foreach ($entities as $entity) {
          $changed = false;
          $languages = $entity->getTranslationLanguages();
          if (!$languages) { $languages = [\Drupal::languageManager()->getDefaultLanguage()]; }

          foreach (array_keys($languages) as $langcode) {
            $translation = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity;
            if (!$translation->hasField($field_name)) { continue; }
            $items = $translation->get($field_name);
            if ($items->isEmpty()) { continue; }

            $new_ids = [];
            foreach ($items as $item) {
              $tid = (int) $item->target_id;
              if (isset($tid_map[$tid])) {
                $tid = $tid_map[$tid];
                $changed = true;
              }
              $new_ids[] = $tid;
            }
            // Deduplicate while preserving order
            $new_ids = array_values(array_unique($new_ids));
            $translation->set($field_name, array_map(fn($id) => ['target_id' => $id], $new_ids));
          }

          if ($changed) {
            $entity->save();
            $changed_here++;
            $total_entities_changed++;
          }
        }
      }
      print sprintf("Updated %s.%s field %s on %d entities\n", $entity_type_id, $bundle, $field_name, $changed_here);
    }
  }
}

// 3) Delete duplicate terms (keep canonicals)
$to_delete = array_keys($tid_map);
if ($to_delete) {
  $terms = $term_storage->loadMultiple($to_delete);
  if ($terms) {
    $term_storage->delete($terms);
    print "Deleted " . count($terms) . " duplicate terms.\n";
  }
}

// 4) Clear caches
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache.bootstrap')->invalidateAll();
\Drupal::service('cache.render')->invalidateAll();

\Drupal::messenger()->addStatus('Merged duplicate taxonomy terms and updated references. Entities changed: ' . $total_entities_changed);
print "Done. Entities changed: $total_entities_changed\n";

