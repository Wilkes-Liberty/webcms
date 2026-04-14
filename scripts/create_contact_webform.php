<?php
/**
 * Create the "Contact" webform with fields, email handler, and honeypot.
 *
 * Run: ddev drush scr scripts/create_contact_webform.php
 * Then: ddev drush cex -y
 */

use Drupal\webform\Entity\Webform;

$id = 'contact';
$webform = Webform::load($id);
if ($webform) {
  echo "Webform '$id' already exists, updating...\n";
} else {
  echo "Creating webform '$id'...\n";
  $webform = Webform::create(['id' => $id]);
}

$webform->set('title', 'Contact');
$webform->set('description', 'Inquiries from the Wilkes & Liberty landing page.');
$webform->set('category', 'Contact');
$webform->set('status', \Drupal\webform\WebformInterface::STATUS_OPEN);

// Form fields — YAML schema.
$elements = <<<'YAML'
name:
  '#type': textfield
  '#title': 'Name'
  '#required': true
  '#maxlength': 120
email:
  '#type': email
  '#title': 'Email'
  '#required': true
organization:
  '#type': textfield
  '#title': 'Organization'
  '#maxlength': 200
subject:
  '#type': textfield
  '#title': 'Subject'
  '#required': true
  '#maxlength': 200
message:
  '#type': textarea
  '#title': 'Message'
  '#required': true
  '#rows': 6
YAML;
$webform->set('elements', $elements);

// Settings
$webform->setSettings([
  'page' => TRUE,
  'page_submit_path' => '/contact-us',  // Drupal-side preview if needed
  'form_open_message' => '',
  'form_close_message' => 'Submissions are closed.',
  'form_exception_message' => 'Sorry, the form is unavailable. Please email inquiry@wilkesliberty.com.',
  'form_confidential' => FALSE,
  'form_convert_anonymous' => FALSE,
  'form_prepopulate' => FALSE,
  'form_reset' => FALSE,
  'form_disable_back' => FALSE,
  'form_submit_back' => FALSE,
  'form_disable_autocomplete' => FALSE,
  'form_novalidate' => FALSE,
  'form_required' => TRUE,
  'form_unsaved' => FALSE,
  'form_disable_inline_errors' => FALSE,
  'form_login' => FALSE,
  'form_autofocus' => FALSE,
  'form_details_toggle' => FALSE,
  'form_access_denied' => 'default',
  'form_remote_addr' => TRUE,
  'submission_log' => TRUE,
  'results_disabled' => FALSE,
  'confirmation_type' => 'message',
  'confirmation_message' => 'Thank you. We will be in touch shortly.',
  'limit_total' => NULL,
  'limit_user' => NULL,
  'limit_user_interval' => 3600,
  'limit_user_message' => 'Please wait before submitting another inquiry.',
]);

// Anonymous can submit.
$webform->setAccessRules([
  'create' => [
    'roles' => ['anonymous', 'authenticated'],
    'users' => [],
    'permissions' => [],
  ],
  'view_any' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
  'update_any' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
  'delete_any' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
  'purge_any' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
  'view_own' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
  'update_own' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
  'delete_own' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
  'administer' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
  'test' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
  'configuration' => [
    'roles' => [],
    'users' => [],
    'permissions' => [],
  ],
]);

// Email notification handler — to inquiry@wilkesliberty.com (idempotent)
if ($webform->getHandlers()->has('email_notification')) {
  $webform->deleteWebformHandler($webform->getHandler('email_notification'));
}
/** @var \Drupal\webform\Plugin\WebformHandlerManagerInterface $handler_manager */
$handler_manager = \Drupal::service('plugin.manager.webform.handler');
$handler = $handler_manager->createInstance('email', [
  'id' => 'email',
  'label' => 'Email notification',
  'handler_id' => 'email_notification',
  'status' => TRUE,
  'conditions' => [],
  'weight' => 0,
  'settings' => [
    'states' => ['completed'],
    'to_mail' => 'inquiry@wilkesliberty.com',
    'from_mail' => '_default',
    'from_name' => '_default',
    'reply_to' => '[webform_submission:values:email:raw]',
    'subject' => 'New inquiry from [webform_submission:values:name:raw]: [webform_submission:values:subject:raw]',
    'body' => '_default',
    'html' => TRUE,
    'exclude_empty' => TRUE,
  ],
]);
$webform->addWebformHandler($handler);

// Honeypot anti-spam (third-party setting on webform).
$webform->setThirdPartySetting('honeypot', 'honeypot', TRUE);
$webform->setThirdPartySetting('honeypot', 'time_restriction', TRUE);

$webform->save();

echo "Webform '$id' configured\n";
echo "  Fields: name, email, organization, subject, message\n";
echo "  Email notifications → inquiry@wilkesliberty.com\n";
echo "  Honeypot + time-restriction enabled\n";
echo "  Anonymous submission allowed\n";
