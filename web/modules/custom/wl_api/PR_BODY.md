# wl_api admin enhancements

This PR delivers a substantial set of admin utilities for decoupled revalidation, observability, and frontend health.

## Highlights
- Multi-frontend support (config entity) with links to Health/CI
- Auto-triggers for nodes/terms (bundle/vocab filters)
- Queue + cron with global rate limiting and nightly sweeps
- Path revalidation (one-off form)
- Status page: Frontends section, “Test now”, recent attempts
- Event logs UI with filters and metrics
- Alerts (Slack/email) with thresholds/backoff on consecutive failures
- GraphQL schema handshake (daily) + optional sweep on change
- Tag Explorer local task on nodes/terms/menus
- Saved GraphQL checks page with one-click Run/Run all
- Drush: wl-api:revalidate:tag|path, wl-api:test, wl-api:logs

## New admin paths
- Status: `/admin/config/api/status`
- Settings: `/admin/config/api/settings`
- Frontends: `/admin/config/api/frontends`
- Logs: `/admin/config/api/logs`
- Path Revalidation: `/admin/config/api/path/revalidate`
- GraphQL Checks: `/admin/config/api/checks`
- Tag Explorer: on node/term/menu pages as “Frontend tags” tab

## Permissions
- view api revalidation status
- trigger api revalidation
- administer api revalidation settings
- administer api frontends
- view api logs
- run api checks

## Configuration
- Auto triggers: enable + select bundles/vocabs
- Scheduling: rate_limit_per_minute, sweep hour/minute
- GraphQL endpoint + sweep-on-change toggle
- Alerts: threshold, Slack webhook, alert emails
- Per-frontend: revalidate URL, path revalidate URL, secret (or Key ID), links

## Screenshots (attach in PR)
- [ ] Status page (Frontends section with Test)
- [ ] Logs page with recent attempts
- [ ] Path revalidation form
- [ ] Frontends list + edit form
- [ ] Tag Explorer tab on a node and a term
- [ ] GraphQL checks page (with a successful run)

## Testing
1. Configure at least one Frontend with revalidate URL + secret.
2. On Status page, click “Test now” — verify a new log entry and OK status.
3. Create/update a node/term (included by settings) — verify tag revalidations/logs.
4. Run Path revalidation for a known page — check frontend response.
5. Save a GraphQL check, then run on the checks page — verify output.
6. Temporarily break the endpoint to trigger consecutive failures — verify alert.

## Notes
- Backward-compatible with existing menu/global state entries.
- PHPCS/DrupalPractice: 0 errors (warnings remain for DI recommendations by design).

## Deployment
- drush updb -y
- drush cr
- Configure frontends + settings described above.
