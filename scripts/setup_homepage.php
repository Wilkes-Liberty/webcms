<?php
/**
 * Set up the editable Homepage:
 *   1. Create p_notice paragraph type with field_title + field_notice_tone
 *   2. Allow p_notice on landing_page field_components
 *   3. Enable landing_page + paragraphs in graphql_compose
 *   4. Seed a Homepage landing_page node with current copy
 *   5. Set system.site.page.front to /homepage
 *
 * Idempotent — safe to re-run.
 *
 * Run: ddev drush scr scripts/setup_homepage.php
 * Then: ddev drush cex -y
 */

use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

// ============================================================
// 1. Create p_notice paragraph type
// ============================================================
echo "=== 1. Create p_notice paragraph type ===\n";
$pt = ParagraphsType::load('p_notice');
if (!$pt) {
  ParagraphsType::create([
    'id' => 'p_notice',
    'label' => 'Notice / Status Pill',
    'description' => 'A small status badge with a colored dot — e.g. "Not accepting new clients", "Now hiring", "Service degraded".',
  ])->save();
  echo "  Created paragraph type: p_notice\n";
} else {
  echo "  Already exists: p_notice\n";
}

// field_notice_tone — list_string
$s = FieldStorageConfig::loadByName('paragraph', 'field_notice_tone');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_notice_tone',
    'entity_type' => 'paragraph',
    'type' => 'list_string',
    'cardinality' => 1,
    'settings' => [
      'allowed_values' => [
        'success' => 'Success (green)',
        'warning' => 'Warning (amber)',
        'info' => 'Info (blue)',
        'danger' => 'Danger (red)',
      ],
    ],
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_notice_tone\n";
}

// field_title instance on p_notice (reuse existing storage)
$f = FieldConfig::loadByName('paragraph', 'p_notice', 'field_title');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_title',
    'entity_type' => 'paragraph',
    'bundle' => 'p_notice',
    'label' => 'Notice Text',
    'required' => TRUE,
    'description' => 'Short text shown inside the pill (e.g., "Not accepting new clients").',
  ])->save();
  echo "  Added field_title to p_notice\n";
}

// field_notice_tone instance on p_notice
$f = FieldConfig::loadByName('paragraph', 'p_notice', 'field_notice_tone');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_notice_tone',
    'entity_type' => 'paragraph',
    'bundle' => 'p_notice',
    'label' => 'Tone',
    'required' => TRUE,
    'default_value' => [['value' => 'warning']],
  ])->save();
  echo "  Added field_notice_tone to p_notice\n";
}

