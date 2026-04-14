<?php
/**
 * Fix graphql_compose.settings — fields belong under top-level `field_config:`,
 * not nested under entity_config.
 *
 * Also clean out the bogus `fields:` key that setup_homepage.php incorrectly
 * placed inside entity_config entries.
 */

use Drupal\field\Entity\FieldConfig;

$config = \Drupal::configFactory()->getEditable('graphql_compose.settings');

// 1. Strip bogus `fields:` keys from entity_config (we put them in the wrong place)
$entity_config = $config->get('entity_config') ?: [];
foreach (['node', 'paragraph'] as $type) {
  if (!isset($entity_config[$type])) continue;
  foreach ($entity_config[$type] as $bundle => $cfg) {
    if (isset($cfg['fields'])) {
      unset($entity_config[$type][$bundle]['fields']);
    }
  }
}
$config->set('entity_config', $entity_config);

// 2. Properly enable fields under field_config
$field_config = $config->get('field_config') ?: [];

$node_fields = [
  'landing_page' => [
    'body', 'field_components', 'field_primary_cta', 'field_secondary_cta',
    'field_seo_title', 'field_meta_description', 'field_summary',
    'field_hero_image', 'field_social_image', 'field_canonical', 'field_noindex',
  ],
];

$paragraph_fields = [
  'p_hero' => ['field_title', 'field_subtitle', 'field_media', 'field_cta_links'],
  'p_text_block' => ['field_title', 'field_body'],
  'p_cta_banner' => ['field_title', 'field_body', 'field_cta_links'],
  'p_notice' => ['field_title', 'field_notice_tone'],
  'p_feature' => ['field_title', 'field_body', 'field_icon'],
  'p_stat' => ['field_title', 'field_value', 'field_suffix'],
  'p_testimonial' => ['field_title', 'field_quote', 'field_attribution', 'field_media'],
  'p_text_image' => ['field_title', 'field_body', 'field_media'],
  'p_image_gallery' => ['field_title', 'field_gallery_images'],
  'p_logo_wall' => ['field_title', 'field_logos'],
  'p_faq_group' => ['field_title', 'field_items'],
  'p_faq_item' => ['field_title', 'field_body'],
  'capability' => ['field_capability_title', 'field_capability_description', 'field_mission_benefit', 'field_icon'],
  'use_case' => ['field_use_case_title', 'field_sector', 'field_challenge', 'field_solution', 'field_results'],
];

foreach ($node_fields as $bundle => $fields) {
  foreach ($fields as $fname) {
    if (FieldConfig::loadByName('node', $bundle, $fname)) {
      $field_config['node'][$bundle][$fname]['enabled'] = TRUE;
    }
  }
  echo "node.$bundle: " . count($field_config['node'][$bundle] ?? []) . " fields enabled\n";
}

foreach ($paragraph_fields as $bundle => $fields) {
  foreach ($fields as $fname) {
    if (FieldConfig::loadByName('paragraph', $bundle, $fname)) {
      $field_config['paragraph'][$bundle][$fname]['enabled'] = TRUE;
    }
  }
  echo "paragraph.$bundle: " . count($field_config['paragraph'][$bundle] ?? []) . " fields enabled\n";
}

$config->set('field_config', $field_config)->save();
echo "Done\n";
