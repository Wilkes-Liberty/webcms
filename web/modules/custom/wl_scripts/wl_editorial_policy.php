<?php

use Drupal\workflows\Entity\Workflow;
use Drupal\user\Entity\Role;

/**
 * WL Editorial policy:
 * - Ensure Editorial workflow exists and is schema-correct
 * - Set Review state to default_revision = TRUE (unpublished default rev)
 * - Allow ONLY Editors/Publishers to submit_for_review
 * - Keep Authors from submit_for_review (they can still create drafts)
 *
 * Idempotent: re-running won't duplicate or over-grant.
 */

// --- helpers ---
function role_exists($id) { return (bool) Role::load($id); }
function grant($rid, array $perms) {
  $role = Role::load($rid); if (!$role) return;
  $available = array_keys(\Drupal::service('user.permissions')->getPermissions());
  foreach ($perms as $p) { if (in_array($p, $available, TRUE)) { $role->grantPermission($p); } }
  $role->save();
}
function revoke($rid, array $perms) {
  $role = Role::load($rid); if (!$role) return;
  foreach ($perms as $p) { $role->revokePermission($p); }
  $role->save();
}

// --- 1) Ensure/normalize workflow ---
$workflow_id = 'editorial';
$bundles = ['basic_page','landing_page','article','service','case_study','resource','event','career'];

$states = [
  'draft' => [
    'label' => 'Draft', 'published' => FALSE, 'default_revision' => FALSE, 'weight' => 0,
  ],
  'review' => [
    'label' => 'Review', 'published' => FALSE, 'default_revision' => TRUE,  'weight' => 10,
  ],
  'published' => [
    'label' => 'Published', 'published' => TRUE, 'default_revision' => TRUE, 'weight' => 20,
  ],
  'archived' => [
    'label' => 'Archived', 'published' => FALSE, 'default_revision' => FALSE, 'weight' => 30,
  ],
];

$transitions = [
  'create_new_draft' => ['label'=>'Create new draft', 'from'=>['draft','review','published','archived'], 'to'=>'draft', 'weight'=>0],
  'submit_for_review'=> ['label'=>'Submit for review', 'from'=>['draft'], 'to'=>'review', 'weight'=>10],
  'publish'          => ['label'=>'Publish', 'from'=>['draft','review'], 'to'=>'published', 'weight'=>20],
  'unpublish'        => ['label'=>'Unpublish', 'from'=>['published'], 'to'=>'draft', 'weight'=>30],
  'archive'          => ['label'=>'Archive', 'from'=>['draft','review','published'], 'to'=>'archived', 'weight'=>40],
  'restore'          => ['label'=>'Restore', 'from'=>['archived'], 'to'=>'draft', 'weight'=>50],
];

$w = Workflow::load($workflow_id);
if (!$w) {
  // Create the workflow if it doesn't exist yet.
  $w = Workflow::create([
    'id' => $workflow_id,
    'label' => 'Editorial',
    'type' => 'content_moderation',
    'type_settings' => [
      'default_moderation_state' => 'draft',
      'states' => $states,
      'transitions' => $transitions,
      'entity_types' => ['node' => $bundles],
    ],
  ]);
} else {
  // Normalize existing workflow (no per-state "default" key; add weights; set default_moderation_state).
  $ts = $w->get('type_settings') ?: [];
  $ts['default_moderation_state'] = 'draft';
  $ts['states'] = $states + ($ts['states'] ?? []);
  $ts['transitions'] = $transitions + ($ts['transitions'] ?? []);
  // Ensure bundle coverage (merge, don't drop any existing if you prefer):
  $current = $ts['entity_types']['node'] ?? [];
  $ts['entity_types']['node'] = array_values(array_unique(array_merge($current, $bundles)));
  $w->set('type_settings', $ts);
}
$w->save();

// --- 2) Tighten transition permissions ---
$perm_submit   = "use $workflow_id transition submit_for_review";
$perm_create   = "use $workflow_id transition create_new_draft";
$perm_publish  = "use $workflow_id transition publish";
$perm_unpub    = "use $workflow_id transition unpublish";
$perm_archive  = "use $workflow_id transition archive";
$perm_restore  = "use $workflow_id transition restore";

// Ensure roles exist in case your site didn’t create them via the earlier script.
foreach ([
           'content_author' => 'Content Author',
           'content_editor' => 'Content Editor',
           'publisher'      => 'Publisher',
         ] as $id => $label) {
  if (!role_exists($id)) {
    $r = Role::create(['id'=>$id, 'label'=>$label]); $r->save();
  }
}

// Authors: CAN create drafts; CANNOT submit for review.
grant('content_author', [$perm_create, 'view latest version']);
revoke('content_author', [$perm_submit]);

// Editors: CAN submit for review (and archive/restore).
grant('content_editor', [$perm_create, $perm_submit, $perm_archive, $perm_restore, 'view latest version']);

// Publishers: full control.
grant('publisher', [$perm_create, $perm_submit, $perm_publish, $perm_unpub, $perm_archive, $perm_restore, 'view latest version', 'view all revisions', 'revert all revisions']);

// --- 3) Clear caches so UI reflects changes ---
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache.bootstrap')->invalidateAll();
\Drupal::service('cache.render')->invalidateAll();

\Drupal::messenger()->addStatus('Editorial policy set: Review is default_revision; Authors cannot submit_for_review; Editors/Publishers can.');
