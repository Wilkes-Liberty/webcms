<?php

namespace Drupal\wl_api\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes wl_api revalidation queue items.
 *
 * @QueueWorker(
 *   id = "wl_api_revalidate",
 *   title = @Translation("WL API revalidate queue"),
 *   cron = {"time" = 60}
 * )
 */
class WlApiRevalidateWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc} */
  public function processItem($data) {
    /** @var \Drupal\wl_api\Service\Revalidator $rv */
    $rv = \Drupal::service('wl_api.revalidator');
    $rv->post($data['endpoint'], $data['secret'] ?? '', $data['payload'] ?? [], $data['meta'] ?? []);
  }

}
