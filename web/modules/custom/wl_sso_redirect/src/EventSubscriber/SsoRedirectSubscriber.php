<?php

namespace Drupal\wl_sso_redirect\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects anonymous browser requests to Keycloak SSO.
 *
 * Passes through:
 *  - All non-GET methods (POST, PUT, PATCH, DELETE)
 *  - Non-HTML Accept headers (JSON:API, REST, OAuth tokens)
 *  - Paths that must remain publicly accessible
 *
 * Only HTML browser GETs from anonymous users are redirected.
 */
class SsoRedirectSubscriber implements EventSubscriberInterface {

  private const BYPASS_PREFIXES = [
    '/jsonapi',
    '/oauth',
    '/openid-connect',
    '/sites/default/files',
    '/system/files',
    '/system/ajax',
    '/admin/reports/status/run-cron',
    '/cron',
    '/health',
  ];

  private const BYPASS_EXACT = [
    '/user/login',
    '/user/logout',
    '/user/password',
    '/user/register',
  ];

  // /user/login with user_login_display=replace + autostart_login=true
  // immediately triggers the KC authorize redirect — no form shown.
  private const KC_INITIATE = '/user/login';

  public function __construct(
    private readonly AccountProxyInterface $account,
  ) {}

  public static function getSubscribedEvents(): array {
    // Priority 100: fires after Authentication (300) so isAnonymous() is
    // accurate, but before RouterListener (32) so unknown routes (404s) are
    // caught and redirected rather than surfaced to anonymous users.
    return [KernelEvents::REQUEST => ['onRequest', 100]];
  }

  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    if (!$this->account->isAnonymous()) {
      return;
    }

    $request = $event->getRequest();

    // Only redirect HTML browser requests; pass API/CLI calls through.
    if ($request->getMethod() !== 'GET') {
      return;
    }
    $accept = $request->headers->get('Accept', '');
    if (!str_contains($accept, 'text/html') && !str_contains($accept, '*/*')) {
      return;
    }
    // Accept header with application/json or application/vnd.api+json but
    // without text/html means a programmatic client — pass through.
    if (
      (str_contains($accept, 'application/json') || str_contains($accept, 'application/vnd.api+json'))
      && !str_contains($accept, 'text/html')
    ) {
      return;
    }

    $path = $request->getPathInfo();

    foreach (self::BYPASS_EXACT as $exact) {
      if ($path === $exact) {
        return;
      }
    }
    foreach (self::BYPASS_PREFIXES as $prefix) {
      if (str_starts_with($path, $prefix)) {
        return;
      }
    }

    // /user/login is in BYPASS_EXACT, so the next request does not loop.
    $base = $request->getSchemeAndHttpHost();
    $event->setResponse(new RedirectResponse($base . self::KC_INITIATE, 302));
  }

}
