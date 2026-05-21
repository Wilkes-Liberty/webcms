<?php

declare(strict_types=1);

namespace Drupal\wl_api\Plugin\GraphQLCompose\SchemaType;

use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeSchemaTypeBase;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

/**
 * {@inheritdoc}
 *
 * @GraphQLComposeSchemaType(
 *   id = "SmartDate",
 * )
 */
class SmartDateType extends GraphQLComposeSchemaTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getTypes(): array {
    $types = [];

    if (!$this->moduleHandler->moduleExists('smart_date')) {
      return $types;
    }

    $types[] = new ObjectType([
      'name' => $this->getPluginId(),
      'description' => (string) $this->t('A smart date range with start, end, duration, timezone, and optional recurrence.'),
      'fields' => fn() => [
        'value' => [
          'type' => static::type('DateTime'),
          'description' => (string) $this->t('The start of the smart date range.'),
        ],
        'endValue' => [
          'type' => static::type('DateTime'),
          'description' => (string) $this->t('The end of the smart date range.'),
        ],
        'duration' => [
          'type' => Type::int(),
          'description' => (string) $this->t('Duration of the range in minutes.'),
        ],
        'timezone' => [
          'type' => Type::string(),
          'description' => (string) $this->t('Preferred timezone identifier for the range.'),
        ],
        'rrule' => [
          'type' => Type::int(),
          'description' => (string) $this->t('Recurrence rule ID, when smart_date_recur is in use.'),
        ],
        'rruleIndex' => [
          'type' => Type::int(),
          'description' => (string) $this->t('Recurrence rule instance index.'),
        ],
      ],
    ]);

    return $types;
  }

}
