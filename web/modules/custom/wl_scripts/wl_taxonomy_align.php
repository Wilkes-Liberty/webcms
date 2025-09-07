<?php

/**
 * WL Taxonomy Align — prefer technologies/solutions/capabilities/categories,
 * migrate/merge terms, fix menus, standardize term fields, and de-duplicate tagging.
 *
 * Safe to re-run (idempotent). Start in DRY_RUN, then set to false.
 */

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\system\Entity\Menu;

const DRY_RUN = false; // <-- set to false to apply

/* =========================
 * Setup & helpers
 * ========================= */
$installer = \Drupal::service('module_installer');
$need = ['taxonomy','media','media_library','pathauto','menu_ui','menu_link_content','focal_point'];
$installer->install(array_values(array_filter($need, fn($m)=>!\Drupal::moduleHandler()->moduleExists($m))));

// Ensure Main menu exists
if (!Menu::load('main')) { if (!DRY_RUN) Menu::create(['id'=>'main','label'=>'Main navigation'])->save(); }

function say($m){ print $m.PHP_EOL; }
function load_vocab($vid){ return Vocabulary::load($vid); }
function ensure_vocab($vid,$label,$hierarchy=0){
  $v = load_vocab($vid);
  if (!$v) { $v = Vocabulary::create(['vid'=>$vid,'name'=>$label,'hierarchy'=>$hierarchy]); if (!DRY_RUN) $v->save(); }
  else { if ($v->get('hierarchy')!=$hierarchy && !DRY_RUN){ $v->set('hierarchy',$hierarchy)->save(); } }
  return Vocabulary::load($vid) ?: $v;
}
function ensure_field_storage($entity,$name,$type,$settings=[],$opts=[]){
  if (!FieldStorageConfig::load("$entity.$name") && !DRY_RUN) {
    FieldStorageConfig::create([
      'field_name'=>$name,'entity_type'=>$entity,'type'=>$type,'settings'=>$settings,
      'cardinality'=>$opts['cardinality']??1,'translatable'=>$opts['translatable']??TRUE,
    ])->save();
  }
}
function ensure_field_instance($entity,$bundle,$name,$label,$settings=[],$opts=[]){
  if (!FieldConfig::load("$entity.$bundle.$name") && !DRY_RUN) {
    FieldConfig::create([
      'field_name'=>$name,'entity_type'=>$entity,'bundle'=>$bundle,'label'=>$label,
      'required'=>$opts['required']??FALSE,'translatable'=>$opts['translatable']??TRUE,'settings'=>$settings,
      'description'=>$opts['description']??'',
    ])->save();
  }
}
function set_form_widget($entity,$bundle,$field,$type,$settings=[]){
  $fd = EntityFormDisplay::load("$entity.$bundle.default") ?: EntityFormDisplay::create(['targetEntityType'=>$entity,'bundle'=>$bundle,'mode'=>'default','status'=>TRUE]);
  $fd->setComponent($field,['type'=>$type,'settings'=>$settings]); if (!DRY_RUN) $fd->save();
}
function set_view_formatter($entity,$bundle,$field,$type,$settings=[],$label='above'){
  $vd = EntityViewDisplay::load("$entity.$bundle.default") ?: EntityViewDisplay::create(['targetEntityType'=>$entity,'bundle'=>$bundle,'mode'=>'default','status'=>TRUE]);
  $vd->setComponent($field,['type'=>$type,'settings'=>$settings,'label'=>$label]); if (!DRY_RUN) $vd->save();
}
function pathauto_for_vocab($id,$label,$pattern,$vid){
  $pat = PathautoPattern::load($id) ?: PathautoPattern::create(['id'=>$id,'label'=>$label,'type'=>'canonical_entities:taxonomy_term','pattern'=>$pattern]);
  $pat->set('pattern',$pattern);
  $pat->setSelectionCriteria([[
    'id'=>'entity_bundle:taxonomy_term','bundles'=>[$vid=>$vid],'negate'=>FALSE,'provider'=>'entity','uuid'=>\Drupal::service('uuid')->generate(),
  ]]);
  if (!DRY_RUN) $pat->save();
}
function find_term($vid,$name,$parent_tid=0):?Term{
  $q = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid',$vid)->condition('name',$name);
  $parent_tid ? $q->condition('parent.target_id',$parent_tid) : $q->notExists('parent.target_id');
  $ids=$q->range(0,1)->execute(); if (!$ids) return NULL; return Term::load(reset($ids));
}
function ensure_term_path($vid,array $names,array $copyFields=[]):Term{
  $parent=0; $term=NULL;
  foreach ($names as $i=>$name){
    $existing = find_term($vid,$name,$parent);
    if (!$existing) {
      $values=['vid'=>$vid,'name'=>$name]; if ($parent) $values['parent']=[['target_id'=>$parent]];
      $existing=Term::create($values); if (!DRY_RUN) $existing->save();
    }
    // On last segment, copy meta fields if provided
    if ($i===count($names)-1 && !DRY_RUN && $copyFields) {
      foreach (['field_show_in_nav','field_seo_title','field_meta_description','field_synonyms'] as $f) {
        if (isset($copyFields[$f])) { $existing->set($f,$copyFields[$f]); }
      }
      if (!empty($copyFields['field_external_canonical'])) {
        $existing->set('field_external_canonical',['uri'=>$copyFields['field_external_canonical']]);
      }
      $existing->save();
    }
    $term=$existing; $parent=(int)$existing->id();
  }
  return $term;
}
function ancestry_names(Term $t):array{
  $names=[]; $current=$t;
  while($current){
    array_unshift($names,$current->getName());
    $parents=\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($current->id());
    $current=$parents?reset($parents):NULL;
  }
  return $names;
}
function copy_term_fields(Term $src):array{
  $out=[];
  foreach (['field_show_in_nav','field_seo_title','field_meta_description','field_synonyms'] as $f) {
    if ($src->hasField($f)) { $out[$f]=$src->get($f)->getValue(); }
  }
  if ($src->hasField('field_external_canonical') && !$src->get('field_external_canonical')->isEmpty()) {
    $out['field_external_canonical']=$src->get('field_external_canonical')->uri;
  }
  return $out;
}

