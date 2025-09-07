<?php

/**
 * WL Taxonomy FULL (bootstrap + add-ons) for headless Drupal 11 → Next.js.
 *
 * - Creates vocabularies (hierarchical + flat)
 * - Adds SEO/media/navigation fields on terms (hero image, icon, synonyms, CTAs)
 * - Wires term form/view displays (Entity Browser -> Media Library -> Autocomplete)
 * - Sets Pathauto patterns for taxonomy term pages
 * - Adds node term-reference fields (primary vs. facet) per bundle
 * - Grants taxonomy permissions (Editors/SEO manage; Authors cannot)
 * - ADD-ONS:
 *     * Enables Focal Point + creates focal-point image styles (with WebP variants)
 *     * Creates/enables `wl_taxo_nav` module to sync top-level Services/Industries
 *       terms (with "Show in navigation?") to the Main menu; runs initial sync
 *
 * Safe to re-run.
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
$need = [
  'taxonomy',
  'media',
  'media_library',
  'pathauto',
  'menu_ui',
  'menu_link_content',
  'focal_point', // add-on
];
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
    $pat->save();
    
    // Add selection condition for entity bundles
    $entity_type = ($type === 'canonical_entities:taxonomy_term') ? 'taxonomy_term' : 'node';
    $pat->addSelectionCondition([
      'id' => 'entity_bundle:' . $entity_type,
      'bundles' => array_combine($bundles, $bundles),
      'negate' => FALSE,
      'context_mapping' => [
        $entity_type => $entity_type,
      ],
    ]);
  } else {
    $pat->set('pattern', $pattern);
  }
  $pat->save();
}
function wl_role_grant_if_exists($role_id, array $perms) {
  $role = Role::load($role_id); if (!$role) return;
  $avail = array_keys(\Drupal::service('user.permissions')->getPermissions());
  foreach ($perms as $p) { if (in_array($p, $avail, TRUE)) $role->grantPermission($p); }
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
$all_vocabs = array_merge(array_keys($hier), array_keys($flat));

/* -------------------------------------------------------
 * 2) Term fields (SEO + media + UX)
 * ------------------------------------------------------- */
wl_field_storage('taxonomy_term', 'field_seo_title',        'string');
wl_field_storage('taxonomy_term', 'field_meta_description', 'string');
wl_field_storage('taxonomy_term', 'field_hero_image',       'entity_reference', ['target_type' => 'media']);
wl_field_storage('taxonomy_term', 'field_icon',             'entity_reference', ['target_type' => 'media']);
wl_field_storage('taxonomy_term', 'field_synonyms',         'string', [], ['cardinality' => -1]);
wl_field_storage('taxonomy_term', 'field_show_in_nav',      'boolean');
wl_field_storage('taxonomy_term', 'field_cta_links',        'link', [], ['cardinality' => -1]);
wl_field_storage('taxonomy_term', 'field_external_canonical','link');

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

/* Term form/view displays */
$has_eb = \Drupal::moduleHandler()->moduleExists('entity_browser');
$has_ml = \Drupal::moduleHandler()->moduleExists('media_library');
$media_widget = $has_eb ? 'entity_browser_entity_reference' : ($has_ml ? 'media_library_widget' : 'entity_reference_autocomplete');
$media_single = $has_eb ? [
  'entity_browser' => 'media_image_browser', // from earlier setup
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
  wl_pathauto_pattern('taxo_services',   'Services terms',   '/services/[term:parents:join-path]/[term:name]', 'canonical_entities:taxonomy_term', ['services']);
  wl_pathauto_pattern('taxo_industries', 'Industries terms', '/industries/[term:parents:join-path]/[term:name]', 'canonical_entities:taxonomy_term', ['industries']);
  wl_pathauto_pattern('taxo_use_cases',  'Use cases terms',  '/solutions/[term:parents:join-path]/[term:name]', 'canonical_entities:taxonomy_term', ['use_cases']);
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
 * 4) Node fields (primary vs. facets)
 * ------------------------------------------------------- */
$bundles = [
  'basic_page','landing_page','article','service','case_study','resource','event','career',
];
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

