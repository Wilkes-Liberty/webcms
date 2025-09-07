<?php

/**
 * WL taxonomy add-ons:
 *  - Enable Focal Point and create FP image styles (with WebP variants).
 *  - Create a small module wl_taxo_nav to sync top-level Services/Industries terms to Main menu
 *    when "Show in navigation?" is checked. Also performs an initial sync.
 *
 * Safe to re-run (idempotent).
 */

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\image\Entity\ImageStyle;
use Drupal\taxonomy\Entity\Term;
use Drupal\menu_link_content\Entity\MenuLinkContent;

$installer = \Drupal::service('module_installer');
$fs = \Drupal::service('file_system');

/* -------------------------------------------------------
 * 0) Ensure required modules
 * ------------------------------------------------------- */
$need = [
  'taxonomy',
  'menu_ui',
  'menu_link_content',
  'focal_point', // add-on
];
$installer->install(array_values(array_filter($need, fn($m) => !\Drupal::moduleHandler()->moduleExists($m))));

/* -------------------------------------------------------
 * 1) Focal Point image styles
 * ------------------------------------------------------- */
function wl_ensure_image_style($name, $label, array $effects) {
  $style = ImageStyle::load($name);
  if (!$style) {
    $style = ImageStyle::create(['name' => $name, 'label' => $label]);
    $style->save();
  }
  // Reset effects to ensure idempotence.
  foreach ($style->getEffects()->getConfiguration() as $uuid => $conf) {
    $style->getEffects()->removeInstanceId($uuid);
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

/** Helper to build FP scale+crop styles */
function wl_fp_cover($name, $label, $width, $ratioW, $ratioH, $webp = FALSE) {
  $height = (int) round($width * $ratioH / $ratioW);
  $effects = [
    [
      'id' => 'focal_point_scale_and_crop',
      'data' => ['width' => $width, 'height' => $height],
      'weight' => 1,
    ],
  ];
  if ($webp) {
    $effects[] = ['id' => 'image_convert', 'data' => ['extension' => 'webp'], 'weight' => 100];
  }
  wl_ensure_image_style($name, $label, $effects);
}

// Hero/cover crops (16:9) sized for banners/headers
foreach ([640, 1024, 1600, 1920] as $w) {
  wl_fp_cover("nx_fp_cover_16x9_$w", "Next FP Cover 16x9 $w", $w, 16, 9, FALSE);
  wl_fp_cover("nx_fp_cover_16x9_{$w}_webp", "Next FP Cover 16x9 $w (WebP)", $w, 16, 9, TRUE);
}
// Square avatars/icons (1:1)
foreach ([400, 800] as $s) {
  wl_fp_cover("nx_fp_square_$s", "Next FP Square $s", $s, 1, 1, FALSE);
  wl_fp_cover("nx_fp_square_{$s}_webp", "Next FP Square $s (WebP)", $s, 1, 1, TRUE);
}
// Ultra-wide hero (21:9)
foreach ([1920] as $w) {
  wl_fp_cover("nx_fp_cover_21x9_$w", "Next FP Cover 21x9 $w", $w, 21, 9, FALSE);
  wl_fp_cover("nx_fp_cover_21x9_{$w}_webp", "Next FP Cover 21x9 $w (WebP)", $w, 21, 9, TRUE);
}

// (Optional) make Media › Image admin preview use an FP style so editors see sensible crops in Drupal.
$media_image_display = EntityViewDisplay::load('media.image.default')
  ?: EntityViewDisplay::create(['targetEntityType'=>'media','bundle'=>'image','mode'=>'default','status'=>TRUE]);
$media_image_display->setComponent('field_media_image', [
  'type' => 'image',
  'label' => 'hidden',
  'settings' => ['image_style' => 'nx_fp_cover_16x9_1024', 'image_link' => ''],
]);
$media_image_display->save();

/* -------------------------------------------------------
 * 2) wl_taxo_nav module (auto menu-sync for terms)
 * ------------------------------------------------------- */

$module_dir = DRUPAL_ROOT . '/modules/custom/wl_taxo_nav';
if (!is_dir($module_dir)) {
  $fs->mkdir($module_dir, 0775, TRUE);
}

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

/**
 * Vocabs we manage for menu sync.
 */
function _wl_taxo_nav_allowed_vocabs(): array {
  return ['services', 'industries'];
}

/**
 * Is this a top-level term?
 */
function _wl_taxo_nav_is_top_level(\Drupal\taxonomy\TermInterface $term): bool {
  $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($term->id());
  return empty($parents);
}

/**
 * Find an existing menu link we created for a term (by our third party setting).
 */
function _wl_taxo_nav_find_link_for_term(int $tid): ?MenuLinkContent {
  $ids = \Drupal::entityQuery('menu_link_content')
    ->accessCheck(FALSE)
    ->condition('menu_name', 'main')
    ->execute();
  if (!$ids) return NULL;
  $links = MenuLinkContent::loadMultiple($ids);
  foreach ($links as $link) {
    $tp = $link->getThirdPartySetting('wl_taxo_nav', 'tid');
    if ((int) $tp === $tid) return $link;
  }
  return NULL;
}

/**
 * Ensure a menu link exists/updated for the given term or remove it when not needed.
 */
function _wl_taxo_nav_sync_term(\Drupal\taxonomy\TermInterface $term): void {
  $vid = $term->bundle();
  if (!in_array($vid, _wl_taxo_nav_allowed_vocabs(), TRUE)) {
    // We only manage selected vocabs.
    return;
  }
  $show = (bool) ($term->get('field_show_in_nav')->value ?? FALSE);
  $top  = _wl_taxo_nav_is_top_level($term);

  $existing = _wl_taxo_nav_find_link_for_term((int) $term->id());

  if ($show && $top) {
    // Create or update the link.
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
      // Update title and link params if needed.
      $existing->set('title', $term->label());
      $existing->set('link', [
        'uri' => 'route:entity.taxonomy_term.canonical',
        'title' => $term->label(),
        'options' => ['route_parameters' => ['taxonomy_term' => (int) $term->id()]],
      ]);
      $existing->set('enabled', TRUE);
    }
    $existing->save();
  }
  else {
    // Remove if it exists (either not shown or has a parent).
    if ($existing) {
      $existing->delete();
    }
  }
}

/**
 * Implements hook_entity_insert().
 */
function wl_taxo_nav_entity_insert(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'taxonomy_term') {
    _wl_taxo_nav_sync_term($entity);
  }
}

