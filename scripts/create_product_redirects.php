<?php
/**
 * Create 301 redirects from old /products/* URLs to new /platforms/* URLs.
 *
 * Run AFTER the platform migration is complete and nodes have new aliases.
 *
 * Run:
 *   ddev drush scr scripts/create_product_redirects.php
 *   ddev drush scr scripts/create_product_redirects.php -- --dry-run
 */

use Drupal\redirect\Entity\Redirect;

$wl_argv = isset($extra) && is_array($extra) ? $extra : array_slice($_SERVER['argv'] ?? [], 1);
$dry_run = in_array('--dry-run', $wl_argv, true);

echo "=== Create Product → Platform 301 Redirects ===\n";
echo 'Mode: ' . ($dry_run ? 'DRY-RUN' : 'LIVE') . "\n\n";

$redirects = [
  'products/sovereign-infrastructure-platform' => 'platforms/sabal',
  'products/liberty-headless-cms'              => 'platforms/keel',
  'products/enterprise-search'                 => 'platforms/alidade',
  'products/fortis-identity'                   => 'platforms/squawk',
  'products/apex-data'                         => 'platforms/manifest',
  'products/vigilance-observability'            => 'platforms/lighthouse',
  'products'                                   => 'platforms',
];

$redirect_storage = \Drupal::entityTypeManager()->getStorage('redirect');

foreach ($redirects as $from => $to) {
  // Check if redirect already exists.
  $existing = $redirect_storage->loadByProperties([
    'redirect_source__path' => $from,
  ]);

  if (!empty($existing)) {
    $r = reset($existing);
    echo "  [=] {$from} → already exists (rid={$r->id()}) — skipping\n";
    continue;
  }

  if ($dry_run) {
    echo "  [?] {$from} → {$to} (301) — would create\n";
    continue;
  }

  $redirect = Redirect::create([
    'redirect_source' => ['path' => $from],
    'redirect_redirect' => ['uri' => 'internal:/' . $to],
    'status_code' => 301,
    'language' => 'und',
  ]);
  $redirect->save();
  echo "  [+] {$from} → {$to} (301) rid={$redirect->id()}\n";
}

echo "\n=== Done ===\n";
if ($dry_run) {
  echo "Dry run: no redirects were created.\n";
}
else {
  echo "All redirects created. Run 'drush cr' to clear caches.\n";
}
