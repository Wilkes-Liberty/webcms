<?php

namespace Drupal\wl_api\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Handles failure alerts via Slack/email.
 */
class Alerts {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ClientInterface $httpClient,
    protected LoggerChannelInterface $logger,
    protected MailManagerInterface $mailManager,
  ) {}

  /**
   * Send an alert (stub for future use in revalidator/log pipeline).
   */
  public function maybeAlert(string $frontend, string $domain, string $scope, int $consecutiveFailures, string $message): void {
    $config = $this->configFactory->get('wl_api.settings');
    $threshold = (int) ($config->get('alerts.threshold') ?? 0);
    if ($threshold <= 0 || $consecutiveFailures < $threshold) {
      return;
    }
    $slack = (string) ($config->get('alerts.slack_webhook') ?? '');
    $emails = (array) ($config->get('alerts.emails') ?? []);
    $text = sprintf('[wl_api] %s/%s/%s failed %d times: %s', $frontend, $domain, $scope, $consecutiveFailures, $message);

    if ($slack) {
      try {
        $this->httpClient->post($slack, ['json' => ['text' => $text], 'timeout' => 5]);
      }
      catch (\Throwable $e) {
        $this->logger->warning('Slack alert failed: @e', ['@e' => $e->getMessage()]);
      }
    }
    foreach ($emails as $to) {
      try {
        $this->mailManager->mail('wl_api', 'alert', $to, 'en', ['message' => $text]);
      }
      catch (\Throwable $e) {
        $this->logger->warning('Email alert failed: @e', ['@e' => $e->getMessage()]);
      }
    }
  }

}
