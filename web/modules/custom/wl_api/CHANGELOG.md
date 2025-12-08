# Changelog

## 0.2.0 (2025-12-08)
Enhancements:
- Auto-triggers for content/taxonomy revalidation (bundle/vocab filters)
- Queue + cron with rate limiting; nightly sweeps
- Path revalidation one-off form
- Status page: Frontends section with “Test now” and recent attempts
- Event logs UI with filters
- Alerts (Slack/email) thresholds with backoff on consecutive failures
- GraphQL schema handshake (daily) + optional sweep on change
- Tag Explorer local task on node/term/menu pages
- GraphQL checks page (Run / Run all)
- Drush commands: wl-api:revalidate:tag, wl-api:revalidate:path, wl-api:test, wl-api:logs

Quality:
- PHPCS/DrupalPractice cleanup (0 errors, warnings remain for DI recommendations)

Migration/BC:
- Backward compatible with previous menu/global state tracking
- wl_headless fully removed; wl_api is the single integration module
