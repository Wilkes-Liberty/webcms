<?php
/**
 * Final architecture optimization — fix audit findings.
 *
 * Run: ddev drush scr scripts/final_optimization.php
 * Then: ddev drush cex -y
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

// ============================================================
// 1. Remove field_news_category from article (duplicates field_categories)
// ============================================================
echo "=== 1. Remove duplicate field_news_category from article ===\n";
$f = FieldConfig::loadByName('node', 'article', 'field_news_category');
if ($f) {
  $f->delete();
  echo "  Removed field_news_category from article\n";
}
$s = FieldStorageConfig::loadByName('node', 'field_news_category');
if ($s) {
  $instances = \Drupal::entityTypeManager()->getStorage('field_config')
    ->loadByProperties(['field_name' => 'field_news_category', 'entity_type' => 'node']);
  if (empty($instances)) {
    $s->delete();
    echo "  Deleted field_news_category storage\n";
  }
}

// ============================================================
// 2. Resolve capabilities vs capability vocab collision
//    Strategy: field_key_capabilities references 'capability' paragraph type (NOT a vocab)
//    field_capabilities and field_primary_capability both reference 'capabilities' vocab
//    So there's no actual vocab collision — 'capability' is a PARAGRAPH TYPE, not a vocab.
//    The confusion is in naming. Let's verify and document.
// ============================================================
echo "\n=== 2. Verify capabilities/capability distinction ===\n";
$cap_vocab = Vocabulary::load('capabilities');
$cap_para = \Drupal::entityTypeManager()->getStorage('paragraphs_type')->load('capability');
echo "  'capabilities' vocabulary: " . ($cap_vocab ? "EXISTS" : "MISSING") . "\n";
echo "  'capability' paragraph type: " . ($cap_para ? "EXISTS" : "MISSING") . "\n";

// Check what field_capabilities actually targets
$fc = FieldConfig::loadByName('node', 'service', 'field_capabilities');
if ($fc) {
  $settings = $fc->getSetting('handler_settings');
  $targets = array_keys($settings['target_bundles'] ?? []);
  echo "  field_capabilities on service -> targets: " . implode(', ', $targets) . " (taxonomy)\n";
}
// Check field_key_capabilities
$fkc = FieldConfig::loadByName('node', 'service', 'field_key_capabilities');
if ($fkc) {
  $settings = $fkc->getSetting('handler_settings');
  $targets = array_keys($settings['target_bundles'] ?? []);
  echo "  field_key_capabilities on service -> targets: " . implode(', ', $targets) . " (paragraph)\n";
}
echo "  RESULT: No collision — 'capabilities' is taxonomy, 'capability' is paragraph type. Names are confusing but structurally correct.\n";

// ============================================================
// 3. Add missing standard fields to Solution
// ============================================================
echo "\n=== 3. Add missing fields to Solution ===\n";
$solution_additions = [
  'field_ab_variant' => 'A/B Variant',
  'field_campaign' => 'Campaign',
  'field_reviewed_on' => 'Content Review Date',
  'field_show_toc' => 'Show Table of Contents',
  'field_parent' => 'Parent Content',
  'field_categories' => 'Categories',
  'field_primary_capability' => 'Primary Capability',
];

foreach ($solution_additions as $field_name => $label) {
  $storage = FieldStorageConfig::loadByName('node', $field_name);
  if (!$storage) {
    echo "  SKIP: No storage for $field_name\n";
    continue;
  }
  $existing = FieldConfig::loadByName('node', 'solution', $field_name);
  if ($existing) {
    echo "  SKIP: $field_name already on solution\n";
    continue;
  }
  $config = [
    'field_name' => $field_name,
    'entity_type' => 'node',
    'bundle' => 'solution',
    'label' => $label,
    'required' => FALSE,
  ];
  // Set handler settings for taxonomy/entity reference fields
  if ($field_name === 'field_categories') {
    $config['settings'] = ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['categories' => 'categories']]];
  }
  if ($field_name === 'field_primary_capability') {
    $config['settings'] = ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['capabilities' => 'capabilities']]];
  }
  FieldConfig::create($config)->save();
  echo "  Added $field_name to solution\n";
}

// Update solution form display
$form = EntityFormDisplay::load('node.solution.default');
if ($form) {
  $form->setComponent('field_ab_variant', ['type' => 'string_textfield', 'weight' => 53]);
  $form->setComponent('field_campaign', ['type' => 'string_textfield', 'weight' => 52]);
  $form->setComponent('field_reviewed_on', ['type' => 'datetime_default', 'weight' => 54]);
  $form->setComponent('field_show_toc', ['type' => 'boolean_checkbox', 'weight' => 25]);
  $form->setComponent('field_parent', ['type' => 'entity_reference_autocomplete', 'weight' => 42]);
  $form->setComponent('field_categories', ['type' => 'entity_reference_autocomplete_tags', 'weight' => 36]);
  $form->setComponent('field_primary_capability', ['type' => 'entity_reference_autocomplete', 'weight' => 37]);

  // Update tab groups to include new fields
  $editorial = $form->getThirdPartySetting('field_group', 'group_technical');
  // Add editorial tab if not exists
  $existing_tabs = $form->getThirdPartySetting('field_group', 'group_tabs');
  if ($existing_tabs && !in_array('group_editorial', $existing_tabs['children'])) {
    $existing_tabs['children'][] = 'group_editorial';
    $form->setThirdPartySetting('field_group', 'group_tabs', $existing_tabs);
    $form->setThirdPartySetting('field_group', 'group_editorial', [
      'children' => ['field_campaign', 'field_ab_variant', 'field_reviewed_on'],
      'label' => 'Editorial', 'region' => 'content', 'parent_name' => 'group_tabs', 'weight' => 9,
      'format_type' => 'tab', 'format_settings' => ['classes' => '', 'show_empty_fields' => FALSE, 'id' => '', 'formatter' => 'closed', 'description' => '', 'required_fields' => TRUE],
    ]);
  }

  // Add new fields to classification tab
  $classification = $form->getThirdPartySetting('field_group', 'group_classification');
  if ($classification) {
    if (!in_array('field_categories', $classification['children'])) {
      $classification['children'][] = 'field_categories';
    }
    if (!in_array('field_primary_capability', $classification['children'])) {
      $classification['children'][] = 'field_primary_capability';
    }
    $form->setThirdPartySetting('field_group', 'group_classification', $classification);
  }

  // Add field_show_toc to SEO tab
  $seo = $form->getThirdPartySetting('field_group', 'group_seo');
  if ($seo && !in_array('field_show_toc', $seo['children'])) {
    $seo['children'][] = 'field_show_toc';
    $form->setThirdPartySetting('field_group', 'group_seo', $seo);
  }

  // Add field_parent to layout tab
  $layout = $form->getThirdPartySetting('field_group', 'group_layout');
  if ($layout && !in_array('field_parent', $layout['children'])) {
    $layout['children'][] = 'field_parent';
    $form->setThirdPartySetting('field_group', 'group_layout', $layout);
  }

  $form->save();
  echo "  Updated solution form display with new fields and tabs\n";
}

// ============================================================
// 4. Add minimal SEO/headless fields to Person
// ============================================================
echo "\n=== 4. Add essential fields to Person ===\n";
$person_additions = [
  'field_seo_title' => 'SEO Title',
  'field_meta_description' => 'Meta Description',
  'field_social_image' => 'Social Share Image',
  'field_canonical' => 'Canonical URL',
  'field_noindex' => 'Robots noindex',
  'field_summary' => 'Summary',
  'field_preview_token' => 'Preview Token',
  'field_cache_tags' => 'CDN Cache Tags',
  'field_revalidate_ttl' => 'Revalidate After',
  'field_visibility' => 'Visibility',
];

foreach ($person_additions as $field_name => $label) {
  $storage = FieldStorageConfig::loadByName('node', $field_name);
  if (!$storage) {
    echo "  SKIP: No storage for $field_name\n";
    continue;
  }
  $existing = FieldConfig::loadByName('node', 'person', $field_name);
  if ($existing) {
    echo "  SKIP: $field_name already on person\n";
    continue;
  }
  $config = [
    'field_name' => $field_name,
    'entity_type' => 'node',
    'bundle' => 'person',
    'label' => $label,
    'required' => FALSE,
  ];
  if ($field_name === 'field_social_image') {
    $config['settings'] = ['handler' => 'default:media', 'handler_settings' => ['target_bundles' => ['image' => 'image']]];
  }
  FieldConfig::create($config)->save();
  echo "  Added $field_name to person\n";
}

// Update person form display
$form = EntityFormDisplay::load('node.person.default');
if ($form) {
  $form->setComponent('field_seo_title', ['type' => 'string_textfield', 'weight' => 20]);
  $form->setComponent('field_meta_description', ['type' => 'string_textfield', 'weight' => 21]);
  $form->setComponent('field_social_image', ['type' => 'entity_reference_autocomplete', 'weight' => 22]);
  $form->setComponent('field_canonical', ['type' => 'string_textfield', 'weight' => 23]);
  $form->setComponent('field_noindex', ['type' => 'boolean_checkbox', 'weight' => 24]);
  $form->setComponent('field_summary', ['type' => 'string_textfield', 'weight' => 2]);
  $form->setComponent('field_preview_token', ['type' => 'string_textfield', 'weight' => 30]);
  $form->setComponent('field_cache_tags', ['type' => 'string_textfield', 'weight' => 31]);
  $form->setComponent('field_revalidate_ttl', ['type' => 'number', 'weight' => 32]);
  $form->setComponent('field_visibility', ['type' => 'string_textfield', 'weight' => 33]);
  $form->save();
  echo "  Updated person form display\n";
}

// ============================================================
// 5. Remove unused vocabularies
// ============================================================
echo "\n=== 5. Remove unused vocabularies ===\n";
foreach (['languages', 'tones'] as $vid) {
  $vocab = Vocabulary::load($vid);
  if ($vocab) {
    // Verify truly unused
    $fields = \Drupal::entityTypeManager()->getStorage('field_config')
      ->loadByProperties(['entity_type' => 'node']);
    $used = FALSE;
    foreach ($fields as $field) {
      $settings = $field->getSetting('handler_settings');
      if (isset($settings['target_bundles'][$vid])) {
        $used = TRUE;
        break;
      }
    }
    if (!$used) {
      // Delete any terms first
      $tids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $vid)->execute();
      if ($tids) {
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple($tids);
        \Drupal::entityTypeManager()->getStorage('taxonomy_term')->delete($terms);
        echo "  Deleted " . count($tids) . " terms from $vid\n";
      }
      $vocab->delete();
      echo "  Removed unused vocabulary: $vid\n";
    } else {
      echo "  KEEP: $vid is referenced by a field\n";
    }
  }
}

// ============================================================
// 6. Add field_read_time to product (consistency)
// ============================================================
echo "\n=== 6. Add field_read_time to product ===\n";
$f = FieldConfig::loadByName('node', 'product', 'field_read_time');
if (!$f) {
  $storage = FieldStorageConfig::loadByName('node', 'field_read_time');
  if ($storage) {
    FieldConfig::create([
      'field_name' => 'field_read_time',
      'entity_type' => 'node',
      'bundle' => 'product',
      'label' => 'Read Time',
      'required' => FALSE,
    ])->save();
    echo "  Added field_read_time to product\n";
  }
}

echo "\n=== Final optimization complete ===\n";
echo "Run: ddev drush cex -y\n";