/* =========================
 * 1) Vocabularies (keep & prefer)
 * ========================= */
$keep = [
  // preferred
  'capabilities' => ['label'=>'Capabilities','hier'=>1],
  'solutions'    => ['label'=>'Solutions','hier'=>1],
  'technologies' => ['label'=>'Technologies','hier'=>1], // allow hierarchy (e.g., Drupal under CMS)
  'categories'   => ['label'=>'Categories','hier'=>0],
  // also keep
  'industries'   => ['label'=>'Industries','hier'=>1],
  'topics'       => ['label'=>'Topics','hier'=>0],
  'tech_stack'   => ['label'=>'Tech Stack','hier'=>0],
  'persona'      => ['label'=>'Persona','hier'=>0],
  'resource_type'=> ['label'=>'Resource Type','hier'=>0],
  'compliance'   => ['label'=>'Compliance','hier'=>0],
  'department'   => ['label'=>'Department','hier'=>0],
  'seniority'    => ['label'=>'Seniority','hier'=>0],
  'event_type'   => ['label'=>'Event Type','hier'=>0],
];
foreach ($keep as $vid=>$def) { ensure_vocab($vid,$def['label'],$def['hier']); }

/* =========================
 * 2) Merge Services → Capabilities; Use cases → Solutions
 * ========================= */
$merges = [
  'services'  => 'capabilities',
  'use_cases' => 'solutions',
];
foreach ($merges as $src=>$dst) {
  $sv = load_vocab($src);
  if (!$sv) { say("[=] '$src' not present, skipping merge."); continue; }
  ensure_vocab($dst, ucfirst($dst), in_array($dst,['capabilities','solutions']) ? 1 : 0);
  say("[*] Merging '$src' → '$dst'…");

  $ids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid',$src)->execute();
  if ($ids) {
    $storage=\Drupal::entityTypeManager()->getStorage('taxonomy_term');
    foreach ($storage->loadMultiple($ids) as $t) {
      $path=ancestry_names($t);
      $copy=copy_term_fields($t);
      $new=ensure_term_path($dst,$path,$copy);
      say("    - {$t->getName()} → {$new->getName()}");
    }
  }
  say("    [info] Source vocab '$src' is NOT deleted automatically (review first).");
}

