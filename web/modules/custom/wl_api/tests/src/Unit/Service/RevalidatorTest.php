<?php

namespace Drupal\Tests\wl_api\Unit\Service;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wl_api\Service\Alerts;
use Drupal\wl_api\Service\FrontendManager;
use Drupal\wl_api\Service\Logger;
use Drupal\wl_api\Service\Revalidator;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * @coversDefaultClass \Drupal\wl_api\Service\Revalidator
 * @group wl_api
 */
class RevalidatorTest extends UnitTestCase {

  /**
   * The HTTP client mock.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The frontend manager mock.
   *
   * @var \Drupal\wl_api\Service\FrontendManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $frontendManager;

  /**
   * The wl_api logger mock.
   *
   * @var \Drupal\wl_api\Service\Logger|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The current user mock.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The state mock.
   *
   * @var \Drupal\Core\State\StateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * The alerts service mock.
   *
   * @var \Drupal\wl_api\Service\Alerts|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $alerts;

  /**
   * The Drupal logger mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $drupalLogger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->frontendManager = $this->createMock(FrontendManager::class);
    $this->logger = $this->createMock(Logger::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->alerts = $this->createMock(Alerts::class);
    $this->drupalLogger = $this->createMock(LoggerChannelInterface::class);
  }

  /**
   * Creates a Revalidator instance with mocked dependencies.
   *
   * @return \Drupal\wl_api\Service\Revalidator
   *   The revalidator service.
   */
  protected function createRevalidator(): Revalidator {
    return new Revalidator(
      $this->httpClient,
      $this->frontendManager,
      $this->logger,
      $this->currentUser,
      $this->state,
      $this->alerts,
      $this->drupalLogger,
    );
  }

  /**
   * Tests successful POST request.
   *
   * @covers ::post
   */
  public function testPostSuccess(): void {
    $response = new Response(200, [], '{"revalidated": true}');
    $this->httpClient->expects($this->once())
      ->method('post')
      ->with(
        'https://example.com/api/revalidate',
        $this->callback(function ($options) {
          $this->assertArrayHasKey('headers', $options);
          $this->assertArrayHasKey('json', $options);
          $this->assertEquals('application/json', $options['headers']['Content-Type']);
          $this->assertEquals('secret123', $options['headers']['X-Next-Secret']);
          return TRUE;
        })
      )
      ->willReturn($response);

    $this->logger->expects($this->once())
      ->method('log')
      ->with($this->callback(function ($row) {
        $this->assertEquals(200, $row['http_code']);
        $this->assertEquals(1, $row['ok']);
        return TRUE;
      }));

    $this->state->expects($this->once())
      ->method('get')
      ->willReturn(0);

    $revalidator = $this->createRevalidator();
    $result = $revalidator->post(
      'https://example.com/api/revalidate',
      'secret123',
      ['tags' => ['content']],
      ['frontend' => 'default', 'domain' => 'content', 'scope' => 'all']
    );

    $this->assertTrue($result['ok']);
    $this->assertEquals(200, $result['status']);
    $this->assertNull($result['error']);
  }

  /**
   * Tests POST request failure with HTTP error.
   *
   * @covers ::post
   */
  public function testPostHttpError(): void {
    $response = new Response(500, [], 'Internal Server Error');
    $this->httpClient->expects($this->once())
      ->method('post')
      ->willReturn($response);

    $this->logger->expects($this->once())
      ->method('log')
      ->with($this->callback(function ($row) {
        $this->assertEquals(500, $row['http_code']);
        $this->assertEquals(0, $row['ok']);
        return TRUE;
      }));

    $this->state->expects($this->once())
      ->method('get')
      ->willReturn(0);

    $this->state->expects($this->once())
      ->method('set')
      ->with('wl_api.failcount.default.content.all', 1);

    $this->drupalLogger->expects($this->once())
      ->method('error');

    $this->alerts->expects($this->once())
      ->method('maybeAlert')
      ->with('default', 'content', 'all', 1, 'HTTP 500');

    $revalidator = $this->createRevalidator();
    $result = $revalidator->post(
      'https://example.com/api/revalidate',
      'secret123',
      ['tags' => ['content']],
      ['frontend' => 'default', 'domain' => 'content', 'scope' => 'all']
    );

    $this->assertFalse($result['ok']);
    $this->assertEquals(500, $result['status']);
  }

