<?php

namespace Drupal\wl_api\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Confirm form for triggering a menu revalidation.
 */
class RevalidateMenuConfirmForm extends ConfirmFormBase {
  /**
   * Menu machine name.
   *
   * @var string
   */
  protected $menuName = '';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'wl_api_revalidate_menu_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to trigger a revalidation for the "@menu" menu?', ['@menu' => $this->menuName]);
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
   * @param string|null $menu
   *   The menu machine name from the route parameter.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $menu = NULL) {
    $this->menuName = $menu ?? '';
    if ($this->menuName === '') {
      $this->messenger()->addError($this->t('Missing menu parameter.'));
      $form_state->setRedirectUrl(Url::fromRoute('wl_api.status'));
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (function_exists('_wl_api_revalidate_menu_if_configured')) {
      _wl_api_revalidate_menu_if_configured($this->menuName);
      $this->messenger()->addStatus($this->t('Triggered revalidation for @menu.', ['@menu' => $this->menuName]));
    }
    $form_state->setRedirectUrl(Url::fromRoute('wl_api.status'));
  }

}
