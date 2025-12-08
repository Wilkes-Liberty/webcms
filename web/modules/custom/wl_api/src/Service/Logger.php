<?php

namespace Drupal\wl_api\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Persists wl_api events and computes simple stats.
 */
class Logger {

  public function __construct(
    protected Connection $db,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * Record an event row.
   *
   * @return int
   *   Inserted event ID (eid).
   */
  public function log(array $row): int {
    $row += [
      'created' => time(),
      'uid' => (int) $this->currentUser->id(),
      'frontend' => 'default',
      'domain' => '',
      'scope' => '',
      'action' => 'revalidate',
      'endpoint' => '',
      'http_code' => 0,
      'ok' => 0,
      'latency_ms' => 0,
      'message' => '',
      'response' => NULL,
    ];
    $this->db->insert('wl_api_event')->fields($row)->execute();
    return (int) $this->db->lastInsertId();
  }

  /**
   * Fetch recent attempts.
   */
  public function lastAttempts(array $filters = [], int $limit = 5): array {
    $q = $this->db->select('wl_api_event', 'e')->fields('e')->orderBy('created', 'DESC')->range(0, $limit);
    foreach (['frontend', 'domain', 'scope', 'action'] as $k) {
      if (!empty($filters[$k])) {
        $q->condition($k, $filters[$k]);
      }
    }
    return $q->execute()->fetchAllAssoc('eid');
  }

  /**
   * Compute simple stats from the last N events.
   */
  public function stats(string $frontend, string $domain, array $scopeFilter = [], int $n = 50): array {
    $q = $this->db->select('wl_api_event', 'e')->fields('e', ['ok', 'latency_ms'])->condition('frontend', $frontend)->condition('domain', $domain)->orderBy('created', 'DESC')->range(0, $n);
    if (!empty($scopeFilter['scope'])) {
      $q->condition('scope', $scopeFilter['scope']);
    }
    $rows = $q->execute()->fetchAll();
    if (!$rows) {
      return ['count' => 0, 'success_rate' => NULL, 'p95' => NULL];
    }
    $count = count($rows);
    $success = 0;
    $latencies = [];
    foreach ($rows as $r) {
      $success += (int) $r->ok;
      $latencies[] = (int) $r->latency_ms;
    }
    sort($latencies);
    $idx = (int) floor(0.95 * (count($latencies) - 1));
    return [
      'count' => $count,
      'success_rate' => $count ? round($success * 100 / $count, 1) : NULL,
      'p95' => $latencies[$idx] ?? NULL,
    ];
  }

}
