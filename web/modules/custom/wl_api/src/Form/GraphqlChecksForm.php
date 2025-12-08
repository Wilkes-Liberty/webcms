<?php

namespace Drupal\wl_api\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;

/**
 * Admin UI to run saved GraphQL checks against the configured endpoint.
 */
class GraphqlChecksForm extends FormBase {

  /**
   * Constructs a GraphqlChecksForm.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   */
  public function __construct(
    private ClientInterface $httpClient,
    private StateInterface $state,
    private TimeInterface $time,
    private DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create($container) {
    return new static(
      $container->get('http_client'),
      $container->get('state'),
      $container->get('datetime.time'),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wl_api_graphql_checks';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $cfg = $this->config('wl_api.settings');
    $endpoint = (string) ($cfg->get('graphql_endpoint') ?? '');
    $checks = (array) ($cfg->get('checks') ?? []);

    $form['endpoint'] = [
      '#type' => 'item',
      '#title' => $this->t('GraphQL endpoint'),
      '#markup' => $endpoint ? $endpoint : $this->t('Not configured'),
      '#description' => $this->t('Configure under API revalidation settings.'),
    ];

    $header = [$this->t('Label'), $this->t('Action'), $this->t('Last result')];
    $rows = [];
    foreach ($checks as $idx => $c) {
      $label = (string) ($c['label'] ?? ('check-' . $idx));
      $rows[] = [
        $label,
        [
          'data' => [
            '#type' => 'submit',
            '#value' => $this->t('Run'),
            '#name' => 'run__' . $idx,
          ],
        ],
        ['data' => ['#markup' => $this->renderResult($label)]],
      ];
    }

    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No checks saved.'),
    ];

    if ($checks) {
      $form['run_all'] = ['#type' => 'submit', '#value' => $this->t('Run all')];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $name = (string) ($trigger['#name'] ?? '');
    $cfg = $this->config('wl_api.settings');
    $endpoint = (string) ($cfg->get('graphql_endpoint') ?? '');
    if (!$endpoint) {
      $this->messenger()->addError($this->t('GraphQL endpoint is not configured.'));
      return;
    }
    $checks = (array) ($cfg->get('checks') ?? []);

    $toRun = [];
    if (str_starts_with($name, 'run__')) {
      $idx = (int) substr($name, 5);
      if (isset($checks[$idx])) {
        $toRun[$idx] = $checks[$idx];
      }
    }
    else {
      // Run all.
      $toRun = $checks;
    }

    foreach ($toRun as $idx => $c) {
      $label = (string) ($c['label'] ?? ('check-' . $idx));
      $query = (string) ($c['query'] ?? '');
      $this->runQuery($endpoint, $label, $query);
    }
    $this->messenger()->addStatus($this->t('Checks executed.'));
    $form_state->setRebuild(TRUE);
  }

  /**
   * Run a GraphQL query and record the result in state.
   *
   * @param string $endpoint
   *   GraphQL endpoint.
   * @param string $label
   *   Check label.
   * @param string $query
   *   GraphQL query to run.
   */
  protected function runQuery(string $endpoint, string $label, string $query): void {
    try {
      $resp = $this->httpClient->post($endpoint, ['json' => ['query' => $query], 'timeout' => 15]);
      $data = (string) $resp->getBody();
      $this->state->set('wl_api.check.' . $this->sanitize($label), [
        't' => (int) $this->time->getRequestTime(),
        'ok' => TRUE,
        'code' => $resp->getStatusCode(),
        'body' => substr($data, 0, 5000),
      ]);
    }
    catch (\Throwable $e) {
      $this->state->set('wl_api.check.' . $this->sanitize($label), [
        't' => (int) $this->time->getRequestTime(),
        'ok' => FALSE,
        'code' => 0,
        'body' => substr($e->getMessage(), 0, 1000),
      ]);
    }
  }

  /**
   * Render the last stored result for a given check label.
   *
   * @param string $label
   *   The check label.
   *
   * @return string
   *   HTML markup of the result.
   */
  protected function renderResult(string $label): string {
    $rec = $this->state->get('wl_api.check.' . $this->sanitize($label));
    if (!$rec) {
      return '';
    }
    $when = $this->dateFormatter->format((int) $rec['t'], 'short');
    $status = $rec['ok'] ? 'OK' : 'FAIL';
    $code = (int) $rec['code'];
    return sprintf('%s — %s (HTTP %d)<br><pre style="max-width:100%; white-space: pre-wrap;">%s</pre>', $when, $status, $code, htmlspecialchars((string) $rec['body']));
  }

  /**
   * Sanitize a label for use as a state key suffix.
   *
   * @param string $label
   *   The label to sanitize.
   *
   * @return string
   *   The sanitized label.
   */
  protected function sanitize(string $label): string {
    return preg_replace('/[^A-Za-z0-9_\-]+/', '_', strtolower($label));
  }

}