foreach ($bundles as $b) {
  wl_field_instance('node', $b, 'field_topics',     'Topics',     $eref_topics);
  wl_field_instance('node', $b, 'field_tech_stack', 'Tech Stack', $eref_tech);

  if ($b !== 'service') {
    wl_field_instance('node', $b, 'field_primary_service', 'Primary Service', $eref_services);
  }
  wl_field_instance('node', $b, 'field_use_cases', 'Use Cases', $eref_use_cases);

  if (in_array($b, ['article','case_study','resource','event','basic_page','landing_page'], TRUE)) {
    wl_field_instance('node', $b, 'field_industry', 'Primary Industry', $eref_industries);
  }
  if (in_array($b, ['article','resource','event','basic_page','landing_page'], TRUE)) {
    wl_field_instance('node', $b, 'field_personas', 'Personas', $eref_persona);
  }
  if ($b === 'resource') {
    wl_field_instance('node', $b, 'field_resource_type', 'Resource Type', $eref_res_type);
  }
  if ($b === 'event') {
    wl_field_instance('node', $b, 'field_event_type', 'Event Type', $eref_event_type);
  }
  if ($b === 'career') {
    wl_field_instance('node', $b, 'field_department', 'Department', $eref_dept);
    wl_field_instance('node', $b, 'field_seniority',  'Seniority',  $eref_seniority);
  }
  if (in_array($b, ['resource','case_study','basic_page','landing_page','article'], TRUE)) {
    wl_field_instance('node', $b, 'field_compliance', 'Compliance', $eref_compliance);
  }
}

/* Node form/view displays */
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
$perm_sets = [];
foreach ($all_vocabs as $vid) {
  $perm_sets[$vid] = ["create terms in $vid","edit terms in $vid","delete terms in $vid"];
}
wl_role_grant_if_exists('content_editor', array_merge(['access taxonomy overview'], ...array_values($perm_sets)));
wl_role_grant_if_exists('seo_manager',    array_merge(['access taxonomy overview'], ...array_values($perm_sets)));
$revoke = [];
foreach ($perm_sets as $vid => $perms) { $revoke = array_merge($revoke, $perms); }
wl_role_revoke('content_author', $revoke);

/* -------------------------------------------------------
 * 6) ADD-ON: Focal Point image styles (with WebP)
 * ------------------------------------------------------- */
function wl_ensure_image_style($name, $label, array $effects) {
  $style = ImageStyle::load($name);
  if (!$style) { 
    $style = ImageStyle::create(['name' => $name, 'label' => $label]); 
    $style->save(); 
  } else {
    // Remove existing effects
    foreach ($style->getEffects() as $effect) {
      $style->deleteImageEffect($effect);
    }
  }
  foreach ($effects as $ef) { 
    $style->addImageEffect(['id'=>$ef['id'],'weight'=>$ef['weight'] ?? 0,'data'=>$ef['data'] ?? []]); 
  }
  $style->save();
}
function wl_fp_cover($name, $label, $width, $ratioW, $ratioH, $webp = FALSE) {
  $height = (int) round($width * $ratioH / $ratioW);
  $effects = [['id' => 'focal_point_scale_and_crop','data' => ['width' => $width, 'height' => $height],'weight' => 1]];
  if ($webp) { $effects[] = ['id' => 'image_convert', 'data' => ['extension' => 'webp'], 'weight' => 100]; }
  wl_ensure_image_style($name, $label, $effects);
}
foreach ([640, 1024, 1600, 1920] as $w) {
  wl_fp_cover("nx_fp_cover_16x9_$w", "Next FP Cover 16x9 $w", $w, 16, 9, FALSE);
  wl_fp_cover("nx_fp_cover_16x9_{$w}_webp", "Next FP Cover 16x9 $w (WebP)", $w, 16, 9, TRUE);
}
foreach ([400, 800] as $s) {
  wl_fp_cover("nx_fp_square_$s", "Next FP Square $s", $s, 1, 1, FALSE);
  wl_fp_cover("nx_fp_square_{$s}_webp", "Next FP Square $s (WebP)", $s, 1, 1, TRUE);
}
foreach ([1920] as $w) {
  wl_fp_cover("nx_fp_cover_21x9_$w", "Next FP Cover 21x9 $w", $w, 21, 9, FALSE);
  wl_fp_cover("nx_fp_cover_21x9_{$w}_webp", "Next FP Cover 21x9 $w (WebP)", $w, 21, 9, TRUE);
}
// Use an FP style in Media › Image admin preview so editors see sensible crops.
$media_image_display = EntityViewDisplay::load('media.image.default')
  ?: EntityViewDisplay::create(['targetEntityType'=>'media','bundle'=>'image','mode'=>'default','status'=>TRUE]);
$media_image_display->setComponent('field_media_image', [
  'type' => 'image',
  'label' => 'hidden',
  'settings' => ['image_style' => 'nx_fp_cover_16x9_1024', 'image_link' => ''],
]);
$media_image_display->save();

/* -------------------------------------------------------
 * 7) ADD-ON: wl_taxo_nav (auto menu-sync for Services/Industries)
 * ------------------------------------------------------- */
$fs = \Drupal::service('file_system');
$module_dir = DRUPAL_ROOT . '/modules/custom/wl_taxo_nav';
if (!is_dir($module_dir)) { $fs->mkdir($module_dir, 0775, TRUE); }

