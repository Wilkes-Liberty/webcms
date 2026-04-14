<?php

namespace Drupal\wl_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /api/contact — submit the 'contact' webform from JSON.
 *
 * Body:
 *   { "name": "...", "email": "...", "organization": "...",
 *     "subject": "...", "message": "...",
 *     "_t": <client_open_timestamp_ms> }
 *
 * Returns 200 { ok: true } on success, 4xx with { error } on validation
 * failure, 502 if Drupal save itself failed.
 *
 * Behavior:
 *   - Saves a WebformSubmission to the DB (always, if validation passes)
 *   - Email handler runs synchronously (may take ~5s if SMTP is healthy)
 *   - Email failures are logged but do not block the response — the
 *     submission is preserved in admin and an operator can follow up.
 *
 * Spam mitigation:
 *   - Honeypot field "_hp" must be empty
 *   - Time-restriction: submissions <2s after page load are rejected
 */
class ContactController extends ControllerBase {

  private const WEBFORM_ID = 'contact';
  private const HP_FIELD = '_hp';
  private const TS_FIELD = '_t';
  private const MIN_OPEN_MS = 2000;

  public function submit(Request $request): JsonResponse {
    if ($request->getMethod() === 'OPTIONS') {
      return $this->cors(new JsonResponse(['ok' => TRUE], Response::HTTP_OK));
    }

    $payload = json_decode($request->getContent(), TRUE);
    if (!is_array($payload)) {
      return $this->cors(new JsonResponse(['error' => 'Invalid JSON'], 400));
    }

    // Honeypot — silently accept (don't tell bots they were caught).
    if (!empty($payload[self::HP_FIELD])) {
      return $this->cors(new JsonResponse(['ok' => TRUE]));
    }

    // Time restriction — submissions too soon after page load are spam.
    $opened_at = (int) ($payload[self::TS_FIELD] ?? 0);
    $now_ms = (int) (microtime(TRUE) * 1000);
    if ($opened_at > 0 && ($now_ms - $opened_at) < self::MIN_OPEN_MS) {
      return $this->cors(new JsonResponse(['ok' => TRUE]));
    }

    $webform = Webform::load(self::WEBFORM_ID);
    if (!$webform) {
      return $this->cors(new JsonResponse(['error' => 'Form not configured'], 500));
    }

    $values = [
      'webform_id' => self::WEBFORM_ID,
      'in_draft' => FALSE,
      'data' => [
        'name' => $this->str($payload['name'] ?? ''),
        'email' => $this->str($payload['email'] ?? ''),
        'organization' => $this->str($payload['organization'] ?? ''),
        'subject' => $this->str($payload['subject'] ?? ''),
        'message' => $this->str($payload['message'] ?? '', 5000),
      ],
    ];

    foreach (['name', 'email', 'subject', 'message'] as $req) {
      if ($values['data'][$req] === '') {
        return $this->cors(new JsonResponse(['error' => "Missing required field: $req"], 400));
      }
    }

    if (!filter_var($values['data']['email'], FILTER_VALIDATE_EMAIL)) {
      return $this->cors(new JsonResponse(['error' => 'Invalid email address'], 400));
    }

    $errors = WebformSubmissionForm::validateFormValues($values);
    if ($errors) {
      return $this->cors(new JsonResponse(['error' => 'Validation failed', 'details' => $errors], 400));
    }

    // Submit synchronously; catch ANY exception (incl. email/SMTP errors)
    // so a bad mail server doesn't block submissions from being saved.
    try {
      $submission = WebformSubmissionForm::submitFormValues($values);
    } catch (\Throwable $e) {
      \Drupal::logger('wl_api')->error('Contact submit failed: @msg', ['@msg' => $e->getMessage()]);
      // Try to save the submission directly so the operator still has a record.
      try {
        $submission = WebformSubmission::create($values);
        $submission->save();
        return $this->cors(new JsonResponse([
          'ok' => TRUE,
          'sid' => (int) $submission->id(),
          'note' => 'Saved; notification email may have failed.',
        ]));
      } catch (\Throwable $e2) {
        return $this->cors(new JsonResponse(['error' => 'Submission failed'], 502));
      }
    }

    if (!$submission instanceof WebformSubmission) {
      return $this->cors(new JsonResponse(['error' => 'Submission failed'], 500));
    }

    return $this->cors(new JsonResponse(['ok' => TRUE, 'sid' => (int) $submission->id()]));
  }

  private function str(mixed $value, int $max = 1000): string {
    if (!is_string($value)) {
      return '';
    }
    return mb_substr(trim($value), 0, $max);
  }

  private function cors(JsonResponse $response): JsonResponse {
    $response->headers->set('Access-Control-Allow-Origin', '*');
    $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
    $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
    $response->headers->set('Access-Control-Max-Age', '86400');
    return $response;
  }

}