/**
 * Implements hook_entity_update().
 */
function wl_taxo_nav_entity_update(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'taxonomy_term') {
    _wl_taxo_nav_sync_term($entity);
  }
}

/**
 * Implements hook_entity_delete().
 */
function wl_taxo_nav_entity_delete(EntityInterface $entity) {
  if ($entity->getEntityTypeId() === 'taxonomy_term') {
    $existing = _wl_taxo_nav_find_link_for_term((int) $entity->id());
    if ($existing) {
      $existing->delete();
    }
  }
}
PHP;

file_put_contents($module_dir . '/wl_taxo_nav.info.yml', $info_yml);
file_put_contents($module_dir . '/wl_taxo_nav.module', $module_php);

// Enable the custom module (now that files exist).
if (!\Drupal::moduleHandler()->moduleExists('wl_taxo_nav')) {
  $installer->install(['wl_taxo_nav']);
}

/* -------------------------------------------------------
 * 3) One-time initial sync for existing terms
 * ------------------------------------------------------- */
$allowed = ['services', 'industries'];
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach ($allowed as $vid) {
  $tids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', $vid)->execute();
  if (!$tids) { continue; }
  /** @var \Drupal\taxonomy\Entity\Term[] $terms */
  $terms = $term_storage->loadMultiple($tids);
  foreach ($terms as $term) {
    // Only top-level need consideration; _wl_taxo_nav_sync_term() will also prune non-top-level.
    _wl_taxo_nav_sync_term($term);
  }
}

/* -------------------------------------------------------
 * 4) Clear caches so new module & styles are active
 * ------------------------------------------------------- */
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache.bootstrap')->invalidateAll();
\Drupal::service('cache.render')->invalidateAll();

\Drupal::messenger()->addStatus('Taxonomy add-ons: Focal Point styles created; wl_taxo_nav enabled; initial menu sync complete.');