// Form display
$fd = EntityFormDisplay::load('paragraph.p_notice.default');
if (!$fd) {
  $fd = EntityFormDisplay::create([
    'targetEntityType' => 'paragraph',
    'bundle' => 'p_notice',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}
$fd->setComponent('field_title', ['type' => 'string_textfield', 'weight' => 0]);
$fd->setComponent('field_notice_tone', ['type' => 'options_select', 'weight' => 1]);
$fd->save();
echo "  Saved form display for p_notice\n";

// View display
$vd = EntityViewDisplay::load('paragraph.p_notice.default');
if (!$vd) {
  $vd = EntityViewDisplay::create([
    'targetEntityType' => 'paragraph',
    'bundle' => 'p_notice',
    'mode' => 'default',
    'status' => TRUE,
  ]);
}
$vd->setComponent('field_title', ['weight' => 0]);
$vd->setComponent('field_notice_tone', ['weight' => 1]);
$vd->save();
echo "  Saved view display for p_notice\n";

// ============================================================
// 2. Allow p_notice on landing_page.field_components
// ============================================================
echo "\n=== 2. Allow p_notice on landing_page.field_components ===\n";
$lp = FieldConfig::loadByName('node', 'landing_page', 'field_components');
if ($lp) {
  $settings = $lp->getSetting('handler_settings');
  if (!isset($settings['target_bundles']['p_notice'])) {
    $settings['target_bundles']['p_notice'] = 'p_notice';
    $settings['target_bundles_drag_drop']['p_notice'] = ['enabled' => TRUE, 'weight' => 20];
    $lp->setSetting('handler_settings', $settings);
    $lp->save();
    echo "  Added p_notice to landing_page.field_components target_bundles\n";
  } else {
    echo "  Already allowed\n";
  }
}

// ============================================================
// 3. Enable landing_page + paragraphs in graphql_compose
// ============================================================
echo "\n=== 3. Enable in graphql_compose ===\n";
$config = \Drupal::configFactory()->getEditable('graphql_compose.settings');
$entity_config = $config->get('entity_config') ?: [];

// Enable landing_page node type with all flags
$entity_config['node']['landing_page'] = [
  'enabled' => TRUE,
  'query_load_enabled' => TRUE,
  'edges_enabled' => TRUE,
  'routes_enabled' => TRUE,
  'fields' => [
    'title' => ['enabled' => TRUE],
    'body' => ['enabled' => TRUE],
    'field_components' => ['enabled' => TRUE],
    'field_primary_cta' => ['enabled' => TRUE],
    'field_secondary_cta' => ['enabled' => TRUE],
    'field_seo_title' => ['enabled' => TRUE],
    'field_meta_description' => ['enabled' => TRUE],
    'field_summary' => ['enabled' => TRUE],
    'field_hero_image' => ['enabled' => TRUE],
    'field_social_image' => ['enabled' => TRUE],
  ],
];
echo "  Enabled landing_page in graphql_compose\n";

// Enable paragraph types used on landing_page
$paragraph_types = [
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

foreach ($paragraph_types as $bundle => $fields) {
  $field_config = [];
  foreach ($fields as $fname) {
    // Only enable fields that actually exist on this paragraph bundle
    if (FieldConfig::loadByName('paragraph', $bundle, $fname)) {
      $field_config[$fname] = ['enabled' => TRUE];
    }
  }
  $entity_config['paragraph'][$bundle] = [
    'enabled' => TRUE,
    'query_load_enabled' => TRUE,
    'fields' => $field_config,
  ];
  echo "  Enabled paragraph $bundle (" . count($field_config) . " fields)\n";
}

$config->set('entity_config', $entity_config)->save();

// ============================================================
// 4. Seed Homepage landing_page node
// ============================================================
echo "\n=== 4. Seed Homepage landing_page node ===\n";

// Find existing by alias
$alias_storage = \Drupal::service('path_alias.repository');
$existing = $alias_storage->lookupByAlias('/homepage', 'en');
$node = NULL;
if ($existing && preg_match('|^/node/(\d+)$|', $existing['path'], $m)) {
  $node = Node::load($m[1]);
  echo "  Found existing Homepage node: " . $node->id() . "\n";
}

if (!$node) {
  // Create paragraphs
  $hero = Paragraph::create([
    'type' => 'p_hero',
    'field_title' => 'Building what cannot be compromised.',
    'field_subtitle' => [
      'value' => 'Sovereign infrastructure for sovereign missions.',
      'format' => 'plain_text',
    ],
  ]);
  $hero->save();

  $body = Paragraph::create([
    'type' => 'p_text_block',
    'field_body' => [
      'value' => 'Private platforms for defense, intelligence, and critical infrastructure. Engineered for organizations where failure is not an abstraction.',
      'format' => 'plain_text',
    ],
  ]);
  $body->save();

  $notice = Paragraph::create([
    'type' => 'p_notice',
    'field_title' => 'Not accepting new clients',
    'field_notice_tone' => 'warning',
  ]);
  $notice->save();

  $cta = Paragraph::create([
    'type' => 'p_cta_banner',
    'field_cta_links' => [
      ['uri' => 'mailto:inquiry@wilkesliberty.com', 'title' => 'inquiry@wilkesliberty.com'],
    ],
  ]);
  $cta->save();

  $node = Node::create([
    'type' => 'landing_page',
    'title' => 'Homepage',
    'status' => 1,
    'moderation_state' => 'published',
    'path' => ['alias' => '/homepage'],
    'field_components' => [
      ['target_id' => $hero->id(), 'target_revision_id' => $hero->getRevisionId()],
      ['target_id' => $body->id(), 'target_revision_id' => $body->getRevisionId()],
      ['target_id' => $notice->id(), 'target_revision_id' => $notice->getRevisionId()],
      ['target_id' => $cta->id(), 'target_revision_id' => $cta->getRevisionId()],
    ],
  ]);
  $node->save();
  echo "  Created Homepage node: " . $node->id() . " (published) with 4 paragraphs\n";
} else {
  echo "  Skip seeding (node exists). Edit at /node/" . $node->id() . "/edit\n";
}

// ============================================================
// 5. Set system.site.page.front
// ============================================================
echo "\n=== 5. Set page.front to /homepage ===\n";
$site = \Drupal::configFactory()->getEditable('system.site');
$current = $site->get('page.front');
if ($current !== '/homepage') {
  $site->set('page.front', '/homepage')->save();
  echo "  Updated system.site.page.front: $current → /homepage\n";
} else {
  echo "  Already set\n";
}

echo "\n=== Setup complete ===\n";
echo "Run: ddev drush cex -y\n";
echo "Then visit: https://api.wilkesliberty.dev/admin/content (find Homepage)\n";
