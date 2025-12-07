<?php

namespace Drupal\wl_headless\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\system\Entity\Menu;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HeadlessStatusController extends ControllerBase {

  public function status(): array {
    $header = [
      $this->t('Menu'),
      $this->t('Webhook configured'),
      $this->t('Tag'),
      $this->t('Last status'),
      $this->t('Last revalidated at'),
      $this->t('Action'),
    ];

    $rows = [];
    /** @var \Drupal\system\Entity\Menu[] $menus */
    $menus = $this->entityTypeManager()->getStorage('menu')->loadMultiple();
    $df = $this->dateFormatter();

    foreach ($menus as $menu) {
      $name = $menu->id();
      $title = $menu->label();
      $webhook = (string) $menu->getThirdPartySetting('wl_headless', 'revalidate_webhook', '');
      $tag = (string) $menu->getThirdPartySetting('wl_headless', 'revalidate_tag', 'menu');
      $rec = $this->state()->get('wl_headless.revalidate.' . $name, NULL);
      $status = $rec['status'] ?? '-';
      $ts = $rec['timestamp'] ?? 0;
      $ok = isset($rec['ok']) ? ($rec['ok'] ? 'OK' : 'FAIL') : '-';
      $when = $ts ? $df->format($ts, 'short') : '-';

      $action = Link::fromTextAndUrl(
        $this->t('Revalidate now'),
        Url::fromRoute('wl_headless.revalidate_menu', ['menu' => $name])
      )->toString();

      $rows[] = [
        $this->t('@title (@name)', ['@title' => $title, '@name' => $name]),
        $webhook ? $this->t('Yes') : $this->t('No'),
        $tag ?: '-',
        $status === '-' ? '-' : $this->t('@status (@ok)', ['@status' => $status, '@ok' => $ok]),
        $when,
        [ 'data' => [ '#markup' => $action ] ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No menus found.'),
    ];
  }

  public function revalidateMenu(string $menu): RedirectResponse {
    if (Menu::load($menu)) {
      if (function_exists('_wl_headless_revalidate_menu_if_configured')) {
        _wl_headless_revalidate_menu_if_configured($menu);
        $this->messenger()->addStatus($this->t('Triggered revalidation for @menu.', ['@menu' => $menu]));
      }
    } else {
      $this->messenger()->addError($this->t('Unknown menu: @menu', ['@menu' => $menu]));
    }
    return new RedirectResponse(Url::fromRoute('wl_headless.status')->toString());
  }
}
