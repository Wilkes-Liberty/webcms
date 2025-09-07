<?php
/**
 * Extend existing taxonomy reference fields to include new vocabularies.
 *
 * How to run:
 *   drush scr scripts/extend_taxonomy_fields.php
 *
 * What this does:
 * - Scans node bundle fields named `field_taxonomy` (generic taxonomy entity reference)
 * - If the field is unrestricted (no target_bundles), no change is needed — it already accepts all vocabularies
 * - If the field is restricted (has target_bundles), it adds the new vocabularies created from the Hugo IA:
 *   sections, technologies, solutions, services, industries, capabilities, categories (and keeps tags if present)
 * - Idempotent: re-running will not duplicate entries
 *
 * After running, export configuration if you keep config in git:
 *   drush cex -y
 */

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Return FieldConfig entities for all node bundles that use a given field machine name.
 *
 * @param string $field_name
 *   The machine name of the field (e.g., 'field_taxonomy').
 *
 * @return \Drupal\field\Entity\FieldConfig[]
 */
function wl_get_node_field_configs(string $field_name): array {
  $storage = \Drupal::entityTypeManager()->getStorage('field_config');
  $configs = $storage->loadByProperties([
    'entity_type' => 'node',
    'field_name' => $field_name,
  ]);
  return $configs ?: [];
}

/**
 * Extend target_bundles for a FieldConfig referencing taxonomy terms.
 * - If target_bundles is empty or not set, we leave it unchanged (unrestricted already accepts all vocabs)
 * - If restricted, merge with desired vocabulary IDs and save
 */
function wl_extend_taxonomy_field_bundles(\Drupal\field\Entity\FieldConfig $field_config, array $desired_vids): void {
  $settings = $field_config->get('settings') ?: [];
  $handler = $settings['handler'] ?? 'default:taxonomy_term';
  if ($handler !== 'default:taxonomy_term') {
    print "[=] Skip {$field_config->id()} — not a taxonomy_term handler\n";
    return;
  }

  // Only include desired vocabularies that actually exist.
  $vocab_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary');
  $existing_vocabs = $vocab_storage->loadMultiple();
  $existing_vids = array_keys($existing_vocabs);
  $present = array_values(array_intersect($desired_vids, $existing_vids));

  $handler_settings = $settings['handler_settings'] ?? [];
  $target_bundles = $handler_settings['target_bundles'] ?? [];

  // If target_bundles is empty (or not set), field is unrestricted and already accepts all vocabularies.
  if (empty($target_bundles)) {
    print "[=] {$field_config->id()} is unrestricted — already accepts all vocabularies (no change)\n";
    return;
  }

  // Merge desired vids into existing target bundles.
  $new_target_bundles = $target_bundles;
  foreach ($present as $vid) {
    $new_target_bundles[$vid] = $vid;
  }

  // Only save if changed.
  if ($new_target_bundles !== $target_bundles) {
    $handler_settings['target_bundles'] = $new_target_bundles;
    $settings['handler_settings'] = $handler_settings;
    $field_config->set('settings', $settings);
    $field_config->save();
    print "[~] Extended {$field_config->id()} target_bundles to include: " . implode(', ', $present) . "\n";
  }
  else {
    print "[=] {$field_config->id()} already includes desired vocabularies (no change)\n";
  }
}

function wl_extend_all(): void {
  // Desired vocabularies from the Hugo IA plus existing tags.
  $desired_vids = [
    'tags',
    'sections',
    'technologies',
    'solutions',
    'services',
    'industries',
    'capabilities',
    'categories',
  ];

  // Operate on all node field configs named 'field_taxonomy'.
  $fields = wl_get_node_field_configs('field_taxonomy');
  if (!$fields) {
    print "[!] No node fields named 'field_taxonomy' were found. Nothing to extend.\n";
    return;
  }

  foreach ($fields as $fc) {
    wl_extend_taxonomy_field_bundles($fc, $desired_vids);
  }

  print "\nDone extending taxonomy reference fields.\n";
}

wl_extend_all();

