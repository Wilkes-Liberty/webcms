<?php

namespace Drupal\wl_api\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\wl_api\Service\Revalidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes wl_api revalidation queue items.
 *
 * @QueueWorker(
 *   id = "wl_api_revalidate",
 *   title = @Translation("WL API revalidate queue"),
 *   cron = {"time" = 60}
 * )
 */
class WlApiRevalidateWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a WlApiRevalidateWorker object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\wl_api\Service\Revalidator $revalidator
   *   The revalidator service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected Revalidator $revalidator,
    protected LoggerChannelInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('wl_api.revalidator'),
      $container->get('logger.channel.wl_api'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    try {
      $endpoint = $data['endpoint'] ?? '';
      $secret = $data['secret'] ?? '';
      $payload = $data['payload'] ?? [];
      $meta = $data['meta'] ?? [];

      if (empty($endpoint)) {
        $this->logger->error('Queue item missing endpoint: @data', [
          '@data' => json_encode($data),
        ]);
        return;
      }

      $result = $this->revalidator->post($endpoint, $secret, $payload, $meta);

      if (!$result['ok']) {
        $this->logger->warning('Queue revalidation failed for @endpoint: @error', [
          '@endpoint' => $endpoint,
          '@error' => $result['error'] ?? ('HTTP ' . $result['status']),
        ]);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Queue worker exception: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

}
