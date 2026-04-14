<?php
/**
 * Configure next module to POST revalidation to Next.js when landing_page saves.
 *
 * Run in DDEV: ddev drush scr scripts/wire_revalidation.php
 * Run in prod: docker exec wl_drupal bash -c "cd /opt/drupal && drush scr scripts/wire_revalidation.php"
 *   (after copying script in)
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;

// Create / update next_entity_type_config for landing_page
$config = \Drupal::configFactory()->getEditable('next.next_entity_type_config.node.landing_page');
$config->set('id', 'node.landing_page');
$config->set('site_resolver', 'site_selector');
$config->set('configuration', [
  'sites' => ['wilkesliberty_ui' => 'wilkesliberty_ui'],
]);
$config->set('revalidate', 'revalidator.path');
$config->set('revalidator_configuration', [
  'revalidate_page' => TRUE,
  'additional_paths' => '/' . PHP_EOL . '/homepage',
]);
$config->save();
echo "Configured next_entity_type_config for landing_page\n";
echo "  Revalidates: / and /homepage on every landing_page save\n";
