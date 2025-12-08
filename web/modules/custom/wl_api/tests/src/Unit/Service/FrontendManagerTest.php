<?php

namespace Drupal\Tests\wl_api\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\key\KeyInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wl_api\FrontendInterface;
use Drupal\wl_api\Service\FrontendManager;

/**
 * @coversDefaultClass \Drupal\wl_api\Service\FrontendManager
 * @group wl_api
 */
class FrontendManagerTest extends UnitTestCase {

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The module handler mock.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The key repository mock.
   *
   * @var \Drupal\key\KeyRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $keyRepo;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The logger mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->keyRepo = $this->createMock(KeyRepositoryInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
  }

  /**
   * Creates a FrontendManager instance.
   *
   * @return \Drupal\wl_api\Service\FrontendManager
   *   The frontend manager service.
   */
  protected function createFrontendManager(): FrontendManager {
    return new FrontendManager(
      $this->configFactory,
      $this->moduleHandler,
      $this->keyRepo,
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Helper to create a config mock.
   *
   * @param array $values
   *   Config values.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   *   The config mock.
   */
  protected function createConfig(array $values): ImmutableConfig {
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnCallback(function ($key) use ($values) {
        return $values[$key] ?? NULL;
      });
    return $config;
  }

  /**
   * Tests listFrontends returns config entities when available.
   *
   * @covers ::listFrontends
   */
  public function testListFrontendsFromConfigEntities(): void {
    $frontend1 = $this->createMock(FrontendInterface::class);
    $frontend1->method('id')->willReturn('site_a');
    $frontend1->method('label')->willReturn('Site A');
    $frontend1->method('get')
      ->willReturnCallback(function ($key) {
        $values = [
          'revalidate_webhook' => 'https://site-a.com/api/revalidate',
          'path_revalidate_webhook' => 'https://site-a.com/api/revalidate-path',
          'secret' => 'key_site_a',
          'health_url' => 'https://site-a.com/health',
          'ci_url' => 'https://ci.example.com/site-a',
        ];
        return $values[$key] ?? '';
      });

    $frontend2 = $this->createMock(FrontendInterface::class);
    $frontend2->method('id')->willReturn('site_b');
    $frontend2->method('label')->willReturn('Site B');
    $frontend2->method('get')
      ->willReturnCallback(function ($key) {
        $values = [
          'revalidate_webhook' => 'https://site-b.com/api/revalidate',
          'path_revalidate_webhook' => '',
          'secret' => '',
          'health_url' => '',
          'ci_url' => '',
        ];
        return $values[$key] ?? '';
      });

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([
      'site_a' => $frontend1,
      'site_b' => $frontend2,
    ]);

    $this->entityTypeManager->method('getStorage')
      ->with('wl_api_frontend')
      ->willReturn($storage);

    $manager = $this->createFrontendManager();
    $frontends = $manager->listFrontends();

    $this->assertCount(2, $frontends);
    $this->assertArrayHasKey('site_a', $frontends);
    $this->assertArrayHasKey('site_b', $frontends);

    $this->assertEquals('Site A', $frontends['site_a']['label']);
    $this->assertEquals('https://site-a.com/api/revalidate', $frontends['site_a']['revalidate_webhook']);
    $this->assertEquals('key_site_a', $frontends['site_a']['secret']);

    $this->assertEquals('Site B', $frontends['site_b']['label']);
    $this->assertEquals('', $frontends['site_b']['secret']);
  }

  /**
   * Tests listFrontends falls back to legacy config.
   *
   * @covers ::listFrontends
   */
  public function testListFrontendsFallsBackToLegacyConfig(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('wl_api_frontend')
      ->willReturn($storage);

    $config = $this->createConfig([
      'revalidate_content_webhook' => 'https://legacy.com/revalidate',
      'revalidate_path_webhook' => 'https://legacy.com/revalidate-path',
      'revalidate_secret' => 'legacy_secret',
      'frontend_health_url' => 'https://legacy.com/health',
      'frontend_ci_url' => 'https://ci.legacy.com',
    ]);

    $this->configFactory->method('get')
      ->with('wl_api.settings')
      ->willReturn($config);

    $manager = $this->createFrontendManager();
    $frontends = $manager->listFrontends();

    $this->assertCount(1, $frontends);
    $this->assertArrayHasKey('default', $frontends);
    $this->assertEquals('Default', $frontends['default']['label']);
    $this->assertEquals('https://legacy.com/revalidate', $frontends['default']['revalidate_webhook']);
    $this->assertEquals('legacy_secret', $frontends['default']['secret']);
  }

  /**
   * Tests listFrontends logs warning on entity load failure.
   *
   * @covers ::listFrontends
   */
  public function testListFrontendsLogsWarningOnFailure(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \RuntimeException('Entity storage error'));

    $config = $this->createConfig([
      'revalidate_content_webhook' => 'https://fallback.com/revalidate',
      'revalidate_path_webhook' => '',
      'revalidate_secret' => '',
      'frontend_health_url' => '',
      'frontend_ci_url' => '',
    ]);

    $this->configFactory->method('get')
      ->with('wl_api.settings')
      ->willReturn($config);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('falling back to legacy config'),
        $this->anything()
      );

    $manager = $this->createFrontendManager();
    $frontends = $manager->listFrontends();

    $this->assertArrayHasKey('default', $frontends);
  }

