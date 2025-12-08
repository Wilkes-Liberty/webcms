<?php

namespace Drupal\wl_api\Controller;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;

/**
 * Renders wl_api event logs with basic filters.
 */
class LogsController extends ControllerBase {

  /**
   * Build logs page.
   *
   * @return array
   *   Render array with filters and table.
   */
  public function logs() {
    $req = \Drupal::request();
    $filters = [
      'frontend' => $req->query->get('frontend') ?? NULL,
      'domain' => $req->query->get('domain') ?? NULL,
      'scope' => $req->query->get('scope') ?? NULL,
      'action' => $req->query->get('action') ?? NULL,
    ];

    /** @var \Drupal\wl_api\Service\Logger $logger */
    $logger = $this->container->get('wl_api.logger');
    $rows = $logger->lastAttempts(array_filter($filters), 50);

    $header = [
      $this->t('When'), $this->t('Frontend'), $this->t('Domain'), $this->t('Scope'), $this->t('Action'), $this->t('HTTP'), $this->t('OK'), $this->t('ms'), $this->t('Message'),
    ];

    $tableRows = [];
    $df = $this->dateFormatter();
    foreach ($rows as $r) {
      $tableRows[] = [
        $df->format((int) $r->created, 'short'),
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
  // public function logsCsv(): Response {
  // }

}
