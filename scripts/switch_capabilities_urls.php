<?php
/**
 * Switch Capabilities term URLs from /services/* to /capabilities/* with 301 redirects.
 * Usage: ddev drush scr scripts/switch_capabilities_urls.php
 */

use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\taxonomy\Entity\Term;
use Drupal\redirect\Entity\Redirect;

$language = 'en';
$pattern_id = 'taxo_capabilities';
$new_pattern = '/capabilities/[term:parents:join-path]/[term:name]';

// 1) Ensure/Update Pathauto pattern for capabilities.
$pat = PathautoPattern::load($pattern_id);
if (!$pat) {
  $pat = PathautoPattern::create([
    'id' => $pattern_id,
    'label' => 'Capabilities terms',
    'type' => 'canonical_entities:taxonomy_term',
    'pattern' => $new_pattern,
  ]);
}
else {
  $pat->set('pattern', $new_pattern);
}
// Selection criteria: only capabilities bundle.
$pat->set('selection_criteria', [
  [
    'id' => 'entity_bundle:taxonomy_term',
    'bundles' => ['capabilities' => 'capabilities'],
    'negate' => FALSE,
    'context_mapping' => ['taxonomy_term' => 'taxonomy_term'],
    'uuid' => \Drupal::service('uuid')->generate(),
  ],
]);
$pat->save();

$aliasStorage = \Drupal::entityTypeManager()->getStorage('path_alias');
$generator = \Drupal::service('pathauto.generator');
$aliasManager = \Drupal::service('path_alias.manager');

// 2) Process all capabilities terms: update alias and add redirect from old alias.
$tids = \Drupal::entityQuery('taxonomy_term')->accessCheck(FALSE)->condition('vid', 'capabilities')->execute();
$processed = 0; $redirects = 0;
if ($tids) {
  $terms = Term::loadMultiple($tids);
  foreach ($terms as $term) {
    $internal = '/taxonomy/term/' . $term->id();
    $oldAlias = $aliasManager->getAliasByPath($internal, $language);

    // Generate new alias using Pathauto.
    // First, delete existing alias for this path to force regeneration; keep value for redirect.
    $existing = $aliasStorage->loadByProperties(['path' => $internal, 'langcode' => $language]);
    if ($existing) { foreach ($existing as $e) { $e->delete(); } }
    $generator->updateEntityAlias($term, 'bulkupdate');

    $newAlias = $aliasManager->getAliasByPath($internal, $language);

    // If old alias existed and differs, create redirect.
    if ($oldAlias && $oldAlias !== $newAlias) {
      $redir = Redirect::create();
      $redir->setSource(ltrim($oldAlias, '/'));
      $redir->setRedirect('internal:' . $newAlias);
      $redir->setStatusCode(301);
      $redir->save();
      $redirects++;
    }
    $processed++;
  }
}
print "Updated capabilities aliases: $processed; redirects created: $redirects\n";
