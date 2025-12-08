<?php

namespace Drupal\wl_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\system\Entity\Menu;
use Drupal\wl_api\Service\TagResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows derived frontend tags for entities.
 */
class TagExplorerController extends ControllerBase {

  public function __construct(protected TagResolver $resolver) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('wl_api.tag_resolver'));
  }

  /**
   * Node tags.
   */
  public function nodeTags(NodeInterface $node): array {
    return $this->build($node, 'node');
  }

  /**
   * Term tags.
   */
  public function termTags(TermInterface $taxonomy_term): array {
    return $this->build($taxonomy_term, 'term');
  }

  /**
   * Menu tags page builder.
   *
   * @param \Drupal\system\Entity\Menu $menu
   *   Menu config entity.
   *
   * @return array
   *   Render array for menu tags.
   */
  public function menuTags(Menu $menu): array {
    return $this->build($menu, 'menu');
  }

  /**
   * Build tag list for an entity.
   *
   * @param object $entity
   *   Entity object (node, term, or menu).
   * @param string $type
   *   Entity type label, for future use.
   *
   * @return array
   *   Render array detailing derived tags.
   */
  protected function build($entity, string $type): array {
    $tags = $this->resolver->tagsForEntity($entity);

    $items = [];
    foreach ($tags as $tag) {
      $items[] = ['#markup' => $this->t('@tag', ['@tag' => $tag])];
    }

    return [
      '#type' => 'details',
      '#title' => $this->t('Derived frontend tags'),
      '#open' => TRUE,
      'list' => ['#theme' => 'item_list', '#items' => $items],
      '#attached' => [],
    ];
  }

}
