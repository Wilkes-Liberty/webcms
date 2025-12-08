<?php

namespace Drupal\wl_api\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirm form for triggering global (content|taxonomy) revalidation.
 */
class RevalidateGlobalConfirmForm extends ConfirmFormBase {
  /**
   * Global hook name: content|taxonomy.
   *
   * @var string
   */
  protected $hook = '';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wl_api_revalidate_global_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to trigger a "@hook" revalidation now?', ['@hook' => $this->hook]);
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
    return $this->t('Revalidate');
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string|null $hook
   *   The global hook to trigger: content or taxonomy.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $hook = NULL) {
    $this->hook = $hook ?? '';
    if (!in_array($this->hook, ['content', 'taxonomy'], TRUE)) {
      $this->messenger()->addError($this->t('Invalid revalidation hook.'));
      $form_state->setRedirect('wl_api.status');
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (function_exists('_wl_api_revalidate_global')) {
      _wl_api_revalidate_global($this->hook);
      $this->messenger()->addStatus($this->t('Triggered revalidation for @hook.', ['@hook' => $this->hook]));
    }
    $form_state->setRedirect('wl_api.status');
  }

}
