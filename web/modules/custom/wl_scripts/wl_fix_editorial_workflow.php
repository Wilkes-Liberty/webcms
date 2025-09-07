<?php

use Drupal\workflows\Entity\Workflow;

/**
 * Fix Editorial workflow schema:
 * - add weights to states & transitions
 * - remove invalid per-state "default" flags
 * - set type_settings.default_moderation_state
 * - ensure bundle coverage stays intact
 * Safe to re-run.
 */

$workflow_id = 'editorial';
$w = Workflow::load($workflow_id);

if (!$w) {
  throw new \RuntimeException("Workflow '$workflow_id' not found. Create it first, then rerun this script.");
}

$ts = $w->get('type_settings') ?: [];

// Keep your bundle coverage (override if you prefer a specific set).
$bundles = ['basic_page','landing_page','article','service','case_study','resource','event','career'];
$ts['entity_types']['node'] = $bundles;

// Correct states (schema-compliant)
$ts['states'] = [
  'draft' => [
    'label' => 'Draft',
    'published' => FALSE,
    'default_revision' => FALSE,
    'weight' => 0,
  ],
  'review' => [
    'label' => 'Review',
    'published' => FALSE,
    'default_revision' => FALSE,
    'weight' => 10,
  ],
  'published' => [
    'label' => 'Published',
    'published' => TRUE,
    'default_revision' => TRUE,
    'weight' => 20,
  ],
  'archived' => [
    'label' => 'Archived',
    'published' => FALSE,
    'default_revision' => FALSE,
    'weight' => 30,
  ],
];

// Set correct default state here (not inside states)
$ts['default_moderation_state'] = 'draft';

// Correct transitions (with weights)
$ts['transitions'] = [
  'create_new_draft' => [
    'label' => 'Create new draft',
    'from' => ['draft','review','published','archived'],
    'to' => 'draft',
    'weight' => 0,
  ],
  'submit_for_review' => [
    'label' => 'Submit for review',
    'from' => ['draft'],
    'to' => 'review',
    'weight' => 10,
  ],
  'publish' => [
    'label' => 'Publish',
    'from' => ['draft','review'],
    'to' => 'published',
    'weight' => 20,
  ],
  'unpublish' => [
    'label' => 'Unpublish',
    'from' => ['published'],
    'to' => 'draft',
    'weight' => 30,
  ],
  'archive' => [
    'label' => 'Archive',
    'from' => ['draft','review','published'],
    'to' => 'archived',
    'weight' => 40,
  ],
  'restore' => [
    'label' => 'Restore',
    'from' => ['archived'],
    'to' => 'draft',
    'weight' => 50,
  ],
];

// Save back
$w->set('type_settings', $ts)->save();

// Clear caches so Workflows rereads config without throwing warnings.
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache.bootstrap')->invalidateAll();
\Drupal::service('cache.render')->invalidateAll();

\Drupal::messenger()->addStatus("Editorial workflow fixed: weights set, default_moderation_state applied, schema-compliant.");
