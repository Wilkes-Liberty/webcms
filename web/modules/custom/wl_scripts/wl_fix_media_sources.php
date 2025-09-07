<?php

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\media\Entity\MediaType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Ensure required 'source_field' exists and is attached on media bundles.
 * Idempotent: safe to re-run.
 */

$map = [
  // bundle => [source, field_name, field_type, settings, label]
  'image'        => ['image',          'field_media_image',        'image', ['uri_scheme'=>'public','target_type'=>'file','display_field'=>FALSE,'display_default'=>FALSE,'alt_field_required'=>TRUE,'title_field'=>FALSE,'file_extensions'=>'png gif jpg jpeg webp avif'], 'Image'],
  'svg_image'    => ['file',           'field_media_file',         'file',  ['uri_scheme'=>'public','target_type'=>'file','file_extensions'=>'svg svgz'], 'SVG'],
  'icon'         => ['file',           'field_media_file',         'file',  ['uri_scheme'=>'public','target_type'=>'file','file_extensions'=>'svg svgz png'], 'Icon'],
  'video_file'   => ['file',           'field_media_file',         'file',  ['uri_scheme'=>'public','target_type'=>'file','file_extensions'=>'mp4 mov webm'], 'Video file'],
  'remote_video' => ['oembed:video',   'field_media_oembed_video', 'string',[], 'Remote video URL'],
  'audio'        => ['file',           'field_media_file',         'file',  ['uri_scheme'=>'public','target_type'=>'file','file_extensions'=>'mp3 ogg wav'], 'Audio file'],
  'document'     => ['file',           'field_media_file',         'file',  ['uri_scheme'=>'public','target_type'=>'file','file_extensions'=>'pdf doc docx xls xlsx ppt pptx txt'], 'Document file'],
];

// 1) Ensure storages exist (once per field name)
function ensure_storage($entity_type, $field_name, $type, array $settings = [], $cardinality = 1) {
  $id = "$entity_type.$field_name";
  if (!FieldStorageConfig::load($id)) {
    FieldStorageConfig::create([
      'entity_type' => $entity_type,
      'field_name'  => $field_name,
      'type'        => $type,
      'settings'    => $settings,
      'cardinality' => $cardinality,
      'translatable'=> TRUE,
    ])->save();
  }
}

// 2) Attach field to bundle if missing
function ensure_instance($entity_type, $bundle, $field_name, $label, array $settings = []) {
  $id = "$entity_type.$bundle.$field_name";
  if (!FieldConfig::load($id)) {
    FieldConfig::create([
      'entity_type' => $entity_type,
      'bundle'      => $bundle,
      'field_name'  => $field_name,
      'label'       => $label,
      'settings'    => $settings,
      'required'    => TRUE,
      'translatable'=> TRUE,
    ])->save();
  }
}

// 3) Give each bundle a minimal usable form/view display for the source field
function ensure_media_displays($bundle, $field_name) {
  $form = EntityFormDisplay::load("media.$bundle.default")
    ?: EntityFormDisplay::create(['targetEntityType'=>'media','bundle'=>$bundle,'mode'=>'default','status'=>TRUE]);

  // Choose a sensible widget
  $widget = 'string_textfield';
  if (str_starts_with($field_name, 'field_media_file')) $widget = 'file_generic';
  if ($field_name === 'field_media_image') $widget = 'image_image';
  if ($field_name === 'field_media_oembed_video') $widget = 'string_textfield';

  $form->setComponent($field_name, ['type' => $widget]);
  $form->save();

  $view = EntityViewDisplay::load("media.$bundle.default")
    ?: EntityViewDisplay::create(['targetEntityType'=>'media','bundle'=>$bundle,'mode'=>'default','status'=>TRUE]);

  // Choose a sensible formatter
  $formatter = 'string';
  if ($field_name === 'field_media_image') $formatter = 'image';
  if (str_starts_with($field_name, 'field_media_file')) $formatter = 'file_default';

  $view->setComponent($field_name, ['type' => $formatter, 'label' => 'hidden']);
  $view->save();
}

// 4) Repair each mapped bundle if its source points to a missing field.
$repaired = [];
foreach ($map as $bundle => [$expected_source, $field_name, $field_type, $storage_settings, $label]) {
  $mt = MediaType::load($bundle);
  if (!$mt) {
    // Skip if the bundle doesn't exist on this site.
    continue;
  }
  $actual_source = $mt->getSource()->getPluginId();
  if ($actual_source !== $expected_source) {
    // If the source differs (e.g. core 'image' already fine), we still ensure the field exists.
  }

  // Ensure storage + instance
  ensure_storage('media', $field_name, $field_type, $storage_settings);
  ensure_instance('media', $bundle, $field_name, $label);

  // Ensure the field instance has the correct file extension restrictions (if applicable)
  if (isset($storage_settings['file_extensions'])) {
    if ($fc = FieldConfig::load("media.$bundle.$field_name")) {
      if ($fc->getSetting('file_extensions') !== $storage_settings['file_extensions']) {
        $fc->setSetting('file_extensions', $storage_settings['file_extensions']);
        $fc->save();
      }
    }
  }

  // If the media type has a different source_field configured, align it.
  $conf = $mt->getSource()->getConfiguration();
  if (empty($conf['source_field']) || $conf['source_field'] !== $field_name) {
    $conf['source_field'] = $field_name;
    $mt->set('source_configuration', $conf);
    $mt->save();
  }

  // Form/view displays
  ensure_media_displays($bundle, $field_name);
  $repaired[] = $bundle;
}

// 5) Clear caches to flush plugin discovery and field definitions
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache.bootstrap')->invalidateAll();
\Drupal::service('cache.render')->invalidateAll();

\Drupal::messenger()->addStatus('Repaired media source fields for bundles: ' . implode(', ', $repaired));
