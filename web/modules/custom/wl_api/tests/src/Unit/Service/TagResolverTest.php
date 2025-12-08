<?php

namespace Drupal\Tests\wl_api\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\system\Entity\Menu;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wl_api\Service\TagResolver;

/**
 * @coversDefaultClass \Drupal\wl_api\Service\TagResolver
 * @group wl_api
 */
class TagResolverTest extends UnitTestCase {

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
  }

  /**
   * Creates a TagResolver instance.
   *
   * @return \Drupal\wl_api\Service\TagResolver
   *   The tag resolver service.
   */
  protected function createTagResolver(): TagResolver {
    return new TagResolver($this->configFactory);
  }

  /**
   * Tests tags for node entities.
   *
   * @covers ::tagsForEntity
   */
  public function testTagsForNode(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('getEntityTypeId')->willReturn('node');
    $node->method('bundle')->willReturn('article');
    $node->method('id')->willReturn(123);

    $resolver = $this->createTagResolver();
    $tags = $resolver->tagsForEntity($node);

    $this->assertContains('content', $tags);
    $this->assertContains('content:article', $tags);
    $this->assertContains('node:123', $tags);
    $this->assertCount(3, $tags);
  }

  /**
   * Tests tags for different node bundles.
   *
   * @covers ::tagsForEntity
   * @dataProvider nodeDataProvider
   */
  public function testTagsForDifferentNodeBundles(string $bundle, int $id): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('getEntityTypeId')->willReturn('node');
    $node->method('bundle')->willReturn($bundle);
    $node->method('id')->willReturn($id);

    $resolver = $this->createTagResolver();
    $tags = $resolver->tagsForEntity($node);

    $this->assertContains('content', $tags);
    $this->assertContains('content:' . $bundle, $tags);
    $this->assertContains('node:' . $id, $tags);
  }

  /**
   * Data provider for node tests.
   *
   * @return array
   *   Test data.
   */
  public static function nodeDataProvider(): array {
    return [
      'article' => ['article', 1],
      'page' => ['page', 42],
      'landing_page' => ['landing_page', 999],
      'case_study' => ['case_study', 500],
    ];
  }

  /**
   * Tests tags for taxonomy term entities.
   *
   * @covers ::tagsForEntity
   */
  public function testTagsForTaxonomyTerm(): void {
    $term = $this->createMock(TermInterface::class);
    $term->method('getEntityTypeId')->willReturn('taxonomy_term');
    $term->method('bundle')->willReturn('solutions');
    $term->method('id')->willReturn(456);

    $resolver = $this->createTagResolver();
    $tags = $resolver->tagsForEntity($term);

    $this->assertContains('taxonomy', $tags);
    $this->assertContains('taxonomy:solutions', $tags);
    $this->assertContains('term:456', $tags);
    $this->assertCount(3, $tags);
  }

  /**
   * Tests tags for different vocabulary terms.
   *
   * @covers ::tagsForEntity
   * @dataProvider taxonomyDataProvider
   */
  public function testTagsForDifferentVocabularies(string $vid, int $id): void {
    $term = $this->createMock(TermInterface::class);
    $term->method('getEntityTypeId')->willReturn('taxonomy_term');
    $term->method('bundle')->willReturn($vid);
    $term->method('id')->willReturn($id);

    $resolver = $this->createTagResolver();
    $tags = $resolver->tagsForEntity($term);

    $this->assertContains('taxonomy', $tags);
    $this->assertContains('taxonomy:' . $vid, $tags);
    $this->assertContains('term:' . $id, $tags);
  }

  /**
   * Data provider for taxonomy tests.
   *
   * @return array
   *   Test data.
   */
  public static function taxonomyDataProvider(): array {
    return [
      'solutions' => ['solutions', 10],
      'technologies' => ['technologies', 20],
      'industries' => ['industries', 30],
      'topics' => ['topics', 40],
    ];
  }

  /**
   * Tests tags for menu entities.
   *
   * @covers ::tagsForEntity
   */
  public function testTagsForMenu(): void {
    $menu = $this->createMock(Menu::class);
    $menu->method('getEntityTypeId')->willReturn('menu');
    $menu->method('id')->willReturn('main');

    $resolver = $this->createTagResolver();
    $tags = $resolver->tagsForEntity($menu);

    $this->assertContains('menu', $tags);
    $this->assertContains('menu:main', $tags);
    $this->assertCount(2, $tags);
  }

  /**
   * Tests tags for different menus.
   *
   * @covers ::tagsForEntity
   * @dataProvider menuDataProvider
   */
  public function testTagsForDifferentMenus(string $menuId): void {
    $menu = $this->createMock(Menu::class);
    $menu->method('getEntityTypeId')->willReturn('menu');
    $menu->method('id')->willReturn($menuId);

    $resolver = $this->createTagResolver();
    $tags = $resolver->tagsForEntity($menu);

    $this->assertContains('menu', $tags);
    $this->assertContains('menu:' . $menuId, $tags);
  }

  /**
   * Data provider for menu tests.
   *
   * @return array
   *   Test data.
   */
  public static function menuDataProvider(): array {
    return [
      'main' => ['main'],
      'footer' => ['footer'],
      'admin' => ['admin'],
    ];
  }

  /**
   * Tests empty tags for unsupported entity types.
   *
   * @covers ::tagsForEntity
   */
  public function testEmptyTagsForUnsupportedEntity(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('user');

    $resolver = $this->createTagResolver();
    $tags = $resolver->tagsForEntity($entity);

    $this->assertEmpty($tags);
  }

  /**
   * Tests empty tags for various unsupported entities.
   *
   * @covers ::tagsForEntity
   * @dataProvider unsupportedEntityDataProvider
   */
  public function testEmptyTagsForVariousUnsupportedEntities(string $entityType): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn($entityType);

    $resolver = $this->createTagResolver();
    $tags = $resolver->tagsForEntity($entity);

    $this->assertEmpty($tags);
  }

  /**
   * Data provider for unsupported entities.
   *
   * @return array
   *   Test data.
   */
  public static function unsupportedEntityDataProvider(): array {
    return [
      'user' => ['user'],
      'file' => ['file'],
      'media' => ['media'],
      'block' => ['block'],
      'paragraph' => ['paragraph'],
    ];
  }

}
