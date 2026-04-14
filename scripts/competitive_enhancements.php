<?php
/**
 * Competitive enhancements — content architecture improvements modeled after
 * GDIT and Palantir patterns for defense/gov technology companies.
 *
 * Run: ddev drush scr scripts/competitive_enhancements.php
 * Then: ddev drush cex -y
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

// ============================================================
// 1. Outcome paragraph type + attach to Case Study
//    (Palantir "Impact Studies" pattern — structured metrics)
// ============================================================
echo "=== 1. Create Outcome paragraph type ===\n";

$outcome = \Drupal::entityTypeManager()->getStorage('paragraphs_type')->load('outcome');
if (!$outcome) {
  ParagraphsType::create([
    'id' => 'outcome',
    'label' => 'Outcome',
    'description' => 'A quantified result or metric from a case study (e.g., "40% cost reduction").',
  ])->save();
  echo "  Created paragraph type: outcome\n";
} else {
  echo "  Already exists: outcome\n";
}

// field_metric_value — the number/headline (e.g., "40%", "3x", "$2.1M")
$s = FieldStorageConfig::loadByName('paragraph', 'field_metric_value');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_metric_value',
    'entity_type' => 'paragraph',
    'type' => 'string',
    'cardinality' => 1,
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_metric_value\n";
}
$f = FieldConfig::loadByName('paragraph', 'outcome', 'field_metric_value');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_metric_value',
    'entity_type' => 'paragraph',
    'bundle' => 'outcome',
    'label' => 'Metric Value',
    'required' => TRUE,
    'description' => 'The headline metric (e.g., "40%", "3x faster", "$2.1M saved").',
  ])->save();
  echo "  Added field_metric_value to outcome\n";
}

// field_metric_label — what the metric measures (e.g., "Cost Reduction")
$s = FieldStorageConfig::loadByName('paragraph', 'field_metric_label');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_metric_label',
    'entity_type' => 'paragraph',
    'type' => 'string',
    'cardinality' => 1,
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_metric_label\n";
}
$f = FieldConfig::loadByName('paragraph', 'outcome', 'field_metric_label');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_metric_label',
    'entity_type' => 'paragraph',
    'bundle' => 'outcome',
    'label' => 'Metric Label',
    'required' => TRUE,
    'description' => 'What this metric measures (e.g., "Cost Reduction", "Deployment Speed").',
  ])->save();
  echo "  Added field_metric_label to outcome\n";
}

// field_metric_context — optional supporting sentence
$f = FieldConfig::loadByName('paragraph', 'outcome', 'field_mission_benefit');
if (!$f) {
  $s = FieldStorageConfig::loadByName('paragraph', 'field_mission_benefit');
  if ($s) {
    FieldConfig::create([
      'field_name' => 'field_mission_benefit',
      'entity_type' => 'paragraph',
      'bundle' => 'outcome',
      'label' => 'Mission Context',
      'required' => FALSE,
      'description' => 'Optional one-line context connecting this metric to mission impact.',
    ])->save();
    echo "  Added field_mission_benefit (as context) to outcome\n";
  }
}

// Now add field_outcomes to Case Study (paragraphs reference → outcome)
$s = FieldStorageConfig::loadByName('node', 'field_outcomes');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_outcomes',
    'entity_type' => 'node',
    'type' => 'entity_reference_revisions',
    'cardinality' => -1,
    'settings' => ['target_type' => 'paragraph'],
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_outcomes\n";
}

$f = FieldConfig::loadByName('node', 'case_study', 'field_outcomes');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_outcomes',
    'entity_type' => 'node',
    'bundle' => 'case_study',
    'label' => 'Key Outcomes',
    'required' => FALSE,
    'description' => 'Quantified results — displayed as headline metrics on the case study page.',
    'settings' => [
      'handler' => 'default:paragraph',
      'handler_settings' => [
        'target_bundles' => ['outcome' => 'outcome'],
        'target_bundles_drag_drop' => ['outcome' => ['enabled' => TRUE, 'weight' => 0]],
      ],
    ],
  ])->save();
  echo "  Added field_outcomes to case_study\n";

  // Add to form display
  $form = EntityFormDisplay::load('node.case_study.default');
  if ($form) {
    $form->setComponent('field_outcomes', [
      'type' => 'entity_reference_paragraphs',
      'weight' => 3,
    ]);
    $form->save();
    echo "  Updated case_study form display\n";
  }
}

// ============================================================
// 2. Career enhancements — clearance, career stage, veteran flag
//    (GDIT career-stage segmentation pattern)
// ============================================================
echo "\n=== 2. Enhance Career content type ===\n";

// field_clearance_level — list (select)
$s = FieldStorageConfig::loadByName('node', 'field_clearance_level');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_clearance_level',
    'entity_type' => 'node',
    'type' => 'list_string',
    'cardinality' => 1,
    'settings' => [
      'allowed_values' => [
        'none' => 'No Clearance Required',
        'public_trust' => 'Public Trust',
        'secret' => 'Secret',
        'top_secret' => 'Top Secret',
        'ts_sci' => 'TS/SCI',
        'ts_sci_poly' => 'TS/SCI with Polygraph',
      ],
    ],
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_clearance_level\n";
}
$f = FieldConfig::loadByName('node', 'career', 'field_clearance_level');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_clearance_level',
    'entity_type' => 'node',
    'bundle' => 'career',
    'label' => 'Security Clearance',
    'required' => FALSE,
  ])->save();
  echo "  Added field_clearance_level to career\n";
}

// field_career_stage — list (select)
$s = FieldStorageConfig::loadByName('node', 'field_career_stage');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_career_stage',
    'entity_type' => 'node',
    'type' => 'list_string',
    'cardinality' => 1,
    'settings' => [
      'allowed_values' => [
        'early' => 'Early Career / New Graduate',
        'mid' => 'Mid-Career Professional',
        'senior' => 'Senior / Leadership',
        'veteran' => 'Military Veteran Transition',
        'cleared' => 'Cleared Professional',
      ],
    ],
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_career_stage\n";
}
$f = FieldConfig::loadByName('node', 'career', 'field_career_stage');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_career_stage',
    'entity_type' => 'node',
    'bundle' => 'career',
    'label' => 'Career Stage',
    'required' => FALSE,
  ])->save();
  echo "  Added field_career_stage to career\n";
}

// field_veteran_friendly — boolean
$s = FieldStorageConfig::loadByName('node', 'field_veteran_friendly');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_veteran_friendly',
    'entity_type' => 'node',
    'type' => 'boolean',
    'cardinality' => 1,
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_veteran_friendly\n";
}
$f = FieldConfig::loadByName('node', 'career', 'field_veteran_friendly');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_veteran_friendly',
    'entity_type' => 'node',
    'bundle' => 'career',
    'label' => 'Veteran Friendly',
    'required' => FALSE,
    'description' => 'Flag this position as especially suited for military veteran transitions.',
  ])->save();
  echo "  Added field_veteran_friendly to career\n";
}

// field_remote_policy — list (select)
$s = FieldStorageConfig::loadByName('node', 'field_remote_policy');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_remote_policy',
    'entity_type' => 'node',
    'type' => 'list_string',
    'cardinality' => 1,
    'settings' => [
      'allowed_values' => [
        'onsite' => 'On-Site',
        'hybrid' => 'Hybrid',
        'remote' => 'Remote',
        'scif' => 'SCIF / Secure Facility Required',
      ],
    ],
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_remote_policy\n";
}
$f = FieldConfig::loadByName('node', 'career', 'field_remote_policy');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_remote_policy',
    'entity_type' => 'node',
    'bundle' => 'career',
    'label' => 'Remote Policy',
    'required' => FALSE,
  ])->save();
  echo "  Added field_remote_policy to career\n";
}

// Update career form display
$form = EntityFormDisplay::load('node.career.default');
if ($form) {
  $form->setComponent('field_clearance_level', ['type' => 'options_select', 'weight' => 5]);
  $form->setComponent('field_career_stage', ['type' => 'options_select', 'weight' => 6]);
  $form->setComponent('field_veteran_friendly', ['type' => 'boolean_checkbox', 'weight' => 7]);
  $form->setComponent('field_remote_policy', ['type' => 'options_select', 'weight' => 8]);
  $form->save();
  echo "  Updated career form display\n";
}

// ============================================================
// 3. Solution content type
//    (GDIT "Digital Accelerators" / Palantir "Offerings" pattern)
// ============================================================
echo "\n=== 3. Create Solution content type ===\n";

$type = NodeType::load('solution');
if (!$type) {
  NodeType::create([
    'type' => 'solution',
    'name' => 'Solution',
    'description' => 'Branded, deployable solution packages bridging Products and Services. Similar to GDIT Digital Accelerators or Palantir Offerings.',
    'new_revision' => TRUE,
    'preview_mode' => 1,
    'display_submitted' => FALSE,
  ])->save();
  echo "  Created content type: solution\n";
} else {
  echo "  Already exists: solution\n";
}

// Add fields to Solution — reuse existing storages where possible
$solution_fields = [
  ['field_name' => 'body', 'label' => 'Body'],
  ['field_name' => 'field_summary', 'label' => 'Summary'],
  ['field_name' => 'field_hero_image', 'label' => 'Hero Image', 'settings' => ['handler' => 'default:media', 'handler_settings' => ['target_bundles' => ['image' => 'image']]]],
  ['field_name' => 'field_social_image', 'label' => 'Social Share Image', 'settings' => ['handler' => 'default:media', 'handler_settings' => ['target_bundles' => ['image' => 'image']]]],
  ['field_name' => 'field_primary_cta', 'label' => 'Primary CTA'],
  ['field_name' => 'field_secondary_cta', 'label' => 'Secondary CTA'],
  ['field_name' => 'field_mission_impact', 'label' => 'Mission Impact', 'required' => TRUE],
  ['field_name' => 'field_key_capabilities', 'label' => 'Key Capabilities', 'settings' => ['handler' => 'default:paragraph', 'handler_settings' => ['target_bundles' => ['capability' => 'capability'], 'target_bundles_drag_drop' => ['capability' => ['enabled' => TRUE, 'weight' => 0]]]]],
  ['field_name' => 'field_outcomes', 'label' => 'Key Outcomes', 'settings' => ['handler' => 'default:paragraph', 'handler_settings' => ['target_bundles' => ['outcome' => 'outcome'], 'target_bundles_drag_drop' => ['outcome' => ['enabled' => TRUE, 'weight' => 0]]]]],
  ['field_name' => 'field_seo_title', 'label' => 'SEO Title'],
  ['field_name' => 'field_meta_description', 'label' => 'Meta Description'],
  ['field_name' => 'field_canonical', 'label' => 'Canonical URL'],
  ['field_name' => 'field_breadcrumb_label', 'label' => 'Breadcrumb Label'],
  ['field_name' => 'field_noindex', 'label' => 'Robots noindex'],
  ['field_name' => 'field_solutions', 'label' => 'Solutions', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['solutions' => 'solutions']]]],
  ['field_name' => 'field_technologies', 'label' => 'Technologies', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['technologies' => 'technologies']]]],
  ['field_name' => 'field_industries', 'label' => 'Industries', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['industries' => 'industries']]]],
  ['field_name' => 'field_target_sectors', 'label' => 'Target Sectors', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['target_sectors' => 'target_sectors']]]],
  ['field_name' => 'field_compliance', 'label' => 'Compliance Frameworks', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['compliance' => 'compliance']]]],
  ['field_name' => 'field_personas', 'label' => 'Personas', 'settings' => ['handler' => 'default:taxonomy_term', 'handler_settings' => ['target_bundles' => ['persona' => 'persona']]]],
  ['field_name' => 'field_related', 'label' => 'Related Content'],
  ['field_name' => 'field_template', 'label' => 'Template'],
  ['field_name' => 'field_visibility', 'label' => 'Visibility'],
  ['field_name' => 'field_preview_token', 'label' => 'Preview Token'],
  ['field_name' => 'field_cache_tags', 'label' => 'CDN Cache Tags'],
  ['field_name' => 'field_revalidate_ttl', 'label' => 'Revalidate After'],
];

$count = 0;
foreach ($solution_fields as $f) {
  $storage = FieldStorageConfig::loadByName('node', $f['field_name']);
  if (!$storage) {
    echo "  SKIP: No storage for {$f['field_name']}\n";
    continue;
  }
  $existing = FieldConfig::loadByName('node', 'solution', $f['field_name']);
  if ($existing) {
    echo "  SKIP: {$f['field_name']} already on solution\n";
    continue;
  }
  $config = [
    'field_name' => $f['field_name'],
    'entity_type' => 'node',
    'bundle' => 'solution',
    'label' => $f['label'],
    'required' => $f['required'] ?? FALSE,
  ];
  if (isset($f['settings'])) $config['settings'] = $f['settings'];
  try {
    FieldConfig::create($config)->save();
    $count++;
  } catch (\Exception $e) {
    echo "  ERROR: {$f['field_name']}: " . $e->getMessage() . "\n";
  }
}
echo "  Added $count fields to solution\n";

// Create Solution form display with tabs
$form_display = EntityFormDisplay::create([
  'targetEntityType' => 'node',
  'bundle' => 'solution',
  'mode' => 'default',
  'status' => TRUE,
]);

$solution_form_fields = [
  'body' => ['type' => 'text_textarea_with_summary', 'weight' => 1],
  'field_summary' => ['type' => 'string_textfield', 'weight' => 2],
  'field_mission_impact' => ['type' => 'text_textarea', 'weight' => 3],
  'field_key_capabilities' => ['type' => 'entity_reference_paragraphs', 'weight' => 5],
  'field_outcomes' => ['type' => 'entity_reference_paragraphs', 'weight' => 6],
  'field_hero_image' => ['type' => 'entity_reference_autocomplete', 'weight' => 10],
  'field_social_image' => ['type' => 'entity_reference_autocomplete', 'weight' => 11],
  'field_primary_cta' => ['type' => 'link_default', 'weight' => 15],
  'field_secondary_cta' => ['type' => 'link_default', 'weight' => 16],
  'field_seo_title' => ['type' => 'string_textfield', 'weight' => 20],
  'field_meta_description' => ['type' => 'string_textfield', 'weight' => 21],
  'field_canonical' => ['type' => 'string_textfield', 'weight' => 22],
  'field_breadcrumb_label' => ['type' => 'string_textfield', 'weight' => 23],
  'field_noindex' => ['type' => 'boolean_checkbox', 'weight' => 24],
  'field_solutions' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 30],
  'field_technologies' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 31],
  'field_industries' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 32],
  'field_target_sectors' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 33],
  'field_compliance' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 34],
  'field_personas' => ['type' => 'entity_reference_autocomplete_tags', 'weight' => 35],
  'field_template' => ['type' => 'string_textfield', 'weight' => 40],
  'field_visibility' => ['type' => 'string_textfield', 'weight' => 41],
  'field_related' => ['type' => 'entity_reference_autocomplete', 'weight' => 42],
  'field_preview_token' => ['type' => 'string_textfield', 'weight' => 50],
  'field_cache_tags' => ['type' => 'string_textfield', 'weight' => 51],
  'field_revalidate_ttl' => ['type' => 'number', 'weight' => 52],
];

foreach ($solution_form_fields as $field_name => $config) {
  $form_display->setComponent($field_name, ['type' => $config['type'], 'weight' => $config['weight']]);
}

$form_display->setThirdPartySetting('field_group', 'group_tabs', [
  'children' => ['group_content', 'group_proof', 'group_media', 'group_ctas', 'group_seo', 'group_classification', 'group_layout', 'group_technical'],
  'label' => 'Tabs', 'region' => 'content', 'parent_name' => '', 'weight' => 0,
  'format_type' => 'tabs', 'format_settings' => ['direction' => 'horizontal', 'width_breakpoint' => 640],
]);

$solution_tabs = [
  'group_content' => ['label' => 'Content', 'weight' => 0, 'children' => ['body', 'field_summary', 'field_mission_impact', 'field_key_capabilities']],
  'group_proof' => ['label' => 'Proof Points', 'weight' => 1, 'children' => ['field_outcomes']],
  'group_media' => ['label' => 'Media', 'weight' => 2, 'children' => ['field_hero_image', 'field_social_image']],
  'group_ctas' => ['label' => 'CTAs', 'weight' => 3, 'children' => ['field_primary_cta', 'field_secondary_cta']],
  'group_seo' => ['label' => 'SEO', 'weight' => 4, 'children' => ['field_seo_title', 'field_meta_description', 'field_canonical', 'field_breadcrumb_label', 'field_noindex']],
  'group_classification' => ['label' => 'Classification', 'weight' => 5, 'children' => ['field_solutions', 'field_technologies', 'field_industries', 'field_target_sectors', 'field_compliance', 'field_personas']],
  'group_layout' => ['label' => 'Layout', 'weight' => 6, 'children' => ['field_template', 'field_visibility', 'field_related']],
  'group_technical' => ['label' => 'Technical', 'weight' => 7, 'children' => ['field_preview_token', 'field_cache_tags', 'field_revalidate_ttl']],
];

foreach ($solution_tabs as $group_name => $tab) {
  $form_display->setThirdPartySetting('field_group', $group_name, [
    'children' => $tab['children'], 'label' => $tab['label'], 'region' => 'content',
    'parent_name' => 'group_tabs', 'weight' => $tab['weight'],
    'format_type' => 'tab', 'format_settings' => ['classes' => '', 'show_empty_fields' => FALSE, 'id' => '', 'formatter' => 'closed', 'description' => '', 'required_fields' => TRUE],
  ]);
}
$form_display->save();
echo "  Created solution form display with " . count($solution_tabs) . " tabs\n";

// View display
$view_display = EntityViewDisplay::create([
  'targetEntityType' => 'node', 'bundle' => 'solution', 'mode' => 'default', 'status' => TRUE,
]);
foreach ($solution_form_fields as $field_name => $config) {
  $view_display->setComponent($field_name, ['weight' => $config['weight']]);
}
$view_display->save();
echo "  Created solution view display\n";

// Add Solution to Editorial workflow
$workflow = \Drupal\workflows\Entity\Workflow::load('editorial');
if ($workflow) {
  $config = $workflow->get('type_settings');
  if (!in_array('solution', $config['entity_types']['node'] ?? [])) {
    $config['entity_types']['node'][] = 'solution';
    $workflow->set('type_settings', $config);
    $workflow->save();
    echo "  Added solution to Editorial workflow\n";
  }
}

// ============================================================
// 4. Compliance badge field on taxonomy terms
// ============================================================
echo "\n=== 4. Add badge/icon to compliance taxonomy ===\n";

$s = FieldStorageConfig::loadByName('taxonomy_term', 'field_badge_icon');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_badge_icon',
    'entity_type' => 'taxonomy_term',
    'type' => 'entity_reference',
    'cardinality' => 1,
    'settings' => ['target_type' => 'media'],
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_badge_icon (taxonomy_term)\n";
}

$f = FieldConfig::loadByName('taxonomy_term', 'compliance', 'field_badge_icon');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_badge_icon',
    'entity_type' => 'taxonomy_term',
    'bundle' => 'compliance',
    'label' => 'Badge / Icon',
    'required' => FALSE,
    'description' => 'Compliance badge image (e.g., FedRAMP, IL5, SOC2 logos) — displayed on Product and Service pages.',
    'settings' => [
      'handler' => 'default:media',
      'handler_settings' => ['target_bundles' => ['image' => 'image']],
    ],
  ])->save();
  echo "  Added field_badge_icon to compliance vocabulary\n";
}

// Also add a short description field for compliance terms
$s = FieldStorageConfig::loadByName('taxonomy_term', 'field_short_description');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_short_description',
    'entity_type' => 'taxonomy_term',
    'type' => 'string',
    'cardinality' => 1,
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_short_description (taxonomy_term)\n";
}
$f = FieldConfig::loadByName('taxonomy_term', 'compliance', 'field_short_description');
if (!$f) {
  FieldConfig::create([
    'field_name' => 'field_short_description',
    'entity_type' => 'taxonomy_term',
    'bundle' => 'compliance',
    'label' => 'Short Description',
    'required' => FALSE,
    'description' => 'One-line description of this compliance framework (e.g., "Federal Risk and Authorization Management Program").',
  ])->save();
  echo "  Added field_short_description to compliance vocabulary\n";
}

// ============================================================
// 5. Platform taxonomy for product ecosystems (Palantir pattern)
// ============================================================
echo "\n=== 5. Create Platform taxonomy ===\n";

$vocab = Vocabulary::load('platforms');
if (!$vocab) {
  Vocabulary::create([
    'vid' => 'platforms',
    'name' => 'Platforms',
    'description' => 'Top-level product platform brands. Each platform groups related products, solutions, and content into a coherent ecosystem.',
  ])->save();
  echo "  Created vocabulary: platforms\n";
} else {
  echo "  Already exists: platforms\n";
}

// Create field_platform storage
$s = FieldStorageConfig::loadByName('node', 'field_platform');
if (!$s) {
  FieldStorageConfig::create([
    'field_name' => 'field_platform',
    'entity_type' => 'node',
    'type' => 'entity_reference',
    'cardinality' => 1,
    'settings' => ['target_type' => 'taxonomy_term'],
    'translatable' => TRUE,
  ])->save();
  echo "  Created storage: field_platform\n";
}

// Add field_platform to Product, Solution, Service, Case Study
foreach (['product', 'solution', 'service', 'case_study'] as $bundle) {
  $f = FieldConfig::loadByName('node', $bundle, 'field_platform');
  if (!$f) {
    FieldConfig::create([
      'field_name' => 'field_platform',
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => 'Platform',
      'required' => FALSE,
      'description' => 'Which platform brand this content belongs to.',
      'settings' => [
        'handler' => 'default:taxonomy_term',
        'handler_settings' => ['target_bundles' => ['platforms' => 'platforms']],
      ],
    ])->save();
    echo "  Added field_platform to $bundle\n";

    // Add to form display
    $form = EntityFormDisplay::load("node.$bundle.default");
    if ($form) {
      $form->setComponent('field_platform', ['type' => 'entity_reference_autocomplete', 'weight' => 38]);
      $form->save();
    }
  }
}

echo "\n=== Competitive enhancements complete ===\n";
echo "Run: ddev drush cex -y\n";
