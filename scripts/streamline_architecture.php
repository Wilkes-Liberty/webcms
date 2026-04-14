<?php
/**
 * Streamline content architecture — consolidate fields, fix references, remove redundancy.
 *
 * Run: ddev drush scr scripts/streamline_architecture.php
 * Then: ddev drush cex -y
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\node\Entity\NodeType;

// ============================================================
// 1. Fix Product's persona reference (personas → persona)
// ============================================================
echo "=== Fix Product persona vocabulary reference ===\n";
$field = FieldConfig::loadByName('node', 'product', 'field_personas');
if ($field) {
  $settings = $field->getSetting('handler_settings');
  if (isset($settings['target_bundles']['personas'])) {
    unset($settings['target_bundles']['personas']);
    $settings['target_bundles']['persona'] = 'persona';
    $field->setSetting('handler_settings', $settings);
    $field->save();
    echo "  Fixed: field_personas now targets 'persona' vocabulary\n";
  } else {
    echo "  Already correct\n";
  }
} else {
  echo "  SKIP: field_personas not on product\n";
}

// ============================================================
// 2. Remove field_taxonomy from all content types
// ============================================================
echo "\n=== Remove field_taxonomy (kitchen-sink field) ===\n";
$bundles = ['article', 'basic_page', 'career', 'case_study', 'event', 'landing_page', 'resource', 'service'];
foreach ($bundles as $bundle) {
  $field = FieldConfig::loadByName('node', $bundle, 'field_taxonomy');
  if ($field) {
    $field->delete();
    echo "  Removed field_taxonomy from $bundle\n";
  }
}
// Delete the field storage if no instances remain
$storage = FieldStorageConfig::loadByName('node', 'field_taxonomy');
if ($storage) {
  // Check if any instances still exist
  $instances = \Drupal::entityTypeManager()
    ->getStorage('field_config')
    ->loadByProperties(['field_name' => 'field_taxonomy', 'entity_type' => 'node']);
  if (empty($instances)) {
    $storage->delete();
    echo "  Deleted field_taxonomy storage\n";
  } else {
    echo "  Storage kept — instances still exist on: " . implode(', ', array_map(fn($i) => $i->getTargetBundle(), $instances)) . "\n";
  }
}

// ============================================================
// 3. Remove field_industry (singular), keep field_industries (plural)
// ============================================================
echo "\n=== Consolidate field_industry → field_industries ===\n";
// First, ensure field_industries exists on types that only have field_industry
$types_needing_industries = [];
foreach (['article', 'basic_page', 'case_study', 'event', 'landing_page', 'resource'] as $bundle) {
  $has_singular = FieldConfig::loadByName('node', $bundle, 'field_industry');
  $has_plural = FieldConfig::loadByName('node', $bundle, 'field_industries');
  if ($has_singular && !$has_plural) {
    $types_needing_industries[] = $bundle;
  }
}

// Ensure field_industries storage exists
$industries_storage = FieldStorageConfig::loadByName('node', 'field_industries');
if (!$industries_storage) {
  echo "  ERROR: field_industries storage doesn't exist\n";
} else {
  // Add field_industries to types that only had field_industry
  foreach ($types_needing_industries as $bundle) {
    FieldConfig::create([
      'field_name' => 'field_industries',
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => 'Industries',
      'required' => FALSE,
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => ['industries' => 'industries'],
          'sort' => ['field' => '_none'],
          'auto_create' => FALSE,
        ],
      ],
    ])->save();
    echo "  Added field_industries to $bundle\n";

    // Add to form display
    $form = EntityFormDisplay::load("node.$bundle.default");
    if ($form) {
      $form->setComponent('field_industries', [
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => 21,
      ]);
      $form->save();
    }
  }
}

// Now remove field_industry from all types
foreach (['article', 'basic_page', 'case_study', 'event', 'landing_page', 'resource', 'person'] as $bundle) {
  $field = FieldConfig::loadByName('node', $bundle, 'field_industry');
  if ($field) {
    $field->delete();
    echo "  Removed field_industry from $bundle\n";
  }
}
$industry_storage = FieldStorageConfig::loadByName('node', 'field_industry');
if ($industry_storage) {
  $instances = \Drupal::entityTypeManager()
    ->getStorage('field_config')
    ->loadByProperties(['field_name' => 'field_industry', 'entity_type' => 'node']);
  if (empty($instances)) {
    $industry_storage->delete();
    echo "  Deleted field_industry storage\n";
  }
}

// ============================================================
// 4. Add missing fields to Product
// ============================================================
echo "\n=== Add missing fields to Product ===\n";
$product_additions = [
  ['field_name' => 'field_summary', 'label' => 'Summary', 'description' => 'Short deck text for listings and teasers.'],
  ['field_name' => 'field_preview_token', 'label' => 'Preview Token', 'description' => 'Token for Next.js draft preview.'],
  ['field_name' => 'field_industries', 'label' => 'Industries', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['industries' => 'industries']]]],
  ['field_name' => 'field_breadcrumb_label', 'label' => 'Breadcrumb Label'],
  ['field_name' => 'field_categories', 'label' => 'Categories', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['categories' => 'categories']]]],
  ['field_name' => 'field_campaign', 'label' => 'Campaign'],
  ['field_name' => 'field_ab_variant', 'label' => 'A/B Variant'],
  ['field_name' => 'field_primary_capability', 'label' => 'Primary Capability', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['capabilities' => 'capabilities']]]],
  ['field_name' => 'field_target_sectors', 'label' => 'Target Sectors', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['target_sectors' => 'target_sectors']]]],
];

// Need to create field_target_sectors storage first
$ts_storage = FieldStorageConfig::loadByName('node', 'field_target_sectors');
if (!$ts_storage) {
  FieldStorageConfig::create([
    'field_name' => 'field_target_sectors',
    'entity_type' => 'node',
    'type' => 'entity_reference',
    'cardinality' => -1,
    'settings' => ['target_type' => 'taxonomy_term'],
    'translatable' => TRUE,
  ])->save();
  echo "  Created field_target_sectors storage\n";
}

foreach ($product_additions as $f) {
  $storage = FieldStorageConfig::loadByName('node', $f['field_name']);
  if (!$storage) {
    echo "  SKIP: No storage for {$f['field_name']}\n";
    continue;
  }
  $existing = FieldConfig::loadByName('node', 'product', $f['field_name']);
  if ($existing) {
    echo "  SKIP: {$f['field_name']} already on product\n";
    continue;
  }
  $config = [
    'field_name' => $f['field_name'],
    'entity_type' => 'node',
    'bundle' => 'product',
    'label' => $f['label'],
    'required' => FALSE,
  ];
  if (isset($f['description'])) $config['description'] = $f['description'];
  if (isset($f['settings'])) $config['settings'] = $f['settings'];
  FieldConfig::create($config)->save();
  echo "  Added {$f['field_name']} to product\n";
}

// ============================================================
// 5. Add missing fields to Service
// ============================================================
echo "\n=== Add missing fields to Service ===\n";
$service_additions = [
  ['field_name' => 'field_compliance', 'label' => 'Compliance Frameworks', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['compliance' => 'compliance']]]],
  ['field_name' => 'field_personas', 'label' => 'Personas', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['persona' => 'persona']]]],
  ['field_name' => 'field_target_sectors', 'label' => 'Target Sectors', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['target_sectors' => 'target_sectors']]]],
];

foreach ($service_additions as $f) {
  $existing = FieldConfig::loadByName('node', 'service', $f['field_name']);
  if ($existing) {
    echo "  SKIP: {$f['field_name']} already on service\n";
    continue;
  }
  $config = [
    'field_name' => $f['field_name'],
    'entity_type' => 'node',
    'bundle' => 'service',
    'label' => $f['label'],
    'required' => FALSE,
  ];
  if (isset($f['settings'])) $config['settings'] = $f['settings'];
  FieldConfig::create($config)->save();
  echo "  Added {$f['field_name']} to service\n";
}

// Also add target_sectors to case_study and resource
foreach (['case_study', 'resource'] as $bundle) {
  $existing = FieldConfig::loadByName('node', $bundle, 'field_target_sectors');
  if (!$existing) {
    FieldConfig::create([
      'field_name' => 'field_target_sectors',
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => 'Target Sectors',
      'required' => FALSE,
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => ['target_bundles' => ['target_sectors' => 'target_sectors']],
      ],
    ])->save();
    echo "  Added field_target_sectors to $bundle\n";
  }
}

// ============================================================
// 6. Remove duplicate paragraph types
// ============================================================
echo "\n=== Remove duplicate paragraph types ===\n";

// Remove p_hero_image (duplicate of p_hero)
$p_hero_image = \Drupal::entityTypeManager()->getStorage('paragraphs_type')->load('p_hero_image');
if ($p_hero_image) {
  // Delete field instances first
  $fields = \Drupal::entityTypeManager()->getStorage('field_config')
    ->loadByProperties(['entity_type' => 'paragraph', 'bundle' => 'p_hero_image']);
  foreach ($fields as $field) {
    $field->delete();
  }
  $p_hero_image->delete();
  echo "  Removed p_hero_image (duplicate of p_hero)\n";
}

// Remove p_faqs (duplicate of p_faq_group — add field_title to p_faq_group instead)
$p_faqs = \Drupal::entityTypeManager()->getStorage('paragraphs_type')->load('p_faqs');
if ($p_faqs) {
  // First, add field_title to p_faq_group if missing
  $faq_group_title = FieldConfig::loadByName('paragraph', 'p_faq_group', 'field_title');
  if (!$faq_group_title) {
    $title_storage = FieldStorageConfig::loadByName('paragraph', 'field_title');
    if ($title_storage) {
      FieldConfig::create([
        'field_name' => 'field_title',
        'entity_type' => 'paragraph',
        'bundle' => 'p_faq_group',
        'label' => 'Section Title',
        'required' => FALSE,
      ])->save();
      echo "  Added field_title to p_faq_group\n";
    }
  }

  // Delete p_faqs field instances and type
  $fields = \Drupal::entityTypeManager()->getStorage('field_config')
    ->loadByProperties(['entity_type' => 'paragraph', 'bundle' => 'p_faqs']);
  foreach ($fields as $field) {
    $field->delete();
  }
  $p_faqs->delete();
  echo "  Removed p_faqs (consolidated into p_faq_group with title)\n";
}

// Update landing_page field_components to remove deleted types
$lp_components = FieldConfig::loadByName('node', 'landing_page', 'field_components');
if ($lp_components) {
  $settings = $lp_components->getSetting('handler_settings');
  $removed = [];
  foreach (['p_hero_image', 'p_faqs'] as $dead) {
    if (isset($settings['target_bundles'][$dead])) {
      unset($settings['target_bundles'][$dead]);
      $removed[] = $dead;
    }
    if (isset($settings['target_bundles_drag_drop'][$dead])) {
      unset($settings['target_bundles_drag_drop'][$dead]);
    }
  }
  if ($removed) {
    $lp_components->setSetting('handler_settings', $settings);
    $lp_components->save();
    echo "  Removed " . implode(', ', $removed) . " from landing_page field_components\n";
  }
}

// ============================================================
// 7. Create Product form display with tab groups
// ============================================================
echo "\n=== Create Product form display with tab groups ===\n";

$form_display = EntityFormDisplay::load('node.product.default');
if (!$form_display) {
  $form_display = EntityFormDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'product',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}

// Set field weights and widgets
$fields_config = [
  'body' => ['type' => 'text_textarea_with_summary', 'weight' => 1],
  'field_summary' => ['type' => 'string_textfield', 'weight' => 2],
  'field_mission_impact' => ['type' => 'text_textarea', 'weight' => 3],
  'field_defense_relevance' => ['type' => 'text_textarea', 'weight' => 4],
  'field_key_capabilities' => ['type' => 'entity_reference_paragraphs', 'weight' => 5],
  'field_deployment_options' => ['type' => 'string_textfield', 'weight' => 6],
  'field_sovereignty_features' => ['type' => 'text_textarea', 'weight' => 7],
  'field_hero_image' => ['type' => 'entity_reference_autocomplete', 'weight' => 10],
  'field_social_image' => ['type' => 'entity_reference_autocomplete', 'weight' => 11],
  'field_primary_cta' => ['type' => 'link_default', 'weight' => 15],
  'field_secondary_cta' => ['type' => 'link_default', 'weight' => 16],
  'field_seo_title' => ['type' => 'string_textfield', 'weight' => 20],
  'field_meta_description' => ['type' => 'string_textfield', 'weight' => 21],
  'field_canonical' => ['type' => 'string_textfield', 'weight' => 22],
  'field_breadcrumb_label' => ['type' => 'string_textfield', 'weight' => 23],
  'field_noindex' => ['type' => 'boolean_checkbox', 'weight' => 24],
  'field_show_toc' => ['type' => 'boolean_checkbox', 'weight' => 25],
  'field_solutions' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 30],
  'field_technologies' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 31],
  'field_personas' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 32],
  'field_compliance' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 33],
  'field_industries' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 34],
  'field_categories' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 35],
  'field_primary_capability' => ['type' => 'entity_reference_autocomplete', 'weight' => 36],
  'field_target_sectors' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 37],
  'field_template' => ['type' => 'string_textfield', 'weight' => 40],
  'field_visibility' => ['type' => 'string_textfield', 'weight' => 41],
  'field_parent' => ['type' => 'entity_reference_autocomplete', 'weight' => 42],
  'field_related' => ['type' => 'entity_reference_autocomplete', 'weight' => 43],
  'field_cache_tags' => ['type' => 'string_textfield', 'weight' => 50],
  'field_revalidate_ttl' => ['type' => 'number', 'weight' => 51],
  'field_preview_token' => ['type' => 'string_textfield', 'weight' => 52],
  'field_campaign' => ['type' => 'string_textfield', 'weight' => 53],
  'field_ab_variant' => ['type' => 'string_textfield', 'weight' => 54],
  'field_reviewed_on' => ['type' => 'datetime_default', 'weight' => 55],
];

foreach ($fields_config as $field_name => $config) {
  $form_display->setComponent($field_name, [
    'type' => $config['type'],
    'weight' => $config['weight'],
  ]);
}

// Add field groups (tabs) via third_party_settings
$form_display->setThirdPartySetting('field_group', 'group_tabs', [
  'children' => ['group_content', 'group_mission', 'group_capabilities_tab', 'group_media', 'group_ctas', 'group_seo', 'group_classification', 'group_layout', 'group_technical', 'group_editorial'],
  'label' => 'Tabs',
  'region' => 'content',
  'parent_name' => '',
  'weight' => 0,
  'format_type' => 'tabs',
  'format_settings' => ['direction' => 'horizontal', 'width_breakpoint' => 640],
]);

$tabs = [
  'group_content' => ['label' => 'Content', 'weight' => 0, 'children' => ['body', 'field_summary', 'field_mission_impact', 'field_defense_relevance']],
  'group_capabilities_tab' => ['label' => 'Capabilities', 'weight' => 1, 'children' => ['field_key_capabilities']],
  'group_mission' => ['label' => 'Deployment', 'weight' => 2, 'children' => ['field_deployment_options', 'field_sovereignty_features']],
  'group_media' => ['label' => 'Media', 'weight' => 3, 'children' => ['field_hero_image', 'field_social_image']],
  'group_ctas' => ['label' => 'CTAs', 'weight' => 4, 'children' => ['field_primary_cta', 'field_secondary_cta']],
  'group_seo' => ['label' => 'SEO', 'weight' => 5, 'children' => ['field_seo_title', 'field_meta_description', 'field_canonical', 'field_breadcrumb_label', 'field_noindex', 'field_show_toc']],
  'group_classification' => ['label' => 'Classification', 'weight' => 6, 'children' => ['field_solutions', 'field_technologies', 'field_personas', 'field_compliance', 'field_industries', 'field_categories', 'field_primary_capability', 'field_target_sectors']],
  'group_layout' => ['label' => 'Layout', 'weight' => 7, 'children' => ['field_template', 'field_visibility', 'field_parent', 'field_related']],
  'group_technical' => ['label' => 'Technical', 'weight' => 8, 'children' => ['field_cache_tags', 'field_revalidate_ttl', 'field_preview_token']],
  'group_editorial' => ['label' => 'Editorial', 'weight' => 9, 'children' => ['field_campaign', 'field_ab_variant', 'field_reviewed_on']],
];

foreach ($tabs as $group_name => $tab) {
  $form_display->setThirdPartySetting('field_group', $group_name, [
    'children' => $tab['children'],
    'label' => $tab['label'],
    'region' => 'content',
    'parent_name' => 'group_tabs',
    'weight' => $tab['weight'],
    'format_type' => 'tab',
    'format_settings' => ['classes' => '', 'show_empty_fields' => FALSE, 'id' => '', 'formatter' => 'closed', 'description' => '', 'required_fields' => TRUE],
  ]);
}

$form_display->save();
echo "  Created Product form display with " . count($tabs) . " tab groups\n";

// Create view display
$view_display = EntityViewDisplay::load('node.product.default');
if (!$view_display) {
  $view_display = EntityViewDisplay::create([
    'targetEntityType' => 'node',
    'bundle' => 'product',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}
foreach ($fields_config as $field_name => $config) {
  $view_display->setComponent($field_name, ['weight' => $config['weight']]);
}
$view_display->save();
echo "  Created Product view display\n";

echo "\n=== Architecture streamlining complete ===\n";
echo "Run: ddev drush cex -y\n";