/* =========================
 * 3) Standard term fields across kept vocabs
 * ========================= */
$termFields = [
  'field_seo_title'        => ['string', [], []],
  'field_meta_description' => ['string', [], []],
  'field_hero_image'       => ['entity_reference', ['target_type'=>'media'], []],
  'field_icon'             => ['entity_reference', ['target_type'=>'media'], []],
  'field_synonyms'         => ['string', [], ['cardinality'=>-1]],
  'field_show_in_nav'      => ['boolean', [], []],
  'field_cta_links'        => ['link', [], ['cardinality'=>-1]],
  'field_external_canonical'=>['link', [], []],
];
foreach ($termFields as $name=>[$type,$settings,$opts]) {
  ensure_field_storage('taxonomy_term',$name,$type,$settings,$opts);
  foreach (array_keys($keep) as $vid) {
    ensure_field_instance('taxonomy_term',$vid,$name, ucwords(str_replace('_',' ',substr($name,6))), $settings);
  }
}

/* Term form/view displays (consistent UX) */
$has_eb = \Drupal::moduleHandler()->moduleExists('entity_browser');
$has_ml = \Drupal::moduleHandler()->moduleExists('media_library');
$media_widget = $has_eb ? 'entity_browser_entity_reference' : ($has_ml ? 'media_library_widget' : 'entity_reference_autocomplete');
$media_settings = $has_eb ? [
  'entity_browser'=>'media_image_browser','field_widget_display'=>'rendered_entity','field_widget_edit'=>FALSE,'field_widget_remove'=>TRUE,'open'=>TRUE,'selection_mode'=>'selection_replace',
] : ($has_ml ? ['media_types'=>['image'=>'image','svg_image'=>'svg_image','icon'=>'icon'],'open'=>FALSE] : []);
foreach (array_keys($keep) as $vid) {
  set_form_widget('taxonomy_term',$vid,'name','string_textfield');
  set_form_widget('taxonomy_term',$vid,'description','text_textarea',['rows'=>4]);
  foreach (['field_seo_title','field_meta_description','field_synonyms'] as $f) set_form_widget('taxonomy_term',$vid,$f,'string_textfield');
  set_form_widget('taxonomy_term',$vid,'field_show_in_nav','boolean_checkbox');
  set_form_widget('taxonomy_term',$vid,'field_cta_links','link_default');
  set_form_widget('taxonomy_term',$vid,'field_external_canonical','link_default');
  set_form_widget('taxonomy_term',$vid,'field_hero_image',$media_widget,$media_settings);
  set_form_widget('taxonomy_term',$vid,'field_icon',$media_widget,$media_settings);

  set_view_formatter('taxonomy_term',$vid,'description','text_default',[], 'hidden');
  set_view_formatter('taxonomy_term',$vid,'field_hero_image','entity_reference_entity_view',[], 'hidden');
  set_view_formatter('taxonomy_term',$vid,'field_icon','entity_reference_entity_view',[], 'hidden');
  set_view_formatter('taxonomy_term',$vid,'field_cta_links','link',[], 'above');
}

/* =========================
 * 4) Pathauto patterns (model old URLs)
 * ========================= */
