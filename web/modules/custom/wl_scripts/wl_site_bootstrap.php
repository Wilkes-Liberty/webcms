<?php

/**
 * WL full bootstrap: content types + paragraphs + fields + displays +
 * media bundles + entity browsers + widgets + pathauto + Next.js image styles.
 *
 * Run:
 *   drush scr web/modules/custom/wl_scripts/wl_full_bootstrap.php
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\MediaType;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\pathauto\Entity\PathautoPattern;

/* ============================================================
 * 0) Ensure required modules
 * ============================================================ */
$installer = \Drupal::service('module_installer');
$required = [
  'media',
  'media_library',
  'entity_browser',
  'entity_browser_entity_form',
  'paragraphs',
  'pathauto',
  'menu_ui',
];
$to_install = array_values(array_filter($required, fn($m) => !\Drupal::moduleHandler()->moduleExists($m)));
if ($to_install) { $installer->install($to_install); }

/* ============================================================
 * Helpers
 * ============================================================ */

function wl_ensure_node_type($type, $label, $desc = '') {
  if (!NodeType::load($type)) {
    NodeType::create([
      'type' => $type,
      'name' => $label,
      'description' => $desc,
      'new_revision' => TRUE,
      'preview_mode' => DRUPAL_OPTIONAL,
      'display_submitted' => FALSE,
    ])->save();
  }
}

function wl_field_storage($entity, $name, $type, array $settings = [], array $opts = []) {
  if (!FieldStorageConfig::load("$entity.$name")) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => $entity,
      'type' => $type,
      'settings' => $settings,
      'cardinality' => $opts['cardinality'] ?? 1,
      'translatable' => $opts['translatable'] ?? TRUE,
    ])->save();
  }
}

function wl_field_instance($entity, $bundle, $name, $label, array $settings = [], array $opts = []) {
  if (!FieldConfig::load("$entity.$bundle.$name")) {
    FieldConfig::create([
      'field_name' => $name,
      'entity_type' => $entity,
      'bundle' => $bundle,
      'label' => $label,
      'required' => $opts['required'] ?? FALSE,
      'translatable' => $opts['translatable'] ?? TRUE,
      'settings' => $settings,
      'description' => $opts['description'] ?? '',
    ])->save();
  }
}

