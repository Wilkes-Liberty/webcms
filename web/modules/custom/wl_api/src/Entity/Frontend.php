<?php

namespace Drupal\wl_api\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\wl_api\FrontendInterface;

/**
 * Defines a Frontend config entity.
 *
 * @ConfigEntityType(
 *   id = "wl_api_frontend",
 *   label = @Translation("API Frontend"),
 *   handlers = {
 * "list_builder" = "Drupal\wl_api\FrontendListBuilder",
 *     "form" = {
 * "add" = "Drupal\wl_api\Form\FrontendForm",
 * "edit" = "Drupal\wl_api\Form\FrontendForm",
 * "delete" = "Drupal\Core\Entity\Form\ConfigEntityDeleteForm"
 *     },
 *     "route_provider" = {
 * "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider"
 *     }
 *   },
 *   config_prefix = "frontend",
 *   admin_permission = "administer api frontends",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "collection" = "/admin/config/api/frontends",
 *     "add-form" = "/admin/config/api/frontends/add",
 *     "edit-form" = "/admin/config/api/frontends/{wl_api_frontend}",
 *     "delete-form" = "/admin/config/api/frontends/{wl_api_frontend}/delete"
 *   }
 * )
 */
class Frontend extends ConfigEntityBase implements FrontendInterface {
  use EntityChangedTrait;

  /**
   * Machine name ID.
   *
   * @var string
   */
  protected $id;
  /**
   * Human-readable label.
   *
   * @var string
   */
  protected $label;

  /**
   * Revalidate webhook URL.
   *
   * @var string
   */
  protected $revalidate_webhook = '';
  /**
   * Path revalidate webhook URL.
   *
   * @var string
   */
  protected $path_revalidate_webhook = '';
  /**
   * Secret (or Key ID).
   *
   * @var string
   */
  protected $secret = '';
  /**
   * Health URL.
   *
   * @var string
   */
  protected $health_url = '';
  /**
   * CI/CD URL.
   *
   * @var string
   */
  protected $ci_url = '';

  /**
   * Get arbitrary field value.
   */
  public function get($field): mixed {
    return $this->$field ?? NULL;
  }

}
