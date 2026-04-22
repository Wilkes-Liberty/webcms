<?php

namespace Drupal\wl_postmark_webhook\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PostmarkWebhookSettingsForm extends ConfigFormBase {

  public function getFormId(): string {
    return 'wl_postmark_webhook_settings_form';
  }

  protected function getEditableConfigNames(): array {
    return ['wl_postmark_webhook.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('wl_postmark_webhook.settings');

    $secret = $config->get('webhook_secret') ?: '';
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $webhook_url = $secret
      ? $base_url . '/api/webhooks/postmark/' . $secret
      : $this->t('(not configured — set postmark_webhook_secret in app_secrets.yml and re-deploy)');

    $form['webhook_url'] = [
      '#type'   => 'item',
      '#title'  => $this->t('Webhook URL'),
      '#markup' => '<code>' . $this->t('@url', ['@url' => $webhook_url]) . '</code>',
      '#description' => $this->t('Enter this URL in the Postmark dashboard under Streams → Webhooks. The secret is managed by Ansible — do not change it here.'),
    ];

    $form['suppression'] = [
      '#type'  => 'details',
      '#title' => $this->t('Bounce & spam suppression'),
      '#open'  => TRUE,
    ];

    $form['suppression']['enabled'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable suppression'),
      '#description'   => $this->t('When checked, outbound email to bounced/spam addresses is blocked before sending.'),
      '#default_value' => $config->get('enabled') ?? TRUE,
    ];

    $form['suppression']['bounce_suppression_days'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Soft bounce suppression window (days)'),
      '#description'   => $this->t('Suppress sends to soft-bounced addresses for this many days. Applies to: Transient, SoftBounce, DnsError, MailboxFull, MessageTooLarge.'),
      '#default_value' => $config->get('bounce_suppression_days') ?? 30,
      '#min'           => 0,
      '#max'           => 365,
    ];

    $form['suppression']['complaint_suppression_days'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Spam complaint suppression window (days)'),
      '#description'   => $this->t('0 = permanent (never retry after a spam complaint). Hard bounces (HardBounce, BadEmailAddress, etc.) are always permanent regardless of this setting.'),
      '#default_value' => $config->get('complaint_suppression_days') ?? 0,
      '#min'           => 0,
      '#max'           => 3650,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('wl_postmark_webhook.settings')
      ->set('enabled', (bool) $form_state->getValue('enabled'))
      ->set('bounce_suppression_days', (int) $form_state->getValue('bounce_suppression_days'))
      ->set('complaint_suppression_days', (int) $form_state->getValue('complaint_suppression_days'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
