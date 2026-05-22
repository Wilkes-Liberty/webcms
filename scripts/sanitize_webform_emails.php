<?php

/**
 * @file
 * Sanitizes email values in webform_submission_data and webform_submission.
 *
 * Enumerates email-type elements from all webform entities, then rewrites
 * matching rows in webform_submission_data (column: name, not element_id).
 * Also sanitizes remote_addr in webform_submission to '127.0.0.1'.
 * Safe to re-run: result is deterministic (sid is stable).
 *
 * Used by:
 *   - infra/ansible/playbooks/refresh-staging.yml (drush scr)
 *   - webcms/scripts/refresh-env.sh
 */

if (!\Drupal::moduleHandler()->moduleExists('webform')) {
  echo "Webform module not installed — skipping.\n";
  return;
}

$db = \Drupal::database();

// Sanitize remote_addr (IP PII) in webform_submission.
if ($db->schema()->tableExists('webform_submission')) {
  $db->query("UPDATE {webform_submission} SET remote_addr = '127.0.0.1'");
  echo "Sanitized webform_submission.remote_addr → 127.0.0.1\n";
}

// Enumerate email-type element machine names across all webforms.
$email_elements = [];

/** @var \Drupal\webform\WebformInterface[] $webforms */
$webforms = \Drupal::entityTypeManager()->getStorage('webform')->loadMultiple();

foreach ($webforms as $webform) {
  $elements = $webform->getElementsDecodedAndFlattened();
  foreach ($elements as $key => $element) {
    if (isset($element['#type']) &&
        in_array($element['#type'], ['email', 'webform_email_confirm'], true)) {
      $email_elements[] = $key;
    }
  }
}

$email_elements = array_unique($email_elements);

if (empty($email_elements)) {
  echo "No email elements found in any webform — skipping.\n";
  return;
}

echo "Found email elements: " . implode(', ', $email_elements) . "\n";

// Rewrite values in webform_submission_data for those element names.
if ($db->schema()->tableExists('webform_submission_data')) {
  $in = implode("','", $email_elements);
  $db->query(
    "UPDATE {webform_submission_data}
     SET value = CONCAT('noreply+stg-webform-', sid, '@wilkesliberty.com')
     WHERE name IN ('$in') AND value IS NOT NULL AND value != ''"
  );
  echo "Sanitized webform_submission_data for email elements.\n";
}

echo "Webform email sanitization complete.\n";
