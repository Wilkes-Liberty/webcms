<?php

namespace Drupal\wl_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Config form to create/edit saved GraphQL checks.
 */
class GraphqlChecksConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc} */
  public function getFormId(): string {
    return 'wl_api_graphql_checks_config';
  }

  /**
   * {@inheritdoc} */
  protected function getEditableConfigNames(): array {
    return ['wl_api.settings'];
  }

  /**
   * {@inheritdoc} */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $cfg = $this->config('wl_api.settings');
    $checks = (array) ($cfg->get('checks') ?? []);

    $form['help'] = [
      '#markup' => $this->t('Define a set of saved GraphQL checks. Each row is a label + query. Leave label empty to remove a row.'),
    ];

    $form['checks'] = [
      '#type' => 'table',
      '#header' => [$this->t('Label'), $this->t('Query')],
      '#tree' => TRUE,
    ];

    $rows = max(count($checks) + 1, 3);
    for ($i = 0; $i < $rows; $i++) {
      $form['checks'][$i]['label'] = [
        '#type' => 'textfield',
        '#default_value' => (string) ($checks[$i]['label'] ?? ''),
        '#placeholder' => $this->t('Label'),
      ];
      $form['checks'][$i]['query'] = [
        '#type' => 'textarea',
        '#default_value' => (string) ($checks[$i]['query'] ?? ''),
        '#rows' => 4,
        '#placeholder' => '{ nodeQuery { entities { entityId } } }',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $values = (array) $form_state->getValue('checks');
    $out = [];
    foreach ($values as $row) {
      $label = trim((string) ($row['label'] ?? ''));
      $query = trim((string) ($row['query'] ?? ''));
      if ($label !== '' && $query !== '') {
        $out[] = ['label' => $label, 'query' => $query];
      }
    }
    $this->configFactory->getEditable('wl_api.settings')->set('checks', $out)->save();
    $this->messenger()->addStatus($this->t('Saved @n checks.', ['@n' => count($out)]));
  }

}
