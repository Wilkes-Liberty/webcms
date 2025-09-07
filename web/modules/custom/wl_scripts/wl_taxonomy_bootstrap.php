<?php

/**
 * WL Taxonomy bootstrap for headless Drupal 11 → Next.js.
 *
 * - Creates vocabularies (hierarchical + flat)
 * - Adds SEO/media/navigation fields on terms
 * - Sets Pathauto patterns for taxonomy term pages
 * - Adds node term-reference fields (primary vs. facets) per bundle
 * - Grants reasonable taxonomy permissions to roles
 *
 * Safe to re-run (idempotent).
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\image\Entity\ImageStyle;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;

/* -------------------------------------------------------
 * Prereqs
 * ------------------------------------------------------- */
$installer = \Drupal::service('module_installer');
$need = ['taxonomy', 'media', 'media_library', 'pathauto'];
$installer->install(array_values(array_filter($need, fn($m) => !\Drupal::moduleHandler()->moduleExists($m))));

/* -------------------------------------------------------
 * Helpers
 * ------------------------------------------------------- */
function wl_voc($vid, $label, $hierarchy = 0) {
  $v = Vocabulary::load($vid);
  if (!$v) {
    $v = Vocabulary::create(['vid' => $vid, 'name' => $label, 'hierarchy' => $hierarchy]);
    $v->save();
  } else {
    // Keep hierarchy aligned (0 = none, 1 = single parent, 2 = multiple)
    if ((int) $v->get('hierarchy') !== (int) $hierarchy) {
      $v->set('hierarchy', (int) $hierarchy)->save();
    }
  }
  return $v;
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
    ?: EntityFormDisplay::create(['targetEntityType'=>$entity,'bundle'=>$bundle,'mode'=>'default','status'=>TRUE]);
}
function wl_view_display($entity, $bundle) {
  return EntityViewDisplay::load("$entity.$bundle.default")
    ?: EntityViewDisplay::create(['targetEntityType'=>$entity,'bundle'=>$bundle,'mode'=>'default','status'=>TRUE]);
}
function wl_pathauto_pattern($id, $label, $pattern, $type, array $bundles) {
  $pat = PathautoPattern::load($id);
  if (!$pat) {
    $pat = PathautoPattern::create([
      'id' => $id, 'label' => $label, 'pattern' => $pattern, 'type' => $type,
    ]);
  } else {
    $pat->set('pattern', $pattern);
  }
  // Selection criteria: by bundle
  $pat->setSelectionCriteria([[
    'id' => 'entity_bundle:'.($type === 'canonical_entities:taxonomy_term' ? 'taxonomy_term' : 'node'),
    'bundles' => array_combine($bundles, $bundles),
    'negate' => FALSE,
    'provider' => 'entity',
    'uuid' => \Drupal::service('uuid')->generate(),
  ]]);
  $pat->save();
}
function wl_role_grant_if_exists($role_id, array $perms) {
  $role = Role::load($role_id); if (!$role) return;
  $avail = array_keys(\Drupal::service('user.permissions')->getPermissions());
  foreach ($perms as $p) {
    if (in_array($p, $avail, TRUE)) $role->grantPermission($p);
  }
  $role->save();
}
function wl_role_revoke($role_id, array $perms) {
  $role = Role::load($role_id); if (!$role) return;
  foreach ($perms as $p) { $role->revokePermission($p); }
  $role->save();
}

/* -------------------------------------------------------
 * 1) Vocabularies
 * ------------------------------------------------------- */
$hier = [
  'services'   => 'Services',
  'industries' => 'Industries',
  'use_cases'  => 'Use Cases',
];
$flat = [
  'topics'        => 'Topics',
  'tech_stack'    => 'Tech Stack',
  'persona'       => 'Persona',
  'resource_type' => 'Resource Type',
  'compliance'    => 'Compliance',
  'department'    => 'Department',
  'seniority'     => 'Seniority',
  'event_type'    => 'Event Type',
];
foreach ($hier as $vid => $label) { wl_voc($vid, $label, 1); }
foreach ($flat as $vid => $label) { wl_voc($vid, $label, 0); }

/* -------------------------------------------------------
 * 2) Term fields (shared storages, attached per-vocab)
 * ------------------------------------------------------- */
