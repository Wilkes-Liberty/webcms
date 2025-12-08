<?php

namespace Drupal\wl_api\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commands for wl_api utilities.
 */
class WlApiCommands extends DrushCommands {

  /**
   * Revalidate a tag.
   *
   * @command wl-api:revalidate:tag
   * @aliases wl-api:rt
   * @option frontend Frontend ID (default: default)
   * @argument tag Tag to revalidate
   */
  public function revalidateTag($tag, $options = ['frontend' => 'default']) {
    $frontend = (string) $options['frontend'];
    /** @var \Drupal\wl_api\Service\FrontendManager $fm */
    $fm = \Drupal::service('wl_api.frontend_manager');
    /** @var \Drupal\wl_api\Service\Revalidator $rv */
    $rv = \Drupal::service('wl_api.revalidator');
    $fe = $fm->listFrontends()[$frontend] ?? NULL;
    if (!$fe || empty($fe['revalidate_webhook'])) {
      $this->logger()->error('No endpoint.');
      return;
    }
    $secret = $fm->resolveSecret($fe['secret'] ?? '');
    $rv->post(
      $fe['revalidate_webhook'],
      $secret,
      ['tag' => $tag, 'domain' => 'content'],
      [
        'frontend' => $frontend,
        'domain' => 'content',
        'scope' => $tag,
        'action' => 'revalidate',
      ]
    );
    $this->logger()->success("Triggered tag $tag on $frontend");
  }

  /**
   * Revalidate a path.
   *
   * @command wl-api:revalidate:path
   * @aliases wl-api:rp
   * @option frontend Frontend ID (default: default)
   * @argument path Path to revalidate
   */
  public function revalidatePath($path, $options = ['frontend' => 'default']) {
    $frontend = (string) $options['frontend'];
    /** @var \Drupal\wl_api\Service\FrontendManager $fm */
    $fm = \Drupal::service('wl_api.frontend_manager');
    /** @var \Drupal\wl_api\Service\Revalidator $rv */
    $rv = \Drupal::service('wl_api.revalidator');
    $fe = $fm->listFrontends()[$frontend] ?? NULL;
    if (!$fe) {
      $this->logger()->error('Unknown frontend.');
      return;
    }
    $endpoint = $fe['path_revalidate_webhook'] ?? $fe['revalidate_webhook'] ?? '';
    if (!$endpoint) {
      $this->logger()->error('No endpoint.');
      return;
    }
    $secret = $fm->resolveSecret($fe['secret'] ?? '');
    $rv->post(
      $endpoint,
      $secret,
      ['path' => $path, 'domain' => 'path'],
      [
        'frontend' => $frontend,
        'domain' => 'path',
        'scope' => $path,
        'action' => 'revalidate',
      ]
    );
    $this->logger()->success("Triggered path $path on $frontend");
  }

  /**
   * Test endpoint.
   *
   * @command wl-api:test
   * @option frontend Frontend ID (default: default)
   */
  public function test($options = ['frontend' => 'default']) {
    $frontend = (string) $options['frontend'];
    /** @var \Drupal\wl_api\Service\FrontendManager $fm */
    $fm = \Drupal::service('wl_api.frontend_manager');
    /** @var \Drupal\wl_api\Service\Revalidator $rv */
    $rv = \Drupal::service('wl_api.revalidator');
    $fe = $fm->listFrontends()[$frontend] ?? NULL;
    if (!$fe || empty($fe['revalidate_webhook'])) {
      $this->logger()->error('No endpoint.');
      return;
    }
    $secret = $fm->resolveSecret($fe['secret'] ?? '');
    $rv->post(
      $fe['revalidate_webhook'],
      $secret,
      ['__action' => 'test', 'tag' => 'health', 'domain' => 'test'],
      [
        'frontend' => $frontend,
        'domain' => 'test',
        'scope' => 'ping',
        'action' => 'test',
      ]
    );
    $this->logger()->success("Test executed on $frontend");
  }

  /**
   * Show recent logs.
   *
   * @command wl-api:logs
   * @option limit Number of rows
   */
  public function logs($options = ['limit' => 20]) {
    $limit = (int) $options['limit'];
    /** @var \Drupal\wl_api\Service\Logger $logger */
    $logger = \Drupal::service('wl_api.logger');
    $rows = $logger->lastAttempts([], $limit);
    foreach ($rows as $r) {
      $this->output()->writeln(sprintf('%s %s/%s %s %d %s %dms %s', date('Y-m-d H:i', (int) $r->created), $r->frontend, $r->domain, $r->scope, $r->http_code, $r->ok ? 'OK' : 'FAIL', $r->latency_ms, $r->message));
    }
  }

}