pathauto_for_vocab('taxo_capabilities','Capabilities terms','/services/[term:parents:join-path]/[term:name]','capabilities'); // keep /services/* for continuity
pathauto_for_vocab('taxo_industries','Industries terms','/industries/[term:parents:join-path]/[term:name]','industries');
pathauto_for_vocab('taxo_solutions','Solutions terms','/solutions/[term:parents:join-path]/[term:name]','solutions');
pathauto_for_vocab('taxo_technologies','Technologies terms','/tech/[term:name]','technologies'); // reuse /tech/*
pathauto_for_vocab('taxo_categories','News categories','/news/category/[term:name]','categories');
pathauto_for_vocab('taxo_topics','Topics terms','/topics/[term:name]','topics');
pathauto_for_vocab('taxo_tech_stack','Tech Stack terms','/tech/[term:name]','tech_stack');

/* =========================
 * 5) Node fields & migrations
 * ========================= */
$bundles = ['basic_page','landing_page','article','service','case_study','resource','event','career'];

// a) New storages
ensure_field_storage('node','field_primary_capability','entity_reference',['target_type'=>'taxonomy_term'],['cardinality'=>1]);
ensure_field_storage('node','field_solutions','entity_reference',['target_type'=>'taxonomy_term'],['cardinality'=>-1]);
ensure_field_storage('node','field_technologies','entity_reference',['target_type'=>'taxonomy_term'],['cardinality'=>-1]);
ensure_field_storage('node','field_news_category','entity_reference',['target_type'=>'taxonomy_term'],['cardinality'=>1]);

// b) Attach to bundles
$eref = fn($vid)=>['handler'=>'default:taxonomy_term','handler_settings'=>['target_bundles'=>[$vid=>$vid]]];
foreach ($bundles as $b) {
  // Primary Capability on all except (optional) the "service" bundle
  if ($b !== 'service') ensure_field_instance('node',$b,'field_primary_capability','Primary Capability',$eref('capabilities'));
  // Solutions wide
  ensure_field_instance('node',$b,'field_solutions','Solutions',$eref('solutions'));
  // Technologies wide
  ensure_field_instance('node',$b,'field_technologies','Technologies',$eref('technologies'));
  // News category on Article only
  if ($b==='article') ensure_field_instance('node','article','field_news_category','News Category',$eref('categories'));
}

// c) Migrate values: primary_service → primary_capability; use_cases → solutions
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$nids = \Drupal::entityQuery('node')->accessCheck(FALSE)->execute();
if ($nids) {
  foreach ($node_storage->loadMultiple($nids) as $node) {
    $changed = false;
    // migrate primary_service
    if ($node->hasField('field_primary_service') && !$node->get('field_primary_service')->isEmpty()) {
      $src = $node->get('field_primary_service')->entity;
      if ($src && $src->bundle()==='services') {
        $dest = ensure_term_path('capabilities', ancestry_names($src), copy_term_fields($src));
        if (!DRY_RUN) $node->set('field_primary_capability',['target_id'=>$dest->id()]);
        $changed = true;
      }
    }
    // migrate use_cases → solutions
    if ($node->hasField('field_use_cases') && !$node->get('field_use_cases')->isEmpty()) {
      foreach ($node->get('field_use_cases')->referencedEntities() as $t) {
        if ($t->bundle()==='use_cases') {
          $dest = ensure_term_path('solutions', ancestry_names($t), copy_term_fields($t));
          if (!DRY_RUN) $node->get('field_solutions')->appendItem(['target_id'=>$dest->id()]);
          $changed = true;
        }
      }
    }
    if ($changed && !DRY_RUN) $node->save();
  }
}

// d) Remove old instances from forms (keep storage to be safe)
foreach ($bundles as $b) {
  $old = ['field_primary_service','field_use_cases'];
  foreach ($old as $f) {
    if (FieldConfig::load("node.$b.$f") && !DRY_RUN) {
      FieldConfig::load("node.$b.$f")->delete();
      say("[i] Removed node.$b.$f instance (storage kept).");
    }
  }
}

// e) Wire form/view widgets for new fields
foreach ($bundles as $b) {
  foreach (['field_primary_capability','field_solutions','field_technologies','field_news_category'] as $f) {
    if (FieldConfig::load("node.$b.$f")) {
      set_form_widget('node',$b,$f,'entity_reference_autocomplete');
      set_view_formatter('node',$b,$f,'entity_reference_label',['link'=>TRUE],'above');
    }
  }
}