wl_field_storage('taxonomy_term', 'field_seo_title',        'string');
wl_field_storage('taxonomy_term', 'field_meta_description', 'string');
wl_field_storage('taxonomy_term', 'field_hero_image',       'entity_reference', ['target_type' => 'media']);
wl_field_storage('taxonomy_term', 'field_icon',             'entity_reference', ['target_type' => 'media']);
wl_field_storage('taxonomy_term', 'field_synonyms',         'string', [], ['cardinality' => -1]);
wl_field_storage('taxonomy_term', 'field_show_in_nav',      'boolean');
wl_field_storage('taxonomy_term', 'field_cta_links',        'link', [], ['cardinality' => -1]);
wl_field_storage('taxonomy_term', 'field_external_canonical','link');

$all_vocabs = array_merge(array_keys($hier), array_keys($flat));
foreach ($all_vocabs as $vid) {
  wl_field_instance('taxonomy_term', $vid, 'field_seo_title',        'SEO Title');
  wl_field_instance('taxonomy_term', $vid, 'field_meta_description', 'Meta Description');
  wl_field_instance('taxonomy_term', $vid, 'field_hero_image',       'Hero Image', [
    'handler' => 'default:media',
    'handler_settings' => ['target_bundles' => ['image'=>'image','svg_image'=>'svg_image','icon'=>'icon']],
  ]);
  wl_field_instance('taxonomy_term', $vid, 'field_icon',             'Icon', [
    'handler' => 'default:media',
    'handler_settings' => ['target_bundles' => ['image'=>'image','svg_image'=>'svg_image','icon'=>'icon']],
  ]);
  wl_field_instance('taxonomy_term', $vid, 'field_synonyms',         'Synonyms / Aliases');
  wl_field_instance('taxonomy_term', $vid, 'field_show_in_nav',      'Show in navigation?');
  wl_field_instance('taxonomy_term', $vid, 'field_cta_links',        'Default CTA Links');
  wl_field_instance('taxonomy_term', $vid, 'field_external_canonical','External Canonical URL');
}

/* Form widgets for term fields */
$has_eb  = \Drupal::moduleHandler()->moduleExists('entity_browser');
$has_ml  = \Drupal::moduleHandler()->moduleExists('media_library');
$media_widget = $has_eb ? 'entity_browser_entity_reference' : ($has_ml ? 'media_library_widget' : 'entity_reference_autocomplete');
$media_single  = $has_eb ? [
  'entity_browser' => 'media_image_browser', // from your earlier setup
  'field_widget_display' => 'rendered_entity',
  'field_widget_edit' => FALSE,
  'field_widget_remove' => TRUE,
  'open' => TRUE,
  'selection_mode' => 'selection_replace',
] : ($has_ml ? ['media_types' => ['image'=>'image','svg_image'=>'svg_image','icon'=>'icon'], 'open' => FALSE] : []);

foreach ($all_vocabs as $vid) {
  $form = wl_form_display('taxonomy_term', $vid);
  $form->setComponent('name', ['type' => 'string_textfield']);
  $form->setComponent('description', ['type' => 'text_textarea', 'settings' => ['rows' => 4]]);
  $form->setComponent('field_seo_title', ['type' => 'string_textfield']);
  $form->setComponent('field_meta_description', ['type' => 'string_textfield']);
  $form->setComponent('field_hero_image', ['type' => $media_widget, 'settings' => $media_single]);
  $form->setComponent('field_icon', ['type' => $media_widget, 'settings' => $media_single]);
  $form->setComponent('field_synonyms', ['type' => 'string_textfield']);
  $form->setComponent('field_show_in_nav', ['type' => 'boolean_checkbox']);
  $form->setComponent('field_cta_links', ['type' => 'link_default']);
  $form->setComponent('field_external_canonical', ['type' => 'link_default']);
  $form->save();

  // Minimal view display (mostly for admin previews; headless uses GraphQL)
  $view = wl_view_display('taxonomy_term', $vid);
  $view->setComponent('description', ['type' => 'text_default', 'label' => 'hidden']);
  $view->setComponent('field_hero_image', ['type' => 'entity_reference_entity_view', 'label' => 'hidden']);
  $view->setComponent('field_icon', ['type' => 'entity_reference_entity_view', 'label' => 'hidden']);
  $view->setComponent('field_cta_links', ['type' => 'link', 'label' => 'above']);
  $view->save();
}

