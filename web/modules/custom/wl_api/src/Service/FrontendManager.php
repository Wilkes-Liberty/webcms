<?php

namespace Drupal\wl_api\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\\Core\\Extension\\ModuleHandlerInterface;
use Drupal\\Core\\Entity\\EntityTypeManagerInterface;
use Drupal\\key\\KeyRepositoryInterface;

/**
 * Manages Frontend configurations and secrets.
 */
class FrontendManager {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected ?KeyRepositoryInterface $keyRepo = NULL,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * List frontends (fallback to a single default if none configured).
   *
   * @return array<string,array>
   *   Frontends keyed by id with fields: label, revalidate_webhook, path_revalidate_webhook,
   *   secret (string|null), health_url, ci_url.
   */
  public function listFrontends(): array {
    // Try config entity first.
    try {
      $storage = $this->entityTypeManager->getStorage('wl_api_frontend');
      if ($storage) {
        $entities = $storage->loadMultiple();
        if ($entities) {
          $out = [];
          foreach ($entities as $fe) {
            $out[$fe->id()] = [
              'id' => $fe->id(),
              'label' => $fe->label(),
              'revalidate_webhook' => (string) $fe->get('revalidate_webhook'),
              'path_revalidate_webhook' => (string) $fe->get('path_revalidate_webhook'),
              'secret' => (string) $fe->get('secret'),
              'health_url' => (string) $fe->get('health_url'),
              'ci_url' => (string) $fe->get('ci_url'),
            ];
          }
          if ($out) {
            return $out;
          }
        }
      }
    }
    catch (\Throwable $e) {
      // Fall back below.
    }
    // Fallback to single frontend based on legacy settings.
    $config = $this->configFactory->get('wl_api.settings');
    $single = [
      'id' => 'default',
      'label' => 'Default',
      'revalidate_webhook' => (string) ($config->get('revalidate_content_webhook') ?? ''),
      'path_revalidate_webhook' => (string) ($config->get('revalidate_path_webhook') ?? ''),
      'secret' => (string) ($config->get('revalidate_secret') ?? ''),
      'health_url' => (string) ($config->get('frontend_health_url') ?? ''),
      'ci_url' => (string) ($config->get('frontend_ci_url') ?? ''),
    ];
    return ['default' => $single];
  }

  /**
   * Resolve secret: prefer Key module if configured, else plain string.
   */
  public function resolveSecret(?string $keyIdOrSecret): string {
    if ($keyIdOrSecret === NULL || $keyIdOrSecret === '') {
      return '';
    }
    if ($this->moduleHandler->moduleExists('key') && $this->keyRepo) {
      // If a Key exists with this id, return its value; otherwise assume raw secret.
      try {
        $key = $this->keyRepo->getKey($keyIdOrSecret);
        if ($key) {
          return (string) $key->getKeyValue();
        }
      }
      catch (\Throwable $e) {
        // Fall through to raw secret.
      }
    }
    return (string) $keyIdOrSecret;
  }

}
