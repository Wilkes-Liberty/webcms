<?php
/**
 * Create dedicated taxonomy fields per vocabulary and attach to content types.
 *
 * How to run:
 *   drush scr scripts/add_dedicated_taxonomy_fields.php
 *
 * Prerequisite:
 *   Run scripts/taxonomy_setup.php first to ensure vocabularies exist.
 *
 * What this does:
 * - Defines per-vocabulary field storages (entity reference to taxonomy_term)
 * - Attaches fields to selected node bundles with target_bundles restricted to that vocabulary
 * - Updates default form and view displays
 * - Idempotent: safe to run repeatedly
 *
 * Adjust mapping below ($vocab_to_field and $bundle_fields) to change labels/attachments.
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Entity\Display\EntityFormDisplay;
use Drupal\Core\Entity\Display\EntityViewDisplay;

/**
 * Map vocabularies to field definitions (field name + label).
 */
function wl_get_vocab_to_field_map(): array {
  return [
    'sections' => ['field_name' => 'field_sections',     'label' => 'Sections'],
    'technologies' => ['field_name' => 'field_technologies', 'label' => 'Technologies'],
    'solutions' => ['field_name' => 'field_solutions',   'label' => 'Solutions'],
    'services' => ['field_name' => 'field_services',     'label' => 'Services'],
    'industries' => ['field_name' => 'field_industries', 'label' => 'Industries'],
    'capabilities' => ['field_name' => 'field_capabilities', 'label' => 'Capabilities'],
    'categories' => ['field_name' => 'field_categories', 'label' => 'Categories'],
    // 'tags' is intentionally not auto-created here, as many sites already have field_tags.
  ];
}

/**
 * Map bundles to vocabularies they should reference.
 * Adjust as needed for your editorial model.
 */
function wl_get_bundle_field_map(): array {
  return [
    'article' => ['categories'], // keep existing field_tags as-is
    'basic_page' => ['sections', 'categories'],
    'landing_page' => ['sections', 'categories'],
    'service' => ['capabilities', 'technologies', 'industries', 'categories'],
    'case_study' => ['industries', 'technologies', 'solutions', 'categories'],
    'resource' => ['categories', 'technologies'],
    'event' => ['industries', 'technologies', 'categories'],
    'career' => ['industries', 'categories'],
  ];
}

/**
 * Ensure field storage exists for a field name.
 */
function wl_ensure_field_storage(string $field_name): FieldStorageConfig {
  $storage = FieldStorageConfig::loadByName('node', $field_name);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'taxonomy_term',
      ],
      'cardinality' => -1, // unlimited
      'translatable' => TRUE,
    ]);
    $storage->save();
    print "[+] Created field storage: {$field_name}\n";
  }
  else {
    print "[=] Field storage exists: {$field_name}\n";
  }
  return $storage;
}

/**
 * Ensure a field config exists on the given bundle, restricted to one vocabulary.
 */
function wl_ensure_field_on_bundle(string $bundle, string $vid, string $field_name, string $label): ?FieldConfig {
  // Verify bundle exists.
  if (!NodeType::load($bundle)) {
    print "[!] Skip bundle '{$bundle}' — content type does not exist\n";
    return NULL;
  }

  // Verify vocabulary exists.
  $vocab = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load($vid);
  if (!$vocab) {
    print "[!] Skip field {$field_name} on {$bundle} — vocabulary '{$vid}' does not exist\n";
    return NULL;
  }

  // Ensure field storage exists.
  wl_ensure_field_storage($field_name);

  // Attempt to load existing field config.
  $field = FieldConfig::loadByName('node', $bundle, $field_name);
  if (!$field) {
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => $label,
      'required' => FALSE,
      'translatable' => TRUE,
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [ $vid => $vid ],
          'sort' => [ 'field' => '_none' ],
          'auto_create' => FALSE,
        ],
      ],
    ]);
    $field->save();
    print "[+] Created field {$field_name} on {$bundle} (vocab: {$vid})\n";
  }
  else {
    // Ensure label and handler settings are correct and restricted to the single vocabulary.
    $changed = FALSE;
    if ($field->label() !== $label) {
      $field->setLabel($label);
      $changed = TRUE;
    }
    $settings = $field->get('settings') ?: [];
    if (($settings['handler'] ?? 'default:taxonomy_term') !== 'default:taxonomy_term') {
      $settings['handler'] = 'default:taxonomy_term';
      $changed = TRUE;
    }
    $hs = $settings['handler_settings'] ?? [];
    $targets = $hs['target_bundles'] ?? [];
    $desired = [ $vid => $vid ];
    if ($targets !== $desired) {
      $hs['target_bundles'] = $desired;
      $hs['sort']['field'] = '_none';
      $hs['auto_create'] = FALSE;
      $settings['handler_settings'] = $hs;
      $field->set('settings', $settings);
      $changed = TRUE;
    }
    if ($changed) {
      $field->save();
      print "[~] Updated field {$field_name} on {$bundle} to target '{$vid}'\n";
    }
    else {
      print "[=] Field {$field_name} on {$bundle} already targets '{$vid}'\n";
    }
  }

  // Update default form & view displays.
  wl_ensure_displays($bundle, $field_name);
  return $field;
}

/**
 * Ensure form and view displays include this field with sensible defaults.
 */
function wl_ensure_displays(string $bundle, string $field_name): void {
  // Form display.
  $form_display = EntityFormDisplay::load("node.{$bundle}.default");
  if (!$form_display) {
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
  }
  $components = $form_display->getComponents();
  if (!isset($components[$field_name])) {
    $form_display->setComponent($field_name, [
      'type' => 'entity_reference_autocomplete_tags',
      'weight' => 20,
      'settings' => [],
    ]);
    $form_display->save();
    print "[~] Updated form display for {$bundle}: added {$field_name}\n";
  }

  // View display.
  $view_display = EntityViewDisplay::load("node.{$bundle}.default");
  if (!$view_display) {
    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
  }
  $vcomponents = $view_display->getComponents();
  if (!isset($vcomponents[$field_name])) {
    $view_display->setComponent($field_name, [
      'type' => 'entity_reference_label',
      'weight' => 20,
      'settings' => [ 'link' => FALSE ],
    ]);
    $view_display->save();
    print "[~] Updated view display for {$bundle}: added {$field_name}\n";
  }
}

function wl_add_dedicated_taxonomy_fields(): void {
  $vocab_to_field = wl_get_vocab_to_field_map();
  $bundle_fields = wl_get_bundle_field_map();

  // Only proceed for vocabularies that exist.
  $existing_vocabs = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple();
  $existing_vids = array_keys($existing_vocabs);

  foreach ($bundle_fields as $bundle => $vids) {
    foreach ($vids as $vid) {
      if (!in_array($vid, $existing_vids, true)) {
        print "[!] Skip {$bundle} -> {$vid} (vocabulary missing)\n";
        continue;
      }
      if (!isset($vocab_to_field[$vid])) {
        print "[!] Skip {$bundle} -> {$vid} (no field mapping defined)\n";
        continue;
      }
      $field_name = $vocab_to_field[$vid]['field_name'];
      $label = $vocab_to_field[$vid]['label'];
      wl_ensure_field_on_bundle($bundle, $vid, $field_name, $label);
    }
  }

  print "\nDone creating dedicated taxonomy fields.\n";
}

wl_add_dedicated_taxonomy_fields();

