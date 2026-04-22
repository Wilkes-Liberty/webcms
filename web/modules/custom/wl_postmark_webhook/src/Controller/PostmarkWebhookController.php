<?php

namespace Drupal\wl_postmark_webhook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PostmarkWebhookController extends ControllerBase {

  public function receive(Request $request, string $secret): Response {
    $configured = \Drupal::config('wl_postmark_webhook.settings')->get('webhook_secret');
    if (!$configured || !hash_equals($configured, $secret)) {
      return new Response('Forbidden', 403);
    }

    $body = $request->getContent();
    $data = json_decode($body, TRUE);
    if (!is_array($data)) {
      return new Response('Bad Request', 400);
    }

    \Drupal::database()->insert('postmark_events')->fields([
      'created'     => \Drupal::time()->getRequestTime(),
      'event_type'  => $data['RecordType'] ?? '',
      'message_id'  => $data['MessageID'] ?? '',
      'recipient'   => $data['Recipient'] ?? $data['Email'] ?? '',
      'bounce_type' => $data['Type'] ?? '',
      'description' => mb_substr($data['Description'] ?? $data['Name'] ?? '', 0, 512),
      'payload'     => $body,
    ])->execute();

    return new Response('OK', 200);
  }

}
