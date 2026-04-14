<?php
/**
 * Fix remaining LOW audit items.
 *
 * Run: ddev drush scr scripts/fix_low_items.php
 * Then: ddev drush cex -y
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Vocabulary;

// ============================================================
// 1. Audit tech_stack, services, use_cases vocabularies
//    Remove if truly unused (no field references them anywhere)
// ============================================================
echo "=== 1. Audit potentially unused vocabularies ===\n";

$all_fields = \Drupal::entityTypeManager()->getStorage('field_config')->loadMultiple();

foreach (['tech_stack', 'use_cases'] as $vid) {
  $used = FALSE;
  foreach ($all_fields as $field) {
    $settings = $field->getSetting('handler_settings') ?? [];
    if (isset($settings['target_bundles'][$vid])) {
      $used = TRUE;
      echo "  $vid: USED by " . $field->id() . "\n";
      break;
    }
  }
  if (!$used) {
    $vocab = Vocabulary::load($vid);
    if ($vocab) {
      // Delete terms
      $tids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $vid)->execute();
      if ($tids) {
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($tids);
        \Drupal::entityTypeManager()->getStorage('taxonomy_term')->delete($terms);
        echo "  Deleted " . count($tids) . " terms from $vid\n";
      }
      $vocab->delete();
      echo "  Removed unused vocabulary: $vid\n";
    }
  }
}

// Check 'services' vocab separately — it's referenced by field_services on some types
$services_used = FALSE;
foreach ($all_fields as $field) {
  $settings = $field->getSetting('handler_settings') ?? [];
  if (isset($settings['target_bundles']['services'])) {
    $services_used = TRUE;
    echo "  services: USED by " . $field->id() . "\n";
    break;
  }
}
if (!$services_used) {
  $vocab = Vocabulary::load('services');
  if ($vocab) {
    $tids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', 'services')->execute();
    if ($tids) {
      $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($tids);
      \Drupal::entityTypeManager()->getStorage('taxonomy_term')->delete($terms);
      echo "  Deleted " . count($tids) . " terms from services\n";
    }
    $vocab->delete();
    echo "  Removed unused vocabulary: services\n";
  }
} else {
  echo "  services: keeping (in use)\n";
}

// ============================================================
// 2. Standardize image field names
//    article uses field_image, person uses field_photo
//    Both should also have field_hero_image for consistency
//    Strategy: Add field_hero_image to article and person where missing,
//    keep field_image and field_photo as they are (legacy, may have content)
// ============================================================
echo "\n=== 2. Standardize image fields ===\n";

// Article: has field_image but not field_hero_image — add hero_image
$f = FieldConfig::loadByName('node', 'article', 'field_hero_image');
if (!$f) {
  $storage = FieldStorageConfig::loadByName('node', 'field_hero_image');
  if ($storage) {
    FieldConfig::create([
      'field_name' => 'field_hero_image',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Hero Image',
      'required' => FALSE,
      'description' => 'Primary hero/banner image. Use this for page headers; field_image remains for inline/thumbnail use.',
      'settings' => [
        'handler' => 'default:media',
        'handler_settings' => ['target_bundles' => ['image' => 'image']],
      ],
    ])->save();
    echo "  Added field_hero_image to article (field_image kept for thumbnails)\n";
  }
} else {
  echo "  article already has field_hero_image\n";
}

// Person: has field_photo but not field_hero_image — person doesn't need a hero,
// but should have field_social_image (already added in previous commit).
// field_photo is the right name for a portrait/headshot. No change needed.
echo "  person: field_photo is correct for headshots, no hero needed\n";

// ============================================================
// 3. Rename capabilities vocab description for clarity
//    (to distinguish from 'capability' paragraph type)
// ============================================================
echo "\n=== 3. Clarify capabilities vocab vs capability paragraph ===\n";
$vocab = Vocabulary::load('capabilities');
if ($vocab) {
  $desc = $vocab->get('description');
  if (strpos($desc, 'paragraph') === FALSE) {
    $vocab->set('description', 'Taxonomy vocabulary for classifying content by organizational capability. Not to be confused with the "Capability" paragraph type, which is a structured content component used on Product/Service/Solution pages.');
    $vocab->save();
    echo "  Updated capabilities vocabulary description for clarity\n";
  } else {
    echo "  Already clarified\n";
  }
}

$para = \Drupal::entityTypeManager()->getStorage('paragraphs_type')->load('capability');
if ($para) {
  $desc = $para->get('description');
  if (strpos($desc, 'vocabulary') === FALSE) {
    $para->set('description', 'Individual capability item for the Key Capabilities section of Product, Service, and Solution pages. Not to be confused with the "Capabilities" taxonomy vocabulary used for classification.');
    $para->save();
    echo "  Updated capability paragraph type description for clarity\n";
  }
}

// ============================================================
// 4. Check for field_services — if storage exists but no instances, clean up
// ============================================================
echo "\n=== 4. Clean up orphaned field_services if unused ===\n";
$s = FieldStorageConfig::loadByName('node', 'field_services');
if ($s) {
  $instances = \Drupal::entityTypeManager()->getStorage('field_config')
    ->loadByProperties(['field_name' => 'field_services', 'entity_type' => 'node']);
  if (empty($instances)) {
    $s->delete();
    echo "  Deleted orphaned field_services storage\n";
  } else {
    $bundles = array_map(fn($i) => $i->getTargetBundle(), $instances);
    echo "  field_services still used on: " . implode(', ', $bundles) . "\n";
  }
} else {
  echo "  No field_services storage exists\n";
}

// ============================================================
// 5. Add field_read_time to solution (was added to product, missing from solution)
// ============================================================
echo "\n=== 5. Add field_read_time to solution ===\n";
$f = FieldConfig::loadByName('node', 'solution', 'field_read_time');
if (!$f) {
  $storage = FieldStorageConfig::loadByName('node', 'field_read_time');
  if ($storage) {
    FieldConfig::create([
      'field_name' => 'field_read_time',
      'entity_type' => 'node',
      'bundle' => 'solution',
      'label' => 'Read Time',
      'required' => FALSE,
    ])->save();
    echo "  Added field_read_time to solution\n";
  }
} else {
  echo "  Already exists\n";
}

echo "\n=== Low items fixed ===\n";
echo "Run: ddev drush cex -y\n";
