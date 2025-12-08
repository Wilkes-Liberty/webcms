<?php

namespace Drupal\wl_api\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for API Frontend add/edit.
 */
class FrontendForm extends EntityForm {

  /**
   * {@inheritdoc} */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\wl_api\Entity\Frontend $frontend */
    $frontend = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $frontend->label(),
      '#required' => TRUE,
      '#description' => $this->t('Human-friendly name (e.g., “Main site” or “Docs site”).'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $frontend->id(),
      '#machine_name' => [
        'exists' => ['\\Drupal\\wl_api\\Entity\\Frontend', 'load'],
      ],
      '#disabled' => !$frontend->isNew(),
    ];

    $form['revalidate_webhook'] = [
      '#type' => 'url',
      '#title' => $this->t('Revalidate webhook URL'),
      '#default_value' => $frontend->get('revalidate_webhook'),
      '#required' => FALSE,
      '#description' => $this->t('Next.js API route for tag-based revalidation. Example: https://frontend.example.com/api/revalidate'),
    ];

    $form['path_revalidate_webhook'] = [
      '#type' => 'url',
      '#title' => $this->t('Path revalidate URL'),
      '#default_value' => $frontend->get('path_revalidate_webhook'),
      '#description' => $this->t('Optional endpoint to revalidate by path/slug. If empty, the main revalidate webhook is used.'),
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret (or Key ID)'),
      '#default_value' => $frontend->get('secret'),
      '#description' => $this->t('Shared secret header (X-Next-Secret). If using the Key module, provide the Key ID instead and wl_api will resolve it.'),
    ];

    $form['health_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Frontend health URL'),
      '#default_value' => $frontend->get('health_url'),
      '#description' => $this->t('Optional link to the frontend health/status page for quick access.'),
    ];

    $form['ci_url'] = [
      '#type' => 'url',
      '#title' => $this->t('CI/CD dashboard URL'),
      '#default_value' => $frontend->get('ci_url'),
      '#description' => $this->t('Optional link to the deployment dashboard.'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc} */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->messenger()->addStatus($this->t('Frontend %label saved.', ['%label' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
