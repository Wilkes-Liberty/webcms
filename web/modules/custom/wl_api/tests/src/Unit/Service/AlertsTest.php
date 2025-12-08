<?php

namespace Drupal\Tests\wl_api\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\wl_api\Service\Alerts;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * @coversDefaultClass \Drupal\wl_api\Service\Alerts
 * @group wl_api
 */
class AlertsTest extends UnitTestCase {

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The HTTP client mock.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The logger mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The mail manager mock.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
  }

  /**
   * Creates an Alerts instance with mocked dependencies.
   *
   * @return \Drupal\wl_api\Service\Alerts
   *   The alerts service.
   */
  protected function createAlerts(): Alerts {
    return new Alerts(
      $this->configFactory,
      $this->httpClient,
      $this->logger,
      $this->mailManager,
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
   * Tests that alert is not sent when threshold is zero.
   *
   * @covers ::maybeAlert
   */
  public function testNoAlertWhenThresholdZero(): void {
    $config = $this->createConfig([
      'alerts.threshold' => 0,
    ]);
    $this->configFactory->method('get')->with('wl_api.settings')->willReturn($config);

    $this->httpClient->expects($this->never())->method('post');
    $this->mailManager->expects($this->never())->method('mail');

    $alerts = $this->createAlerts();
    $alerts->maybeAlert('frontend', 'domain', 'scope', 5, 'Error message');
  }

  /**
   * Tests that alert is not sent when below threshold.
   *
   * @covers ::maybeAlert
   */
  public function testNoAlertWhenBelowThreshold(): void {
    $config = $this->createConfig([
      'alerts.threshold' => 5,
    ]);
    $this->configFactory->method('get')->with('wl_api.settings')->willReturn($config);

    $this->httpClient->expects($this->never())->method('post');
    $this->mailManager->expects($this->never())->method('mail');

    $alerts = $this->createAlerts();
    $alerts->maybeAlert('frontend', 'domain', 'scope', 4, 'Error message');
  }

  /**
   * Tests Slack alert sent when threshold reached.
   *
   * @covers ::maybeAlert
   */
  public function testSlackAlertSentAtThreshold(): void {
    $config = $this->createConfig([
      'alerts.threshold' => 3,
      'alerts.slack_webhook' => 'https://hooks.slack.com/services/xxx',
      'alerts.emails' => [],
    ]);
    $this->configFactory->method('get')->with('wl_api.settings')->willReturn($config);

    $this->httpClient->expects($this->once())
      ->method('post')
      ->with(
        'https://hooks.slack.com/services/xxx',
        $this->callback(function ($options) {
          $this->assertArrayHasKey('json', $options);
          $this->assertArrayHasKey('text', $options['json']);
          $this->assertStringContainsString('[wl_api]', $options['json']['text']);
          $this->assertStringContainsString('frontend', $options['json']['text']);
          $this->assertStringContainsString('3 times', $options['json']['text']);
          return TRUE;
        })
      );

    $alerts = $this->createAlerts();
    $alerts->maybeAlert('frontend', 'domain', 'scope', 3, 'Connection failed');
  }

  /**
   * Tests email alert sent when threshold reached.
   *
   * @covers ::maybeAlert
   */
  public function testEmailAlertSentAtThreshold(): void {
    $config = $this->createConfig([
      'alerts.threshold' => 2,
      'alerts.slack_webhook' => '',
      'alerts.emails' => ['admin@example.com', 'ops@example.com'],
    ]);
    $this->configFactory->method('get')->with('wl_api.settings')->willReturn($config);

    $this->mailManager->expects($this->exactly(2))
      ->method('mail')
      ->with(
        'wl_api',
        'alert',
        $this->logicalOr('admin@example.com', 'ops@example.com'),
        'en',
        $this->callback(function ($params) {
          $this->assertArrayHasKey('message', $params);
          $this->assertStringContainsString('[wl_api]', $params['message']);
          return TRUE;
        })
      );

    $alerts = $this->createAlerts();
    $alerts->maybeAlert('frontend', 'domain', 'scope', 2, 'HTTP 500');
  }

  /**
   * Tests both Slack and email alerts sent together.
   *
   * @covers ::maybeAlert
   */
  public function testBothSlackAndEmailAlertsSent(): void {
    $config = $this->createConfig([
      'alerts.threshold' => 1,
      'alerts.slack_webhook' => 'https://hooks.slack.com/test',
      'alerts.emails' => ['test@example.com'],
    ]);
    $this->configFactory->method('get')->with('wl_api.settings')->willReturn($config);

    $this->httpClient->expects($this->once())->method('post');
    $this->mailManager->expects($this->once())->method('mail');

    $alerts = $this->createAlerts();
    $alerts->maybeAlert('frontend', 'domain', 'scope', 1, 'Error');
  }

  /**
   * Tests Slack failure is logged but does not throw.
   *
   * @covers ::maybeAlert
   */
  public function testSlackFailureLogged(): void {
    $config = $this->createConfig([
      'alerts.threshold' => 1,
      'alerts.slack_webhook' => 'https://hooks.slack.com/test',
      'alerts.emails' => [],
    ]);
    $this->configFactory->method('get')->with('wl_api.settings')->willReturn($config);

    $request = new Request('POST', 'https://hooks.slack.com/test');
    $this->httpClient->expects($this->once())
      ->method('post')
      ->willThrowException(new RequestException('Slack unavailable', $request));

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Slack alert failed'),
        $this->callback(function ($context) {
          $this->assertStringContainsString('Slack unavailable', $context['@e']);
          return TRUE;
        })
      );

    $alerts = $this->createAlerts();
    // Should not throw exception.
    $alerts->maybeAlert('frontend', 'domain', 'scope', 1, 'Error');
  }

  /**
   * Tests email failure is logged but does not throw.
   *
   * @covers ::maybeAlert
   */
  public function testEmailFailureLogged(): void {
    $config = $this->createConfig([
      'alerts.threshold' => 1,
      'alerts.slack_webhook' => '',
      'alerts.emails' => ['test@example.com'],
    ]);
    $this->configFactory->method('get')->with('wl_api.settings')->willReturn($config);

    $this->mailManager->expects($this->once())
      ->method('mail')
      ->willThrowException(new \RuntimeException('Mail server unavailable'));

    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('Email alert failed'),
        $this->callback(function ($context) {
          $this->assertStringContainsString('Mail server unavailable', $context['@e']);
          return TRUE;
        })
      );

    $alerts = $this->createAlerts();
    // Should not throw exception.
    $alerts->maybeAlert('frontend', 'domain', 'scope', 1, 'Error');
  }

  /**
   * Tests alert message formatting.
   *
   * @covers ::maybeAlert
   */
  public function testAlertMessageFormatting(): void {
    $config = $this->createConfig([
      'alerts.threshold' => 1,
      'alerts.slack_webhook' => 'https://hooks.slack.com/test',
      'alerts.emails' => [],
    ]);
    $this->configFactory->method('get')->with('wl_api.settings')->willReturn($config);

    $capturedMessage = NULL;
    $this->httpClient->expects($this->once())
      ->method('post')
      ->with(
        $this->anything(),
        $this->callback(function ($options) use (&$capturedMessage) {
          $capturedMessage = $options['json']['text'];
          return TRUE;
        })
      );

    $alerts = $this->createAlerts();
    $alerts->maybeAlert('my_frontend', 'content', '/path/to/page', 10, 'Timeout after 30s');

    $this->assertStringContainsString('my_frontend/content//path/to/page', $capturedMessage);
    $this->assertStringContainsString('10 times', $capturedMessage);
    $this->assertStringContainsString('Timeout after 30s', $capturedMessage);
  }

}
