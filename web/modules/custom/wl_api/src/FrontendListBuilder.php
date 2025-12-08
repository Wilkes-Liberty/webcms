<?php

namespace Drupal\wl_api;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Lists configured API Frontends.
 */
class FrontendListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc} */
  public function buildHeader() {
    $header['label'] = $this->t('Frontend');
    $header['revalidate_webhook'] = $this->t('Revalidate URL');
    $header['health_url'] = $this->t('Health');
    $header['ci_url'] = $this->t('CI');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc} */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\wl_api\Entity\Frontend $entity */
    $row['label'] = $entity->label();
    $row['revalidate_webhook'] = $entity->get('revalidate_webhook');
    $row['health_url'] = $entity->get('health_url');
    $row['ci_url'] = $entity->get('ci_url');
    return $row + parent::buildRow($entity);
  }

}