function wl_form_display($entity, $bundle) {
  return EntityFormDisplay::load("$entity.$bundle.default")
    ?: EntityFormDisplay::create([
      'targetEntityType' => $entity,
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
}

function wl_view_display($entity, $bundle) {
  return EntityViewDisplay::load("$entity.$bundle.default")
    ?: EntityViewDisplay::create([
      'targetEntityType' => $entity,
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
}

function wl_ensure_paragraph_type($id, $label) {
  if (!ParagraphsType::load($id)) {
    ParagraphsType::create(['id' => $id, 'label' => $label])->save();
  }
}

/** Ensure Body storage + attach to bundle */
function wl_ensure_body_field($bundle) {
  if (!FieldStorageConfig::load('node.body')) {
    FieldStorageConfig::create([
      'field_name' => 'body',
      'entity_type' => 'node',
      'type' => 'text_with_summary',
      'settings' => [],
      'translatable' => TRUE,
      'cardinality' => 1,
    ])->save();
  }
  if (!FieldConfig::load("node.$bundle.body")) {
    FieldConfig::create([
      'field_name' => 'body',
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => 'Body',
      'required' => FALSE,
      'translatable' => TRUE,
    ])->save();
  }
}

/** Ensure an image style with effects */
function wl_ensure_image_style($name, $label, array $effects) {
  $style = ImageStyle::load($name);
  if (!$style) {
    $style = ImageStyle::create(['name' => $name, 'label' => $label]);
    $style->save();
  }
  foreach ($style->getEffects()->getConfiguration() as $uuid => $conf) {
    $style->getEffects()->removeEffect($uuid);
  }
  foreach ($effects as $ef) {
    $style->addImageEffect([
      'id' => $ef['id'],
      'weight' => $ef['weight'] ?? 0,
      'data' => $ef['data'] ?? [],
    ]);
  }
  $style->save();
}

/* ============================================================
 * 1) Paragraph bundles + fields
 * ============================================================ */
$paragraphs = [
  'p_hero'        => 'Hero',
  'p_feature'     => 'Feature',
  'p_logo_wall'   => 'Logo Wall',
  'p_testimonial' => 'Testimonial',
  'p_faq_item'    => 'FAQ Item',
  'p_faq_group'   => 'FAQ Group',
  'p_stat'        => 'Stat',
  'p_cta_banner'  => 'CTA Banner',
];
foreach ($paragraphs as $id => $label) { wl_ensure_paragraph_type($id, $label); }

// Shared paragraph storages
wl_field_storage('paragraph', 'field_title',        'string');
wl_field_storage('paragraph', 'field_subtitle',     'text_long');
wl_field_storage('paragraph', 'field_media',        'entity_reference', ['target_type' => 'media']);
wl_field_storage('paragraph', 'field_cta_links',    'link', [], ['cardinality' => -1]);
wl_field_storage('paragraph', 'field_icon',         'entity_reference', ['target_type' => 'media']);
wl_field_storage('paragraph', 'field_body',         'text_long');
wl_field_storage('paragraph', 'field_logos',        'entity_reference', ['target_type' => 'media'], ['cardinality' => -1]);
wl_field_storage('paragraph', 'field_quote',        'text_long');
wl_field_storage('paragraph', 'field_attribution',  'string');
wl_field_storage('paragraph', 'field_items',        'entity_reference_revisions', ['target_type' => 'paragraph'], ['cardinality' => -1]);
wl_field_storage('paragraph', 'field_value',        'string');
wl_field_storage('paragraph', 'field_suffix',       'string');

// Instances
// p_hero
wl_field_instance('paragraph', 'p_hero', 'field_title', 'Title');
wl_field_instance('paragraph', 'p_hero', 'field_subtitle', 'Subtitle');
wl_field_instance('paragraph', 'p_hero', 'field_media', 'Hero Media');
wl_field_instance('paragraph', 'p_hero', 'field_cta_links', 'CTA Links', [], ['description' => 'One or more CTAs']);

// p_feature
wl_field_instance('paragraph', 'p_feature', 'field_icon', 'Icon');
wl_field_instance('paragraph', 'p_feature', 'field_title', 'Title');
wl_field_instance('paragraph', 'p_feature', 'field_body', 'Body');

// p_logo_wall
wl_field_instance('paragraph', 'p_logo_wall', 'field_logos', 'Logos');

// p_testimonial
wl_field_instance('paragraph', 'p_testimonial', 'field_quote', 'Quote');
wl_field_instance('paragraph', 'p_testimonial', 'field_attribution', 'Attribution');
wl_field_instance('paragraph', 'p_testimonial', 'field_icon', 'Client Logo');

// p_faq
wl_field_instance('paragraph', 'p_faq_item', 'field_title', 'Question');
wl_field_instance('paragraph', 'p_faq_item', 'field_body', 'Answer');
wl_field_instance('paragraph', 'p_faq_group', 'field_items', 'FAQ Items', [
  'handler' => 'default:paragraph',
  'handler_settings' => ['target_bundles' => ['p_faq_item' => 'p_faq_item']],
]);

// p_stat
wl_field_instance('paragraph', 'p_stat', 'field_title', 'Label');
wl_field_instance('paragraph', 'p_stat', 'field_value', 'Value');
wl_field_instance('paragraph', 'p_stat', 'field_suffix', 'Suffix');

// p_cta
wl_field_instance('paragraph', 'p_cta_banner', 'field_title', 'Title');
wl_field_instance('paragraph', 'p_cta_banner', 'field_body', 'Body');
wl_field_instance('paragraph', 'p_cta_banner', 'field_cta_links', 'CTA Links');

/* ============================================================
 * 2) Media bundles (types) + extra metadata fields
 * ============================================================ */

/**
 * Media bundles to create (id => spec):
 * - image (core default; ensure exists)
 * - svg_image (file-based, svg/svgz only)
 * - icon (file-based, svg|png for small monochrome marks)
 * - video_file (file-based mp4/mov/webm + poster image + captions + transcript)
 * - remote_video (oEmbed video + poster + transcript)
 * - audio (file-based mp3/ogg/wav)
 * - document (file-based pdf/docx/xlsx/pptx/txt)
 */

// Helper to ensure a media type
function wl_ensure_media_type($id, $label, $source, array $source_conf = [], array $third_party = []) {
  $mt = MediaType::load($id);
  if (!$mt) {
    $mt = MediaType::create([
      'id' => $id,
      'label' => $label,
      'source' => $source,
      'source_configuration' => $source_conf,
      'new_revision' => TRUE,
    ]);
    foreach ($third_party as $provider => $data) {
      $mt->setThirdPartySetting($provider, key($data), current($data));
    }
    $mt->save();
  }
  return $mt;
}

// 2a) Image (core-provided 'image' source field: field_media_image)
wl_ensure_media_type('image', 'Image', 'image', [
  'source_field' => 'field_media_image',
]);

// 2b) SVG image (file source with restrictive extension)
wl_ensure_media_type('svg_image', 'SVG Image', 'file', [
  'source_field' => 'field_media_file',
  'allowed_extensions' => 'svg svgz',
]);

// 2c) Icon (SVG or PNG)
wl_ensure_media_type('icon', 'Icon', 'file', [
  'source_field' => 'field_media_file',
  'allowed_extensions' => 'svg svgz png',
]);

// 2d) Video (file)
wl_ensure_media_type('video_file', 'Video (File)', 'file', [
  'source_field' => 'field_media_file',
  'allowed_extensions' => 'mp4 mov webm',
]);

// 2e) Remote video (oEmbed)
wl_ensure_media_type('remote_video', 'Remote Video', 'oembed:video', [
  'source_field' => 'field_media_oembed_video',
]);

// 2f) Audio (file)
wl_ensure_media_type('audio', 'Audio', 'file', [
  'source_field' => 'field_media_file',
  'allowed_extensions' => 'mp3 ogg wav',
]);

// 2g) Document (file)
wl_ensure_media_type('document', 'Document', 'file', [
  'source_field' => 'field_media_file',
  'allowed_extensions' => 'pdf doc docx xls xlsx ppt pptx txt',
]);

// Shared media metadata fields storages
wl_field_storage('media', 'field_caption',     'text_long');
wl_field_storage('media', 'field_credit',      'string');
wl_field_storage('media', 'field_license',     'string');
wl_field_storage('media', 'field_source_url',  'link');
wl_field_storage('media', 'field_poster',      'entity_reference', ['target_type' => 'media']);
wl_field_storage('media', 'field_captions_vtt','file', ['file_extensions' => 'vtt']);
wl_field_storage('media', 'field_transcript',  'text_long');

// Attach to bundles as appropriate
$media_bundles = ['image','svg_image','icon','video_file','remote_video','audio','document'];
foreach ($media_bundles as $mb) {
  wl_field_instance('media', $mb, 'field_caption', 'Caption');
  wl_field_instance('media', $mb, 'field_credit', 'Credit');
  wl_field_instance('media', $mb, 'field_license', 'License');
  wl_field_instance('media', $mb, 'field_source_url', 'Source URL');
}
// Posters & captions: videos only
wl_field_instance('media', 'video_file',   'field_poster', 'Poster Image', [
  'handler' => 'default:media', 'handler_settings' => ['target_bundles' => ['image' => 'image','svg_image'=>'svg_image','icon'=>'icon']]
]);
wl_field_instance('media', 'remote_video', 'field_poster', 'Poster Image', [
  'handler' => 'default:media', 'handler_settings' => ['target_bundles' => ['image' => 'image','svg_image'=>'svg_image','icon'=>'icon']]
]);
wl_field_instance('media', 'video_file', 'field_captions_vtt', 'Captions (WebVTT)');
wl_field_instance('media', 'video_file', 'field_transcript', 'Transcript');
wl_field_instance('media', 'remote_video', 'field_transcript', 'Transcript');

/* Optional: tidy Media form displays (basic) */
foreach ($media_bundles as $mb) {
  $mform = wl_form_display('media', $mb);
  // Leave source field as is; add meta fields
  $mform->setComponent('field_caption', ['type' => 'text_textarea', 'settings' => ['rows' => 3]]);
  $mform->setComponent('field_credit', ['type' => 'string_textfield']);
  $mform->setComponent('field_license', ['type' => 'string_textfield']);
  $mform->setComponent('field_source_url', ['type' => 'link_default']);
  if (in_array($mb, ['video_file','remote_video'])) {
    $mform->setComponent('field_poster', ['type' => 'entity_reference_autocomplete']);
    $mform->setComponent('field_transcript', ['type' => 'text_textarea', 'settings' => ['rows' => 6]]);
  }
  if ($mb === 'video_file') {
    $mform->setComponent('field_captions_vtt', ['type' => 'file_generic']);
  }
  $mform->save();
}

/* ============================================================
 * 3) Node bundles + shared fields (incl. Body)
 * ============================================================ */
$bundles = [
  'basic_page'   => ['Basic Page',  'Simple content page for headless rendering'],
  'landing_page' => ['Landing Page','Flexible marketing page using components'],
  'article'      => ['Article',     'News article (core bundle kept)'],
  'service'      => ['Service',     'Company offering'],
  'case_study'   => ['Case Study',  'Customer success story'],
  'resource'     => ['Resource',    'Whitepaper/eBook/Checklist'],
  'event'        => ['Event',       'Webinar or in-person event'],
  'career'       => ['Career',      'Job posting'],
];
foreach ($bundles as $id => $def) { wl_ensure_node_type($id, $def[0], $def[1]); wl_ensure_body_field($id); }

// Shared node storages
wl_field_storage('node', 'field_summary',          'text_long');
wl_field_storage('node', 'field_hero_image',       'entity_reference', ['target_type' => 'media']);
wl_field_storage('node', 'field_social_image',     'entity_reference', ['target_type' => 'media']);
wl_field_storage('node', 'field_primary_cta',      'link');
wl_field_storage('node', 'field_secondary_cta',    'link');
wl_field_storage('node', 'field_related',          'entity_reference', ['target_type' => 'node'], ['cardinality' => -1]);
wl_field_storage('node', 'field_components',       'entity_reference_revisions', ['target_type' => 'paragraph'], ['cardinality' => -1]);
wl_field_storage('node', 'field_parent',           'entity_reference', ['target_type' => 'node']);
wl_field_storage('node', 'field_template',         'list_string', ['allowed_values' => ['default'=>'Default','wide'=>'Wide','no-sidebar'=>'No Sidebar','landing'=>'Landing']]);
wl_field_storage('node', 'field_theme_variant',    'list_string', ['allowed_values' => ['light'=>'Light','dark'=>'Dark','brand-a'=>'Brand A']]);
wl_field_storage('node', 'field_seo_title',        'string');
wl_field_storage('node', 'field_meta_description', 'string');
wl_field_storage('node', 'field_canonical',        'link');
wl_field_storage('node', 'field_noindex',          'boolean');
wl_field_storage('node', 'field_breadcrumb_label', 'string');
wl_field_storage('node', 'field_reviewed_on',      'datetime', ['datetime_type' => 'date']);
wl_field_storage('node', 'field_visibility',       'list_string', ['allowed_values' => ['public'=>'Public','login'=>'Login required','role-partner'=>'Role: Partner']]);
wl_field_storage('node', 'field_revalidate_ttl',   'integer');
wl_field_storage('node', 'field_cache_tags',       'string', [], ['cardinality' => -1]);
wl_field_storage('node', 'field_preview_token',    'string');
wl_field_storage('node', 'field_campaign',         'string');
wl_field_storage('node', 'field_ab_variant',       'list_string', ['allowed_values' => ['control'=>'Control','variant-a'=>'Variant A','variant-b'=>'Variant B']]);
wl_field_storage('node', 'field_show_toc',         'boolean');
wl_field_storage('node', 'field_read_time',        'integer');
wl_field_storage('node', 'field_taxonomy',         'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => -1]); // restrict later when vocabs exist

// Assign instances; tighten media handlers to your media bundles
foreach (array_keys($bundles) as $b) {
  wl_field_instance('node', $b, 'field_summary', 'Summary / Deck');

  wl_field_instance('node', $b, 'field_hero_image', 'Hero Image', [
    'handler' => 'default:media',
    'handler_settings' => ['target_bundles' => ['image'=>'image','svg_image'=>'svg_image','icon'=>'icon']],
  ]);
  wl_field_instance('node', $b, 'field_social_image', 'Social Share Image', [
    'handler' => 'default:media',
    'handler_settings' => ['target_bundles' => ['image'=>'image','svg_image'=>'svg_image','icon'=>'icon']],
  ]);

  wl_field_instance('node', $b, 'field_primary_cta', 'Primary CTA');
  wl_field_instance('node', $b, 'field_secondary_cta', 'Secondary CTA');
  wl_field_instance('node', $b, 'field_related', 'Related Content', ['handler' => 'default']);
  wl_field_instance('node', $b, 'field_parent', 'Parent Page', [
    'handler' => 'default',
    'handler_settings' => ['target_bundles' => [$b => $b]],
  ]);
  wl_field_instance('node', $b, 'field_template', 'Template / Layout');
  wl_field_instance('node', $b, 'field_theme_variant', 'Design Variant');
  wl_field_instance('node', $b, 'field_seo_title', 'SEO Title Override');
  wl_field_instance('node', $b, 'field_meta_description', 'Meta Description');
  wl_field_instance('node', $b, 'field_canonical', 'Canonical URL (override)');
  wl_field_instance('node', $b, 'field_noindex', 'Robots: noindex');
  wl_field_instance('node', $b, 'field_breadcrumb_label', 'Breadcrumb Label Override');
  wl_field_instance('node', $b, 'field_reviewed_on', 'Last Reviewed On');
  wl_field_instance('node', $b, 'field_visibility', 'Visibility');
  wl_field_instance('node', $b, 'field_revalidate_ttl', 'Revalidate After (seconds)');
  wl_field_instance('node', $b, 'field_cache_tags', 'CDN Cache Tags (hints)');
  wl_field_instance('node', $b, 'field_preview_token', 'Preview Token');
  wl_field_instance('node', $b, 'field_campaign', 'UTM Bucket / Campaign');
  wl_field_instance('node', $b, 'field_ab_variant', 'A/B Variant');
  wl_field_instance('node', $b, 'field_show_toc', 'Show On-Page TOC');
  wl_field_instance('node', $b, 'field_read_time', 'Estimated Read Time (minutes)');
  wl_field_instance('node', $b, 'field_taxonomy', 'Tags / Taxonomies', ['handler' => 'default']);
}
// Landing Page components (Paragraphs)
wl_field_instance('node', 'landing_page', 'field_components', 'Components', [
  'handler' => 'default:paragraph',
  'handler_settings' => [
    'target_bundles' => [
      'p_hero' => 'p_hero',
      'p_feature' => 'p_feature',
      'p_logo_wall' => 'p_logo_wall',
      'p_testimonial' => 'p_testimonial',
      'p_faq_group' => 'p_faq_group',
      'p_stat' => 'p_stat',
      'p_cta_banner' => 'p_cta_banner',
    ],
  ],
], ['description' => 'Add and reorder content sections.']);

// Career extras
wl_field_storage('node', 'field_job_type', 'list_string', ['allowed_values' => [
  'full-time'=>'Full-time','part-time'=>'Part-time','contract'=>'Contract','intern'=>'Intern'
]]);
wl_field_storage('node', 'field_job_location', 'string');
wl_field_storage('node', 'field_apply_url', 'link');
wl_field_instance('node', 'career', 'field_job_type', 'Employment Type');
wl_field_instance('node', 'career', 'field_job_location', 'Location');
wl_field_instance('node', 'career', 'field_apply_url', 'Apply URL');

/* ============================================================
 * 4) Form & View displays (fix link_default issue)
 * ============================================================ */
foreach (array_keys($bundles) as $b) {
  // FORM widgets (we’ll swap media widgets to EB after EB is created)
  $form = wl_form_display('node', $b);
  $form->setComponent('title', ['type' => 'string_textfield']);
  $form->setComponent('field_summary', ['type' => 'text_textarea', 'settings' => ['rows' => 3]]);
  $form->setComponent('body', ['type' => 'text_textarea_with_summary']);
  $form->setComponent('field_parent', ['type' => 'entity_reference_autocomplete']);
  $form->setComponent('field_template', ['type' => 'options_select']);
  $form->setComponent('field_theme_variant', ['type' => 'options_select']);

  // temp (Entity Browser swap later)
  $form->setComponent('field_hero_image', ['type' => 'entity_reference_autocomplete']);
  $form->setComponent('field_social_image', ['type' => 'entity_reference_autocomplete']);

  $form->setComponent('field_primary_cta', ['type' => 'link_default']);
  $form->setComponent('field_secondary_cta', ['type' => 'link_default']);
  $form->setComponent('field_related', ['type' => 'entity_reference_autocomplete']);
  $form->setComponent('field_seo_title', ['type' => 'string_textfield']);
  $form->setComponent('field_meta_description', ['type' => 'string_textfield']);
  $form->setComponent('field_canonical', ['type' => 'link_default']);
  $form->setComponent('field_noindex', ['type' => 'boolean_checkbox']);
  $form->setComponent('field_breadcrumb_label', ['type' => 'string_textfield']);
  $form->setComponent('field_reviewed_on', ['type' => 'datetime_default']);
  $form->setComponent('field_visibility', ['type' => 'options_select']);
  $form->setComponent('field_revalidate_ttl', ['type' => 'number']);
  $form->setComponent('field_cache_tags', ['type' => 'string_textfield']);
  $form->setComponent('field_preview_token', ['type' => 'string_textfield']);
  $form->setComponent('field_campaign', ['type' => 'string_textfield']);
  $form->setComponent('field_ab_variant', ['type' => 'options_select']);
  $form->setComponent('field_show_toc', ['type' => 'boolean_checkbox']);
  $form->setComponent('field_read_time', ['type' => 'number']);
  $form->setComponent('field_taxonomy', ['type' => 'entity_reference_autocomplete']);
  if ($b === 'landing_page') {
    $form->setComponent('field_components', ['type' => 'paragraphs']);
  }
  if ($b === 'career') {
    $form->setComponent('field_job_type', ['type' => 'options_select']);
    $form->setComponent('field_job_location', ['type' => 'string_textfield']);
    $form->setComponent('field_apply_url', ['type' => 'link_default']);
  }
  $form->save();

  // VIEW formatters (never use link_default here)
  $view = wl_view_display('node', $b);
  $view->setComponent('field_summary', ['type' => 'text_default', 'label' => 'hidden']);
  $view->setComponent('body', ['type' => 'text_default', 'label' => 'hidden']);
  $view->setComponent('field_hero_image', ['type' => 'entity_reference_entity_view', 'label' => 'hidden']);
  $view->removeComponent('field_social_image');
  $view->setComponent('field_primary_cta', ['type' => 'link', 'label' => 'above']);
  $view->setComponent('field_secondary_cta', ['type' => 'link', 'label' => 'above']);
  $view->setComponent('field_related', ['type' => 'entity_reference_label', 'settings' => ['link' => TRUE]]);
  $view->setComponent('field_canonical', ['type' => 'link']);
  if ($b === 'landing_page') {
    $view->setComponent('field_components', ['type' => 'entity_reference_revisions_entity_view']);
  }
  if ($b === 'career') {
    $view->setComponent('field_job_type', ['type' => 'list_default', 'label' => 'inline']);
    $view->setComponent('field_job_location', ['type' => 'string', 'label' => 'inline']);
    $view->setComponent('field_apply_url', ['type' => 'link', 'label' => 'inline']);
  }
  $view->save();
}

/* Paragraph form widgets (temp; swap to EB later) */
foreach (array_keys($paragraphs) as $pb) {
  $pform = wl_form_display('paragraph', $pb);
  if ($pb === 'p_hero')       { $pform->setComponent('field_media', ['type'=>'entity_reference_autocomplete']); }
  if ($pb === 'p_feature')    { $pform->setComponent('field_icon',  ['type'=>'entity_reference_autocomplete']); }
  if ($pb === 'p_logo_wall')  { $pform->setComponent('field_logos', ['type'=>'entity_reference_autocomplete']); }
  if ($pb === 'p_testimonial'){ $pform->setComponent('field_icon',  ['type'=>'entity_reference_autocomplete']); }
  $pform->save();
}

/* ============================================================
 * 5) Pathauto patterns
 * ============================================================ */
if (\Drupal::moduleHandler()->moduleExists('pathauto')) {
  $patterns = [
    'basic_page'   => '[menu:path]/[node:title]', // menu driven
    'landing_page' => '/l/[node:title]',
    'article'      => '/news/[node:created:custom:Y]/[node:created:custom:m]/[node:title]', // keep bundle "article"
    'service'      => '/services/[node:title]',
    'case_study'   => '/work/[node:title]',
    'resource'     => '/resources/[node:title]',
    'event'        => '/events/[node:title]',
    'career'       => '/careers/[node:title]',
  ];
  foreach ($patterns as $bundle => $patternStr) {
    $id = "pathauto_$bundle";
    $pattern = PathautoPattern::load($id) ?: PathautoPattern::create([
      'id' => $id,
      'label' => ucfirst(str_replace('_', ' ', $bundle)) . ' pattern',
      'pattern' => $patternStr,
      'type' => 'canonical_entities:node',
    ]);
    $pattern->addSelectionCondition([
      'id' => 'entity_bundle:node',
      'bundles' => [$bundle],
      'negate' => FALSE,
    ]);
    $pattern->save();
  }
}

/* ============================================================
 * 6) Image styles for Next.js
 * ============================================================ */
$maxWidths = [320, 640, 768, 1024, 1280, 1536, 1920, 2560];
foreach ($maxWidths as $w) {
  wl_ensure_image_style("nx_max_$w", "Next Max $w", [
    ['id' => 'image_scale', 'data' => ['width' => $w, 'upscale' => FALSE], 'weight' => 1],
  ]);
  wl_ensure_image_style("nx_max_{$w}_webp", "Next Max {$w} (WebP)", [
    ['id' => 'image_scale', 'data' => ['width' => $w, 'upscale' => FALSE], 'weight' => 1],
    ['id' => 'image_convert', 'data' => ['extension' => 'webp'], 'weight' => 100],
  ]);
}
function wl_cover($name, $label, $width, $ratioW, $ratioH, $webp = FALSE) {
  $height = (int) round($width * $ratioH / $ratioW);
  $effects = [
    ['id' => 'image_scale_and_crop', 'data' => ['width' => $width, 'height' => $height, 'anchor' => 'center-center'], 'weight' => 1],
  ];
  if ($webp) {
    $effects[] = ['id' => 'image_convert', 'data' => ['extension' => 'webp'], 'weight' => 100];
  }
  wl_ensure_image_style($name, $label, $effects);
}
foreach ([640, 1024, 1600] as $w) { wl_cover("nx_cover_16x9_$w", "Next Cover 16x9 $w", $w, 16, 9, FALSE); wl_cover("nx_cover_16x9_{$w}_webp", "Next Cover 16x9 $w (WebP)", $w, 16, 9, TRUE); }
foreach ([640, 1024] as $w)      { wl_cover("nx_cover_4x3_$w",  "Next Cover 4x3 $w",  $w, 4, 3, FALSE); wl_cover("nx_cover_4x3_{$w}_webp",  "Next Cover 4x3 $w (WebP)",  $w, 4, 3, TRUE); }
foreach ([200, 400, 800] as $s)  { wl_cover("nx_square_$s",     "Next Square $s",     $s, 1, 1, FALSE); wl_cover("nx_square_{$s}_webp",     "Next Square $s (WebP)",     $s, 1, 1, TRUE); }

/* ============================================================
 * 7) Entity Browsers + swap widgets
 * ============================================================ *
 * Two browsers:
 *  - media_image_browser: Images only (image, svg_image, icon)
 *  - media_generic_browser: All media bundles (images, video, audio, document)
 * Both use iFrame display with a Media Library widget.
 */
$cfg = \Drupal::service('config.factory');

// media_image_browser
if (!\Drupal::config('entity_browser.browser.media_image_browser')->getRawData()) {
  $uuid = \Drupal::service('uuid')->generate();
  $cfg->getEditable('entity_browser.browser.media_image_browser')
    ->setData([
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => ['module' => ['entity_browser','media_library','media']],
      'name' => 'media_image_browser',
      'label' => 'Media Image Browser',
      'display' => 'iframe',
      'display_configuration' => ['link_text' => 'Select images','width'=>1024,'height'=>700,'autoclose'=>TRUE],
      'selection_display' => 'no_display',
      'selection_display_configuration' => [],
      'widget_selector' => 'tabs',
      'widget_selector_configuration' => [],
      'widgets' => [[
        'id' => 'media_library',
        'label' => 'Media Library (Images)',
        'weight' => 0,
        'uuid' => $uuid,
        'settings' => [
          'media_types' => ['image'=>'image','svg_image'=>'svg_image','icon'=>'icon'],
          'multiple' => TRUE,
        ],
      ]],
    ])->save();
}

// media_generic_browser
if (!\Drupal::config('entity_browser.browser.media_generic_browser')->getRawData()) {
  $uuid = \Drupal::service('uuid')->generate();
  $cfg->getEditable('entity_browser.browser.media_generic_browser')
    ->setData([
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => ['module' => ['entity_browser','media_library','media']],
      'name' => 'media_generic_browser',
      'label' => 'Media Generic Browser',
      'display' => 'iframe',
      'display_configuration' => ['link_text' => 'Select media','width'=>1200,'height'=>800,'autoclose'=>TRUE],
      'selection_display' => 'no_display',
      'selection_display_configuration' => [],
      'widget_selector' => 'tabs',
      'widget_selector_configuration' => [],
      'widgets' => [[
        'id' => 'media_library',
        'label' => 'Media Library (All)',
        'weight' => 0,
        'uuid' => $uuid,
        'settings' => [
          'media_types' => [
            'image'=>'image','svg_image'=>'svg_image','icon'=>'icon',
            'video_file'=>'video_file','remote_video'=>'remote_video',
            'audio'=>'audio','document'=>'document'
          ],
          'multiple' => TRUE,
        ],
      ]],
    ])->save();
}

/* Swap media widgets to use Entity Browser */
$widget = 'entity_browser_entity_reference';
$img_browser_settings_single = [
  'entity_browser' => 'media_image_browser',
  'field_widget_display' => 'rendered_entity',
  'field_widget_edit' => FALSE,
  'field_widget_remove' => TRUE,
  'open' => TRUE,
  'selection_mode' => 'selection_replace',
  'auto_open' => FALSE,
];
$img_browser_settings_multi = $img_browser_settings_single;
$img_browser_settings_multi['selection_mode'] = 'selection_append';

$gen_browser_settings_single = $img_browser_settings_single;
$gen_browser_settings_single['entity_browser'] = 'media_generic_browser';
$gen_browser_settings_multi = $img_browser_settings_multi;
$gen_browser_settings_multi['entity_browser'] = 'media_generic_browser';

// Node: hero/social (single) → images browser
foreach (array_keys($bundles) as $b) {
  $form = wl_form_display('node', $b);
  $form->setComponent('field_hero_image', ['type' => $widget, 'settings' => $img_browser_settings_single]);
  $form->setComponent('field_social_image', ['type' => $widget, 'settings' => $img_browser_settings_single]);
  $form->save();
}

// Paragraphs:
// - p_hero.field_media (single) → images browser
// - p_feature.field_icon (single) → images browser
// - p_testimonial.field_icon (single) → images browser
// - p_logo_wall.field_logos (multi) → images browser (append)
$pform = wl_form_display('paragraph', 'p_hero');        $pform->setComponent('field_media', ['type'=>$widget,'settings'=>$img_browser_settings_single]); $pform->save();
$pform = wl_form_display('paragraph', 'p_feature');     $pform->setComponent('field_icon',  ['type'=>$widget,'settings'=>$img_browser_settings_single]); $pform->save();
$pform = wl_form_display('paragraph', 'p_testimonial'); $pform->setComponent('field_icon',  ['type'=>$widget,'settings'=>$img_browser_settings_single]); $pform->save();
$pform = wl_form_display('paragraph', 'p_logo_wall');   $pform->setComponent('field_logos', ['type'=>$widget,'settings'=>$img_browser_settings_multi]);  $pform->save();

/* ============================================================
 * 8) Rebuild & clear caches
 * ============================================================ */
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache.bootstrap')->invalidateAll();
\Drupal::service('cache.render')->invalidateAll();

\Drupal::messenger()->addStatus('WL full bootstrap complete: content, paragraphs, media bundles, entity browsers, widgets, pathauto, and Next.js image styles configured.');
