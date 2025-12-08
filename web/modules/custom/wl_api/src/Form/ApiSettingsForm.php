<?php

namespace Drupal\wl_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a settings form for API revalidation configuration.
 */
class ApiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wl_api_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['wl_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('wl_api.settings');

    $form['description'] = ['#markup' => $this->t('Configure global revalidation webhooks used for content and taxonomy changes. Secrets are sent as the X-Next-Secret header.')];

    $form['revalidate_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shared secret'),
      '#default_value' => $config->get('revalidate_secret') ?? '',
      '#description' => $this->t('Optional secret sent as X-Next-Secret.'),
    ];

    $form['content'] = ['#type' => 'details', '#title' => $this->t('Content revalidation'), '#open' => TRUE];
    $form['content']['revalidate_content_webhook'] = [
      '#type' => 'url',
      '#title' => $this->t('Content webhook URL'),
      '#default_value' => $config->get('revalidate_content_webhook') ?? '',
    ];
    $form['content']['revalidate_content_tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content tag'),
      '#default_value' => $config->get('revalidate_content_tag') ?? 'content',
    ];

    $form['taxonomy'] = ['#type' => 'details', '#title' => $this->t('Taxonomy revalidation'), '#open' => TRUE];
    $form['taxonomy']['revalidate_taxonomy_webhook'] = [
      '#type' => 'url',
      '#title' => $this->t('Taxonomy webhook URL'),
      '#default_value' => $config->get('revalidate_taxonomy_webhook') ?? '',
      '#description' => $this->t('Default tag-based invalidation for taxonomy-related changes.'),
    ];
    $form['taxonomy']['revalidate_taxonomy_tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Taxonomy tag'),
      '#default_value' => $config->get('revalidate_taxonomy_tag') ?? 'taxonomy',
    ];

    // Auto triggers.
    $form['auto'] = ['#type' => 'details', '#title' => $this->t('Auto triggers'), '#open' => TRUE];
    $form['auto']['auto_content_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable auto revalidation for content'),
      '#default_value' => $config->get('auto_content_enabled') ?? FALSE,
      '#description' => $this->t('Trigger revalidation on node create/update/delete.'),
    ];
    // List bundles.
    $bundles = [];
    foreach (\Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple() as $id => $type) {
      $bundles[$id] = $type->label();
    }
    $form['auto']['auto_content_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types to include'),
      '#options' => $bundles,
      '#default_value' => $config->get('auto_content_bundles') ?? [],
      '#description' => $this->t('Leave empty to include all content types.'),
    ];
    $form['auto']['auto_taxonomy_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable auto revalidation for taxonomy'),
      '#default_value' => $config->get('auto_taxonomy_enabled') ?? FALSE,
      '#description' => $this->t('Trigger revalidation on taxonomy term create/update/delete.'),
    ];
    $vocabs = [];
    foreach (\Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple() as $id => $v) {
      $vocabs[$id] = $v->label();
    }
    $form['auto']['auto_taxonomy_vocabularies'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Taxonomy vocabularies to include'),
      '#options' => $vocabs,
      '#default_value' => $config->get('auto_taxonomy_vocabularies') ?? [],
      '#description' => $this->t('Leave empty to include all vocabularies.'),
    ];

    // Scheduling & rate limiting.
    $form['schedule'] = ['#type' => 'details', '#title' => $this->t('Scheduling & rate limiting'), '#open' => FALSE];
    $form['schedule']['rate_limit_per_minute'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate limit per minute'),
      '#default_value' => $config->get('rate_limit_per_minute') ?? 60,
      '#min' => 1,
      '#description' => $this->t('Maximum requests per minute across all frontends (best-effort).'),
    ];
    $form['schedule']['sweep_hour'] = [
      '#type' => 'number',
      '#title' => $this->t('Nightly sweep hour (0–23)'),
      '#default_value' => $config->get('sweep_hour') ?? 3,
      '#min' => 0,
      '#max' => 23,
    ];
    $form['schedule']['sweep_minute'] = [
      '#type' => 'number',
      '#title' => $this->t('Nightly sweep minute (0–59)'),
      '#default_value' => $config->get('sweep_minute') ?? 15,
      '#min' => 0,
      '#max' => 59,
    ];

    // GraphQL settings and checks.
    $form['graphql'] = ['#type' => 'details', '#title' => $this->t('GraphQL checks'), '#open' => FALSE];
    $form['graphql']['graphql_endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('GraphQL endpoint URL'),
      '#default_value' => $config->get('graphql_endpoint') ?? '',
      '#description' => $this->t('Absolute URL, e.g., https://frontend.example.com/graphql'),
    ];
    $form['graphql']['schema_sweep_on_change'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Run global sweep when schema changes'),
      '#default_value' => $config->get('schema_sweep_on_change') ?? FALSE,
    ];

    // Alerts.
    $form['alerts'] = ['#type' => 'details', '#title' => $this->t('Alerts'), '#open' => FALSE];
    $form['alerts']['alerts_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Consecutive failures threshold'),
      '#default_value' => $config->get('alerts.threshold') ?? 0,
      '#min' => 0,
      '#description' => $this->t('0 disables alerts.'),
    ];
    $form['alerts']['alerts_slack_webhook'] = [
      '#type' => 'url',
      '#title' => $this->t('Slack webhook URL'),
      '#default_value' => $config->get('alerts.slack_webhook') ?? '',
    ];
    $form['alerts']['alerts_emails'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alert emails (comma-separated)'),
      '#default_value' => implode(',', (array) ($config->get('alerts.emails') ?? [])),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $emails = array_filter(array_map('trim', explode(',', (string) $form_state->getValue('alerts_emails'))));
    $this->configFactory->getEditable('wl_api.settings')
      ->set('revalidate_secret', $form_state->getValue('revalidate_secret'))
      ->set('revalidate_content_webhook', $form_state->getValue('revalidate_content_webhook'))
      ->set('revalidate_content_tag', $form_state->getValue('revalidate_content_tag'))
      ->set('revalidate_taxonomy_webhook', $form_state->getValue('revalidate_taxonomy_webhook'))
      ->set('revalidate_taxonomy_tag', $form_state->getValue('revalidate_taxonomy_tag'))
      ->set('auto_content_enabled', (bool) $form_state->getValue('auto_content_enabled'))
      ->set('auto_content_bundles', array_values(array_filter((array) $form_state->getValue('auto_content_bundles'))))
      ->set('auto_taxonomy_enabled', (bool) $form_state->getValue('auto_taxonomy_enabled'))
      ->set('auto_taxonomy_vocabularies', array_values(array_filter((array) $form_state->getValue('auto_taxonomy_vocabularies'))))
      ->set('rate_limit_per_minute', (int) $form_state->getValue('rate_limit_per_minute'))
      ->set('sweep_hour', (int) $form_state->getValue('sweep_hour'))
      ->set('sweep_minute', (int) $form_state->getValue('sweep_minute'))
      ->set('graphql_endpoint', $form_state->getValue('graphql_endpoint'))
      ->set('schema_sweep_on_change', (bool) $form_state->getValue('schema_sweep_on_change'))
      ->set('alerts.threshold', (int) $form_state->getValue('alerts_threshold'))
      ->set('alerts.slack_webhook', $form_state->getValue('alerts_slack_webhook'))
      ->set('alerts.emails', $emails)
      ->save();
    $this->messenger()->addStatus($this->t('API revalidation settings saved.'));
  }

}
