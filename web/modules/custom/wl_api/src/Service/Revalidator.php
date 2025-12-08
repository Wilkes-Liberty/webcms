<?php

namespace Drupal\wl_api\Service;

use Drupal\Core\Session\AccountProxyInterface;
use GuzzleHttp\ClientInterface;

/**
 * Handles posting to Next.js revalidate endpoints and logs results.
 */
class Revalidator {

  public function __construct(
    protected ClientInterface $httpClient,
    protected FrontendManager $frontends,
    protected Logger $logger,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Low-level POST with timing + logging metadata.
   *
   * @param string $endpoint
   *   Absolute URL of the endpoint.
   * @param string $secret
   *   Shared secret to send in X-Next-Secret, if any.
   * @param array $payload
   *   JSON payload to send.
   * @param array $meta
   *   Metadata: frontend, domain, scope, action.
   *
   * @return array
   *   Result: status, ok, latency_ms, and optional error.
   */
  public function post(string $endpoint, string $secret, array $payload, array $meta): array {
    $headers = ['Content-Type' => 'application/json'];
    if ($secret !== '') {
      $headers['X-Next-Secret'] = $secret;
    }
    $start = microtime(TRUE);
    $status = 0;
    $ok = FALSE;
    $error = NULL;
    $bodySnippet = '';
    try {
      $resp = $this->httpClient->post($endpoint, ['headers' => $headers, 'json' => $payload, 'timeout' => 10]);
      $status = (int) $resp->getStatusCode();
      $ok = ($status >= 200 && $status < 300);
      $body = (string) $resp->getBody();
      $bodySnippet = substr($body, 0, 5000);
    }
    catch (\Throwable $e) {
      $error = $e->getMessage();
    }
    $latency = (int) round((microtime(TRUE) - $start) * 1000);

    $this->logger->log([
      'frontend' => $meta['frontend'] ?? 'default',
      'domain' => $meta['domain'] ?? '',
      'scope' => $meta['scope'] ?? '',
      'action' => $meta['action'] ?? 'revalidate',
      'endpoint' => $endpoint,
      'http_code' => $status,
      'ok' => $ok ? 1 : 0,
      'latency_ms' => $latency,
      'message' => $error ? substr($error, 0, 255) : '',
      'response' => $bodySnippet,
    ]);

    // Track consecutive failures and alert if needed.
    $frontend = (string) ($meta['frontend'] ?? 'default');
    $domain = (string) ($meta['domain'] ?? '');
    $scope = (string) ($meta['scope'] ?? '');
    $key = sprintf('wl_api.failcount.%s.%s.%s', $frontend, $domain, $scope);
    $state = \Drupal::state();
    $count = (int) $state->get($key, 0);
    if ($ok) {
      if ($count) {
        $state->set($key, 0);
      }
    }
    else {
      $count++;
      $state->set($key, $count);
      try {
        /** @var \Drupal\wl_api\Service\Alerts $alerts */
        $alerts = \Drupal::service('wl_api.alerts');
        $alerts->maybeAlert($frontend, $domain, $scope, $count, $error ?: ('HTTP ' . $status));
      }
      catch (\Throwable $e) {
        // Do not break revalidation on alert failure.
      }
    }

    return ['status' => $status, 'ok' => $ok, 'latency_ms' => $latency, 'error' => $error];
  }

}