  /**
   * Tests POST request failure with exception.
   *
   * @covers ::post
   */
  public function testPostException(): void {
    $request = new Request('POST', 'https://example.com/api/revalidate');
    $exception = new RequestException('Connection refused', $request);

    $this->httpClient->expects($this->once())
      ->method('post')
      ->willThrowException($exception);

    $this->logger->expects($this->once())
      ->method('log')
      ->with($this->callback(function ($row) {
        $this->assertEquals(0, $row['http_code']);
        $this->assertEquals(0, $row['ok']);
        $this->assertStringContainsString('Connection refused', $row['message']);
        return TRUE;
      }));

    $this->state->expects($this->once())
      ->method('get')
      ->willReturn(2);

    $this->state->expects($this->once())
      ->method('set')
      ->with('wl_api.failcount.default.content.all', 3);

    $this->alerts->expects($this->once())
      ->method('maybeAlert')
      ->with('default', 'content', 'all', 3, 'Connection refused');

    $revalidator = $this->createRevalidator();
    $result = $revalidator->post(
      'https://example.com/api/revalidate',
      'secret123',
      ['tags' => ['content']],
      ['frontend' => 'default', 'domain' => 'content', 'scope' => 'all']
    );

    $this->assertFalse($result['ok']);
    $this->assertEquals(0, $result['status']);
    $this->assertEquals('Connection refused', $result['error']);
  }

  /**
   * Tests failure count reset on success after failures.
   *
   * @covers ::post
   */
  public function testFailureCountResetOnSuccess(): void {
    $response = new Response(200, [], '{"ok": true}');
    $this->httpClient->expects($this->once())
      ->method('post')
      ->willReturn($response);

    $this->state->expects($this->once())
      ->method('get')
      ->with('wl_api.failcount.default.content.all', 0)
      ->willReturn(5);

    $this->state->expects($this->once())
      ->method('set')
      ->with('wl_api.failcount.default.content.all', 0);

    $this->drupalLogger->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('recovered'),
        $this->callback(function ($context) {
          $this->assertEquals(5, $context['@count']);
          return TRUE;
        })
      );

    $this->logger->expects($this->once())->method('log');

    $revalidator = $this->createRevalidator();
    $result = $revalidator->post(
      'https://example.com/api/revalidate',
      '',
      [],
      ['frontend' => 'default', 'domain' => 'content', 'scope' => 'all']
    );

    $this->assertTrue($result['ok']);
  }

  /**
   * Tests that alert failure does not break revalidation.
   *
   * @covers ::post
   */
  public function testAlertFailureDoesNotBreakRevalidation(): void {
    $response = new Response(500, [], 'Error');
    $this->httpClient->expects($this->once())
      ->method('post')
      ->willReturn($response);

    $this->state->expects($this->once())
      ->method('get')
      ->willReturn(0);

    $this->alerts->expects($this->once())
      ->method('maybeAlert')
      ->willThrowException(new \RuntimeException('Alert service unavailable'));

    $this->drupalLogger->expects($this->exactly(2))
      ->method($this->logicalOr('error', 'warning'));

    $this->logger->expects($this->once())->method('log');

    $revalidator = $this->createRevalidator();
    $result = $revalidator->post(
      'https://example.com/api/revalidate',
      'secret',
      [],
      ['frontend' => 'default', 'domain' => 'test', 'scope' => 'scope']
    );

    // Should still return result despite alert failure.
    $this->assertFalse($result['ok']);
    $this->assertEquals(500, $result['status']);
  }

  /**
   * Tests POST without secret header.
   *
   * @covers ::post
   */
  public function testPostWithoutSecret(): void {
    $response = new Response(200, [], '{}');
    $this->httpClient->expects($this->once())
      ->method('post')
      ->with(
        'https://example.com/api/revalidate',
        $this->callback(function ($options) {
          $this->assertArrayNotHasKey('X-Next-Secret', $options['headers']);
          return TRUE;
        })
      )
      ->willReturn($response);

    $this->state->expects($this->once())->method('get')->willReturn(0);
    $this->logger->expects($this->once())->method('log');

    $revalidator = $this->createRevalidator();
    $result = $revalidator->post(
      'https://example.com/api/revalidate',
      '',
      [],
      []
    );

    $this->assertTrue($result['ok']);
  }

  /**
   * Tests latency tracking.
   *
   * @covers ::post
   */
  public function testLatencyTracking(): void {
    $response = new Response(200, [], '{}');
    $this->httpClient->expects($this->once())
      ->method('post')
      ->willReturn($response);

    $this->state->expects($this->once())->method('get')->willReturn(0);
    $this->logger->expects($this->once())->method('log');

    $revalidator = $this->createRevalidator();
    $result = $revalidator->post('https://example.com/api', '', [], []);

    $this->assertArrayHasKey('latency_ms', $result);
    $this->assertIsInt($result['latency_ms']);
    $this->assertGreaterThanOrEqual(0, $result['latency_ms']);
  }

}
