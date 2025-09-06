<?php

/**
 * WL Roles & Workflow bootstrap for a headless editorial setup.
 *
 * - Creates an "Editorial" workflow (Draft/Review/Published/Archived)
 * - Creates roles + grants safe baseline permissions
 * - Assigns workflow to your node bundles
 * - Grants transition permissions to the right roles
 *
 * Safe to re-run (idempotent). Grants only permissions that exist.
 */

use Drupal\user\Entity\Role;
use Drupal\workflows\Entity\Workflow;

$module_installer = \Drupal::service('module_installer');
$modules = ['workflows', 'content_moderation', 'media'];
$module_installer->install(array_values(array_filter($modules, fn($m) => !\Drupal::moduleHandler()->moduleExists($m))));

/* ---------------------------
 * Helpers
 * --------------------------- */
function wl_role($id, $label) {
  $r = Role::load($id);
  if (!$r) {
    $r = Role::create(['id' => $id, 'label' => $label]);
    $r->save();
  }
  return $r;
}

/** Grant only permissions that currently exist. */
function wl_grant_perms($role_id, array $perms) {
  $perm_handler = \Drupal::service('user.permissions');
  $available = array_keys($perm_handler->getPermissions());
  $role = Role::load($role_id);
  $granted = [];
  foreach ($perms as $p) {
    if (in_array($p, $available, TRUE)) {
      $role->grantPermission($p);
      $granted[] = $p;
    }
  }
  $role->save();
  return $granted;
}

/** Regex grant helper (e.g., grant all "create X content" perms). */
function wl_grant_matching($role_id, $pattern) {
  $perm_handler = \Drupal::service('user.permissions');
  $available = array_keys($perm_handler->getPermissions());
  $matches = array_values(array_filter($available, fn($p) => preg_match($pattern, $p)));
  return wl_grant_perms($role_id, $matches);
}

/* ---------------------------
 * Node bundles to moderate
 * --------------------------- */
$bundles = ['basic_page','landing_page','article','service','case_study','resource','event','career'];

/* ---------------------------
 * 1) Create roles
 * --------------------------- */
wl_role('content_author',  'Content Author');
wl_role('content_editor',  'Content Editor');
wl_role('publisher',       'Publisher');
wl_role('seo_manager',     'SEO Manager');
wl_role('media_manager',   'Media Manager');
wl_role('headless_client', 'Headless Client'); // service account for Next.js (optional)

/* ---------------------------
 * 2) Editorial workflow
 * --------------------------- */
$workflow_id = 'editorial';
$states = [
  'draft' =>      ['label' => 'Draft',     'published' => FALSE, 'default_revision' => FALSE, 'weight' => -5],
  'review' =>     ['label' => 'Review',    'published' => FALSE, 'default_revision' => FALSE, 'weight' => -3],
  'published' =>  ['label' => 'Published', 'published' => TRUE,  'default_revision' => TRUE,  'weight' => 0],
  'archived' =>   ['label' => 'Archived',  'published' => FALSE, 'default_revision' => TRUE,  'weight' => 5],
];
$transitions = [
  'create_new_draft' => ['label' => 'Create new draft',   'from' => ['draft','review','published','archived'], 'to' => 'draft',     'weight' => 0],
  'submit_for_review'=> ['label' => 'Submit for review',  'from' => ['draft'],                                   'to' => 'review',    'weight' => 1],
  'publish'          => ['label' => 'Publish',            'from' => ['draft','review'],                          'to' => 'published', 'weight' => 2],
  'unpublish'        => ['label' => 'Unpublish',          'from' => ['published'],                                'to' => 'draft',     'weight' => 3],
  'archive'          => ['label' => 'Archive',            'from' => ['draft','review','published'],              'to' => 'archived',  'weight' => 4],
  'restore'          => ['label' => 'Restore',            'from' => ['archived'],                                 'to' => 'draft',     'weight' => 5],
];

// Ensure or update the workflow.
$w = Workflow::load($workflow_id);
if (!$w) {
  $w = Workflow::create([
    'id' => $workflow_id,
    'label' => 'Editorial',
    'type' => 'content_moderation',
    'type_settings' => [
      'states' => $states,
      'transitions' => $transitions,
      // Assign to node bundles:
      'entity_types' => ['node' => $bundles],
    ],
    'default_moderation_state' => 'draft',
  ]);
} else {
  $ts = $w->get('type_settings') ?: [];
  $ts['states'] = $states;
  $ts['transitions'] = $transitions;
  $ts['entity_types']['node'] = $bundles;
  $w->set('type_settings', $ts);
  $w->set('default_moderation_state', 'draft');
}
$w->save();

/* ---------------------------
 * 3) Baseline permissions
 * --------------------------- */

// Anonymous: view published content & media.
wl_grant_perms(Role::ANONYMOUS_ID, [
  'access content',
  'view media',
]);

// Authenticated: leave minimal by default (explicit roles below handle authoring).