/* -------------------------------------------------------
 * 3) Pathauto patterns (taxonomy term pages)
 * ------------------------------------------------------- */
if (\Drupal::moduleHandler()->moduleExists('pathauto')) {
  // Hierarchical: include parent path
  wl_pathauto_pattern('taxo_services',   'Services terms',   '/services/[term:parents:join-path]/[term:name]', 'canonical_entities:taxonomy_term', ['services']);
  wl_pathauto_pattern('taxo_industries', 'Industries terms', '/industries/[term:parents:join-path]/[term:name]', 'canonical_entities:taxonomy_term', ['industries']);
  wl_pathauto_pattern('taxo_use_cases',  'Use cases terms',  '/solutions/[term:parents:join-path]/[term:name]', 'canonical_entities:taxonomy_term', ['use_cases']);
  // Flat
  wl_pathauto_pattern('taxo_topics',        'Topics terms',        '/topics/[term:name]', 'canonical_entities:taxonomy_term', ['topics']);
  wl_pathauto_pattern('taxo_tech_stack',    'Tech Stack terms',    '/tech/[term:name]',   'canonical_entities:taxonomy_term', ['tech_stack']);
  wl_pathauto_pattern('taxo_persona',       'Persona terms',       '/personas/[term:name]', 'canonical_entities:taxonomy_term', ['persona']);
  wl_pathauto_pattern('taxo_resource_type', 'Resource Type terms', '/resources/type/[term:name]', 'canonical_entities:taxonomy_term', ['resource_type']);
  wl_pathauto_pattern('taxo_compliance',    'Compliance terms',    '/compliance/[term:name]', 'canonical_entities:taxonomy_term', ['compliance']);
  wl_pathauto_pattern('taxo_department',    'Department terms',    '/careers/department/[term:name]', 'canonical_entities:taxonomy_term', ['department']);
  wl_pathauto_pattern('taxo_seniority',     'Seniority terms',     '/careers/seniority/[term:name]', 'canonical_entities:taxonomy_term', ['seniority']);
  wl_pathauto_pattern('taxo_event_type',    'Event Type terms',    '/events/type/[term:name]', 'canonical_entities:taxonomy_term', ['event_type']);
}

/* -------------------------------------------------------
 * 4) Node fields (primary vs. facet terms)
 * ------------------------------------------------------- */
$bundles = [
  'basic_page','landing_page','article','service','case_study','resource','event','career',
];

