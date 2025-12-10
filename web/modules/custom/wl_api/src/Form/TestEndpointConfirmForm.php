<?php

namespace Drupal\wl_api\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirm and perform a test call to a frontend endpoint.
 */
class TestEndpointConfirmForm extends ConfirmFormBase {
  /**
   * Frontend ID.
   *
   * @var string
   */
  protected string $frontend = 'default';
  /**
   * Domain under test.
   *
   * @var string
   */
  protected string $domain = 'test';
  /**
   * Arbitrary test scope label.
   *
   * @var string
   */
  protected string $scope = 'ping';

  /**
   * {@inheritdoc} */
  public function getFormId(): string {
    return 'wl_api_test_endpoint_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Test @domain endpoint on @frontend?', ['@domain' => $this->domain, '@frontend' => $this->frontend]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('wl_api.status');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Run test');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $frontend = NULL, ?string $domain = NULL, ?string $scope = NULL) {
    $this->frontend = $frontend ?? 'default';
    $this->domain = $domain ?? 'test';
    $this->scope = $scope ?? 'ping';
    $form['help'] = ['#markup' => $this->t('This sends a harmless test payload and records latency and status in the logs.')];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\wl_api\Service\FrontendManager $fm */
    $fm = \Drupal::service('wl_api.frontend_manager');
    /** @var \Drupal\wl_api\Service\Revalidator $rv */
    $rv = \Drupal::service('wl_api.revalidator');
    $frontends = $fm->listFrontends();
    if (!isset($frontends[$this->frontend])) {
      $this->messenger()->addError($this->t('Unknown frontend: @id', ['@id' => $this->frontend]));
      $form_state->setRedirectUrl(Url::fromRoute('wl_api.status'));
      return;
    }
    $fe = $frontends[$this->frontend];
    $endpoint = $fe['revalidate_webhook'] ?: '';
    $secret = $fm->resolveSecret($fe['secret'] ?? '');
    if (!$endpoint) {
      $this->messenger()->addError($this->t('No revalidate webhook configured for frontend @id.', ['@id' => $this->frontend]));
      $form_state->setRedirectUrl(Url::fromRoute('wl_api.status'));
      return;
    }
    $payload = ['__action' => 'test', 'domain' => $this->domain, 'scope' => $this->scope, 'tag' => 'health'];
    $rv->post(
      $endpoint,
      $secret,
      $payload,
      [
        'frontend' => $this->frontend,
        'domain' => 'test',
        'scope' => $this->scope,
        'action' => 'test',
      ]
    );
    $this->messenger()->addStatus($this->t('Test executed for @frontend.', ['@frontend' => $this->frontend]));
    $form_state->setRedirectUrl(Url::fromRoute('wl_api.status'));
  }

}