/* =========================
 * 6) Fix redundant tagging: restrict field_taxonomy to Topics + Tech Stack; migrate/remove duplicates
 * ========================= */
foreach ($bundles as $b) {
  $fc = FieldConfig::load("node.$b.field_taxonomy");
  if ($fc) {
    $settings=$fc->getSettings();
    $settings['handler']='default:taxonomy_term';
    $settings['handler_settings']['target_bundles']=['topics'=>'topics','tech_stack'=>'tech_stack'];
    if (!DRY_RUN) { $fc->setLabel('Tags')->set('settings',$settings)->save(); }
    say("[=] node.$b.field_taxonomy restricted to Topics + Tech Stack and labeled 'Tags'.");
  }
}
// migrate field_topics & field_tech_stack into Tags, then remove instances
$nids = \Drupal::entityQuery('node')->accessCheck(FALSE)->execute();
if ($nids) {
  foreach ($node_storage->loadMultiple($nids) as $node) {
    $changed=false;
    if ($node->hasField('field_taxonomy')) {
      // topics → Tags
      if ($node->hasField('field_topics') && !$node->get('field_topics')->isEmpty()) {
        foreach ($node->get('field_topics')->referencedEntities() as $t) {
          if (!DRY_RUN) $node->get('field_taxonomy')->appendItem(['target_id'=>$t->id()]);
          $changed=true;
        }
      }
      // tech_stack (old field) → Tags
      if ($node->hasField('field_tech_stack') && !$node->get('field_tech_stack')->isEmpty()) {
        foreach ($node->get('field_tech_stack')->referencedEntities() as $t) {
          if (!DRY_RUN) $node->get('field_taxonomy')->appendItem(['target_id'=>$t->id()]);
          $changed=true;
        }
      }
      if ($changed && !DRY_RUN) $node->save();
    }
  }
}
// remove instances (keep storages)
foreach ($bundles as $b) {
  foreach (['field_topics','field_tech_stack'] as $f) {
    if (FieldConfig::load("node.$b.$f") && !DRY_RUN) {
      FieldConfig::load("node.$b.$f")->delete();
      say("[i] Removed node.$b.$f instance (storage kept).");
    }
  }
}

/* =========================
 * 7) Menu auto-sync: switch to Capabilities + Industries
 * ========================= */
$fs = \Drupal::service('file_system');
$module_dir = DRUPAL_ROOT . '/modules/custom/wl_taxo_nav';
if (!is_dir($module_dir)) { $fs->mkdir($module_dir, 0775, TRUE); }
file_put_contents($module_dir.'/wl_taxo_nav.info.yml', <<<YML
name: WL Taxonomy Nav
type: module
description: Auto-sync top-level Capabilities/Industries terms to Main menu when "Show in navigation?" is checked.
core_version_requirement: ^10 || ^11
package: WilkesLiberty
dependencies:
  - drupal:taxonomy
  - drupal:menu_link_content
  - drupal:path
YML);
file_put_contents($module_dir.'/wl_taxo_nav.module', <<<'PHP'
<?php
use Drupal\Core\Entity\EntityInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;

function _wl_taxo_nav_allowed_vocabs(): array { return ['capabilities','industries']; }
function _wl_taxo_nav_is_top_level(\Drupal\taxonomy\TermInterface $term): bool {
  $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($term->id());
  return empty($parents);
}
function _wl_taxo_nav_find_link_for_term(int $tid): ?MenuLinkContent {
  $ids = \Drupal::entityQuery('menu_link_content')->accessCheck(FALSE)->condition('menu_name','main')->execute();
  if (!$ids) return NULL;
  foreach (MenuLinkContent::loadMultiple($ids) as $link) {
    if ((int)$link->getThirdPartySetting('wl_taxo_nav','tid') === $tid) return $link;
  }
  return NULL;
}
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
        'link' => ['uri'=>'route:entity.taxonomy_term.canonical','title'=>$term->label(),'options'=>['route_parameters'=>['taxonomy_term'=>(int)$term->id()]]],
        'enabled' => TRUE, 'weight' => 0,
      ]);
      $existing->setThirdPartySetting('wl_taxo_nav','tid',(int)$term->id());
    } else {
      $existing->set('title',$term->label());
      $existing->set('link',['uri'=>'route:entity.taxonomy_term.canonical','title'=>$term->label(),'options'=>['route_parameters'=>['taxonomy_term'=>(int)$term->id()]]]);
      $existing->set('enabled',TRUE);
    }
    $existing->save();
  } else {
    if ($existing) $existing->delete();
  }
}
function wl_taxo_nav_entity_insert(EntityInterface $entity){ if ($entity->getEntityTypeId()==='taxonomy_term') _wl_taxo_nav_sync_term($entity); }
function wl_taxo_nav_entity_update(EntityInterface $entity){ if ($entity->getEntityTypeId()==='taxonomy_term') _wl_taxo_nav_sync_term($entity); }
function wl_taxo_nav_entity_delete(EntityInterface $entity){ if ($entity->getEntityTypeId()==='taxonomy_term') { $existing=_wl_taxo_nav_find_link_for_term((int)$entity->id()); if ($existing) $existing->delete(); } }
PHP);
if (!\Drupal::moduleHandler()->moduleExists('wl_taxo_nav') && !DRY_RUN) {
  $installer->install(['wl_taxo_nav']);
}

