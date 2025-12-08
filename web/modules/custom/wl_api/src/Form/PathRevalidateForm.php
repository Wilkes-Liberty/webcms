<?php

namespace Drupal\wl_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\wl_api\Service\FrontendManager;
use Drupal\wl_api\Service\Revalidator;

/**
 * Form to trigger a one-off path revalidation for a selected frontend.
 */
class PathRevalidateForm extends FormBase {

  /**
   * Constructs a PathRevalidateForm.
   *
   * @param \Drupal\wl_api\Service\FrontendManager $frontends
   *   The frontend manager service.
   * @param \Drupal\wl_api\Service\Revalidator $revalidator
   *   The revalidator service.
   */
  public function __construct(
    private FrontendManager $frontends,
    private Revalidator $revalidator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create($container) {
    return new static(
      $container->get('wl_api.frontend_manager'),
      $container->get('wl_api.revalidator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wl_api_path_revalidate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\wl_api\Service\FrontendManager $fm */
    $fm = $this->frontends;
    $options = [];
    foreach ($fm->listFrontends() as $id => $fe) {
      $options[$id] = $fe['label'] ?? $id;
    }

    $form['frontend'] = [
      '#type' => 'select',
      '#title' => $this->t('Frontend'),
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('Select which frontend to target.'),
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path/slug'),
      '#required' => TRUE,
      '#description' => $this->t('Example: /about or /blog/my-post'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Revalidate path'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $frontend = (string) $form_state->getValue('frontend');
    $path = (string) $form_state->getValue('path');
    if ($path === '') {
      $this->messenger()->addError($this->t('Path is required.'));
      return;
    }
    $fm = $this->frontends;
    $rv = $this->revalidator;
    $frontends = $fm->listFrontends();
    if (empty($frontends[$frontend])) {
      $this->messenger()->addError($this->t('Unknown frontend.'));
      return;
    }
    $fe = $frontends[$frontend];
    $endpoint = (string) ($fe['path_revalidate_webhook'] ?? $fe['revalidate_webhook'] ?? '');
    if (!$endpoint) {
      $this->messenger()->addError($this->t('No path revalidate endpoint configured.'));
      return;
    }
    $secret = $fm->resolveSecret($fe['secret'] ?? '');
    $payload = ['path' => $path, 'domain' => 'path'];
    $rv->post(
      $endpoint,
      $secret,
      $payload,
      [
        'frontend' => $frontend,
        'domain' => 'path',
        'scope' => $path,
        'action' => 'revalidate',
      ]
    );
    $this->messenger()->addStatus($this->t(
      'Queued revalidation for %path on %frontend.',
      ['%path' => $path, '%frontend' => $frontend]
    ));
    $form_state->setRedirectUrl(Url::fromRoute('wl_api.status'));
  }

}
