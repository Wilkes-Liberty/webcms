<?php

namespace Drupal\wl_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\wl_api\Service\FrontendManager;
use Drupal\wl_api\Service\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the API revalidation status overview page.
 */
class ApiStatusController extends ControllerBase {

  /**
   * Constructs an ApiStatusController.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Drupal\wl_api\Service\FrontendManager $frontendManager
   *   The frontend manager.
   * @param \Drupal\wl_api\Service\Logger $wlApiLogger
   *   The wl_api logger.
   */
  public function __construct(
    protected DateFormatterInterface $dateFormatter,
    protected FrontendManager $frontendManager,
    protected Logger $wlApiLogger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('wl_api.frontend_manager'),
      $container->get('wl_api.logger'),
    );
  }

  /**
   * Builds the status page render array.
   *
   * @return array
   *   A render array containing menu and global webhook statuses.
   */
  public function status(): array {
    $can_trigger = $this->currentUser()->hasPermission('trigger api revalidation');

    // Menus table.
    $menus_header = [
      $this->t('Menu'),
      $this->t('Webhook configured'),
      $this->t('Tag'),
      $this->t('Last status'),
      $this->t('Last revalidated at'),
      $this->t('Action'),
    ];

    $menus_rows = [];
    /** @var \Drupal\system\Entity\Menu[] $menus */
    $menus = $this->entityTypeManager()->getStorage('menu')->loadMultiple();
    $df = $this->dateFormatter;

    foreach ($menus as $menu) {
      $name = $menu->id();
      $title = $menu->label();
      $webhook = (string) $menu->getThirdPartySetting('wl_api', 'revalidate_webhook', '');
      $tag = (string) $menu->getThirdPartySetting('wl_api', 'revalidate_tag', 'menu');
      $rec = $this->state()->get('wl_api.revalidate.' . $name, NULL);
      $status = $rec['status'] ?? '-';
      $ts = $rec['timestamp'] ?? 0;
      $ok = isset($rec['ok']) ? ($rec['ok'] ? 'OK' : 'FAIL') : '-';
      $when = $ts ? $df->format($ts, 'short') : '-';

      $action = $can_trigger
        ? Link::fromTextAndUrl(
            $this->t('Revalidate now'),
            Url::fromRoute('wl_api.revalidate_menu_confirm', ['menu' => $name])
          )->toString()
        : $this->t('—');

      $menus_rows[] = [
        $this->t('@title (@name)', ['@title' => $title, '@name' => $name]),
        $webhook ? $this->t('Yes') : $this->t('No'),
        $tag ?: '-',
        $status === '-' ? '-' : $this->t('@status (@ok)', ['@status' => $status, '@ok' => $ok]),
        $when,
        ['data' => ['#markup' => $action]],
      ];
    }

    // Global hooks (content, taxonomy) table.
    $config = $this->config('wl_api.settings');
    $globals_header = [
      $this->t('Domain'),
      $this->t('Webhook configured'),
      $this->t('Tag'),
      $this->t('Last status'),
      $this->t('Last revalidated at'),
      $this->t('Action'),
    ];
    $globals_rows = [];

    foreach ([
      'content' => [
        'webhook' => (string) $config->get('revalidate_content_webhook'),
        'tag' => (string) ($config->get('revalidate_content_tag') ?? 'content'),
      ],
      'taxonomy' => [
        'webhook' => (string) $config->get('revalidate_taxonomy_webhook'),
        'tag' => (string) ($config->get('revalidate_taxonomy_tag') ?? 'taxonomy'),
      ],
    ] as $domain => $def) {
      $webhook = $def['webhook'] ?? '';
      $tag = $def['tag'] ?? '';
      $rec = $this->state()->get('wl_api.revalidate_global.' . $domain, NULL);
      $status = $rec['status'] ?? '-';
      $ts = $rec['timestamp'] ?? 0;
      $ok = isset($rec['ok']) ? ($rec['ok'] ? 'OK' : 'FAIL') : '-';
      $when = $ts ? $df->format($ts, 'short') : '-';

      $action = $can_trigger
        ? Link::fromTextAndUrl(
            $this->t('Revalidate now'),
            Url::fromRoute('wl_api.revalidate_global_confirm', ['hook' => $domain])
          )->toString()
        : $this->t('—');

      $globals_rows[] = [
        ucfirst($domain),
        $webhook ? $this->t('Yes') : $this->t('No'),
        $tag ?: '-',
        $status === '-' ? '-' : $this->t('@status (@ok)', ['@status' => $status, '@ok' => $ok]),
        $when,
        ['data' => ['#markup' => $action]],
      ];
    }

    // Frontends section with Test buttons and last 5 attempts.
    $frontends = $this->frontendManager->listFrontends();

    $frontend_rows = [];
    $frontend_header = [
      $this->t('Frontend'),
      $this->t('Revalidate URL'),
      $this->t('Health'),
      $this->t('CI'),
      $this->t('Stats'),
      $this->t('Actions'),
      $this->t('Recent'),
    ];
    foreach ($frontends as $id => $fe) {
      $stats = $this->wlApiLogger->stats($id, 'test');
      $statsMarkup = $stats['count']
        ? $this->t('ok @ok%% p95 @p95ms', [
          '@ok' => $stats['success_rate'],
          '@p95' => $stats['p95'],
        ])
        : $this->t('—');
      $actions = $this->currentUser()->hasPermission('run api checks')
        ? Link::fromTextAndUrl(
          $this->t('Test now'),
          Url::fromRoute('wl_api.test_endpoint_confirm', [
            'frontend' => $id,
            'domain' => 'test',
            'scope' => 'ping',
          ])
        )->toString()
        : $this->t('—');
      $recent = $this->wlApiLogger->lastAttempts(['frontend' => $id], 5);
      $recentMarkup = [];
      foreach ($recent as $r) {
        $recentMarkup[] = $df->format((int) $r->created, 'short') . ' ' . ($r->ok ? '✓' : '✗') . ' ' . (int) $r->latency_ms . 'ms';
      }

      $frontend_rows[] = [
        $fe['label'] ?? $id,
        $fe['revalidate_webhook'] ?: '—',
        !empty($fe['health_url']) ? Link::fromTextAndUrl($this->t('Open'), Url::fromUri($fe['health_url']))->toString() : '—',
        !empty($fe['ci_url']) ? Link::fromTextAndUrl($this->t('Open'), Url::fromUri($fe['ci_url']))->toString() : '—',
        ['data' => ['#markup' => $statsMarkup]],
        ['data' => ['#markup' => $actions]],
        ['data' => ['#markup' => implode('<br>', $recentMarkup)]],
      ];
    }

    return [
      'menus_title' => ['#markup' => '<h2>' . $this->t('Menus') . '</h2>'],
      'menus' => [
        '#type' => 'table',
        '#header' => $menus_header,
        '#rows' => $menus_rows,
        '#empty' => $this->t('No menus found.'),
      ],
      'globals_title' => ['#markup' => '<h2>' . $this->t('Global webhooks') . '</h2>'],
      'globals' => [
        '#type' => 'table',
        '#header' => $globals_header,
        '#rows' => $globals_rows,
        '#empty' => $this->t('No global webhooks configured.'),
      ],
      'frontends_title' => ['#markup' => '<h2>' . $this->t('Frontends') . '</h2>'],
      'frontends' => [
        '#type' => 'table',
        '#header' => $frontend_header,
        '#rows' => $frontend_rows,
        '#empty' => $this->t('No frontends configured (using legacy defaults).'),
      ],
    ];
  }

}
