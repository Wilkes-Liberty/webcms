<?php

// phpcs:ignoreFile

/**
 * @file
 * WilkesLiberty Drupal settings.
 *
 * This file is safe to commit — it contains NO secrets or credentials.
 * Environment-specific values (DB credentials, hash salt, etc.) belong in
 * settings.local.php, which is never committed.
 *
 * Environment loading order:
 *   1. This file (settings.php) — shared base config
 *   2. settings.local.php       — environment overrides (DB, cache, secrets)
 *
 * For Docker deployments, settings.docker.php is symlinked or mounted as
 * settings.local.php and reads all values from environment variables.
 */

// ── Config sync directory ────────────────────────────────────────────────────
// Absolute path keeps this portable across web roots and symlinked setups.
$settings['config_sync_directory'] = dirname(DRUPAL_ROOT) . '/config/sync';

// ── Trusted host patterns ────────────────────────────────────────────────────
// Covers production, staging, and local development hostnames.
// settings.local.php can override or append additional patterns.
$settings['trusted_host_patterns'] = [
  // Production
  '^wilkesliberty\.com$',
  '^www\.wilkesliberty\.com$',
  // Internal hostname (Tailscale / Docker service names)
  '^drupal$',
  '^drupal\.int\.wilkesliberty\.com$',
  // Staging
  '^staging\.wilkesliberty\.com$',
  '^staging\.int\.wilkesliberty\.com$',
  // Local development (DDEV and direct)
  '^wilkesliberty\.ddev\.site$',
  '^localhost$',
  '^127\.0\.0\.1$',
];

// ── Private files ────────────────────────────────────────────────────────────
// Keep private files outside the web root.
// Docker deployments override this in settings.local.php / settings.docker.php.
$settings['file_private_path'] = dirname(DRUPAL_ROOT) . '/private';

// ── Security ─────────────────────────────────────────────────────────────────
// Hash salt is required and must be unique per environment.
// Set in settings.local.php — do NOT set here.
// $settings['hash_salt'] = '';  // MUST be in settings.local.php

// Prevent installation from overwriting settings.php.
$settings['update_free_access'] = FALSE;

// ── Performance ──────────────────────────────────────────────────────────────
// These are production defaults. Dev environments override in settings.local.php.
// Render cache — use database by default; settings.local.php can override per environment.
// Note: cache.backend.null is not a registered service in Drupal core; use
// cache.backend.database (production) or cache.backend.memory (dev) instead.
$settings['cache']['bins']['render'] = 'cache.backend.database';

// ── Reverse proxy / load balancer ────────────────────────────────────────────
// Caddy/Varnish sits in front of Drupal in production.
$settings['reverse_proxy'] = TRUE;
$settings['reverse_proxy_addresses'] = ['127.0.0.1'];

// ── Extension-related performance settings ───────────────────────────────────
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];
$settings['entity_update_batch_size'] = 50;
$settings['entity_update_backup'] = TRUE;

// ── Container services YAML ──────────────────────────────────────────────────
// Load the base services definition. Environment-specific overlays are added
// via $settings['container_yamls'] in settings.local.php.
$settings['container_yamls'][] = $app_root . '/sites/default/default.services.yml';

// ── Database placeholder ─────────────────────────────────────────────────────
// The actual $databases array MUST be defined in settings.local.php.
// Leaving it empty here causes a clear error if settings.local.php is missing.
$databases = [];

// ── Load environment-specific overrides ──────────────────────────────────────
// IMPORTANT: Keep these includes at the end of the file so local settings can
// override anything defined above.

// DDEV auto-generates settings.ddev.php with the database connection.
// This is gitignored and only exists in DDEV environments.
// For Docker or other deployments, database config lives in settings.local.php.
if (file_exists($app_root . '/' . $site_path . '/settings.ddev.php')) {
  include $app_root . '/' . $site_path . '/settings.ddev.php';
}

// settings.local.php holds environment-specific overrides (DB credentials,
// hash salt, Redis connection, etc.). Never committed to git.
// For Docker, settings.docker.php is mounted as settings.local.php.
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
