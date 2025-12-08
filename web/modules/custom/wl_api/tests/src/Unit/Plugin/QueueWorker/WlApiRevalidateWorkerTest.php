<?php

namespace Drupal\Tests\wl_api\Unit\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wl_api\Plugin\QueueWorker\WlApiRevalidateWorker;
use Drupal\wl_api\Service\Revalidator;

/**
 * @coversDefaultClass \Drupal\wl_api\Plugin\QueueWorker\WlApiRevalidateWorker
 * @group wl_api
 */
class WlApiRevalidateWorkerTest extends UnitTestCase {

  /**
   * The revalidator mock.
   *
   * @var \Drupal\wl_api\Service\Revalidator|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $revalidator;

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

    $this->revalidator = $this->createMock(Revalidator::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
  }

  /**
   * Creates a WlApiRevalidateWorker instance.
   *
   * @return \Drupal\wl_api\Plugin\QueueWorker\WlApiRevalidateWorker
   *   The queue worker.
   */
  protected function createWorker(): WlApiRevalidateWorker {
    return new WlApiRevalidateWorker(
      [],
      'wl_api_revalidate',
      ['id' => 'wl_api_revalidate'],
      $this->revalidator,
      $this->logger,
    );
  }

  /**
   * Tests successful processing of queue item.
   *
   * @covers ::processItem
   */
  public function testProcessItemSuccess(): void {
    $data = [
      'endpoint' => 'https://example.com/api/revalidate',
      'secret' => 'secret123',
      'payload' => ['tags' => ['content']],
      'meta' => ['frontend' => 'default', 'domain' => 'content'],
    ];

    $this->revalidator->expects($this->once())
      ->method('post')
      ->with(
        'https://example.com/api/revalidate',
        'secret123',
        ['tags' => ['content']],
        ['frontend' => 'default', 'domain' => 'content']
      )
      ->willReturn(['ok' => TRUE, 'status' => 200, 'error' => NULL]);

    $this->logger->expects($this->never())->method('warning');
    $this->logger->expects($this->never())->method('error');

    $worker = $this->createWorker();
    $worker->processItem($data);
  }

  /**
   * Tests queue item processing with failed revalidation.
   *
   * @covers ::processItem
   */
  public function testProcessItemWithFailedRevalidation(): void {
    $data = [
      'endpoint' => 'https://example.com/api/revalidate',
      'secret' => '',
      'payload' => [],
      'meta' => [],
    ];

    $this->revalidator->expects($this->once())
      ->method('post')
      ->willReturn(['ok' => FALSE, 'status' => 500, 'error' => 'Internal error']);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Queue revalidation failed'),
        $this->callback(function ($context) {
          $this->assertStringContainsString('Internal error', $context['@error']);
          return TRUE;
        })
      );

    $worker = $this->createWorker();
    $worker->processItem($data);
  }

  /**
   * Tests queue item processing with missing endpoint.
   *
   * @covers ::processItem
   */
  public function testProcessItemWithMissingEndpoint(): void {
    $data = [
      'secret' => 'secret',
      'payload' => ['tags' => ['content']],
      'meta' => [],
    ];

    $this->revalidator->expects($this->never())->method('post');

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Queue item missing endpoint'),
        $this->anything()
      );

    $worker = $this->createWorker();
    $worker->processItem($data);
  }

  /**
   * Tests queue item processing with empty endpoint.
   *
   * @covers ::processItem
   */
  public function testProcessItemWithEmptyEndpoint(): void {
    $data = [
      'endpoint' => '',
      'secret' => 'secret',
      'payload' => [],
      'meta' => [],
    ];

    $this->revalidator->expects($this->never())->method('post');

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Queue item missing endpoint'),
        $this->anything()
      );

    $worker = $this->createWorker();
    $worker->processItem($data);
  }

  /**
   * Tests queue item processing with default values.
   *
   * @covers ::processItem
   */
  public function testProcessItemWithDefaultValues(): void {
    $data = [
      'endpoint' => 'https://example.com/api/revalidate',
      // Missing secret, payload, meta - should use defaults.
    ];

    $this->revalidator->expects($this->once())
      ->method('post')
      ->with(
        'https://example.com/api/revalidate',
        '',
        [],
        []
      )
      ->willReturn(['ok' => TRUE, 'status' => 200, 'error' => NULL]);

    $worker = $this->createWorker();
    $worker->processItem($data);
  }

  /**
   * Tests exception handling in processItem.
   *
   * @covers ::processItem
   */
  public function testProcessItemExceptionHandling(): void {
    $data = [
      'endpoint' => 'https://example.com/api/revalidate',
      'secret' => '',
      'payload' => [],
      'meta' => [],
    ];

    $exception = new \RuntimeException('Service unavailable');

    $this->revalidator->expects($this->once())
      ->method('post')
      ->willThrowException($exception);

    $this->logger->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Queue worker exception'),
        $this->callback(function ($context) {
          $this->assertStringContainsString('Service unavailable', $context['@message']);
          return TRUE;
        })
      );

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Service unavailable');

    $worker = $this->createWorker();
    $worker->processItem($data);
  }

  /**
   * Tests HTTP status code in warning when error is null.
   *
   * @covers ::processItem
   */
  public function testProcessItemWarningUsesHttpStatus(): void {
    $data = [
      'endpoint' => 'https://example.com/api/revalidate',
      'secret' => '',
      'payload' => [],
      'meta' => [],
    ];

    $this->revalidator->expects($this->once())
      ->method('post')
      ->willReturn(['ok' => FALSE, 'status' => 503, 'error' => NULL]);

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->anything(),
        $this->callback(function ($context) {
          $this->assertStringContainsString('HTTP 503', $context['@error']);
          return TRUE;
        })
      );

    $worker = $this->createWorker();
    $worker->processItem($data);
  }

}