// Content Author: create & edit own; drafts & review; media create; paragraphs are covered by node edit perms.
$author_static = [
  'access content',
  'view media',
  'view latest version',
  'use text format basic_html',   // will only apply if it exists
  'access media overview',
];
wl_grant_perms('content_author', $author_static);
// Node create/edit own across your bundles:
wl_grant_matching('content_author', '/^create .* content$/');
wl_grant_matching('content_author', '/^edit own .* content$/');
wl_grant_matching('content_author', '/^delete own .* content$/');
// Media create/update own (matches exact permission names on your site):
wl_grant_matching('content_author', '/^create .* media$/');
wl_grant_matching('content_author', '/^update own .* media$/');
wl_grant_matching('content_author', '/^delete own .* media$/');

// Content Editor: edit any + manage media broadly; can archive/restore.
$editor_static = [
  'access content',
  'view media',
  'view latest version',
  'create url aliases',
  'access media overview',
];
wl_grant_perms('content_editor', $editor_static);
wl_grant_matching('content_editor', '/^create .* content$/');
wl_grant_matching('content_editor', '/^edit any .* content$/');
wl_grant_matching('content_editor', '/^delete any .* content$/');
wl_grant_matching('content_editor', '/^create .* media$/');
wl_grant_matching('content_editor', '/^update any .* media$/');
wl_grant_matching('content_editor', '/^delete any .* media$/');

// Publisher: everything an editor can + revision management.
$publisher_static = [
  'access content',
  'view media',
  'view latest version',
  'revert all revisions',
  'view all revisions',
];
wl_grant_perms('publisher', $publisher_static);
wl_grant_matching('publisher', '/^edit any .* content$/');
wl_grant_matching('publisher', '/^delete any .* content$/');

// SEO Manager: aliases + redirects (+ meta if you use Metatag).
$seo_static = [
  'create url aliases',
  'administer redirects',     // if Redirect module is enabled
  'create redirect',          // if present
  'edit redirect',            // if present
  'delete redirect',          // if present
  // 'administer meta tags',   // if Metatag is installed
];
wl_grant_perms('seo_manager', array_filter($seo_static));

// Media Manager: full media control.
$mm_static = [
  'access media overview',
  'view media',
];
wl_grant_perms('media_manager', $mm_static);
wl_grant_matching('media_manager', '/^create .* media$/');
wl_grant_matching('media_manager', '/^update any .* media$/');
wl_grant_matching('media_manager', '/^delete any .* media$/');

// Headless Client: read-only published data. If you use GraphQL persisted queries, grant those.
$headless = [
  'access content',
  'view media',
  // GraphQL (grant whichever exists):
  'execute graphql requests',
  'execute persisted queries',
  'use graphql explorer',
];
wl_grant_perms('headless_client', $headless);

// OPTIONAL: Give editors/authors access to the entity browsers/media library if those perms exist.
wl_grant_matching('content_author', '/^access .*entity browser.*$/i');
wl_grant_matching('content_editor', '/^access .*entity browser.*$/i');
wl_grant_matching('media_manager',  '/^access .*entity browser.*$/i');
wl_grant_matching('content_author', '/^access media library$/i');
wl_grant_matching('content_editor', '/^access media library$/i');
wl_grant_matching('media_manager',  '/^access media library$/i');

/* ---------------------------
 * 4) Assign workflow transition permissions to roles
 *    Permissions are: "use {workflow_id} transition {transition_id}"
 * --------------------------- */
$perm_handler = \Drupal::service('user.permissions');
$available = array_keys($perm_handler->getPermissions());

$transition_perms = fn($ids) => array_values(array_filter(array_map(function($tid) use ($available, $workflow_id) {
  $p = "use $workflow_id transition $tid";
  return in_array($p, $available, TRUE) ? $p : NULL;
}, $ids)));

// Authors
wl_grant_perms('content_author', $transition_perms(['create_new_draft','submit_for_review']));
// Editors
wl_grant_perms('content_editor', $transition_perms(['create_new_draft','submit_for_review','archive','restore']));
// Publishers
wl_grant_perms('publisher', $transition_perms(['create_new_draft','submit_for_review','publish','unpublish','archive','restore']));

/* ---------------------------
 * 5) Make sure workflow applies to the desired bundles
 * --------------------------- */
$w = Workflow::load($workflow_id);
$ts = $w->get('type_settings') ?: [];
$ts['entity_types']['node'] = $bundles;
$w->set('type_settings', $ts)->save();

/* ---------------------------
 * 6) Cache rebuild
 * --------------------------- */
\Drupal::service('router.builder')->rebuild();
\Drupal::service('cache.bootstrap')->invalidateAll();
\Drupal::service('cache.render')->invalidateAll();

\Drupal::messenger()->addStatus('Roles, permissions, and Editorial workflow are configured. Review at: People → Roles, and Configuration → Workflow → Workflows.');
