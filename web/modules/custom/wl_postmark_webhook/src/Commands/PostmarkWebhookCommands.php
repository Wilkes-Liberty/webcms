<?php

namespace Drupal\wl_postmark_webhook\Commands;

use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

class PostmarkWebhookCommands extends DrushCommands {

  #[CLI\Command(name: 'wl-postmark:status', aliases: ['wl-pm-status'])]
  #[CLI\Argument(name: 'email', description: 'Email address to check.')]
  #[CLI\Usage(name: 'drush wl-postmark:status user@example.com', description: 'Check if an address is suppressed and why.')]
  public function status(string $email): void {
    $config = \Drupal::config('wl_postmark_webhook.settings');

    if (!$config->get('enabled')) {
      $this->io()->note('Suppression is currently disabled globally (wl_postmark_webhook.settings.enabled = false).');
    }

    $reason = _wl_postmark_webhook_suppression_reason($email, $config);

    if ($reason) {
      $this->io()->error("SUPPRESSED: {$email} — {$reason}");
    }
    else {
      $this->io()->success("OK: {$email} is not suppressed.");
    }

    // Show recent events regardless of suppression state.
    $rows = \Drupal::database()->select('postmark_events', 'pe')
      ->fields('pe', ['eid', 'created', 'event_type', 'bounce_type', 'description'])
      ->condition('recipient', $email)
      ->orderBy('created', 'DESC')
      ->range(0, 10)
      ->execute()
      ->fetchAll();

    if (empty($rows)) {
      $this->io()->text('No Postmark events on record for this address.');
      return;
    }

    $table = [];
    foreach ($rows as $row) {
      $table[] = [
        date('Y-m-d H:i', $row->created),
        $row->event_type,
        $row->bounce_type ?: '—',
        mb_substr($row->description, 0, 60),
      ];
    }
    $this->io()->table(['Date', 'Type', 'Bounce Type', 'Description'], $table);
  }

}
