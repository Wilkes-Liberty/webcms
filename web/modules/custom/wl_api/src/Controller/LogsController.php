<?php

namespace Drupal\wl_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\wl_api\Service\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders wl_api event logs with basic filters.
 */
class LogsController extends ControllerBase {

  /**
   * Constructs a LogsController.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\wl_api\Service\Logger $wlApiLogger
   *   The wl_api logger service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    private RequestStack $requestStack,
    private Logger $wlApiLogger,
    private DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('wl_api.logger'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Build logs page.
   *
   * @return array
   *   Render array with filters and table.
   */
  public function logs(): array {
    $req = $this->requestStack->getCurrentRequest();
    $filters = [
      'frontend' => $req->query->get('frontend') ?? NULL,
      'domain' => $req->query->get('domain') ?? NULL,
      'scope' => $req->query->get('scope') ?? NULL,
      'action' => $req->query->get('action') ?? NULL,
    ];

    $rows = $this->wlApiLogger->lastAttempts(array_filter($filters), 50);

    $header = [
      $this->t('When'), $this->t('Frontend'), $this->t('Domain'), $this->t('Scope'), $this->t('Action'), $this->t('HTTP'), $this->t('OK'), $this->t('ms'), $this->t('Message'),
    ];

    $tableRows = [];
    foreach ($rows as $r) {
      $tableRows[] = [
        $this->dateFormatter->format((int) $r->created, 'short'),
        $r->frontend,
        $r->domain,
        $r->scope,
        $r->action,
        $r->http_code,
        $r->ok ? '✓' : '✗',
        $r->latency_ms,
        $r->message,
      ];
    }

    $build['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
      '#open' => TRUE,
      'form' => [
        '#type' => 'container',
        'frontend' => [
          '#type' => 'textfield',
          '#title' => $this->t('Frontend'),
          '#default_value' => $filters['frontend'] ?? '',
        ],
        'domain' => [
          '#type' => 'textfield',
          '#title' => $this->t('Domain'),
          '#default_value' => $filters['domain'] ?? '',
        ],
        'scope' => [
          '#type' => 'textfield',
          '#title' => $this->t('Scope'),
          '#default_value' => $filters['scope'] ?? '',
        ],
        'action' => [
          '#type' => 'textfield',
          '#title' => $this->t('Action'),
          '#default_value' => $filters['action'] ?? '',
        ],
        'apply' => [
          '#type' => 'submit',
          '#value' => $this->t('Apply'),
          '#submit' => [[get_class($this), 'submitFilters']],
        ],
      ],
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $tableRows,
      '#empty' => $this->t('No events yet.'),
    ];

    return $build;
  }

  /**
   * Submit callback to apply log filters.
   */
  public static function submitFilters(array &$form, FormState $form_state) {
    $values = $form_state->getValues();
    $q = [];
    foreach (['frontend', 'domain', 'scope', 'action'] as $k) {
      if (!empty($values[$k])) {
        $q[$k] = $values[$k];
      }
    }
    $form_state->setResponse(new RedirectResponse(Url::fromRoute('wl_api.logs', [], ['query' => $q])->toString()));
  }

  /**
   * CSV export is added in PR2.
   */

  /**
   * CSV export for logs with current filters.
   */
  public function logsCsv(): Response {
    $req = $this->requestStack->getCurrentRequest();
    $filters = [
      'frontend' => $req->query->get('frontend') ?? NULL,
      'domain' => $req->query->get('domain') ?? NULL,
      'scope' => $req->query->get('scope') ?? NULL,
      'action' => $req->query->get('action') ?? NULL,
    ];
    $rows = $this->wlApiLogger->lastAttempts(array_filter($filters), 500);

    $out = fopen('php://temp', 'r+');
    fputcsv($out, ['when', 'frontend', 'domain', 'scope', 'action', 'http', 'ok', 'ms', 'message']);
    foreach ($rows as $r) {
      fputcsv($out, [
        $this->dateFormatter->format((int) $r->created, 'short'),
        $r->frontend, $r->domain, $r->scope, $r->action,
        $r->http_code, $r->ok ? '1' : '0', $r->latency_ms, $r->message,
      ]);
    }
    rewind($out);
    $csv = stream_get_contents($out);
    return new Response($csv, 200, [
      'Content-Type' => 'text/csv; charset=UTF-8',
      'Content-Disposition' => 'attachment; filename="wl_api_logs.csv"',
    ]);
  }

}