$info_yml = <<<YML
name: WL Taxonomy Nav
type: module
description: Auto-sync top-level Services/Industries terms to the Main menu when "Show in navigation?" is checked.
core_version_requirement: ^10 || ^11
package: WilkesLiberty
dependencies:
  - drupal:taxonomy
  - drupal:menu_link_content
  - drupal:path
YML;

$module_php = <<<'PHP'
<?php

use Drupal\Core\Entity\EntityInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/** Which vocabularies to sync into the Main menu. */
function _wl_taxo_nav_allowed_vocabs(): array { return ['services', 'industries']; }

/** Is the term top-level (no parents)? */
function _wl_taxo_nav_is_top_level(\Drupal\taxonomy\TermInterface $term): bool {
  $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($term->id());
  return empty($parents);
}

/** Find an existing menu link created for a term (by stored tid). */
function _wl_taxo_nav_find_link_for_term(int $tid): ?MenuLinkContent {
  $ids = \Drupal::entityQuery('menu_link_content')->accessCheck(FALSE)->condition('menu_name', 'main')->execute();
  if (!$ids) return NULL;
  foreach (MenuLinkContent::loadMultiple($ids) as $link) {
    if ((int) $link->getThirdPartySetting('wl_taxo_nav', 'tid') === $tid) return $link;
  }
  return NULL;
}

/** Ensure menu link exists/updated or is removed as needed. */
function _wl_taxo_nav_sync_term(\Drupal\taxonomy\TermInterface $term): void {
  $vid = $term->bundle();
  if (!in_array($vid, _wl_taxo_nav_allowed_vocabs(), TRUE)) return;

  $show = (bool) ($term->get('field_show_in_nav')->value ?? FALSE);
  $top  = _wl_taxo_nav_is_top_level($term);
  $existing = _wl_taxo_nav_find_link_for_term((int) $term->id());

  if ($show && $top) {
    if (!$existing) {
      $existing = MenuLinkContent::create([
        'title' => $term->label(),
        'menu_name' => 'main',
        'link' => [
          'uri' => 'route:entity.taxonomy_term.canonical',
          'title' => $term->label(),
          'options' => ['route_parameters' => ['taxonomy_term' => (int) $term->id()]],
        ],
        'enabled' => TRUE,
        'weight' => 0,
      ]);
      $existing->setThirdPartySetting('wl_taxo_nav', 'tid', (int) $term->id());
    } else {
      $existing->set('title', $term->label());
      $existing->set('link', [
        'uri' => 'route:entity.taxonomy_term.canonical',
        'title' => $term->label(),
        'options' => ['route_parameters' => ['taxonomy_term' => (int) $term->id()]],
      ]);
      $existing->set('enabled', TRUE);
    }
    $existing->save();
  } else {
    if ($existing) { $existing->delete(); }
  }
}

/** Implements hook_entity_insert(). */
function wl_taxo_nav_entity_insert(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'taxonomy_term') { _wl_taxo_nav_sync_term($entity); }
}
/** Implements hook_entity_update(). */
function wl_taxo_nav_entity_update(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'taxonomy_term') { _wl_taxo_nav_sync_term($entity); }
}
/** Implements hook_entity_delete(). */
function wl_taxo_nav_entity_delete(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'taxonomy_term') {
    $existing = _wl_taxo_nav_find_link_for_term((int) $entity->id());
    if ($existing) { $existing->delete(); }
  }
}
PHP;

file_put_contents($module_dir . '/wl_taxo_nav.info.yml', $info_yml);
file_put_contents($module_dir . '/wl_taxo_nav.module', $module_php);

// Clear module discovery cache to recognize the new module
if (!\Drupal::moduleHandler()->moduleExists('wl_taxo_nav')) {
  \Drupal::service('extension.list.module')->reset();
  try {
    $installer->install(['wl_taxo_nav']);
  } catch (\Exception $e) {
    \Drupal::messenger()->addWarning('Could not install wl_taxo_nav module: ' . $e->getMessage());
  }
}

/* One-time initial sync for existing terms in Services/Industries */
$allowed = ['services', 'industries'];
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach ($allowed as $vid) {
  $tids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $vid)->execute();
  if ($tids) {
    $terms = $term_storage->loadMultiple($tids);
    foreach ($terms as $term) { _wl_taxo_nav_sync_term($term); }
  }
}

/* -------------------------------------------------------
 * 8) Cache rebuild
 * ------------------------------------------------------- */
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache.bootstrap')->invalidateAll();
\Drupal::service('cache.render')->invalidateAll();

\Drupal::messenger()->addStatus('WL taxonomy FULL: vocabs, fields, displays, pathauto, node fields, permissions, focal-point styles, and menu-sync done.');