/* Trigger one-time sync for existing top-level caps/industries */
$ts = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach (['capabilities','industries'] as $vid) {
  $tids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid',$vid)->execute();
  if ($tids) { foreach ($ts->loadMultiple($tids) as $t) { if (!$t->parent->target_id && !DRY_RUN) $t->save(); } }
}

/* =========================
 * 8) Drop sections (if safe)
 * ========================= */
if ($sec = load_vocab('sections')) {
  try {
    if (!DRY_RUN) $sec->delete();
    say("[✓] Deleted 'sections' vocabulary.");
  } catch (\Throwable $e) {
    say("[!] Could not delete 'sections' (likely in use). Remove references and retry.");
  }
}

/* =========================
 * 9) Focal Point styles (quick)
 * ========================= */
use Drupal\image\Entity\ImageStyle;
function ensure_style($name,$label,$effects){
  $s = ImageStyle::load($name) ?: ImageStyle::create(['name'=>$name,'label'=>$label]);
  if (!DRY_RUN) {
    foreach ($s->getEffects()->getConfiguration() as $uuid=>$_){ $s->getEffects()->removeEffect($uuid); }
    foreach ($effects as $ef){ $s->addImageEffect($ef); }
    $s->save();
  }
}
function fp_cover($name,$label,$w,$rw,$rh,$webp=false){
  $h=(int)round($w*$rh/$rw);
  $eff=[['id'=>'focal_point_scale_and_crop','weight'=>1,'data'=>['width'=>$w,'height'=>$h]]];
  if ($webp) $eff[]=['id'=>'image_convert','weight'=>100,'data'=>['extension'=>'webp']];
  ensure_style($name,$label,$eff);
}
foreach ([640,1024,1600,1920] as $w){ fp_cover("nx_fp_cover_16x9_$w","Next FP Cover 16x9 $w",$w,16,9,false); fp_cover("nx_fp_cover_16x9_{$w}_webp","Next FP Cover 16x9 $w (WebP)",$w,16,9,true); }

/* =========================
 * 10) Cache rebuild
 * ========================= */
if (!DRY_RUN) {
  \Drupal::service('router.builder')->rebuild();
  \Drupal::service('cache.bootstrap')->invalidateAll();
  \Drupal::service('cache.render')->invalidateAll();
  \Drupal::messenger()->addStatus('WL taxonomy alignment applied.');
}

say(DRY_RUN ? "\n[DRY RUN] No changes written. Set DRY_RUN=false to apply." : "\n[APPLIED] WL taxonomy alignment complete. Export config next.");
