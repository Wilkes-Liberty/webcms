<?php
/**
 * Remove the top-level "Services" item from the Main menu.
 * Usage: ddev drush scr scripts/menu_remove_services.php
 */

use Drupal\menu_link_content\Entity\MenuLinkContent;

$storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
$ids = \Drupal::entityQuery('menu_link_content')
  ->accessCheck(FALSE)
  ->condition('menu_name', 'main')
  ->condition('link__uri', 'internal:/services/', '=')
  ->execute();

if (!$ids) {
  // Try by title as fallback.
  $ids = \Drupal::entityQuery('menu_link_content')
    ->accessCheck(FALSE)
    ->condition('menu_name', 'main')
    ->condition('title', 'Services')
    ->execute();
}

if ($ids) {
  $links = $storage->loadMultiple($ids);
  foreach ($links as $link) {
    $link->delete();
    print "Deleted Main menu link: Services\n";
  }
} else {
  print "No 'Services' link found in Main menu.\n";
}
