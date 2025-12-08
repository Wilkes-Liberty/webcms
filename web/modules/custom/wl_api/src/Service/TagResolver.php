<?php

namespace Drupal\wl_api\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Resolves which frontend tags to invalidate for a given entity/menu.
 */
class TagResolver {

  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  /**
   * Compute tags for a node/term/menu. Simple defaults; configurable later.
   *
   * @return string[]
   *   List of tags to revalidate (e.g., content, content:article, node:123).
   */
  public function tagsForEntity(EntityInterface $entity): array {
    $type = $entity->getEntityTypeId();
    if ($type === 'node') {
      $bundle = $entity->bundle();
      return ['content', 'content:' . $bundle, 'node:' . $entity->id()];
    }
    if ($type === 'taxonomy_term') {
      $vid = $entity->bundle();
      return ['taxonomy', 'taxonomy:' . $vid, 'term:' . $entity->id()];
    }
    if ($type === 'menu') {
      /** @var \Drupal\system\Entity\Menu $entity */
      return ['menu', 'menu:' . $entity->id()];
    }
    return [];
  }

}