// Field storages (taxonomy references)
wl_field_storage('node', 'field_primary_service', 'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => 1]);
wl_field_storage('node', 'field_industry',        'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => 1]);
wl_field_storage('node', 'field_use_cases',       'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => -1]);
wl_field_storage('node', 'field_topics',          'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => -1]);
wl_field_storage('node', 'field_tech_stack',      'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => -1]);
wl_field_storage('node', 'field_personas',        'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => -1]);
wl_field_storage('node', 'field_resource_type',   'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => 1]);
wl_field_storage('node', 'field_event_type',      'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => 1]);
wl_field_storage('node', 'field_compliance',      'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => -1]);
wl_field_storage('node', 'field_department',      'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => 1]);
wl_field_storage('node', 'field_seniority',       'entity_reference', ['target_type' => 'taxonomy_term'], ['cardinality' => 1]);

// Attach to bundles with target bundle restrictions
$eref_services   = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['services'=>'services']]];
$eref_industries = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['industries'=>'industries']]];
$eref_use_cases  = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['use_cases'=>'use_cases']]];
$eref_topics     = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['topics'=>'topics']]];
$eref_tech       = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['tech_stack'=>'tech_stack']]];
$eref_persona    = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['persona'=>'persona']]];
$eref_res_type   = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['resource_type'=>'resource_type']]];
$eref_event_type = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['event_type'=>'event_type']]];
$eref_compliance = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['compliance'=>'compliance']]];
$eref_dept       = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['department'=>'department']]];
$eref_seniority  = ['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>['seniority'=>'seniority']]];

// Map fields to bundles
foreach ($bundles as $b) {
  // Common facets
  wl_field_instance('node', $b, 'field_topics',     'Topics',     $eref_topics);
  wl_field_instance('node', $b, 'field_tech_stack', 'Tech Stack', $eref_tech);

  // Service & Use cases (most bundles)
  if ($b !== 'service') {
    wl_field_instance('node', $b, 'field_primary_service', 'Primary Service', $eref_services);
  }
  wl_field_instance('node', $b, 'field_use_cases', 'Use Cases', $eref_use_cases);

  // Industry relevant bundles
  if (in_array($b, ['article','case_study','resource','event','basic_page','landing_page'], TRUE)) {
    wl_field_instance('node', $b, 'field_industry', 'Primary Industry', $eref_industries);
  }

  // Personas for content targeting
  if (in_array($b, ['article','resource','event','basic_page','landing_page'], TRUE)) {
    wl_field_instance('node', $b, 'field_personas', 'Personas', $eref_persona);
  }

  // Resource Type only on Resource
  if ($b === 'resource') {
    wl_field_instance('node', $b, 'field_resource_type', 'Resource Type', $eref_res_type);
  }

  // Event Type only on Event
  if ($b === 'event') {
    wl_field_instance('node', $b, 'field_event_type', 'Event Type', $eref_event_type);
  }

  // Careers
  if ($b === 'career') {
    wl_field_instance('node', $b, 'field_department', 'Department', $eref_dept);
    wl_field_instance('node', $b, 'field_seniority',  'Seniority',  $eref_seniority);
  }

  // Compliance often for resources/case studies/basic pages
  if (in_array($b, ['resource','case_study','basic_page','landing_page','article'], TRUE)) {
    wl_field_instance('node', $b, 'field_compliance', 'Compliance', $eref_compliance);
  }
}

/* Node form display wiring (simple widgets) */
foreach ($bundles as $b) {
  $form = wl_form_display('node', $b);
  foreach ([
             'field_primary_service','field_industry','field_use_cases','field_topics','field_tech_stack',
             'field_personas','field_resource_type','field_event_type','field_compliance','field_department','field_seniority'
           ] as $f) {
    if (FieldConfig::load("node.$b.$f")) {
      $form->setComponent($f, ['type' => 'entity_reference_autocomplete']);
    }
  }
  $form->save();

  // Minimal view display (label-link for sanity; headless uses GraphQL)
  $view = wl_view_display('node', $b);
  foreach ([
             'field_primary_service','field_industry','field_use_cases','field_topics','field_tech_stack',
             'field_personas','field_resource_type','field_event_type','field_compliance','field_department','field_seniority'
           ] as $f) {
    if (FieldConfig::load("node.$b.$f")) {
      $view->setComponent($f, ['type' => 'entity_reference_label', 'label' => 'above', 'settings' => ['link' => TRUE]]);
    }
  }
  $view->save();
}

/* -------------------------------------------------------
 * 5) Permissions (taxonomy governance)
 * ------------------------------------------------------- */
// Build per-vocabulary term permission strings
$perm_sets = [];
foreach ($all_vocabs as $vid) {
  $perm_sets[$vid] = [
    "create terms in $vid",
    "edit terms in $vid",
    "delete terms in $vid",
  ];
}
// Grant to Content Editor & SEO Manager; revoke from Content Author
wl_role_grant_if_exists('content_editor', array_merge(['access taxonomy overview'], ...array_values($perm_sets)));
wl_role_grant_if_exists('seo_manager',    array_merge(['access taxonomy overview'], ...array_values($perm_sets)));
$revoke = [];
foreach ($perm_sets as $vid => $perms) { $revoke = array_merge($revoke, $perms); }
wl_role_revoke('content_author', $revoke);

/* -------------------------------------------------------
 * 6) Cache rebuild
 * ------------------------------------------------------- */
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache.bootstrap')->invalidateAll();
\Drupal::service('cache.render')->invalidateAll();

\Drupal::messenger()->addStatus('WL taxonomy bootstrap complete: vocabularies, term fields, node fields, pathauto, and permissions configured.');
