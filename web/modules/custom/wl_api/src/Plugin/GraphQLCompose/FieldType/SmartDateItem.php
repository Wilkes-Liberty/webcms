<?php

declare(strict_types=1);

namespace Drupal\wl_api\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\FieldType\DateTimeItem;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeFieldType(
 *   id = "smartdate",
 *   type_sdl = "SmartDate",
 * )
 */
class SmartDateItem extends DateTimeItem implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {
    $start = $item->value !== NULL && $item->value !== ''
      ? $this->toDateTimeType(DrupalDateTime::createFromTimestamp((int) $item->value, new \DateTimeZone('UTC')))
      : NULL;

    $end = $item->end_value !== NULL && $item->end_value !== ''
      ? $this->toDateTimeType(DrupalDateTime::createFromTimestamp((int) $item->end_value, new \DateTimeZone('UTC')))
      : NULL;

    return [
      'value' => $start,
      'endValue' => $end,
      'duration' => $item->duration !== NULL ? (int) $item->duration : NULL,
      'timezone' => $item->timezone !== NULL && $item->timezone !== '' ? (string) $item->timezone : NULL,
      'rrule' => $item->rrule !== NULL ? (int) $item->rrule : NULL,
      'rruleIndex' => $item->rrule_index !== NULL ? (int) $item->rrule_index : NULL,
    ];
  }

}
