<?php

namespace Drupal\wl_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Public system-status endpoint for the Next.js landing page.
 *
 * Probes infrastructure services Drupal can reach internally
 * (database, cache, search, internal hosts) and returns a JSON status
 * report. Cached for 10s via response headers.
 */
class SystemStatusController extends ControllerBase {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  public function __construct(Connection $database, ClientInterface $http_client) {
    $this->database = $database;
    $this->httpClient = $http_client;
  }

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
      $container->get('http_client')
    );
  }

  /**
   * GET /api/system-status
   */
  public function status(): JsonResponse {
    $services = [
      'data' => $this->checkPostgres(),
      'cache' => $this->checkRedis(),
      'search' => $this->checkSolr(),
      'mesh' => $this->checkTailscale(),
      'monitoring' => $this->checkPrometheus(),
      'storage' => $this->checkNas(),
    ];

    $response = new JsonResponse([
      'timestamp' => gmdate('c'),
      'services' => $services,
    ]);

    // Cache 10s at the edge / browser to avoid hammering backends.
    $response->setPublic();
    $response->setMaxAge(10);
    $response->setSharedMaxAge(10);
    $response->headers->set('Access-Control-Allow-Origin', '*');

    return $response;
  }

  /**
   * Postgres: trivial query.
   */
  private function checkPostgres(): array {
    $start = microtime(TRUE);
    try {
      $this->database->query('SELECT 1')->fetchField();
      return ['up' => TRUE, 'latencyMs' => $this->ms($start)];
    }
    catch (\Throwable $e) {
      return ['up' => FALSE, 'latencyMs' => $this->ms($start)];
    }
  }

  /**
   * Redis: ping via the redis client if module is present, else cache write.
   */
  private function checkRedis(): array {
    $start = microtime(TRUE);
    try {
      if ($this->moduleHandler()->moduleExists('redis')) {
        // ClientFactory is the simplest way to get the client.
        $client = \Drupal::service('redis.factory')->getClient();
        $pong = $client->ping();
        $up = $pong === TRUE || $pong === '+PONG' || $pong === 'PONG';
        return ['up' => (bool) $up, 'latencyMs' => $this->ms($start)];
      }
      // Fallback: write+read a sentinel key via the default cache backend.
      $cache = \Drupal::cache();
      $cache->set('wl_api:status_probe', 'ok', time() + 5);
      $hit = $cache->get('wl_api:status_probe');
      return ['up' => (bool) ($hit && $hit->data === 'ok'), 'latencyMs' => $this->ms($start)];
    }
    catch (\Throwable $e) {
      return ['up' => FALSE, 'latencyMs' => $this->ms($start)];
    }
  }

  /**
   * Solr: HTTP ping to admin core endpoint (or root if no core).
   */
  private function checkSolr(): array {
    return $this->checkHttp('http://solr:8983/solr/admin/cores?action=STATUS', 2);
  }

  /**
   * Tailscale mesh: try reaching the VPS Tailscale IP.
   * (Falls back to "up" if Drupal itself can resolve api.wilkesliberty.com — that
   * implies network is healthy enough to serve us.)
   */
  private function checkTailscale(): array {
    // VPS Tailscale IP (we don't expose this in public env; treat reachability of
    // the VPS public hostname as a proxy signal).
    return $this->checkHttp('https://www.wilkesliberty.com', 3);
  }

  /**
   * Prometheus: HTTP to its API on the on-prem network.
   */
  private function checkPrometheus(): array {
    return $this->checkHttp('http://prometheus:9090/-/healthy', 2);
  }

  /**
   * NAS: try the LAN admin port (Synology DSM).
   */
  private function checkNas(): array {
    // Use config-stored IP if available, else hardcode. Setting via env keeps
    // the LAN IP out of the Drupal config exports.
    $host = getenv('NAS_INTERNAL_HOST') ?: '192.168.4.60';
    $port = getenv('NAS_INTERNAL_PORT') ?: '5001';
    return $this->checkHttp("https://$host:$port/", 2, TRUE);
  }

  /**
   * Generic HTTP up-check.
   */
  private function checkHttp(string $url, float $timeout, bool $allowSelfSigned = FALSE): array {
    $start = microtime(TRUE);
    try {
      $response = $this->httpClient->request('GET', $url, [
        'timeout' => $timeout,
        'connect_timeout' => $timeout,
        'http_errors' => FALSE,
        'verify' => !$allowSelfSigned,
      ]);
      $code = $response->getStatusCode();
      // Treat 2xx and 3xx as up; many devices return 302 for root.
      $up = $code >= 200 && $code < 400;
      return ['up' => $up, 'latencyMs' => $this->ms($start), 'status' => $code];
    }
    catch (GuzzleException | \Throwable $e) {
      return ['up' => FALSE, 'latencyMs' => $this->ms($start)];
    }
  }

  private function ms(float $start): int {
    return (int) round((microtime(TRUE) - $start) * 1000);
  }

}