  /**
   * Tests resolveSecret returns key value when Key module available.
   *
   * @covers ::resolveSecret
   */
  public function testResolveSecretWithKeyModule(): void {
    $this->moduleHandler->method('moduleExists')
      ->with('key')
      ->willReturn(TRUE);

    $key = $this->createMock(KeyInterface::class);
    $key->method('getKeyValue')->willReturn('resolved_secret_value');

    $this->keyRepo->method('getKey')
      ->with('my_key_id')
      ->willReturn($key);

    $manager = $this->createFrontendManager();
    $secret = $manager->resolveSecret('my_key_id');

    $this->assertEquals('resolved_secret_value', $secret);
  }

  /**
   * Tests resolveSecret returns raw value when key not found.
   *
   * @covers ::resolveSecret
   */
  public function testResolveSecretReturnsRawWhenKeyNotFound(): void {
    $this->moduleHandler->method('moduleExists')
      ->with('key')
      ->willReturn(TRUE);

    $this->keyRepo->method('getKey')
      ->with('not_a_key')
      ->willReturn(NULL);

    $manager = $this->createFrontendManager();
    $secret = $manager->resolveSecret('not_a_key');

    $this->assertEquals('not_a_key', $secret);
  }

  /**
   * Tests resolveSecret returns raw value when Key module unavailable.
   *
   * @covers ::resolveSecret
   */
  public function testResolveSecretWithoutKeyModule(): void {
    $this->moduleHandler->method('moduleExists')
      ->with('key')
      ->willReturn(FALSE);

    $this->keyRepo->expects($this->never())->method('getKey');

    $manager = $this->createFrontendManager();
    $secret = $manager->resolveSecret('raw_secret_value');

    $this->assertEquals('raw_secret_value', $secret);
  }

  /**
   * Tests resolveSecret returns empty string for null input.
   *
   * @covers ::resolveSecret
   */
  public function testResolveSecretWithNullInput(): void {
    $manager = $this->createFrontendManager();

    $this->assertEquals('', $manager->resolveSecret(NULL));
    $this->assertEquals('', $manager->resolveSecret(''));
  }

  /**
   * Tests resolveSecret logs notice on key lookup failure.
   *
   * @covers ::resolveSecret
   */
  public function testResolveSecretLogsNoticeOnFailure(): void {
    $this->moduleHandler->method('moduleExists')
      ->with('key')
      ->willReturn(TRUE);

    $this->keyRepo->method('getKey')
      ->willThrowException(new \RuntimeException('Key lookup failed'));

    $this->logger->expects($this->once())
      ->method('notice')
      ->with(
        $this->stringContains('Key module lookup failed'),
        $this->anything()
      );

    $manager = $this->createFrontendManager();
    $secret = $manager->resolveSecret('problem_key');

    // Should return the raw value as fallback.
    $this->assertEquals('problem_key', $secret);
  }

}
