# Local Development Guide

This guide covers setting up a local Drupal 11 development environment using DDEV. The local environment uses **PostgreSQL 16** to match production.

## Prerequisites

| Tool | Version | Install |
|------|---------|---------|
| Docker Desktop | 20.10+ | https://docs.docker.com/get-docker/ |
| DDEV | 1.22.0+ | https://ddev.readthedocs.io/en/stable/users/install/ |
| Composer | 2.x | `brew install composer` |
| Git | 2.30+ | `brew install git` |

### macOS Quick Install

```bash
brew install --cask docker
brew install ddev
brew install composer
```

## Initial Setup

```bash
# Clone the repository
git clone git@github.com:wilkesliberty/webcms.git
cd webcms

# Start DDEV (creates PostgreSQL 16 container automatically)
ddev start

# Install PHP dependencies
ddev composer install

# Import configuration
ddev drush cim -y

# Clear caches
ddev drush cr

# Open the site
ddev launch          # https://api.wilkesliberty.dev
ddev launch /admin   # Admin interface
```

## DDEV Configuration

The project's `.ddev/config.yaml` is pre-configured and committed. Key settings:

```yaml
name: api
type: drupal11
docroot: web
php_version: "8.3"
webserver_type: nginx-fpm
database:
  type: postgres       # PostgreSQL 16 — matches production
  version: "16"
additional_fqdns:
  - api.wilkesliberty.dev
composer_version: "2"
```

**Important**: This project uses **PostgreSQL**, not MySQL or MariaDB. Use `ddev psql` (not `ddev mysql`) for database access.

## Settings File Hierarchy

1. `web/sites/default/settings.php` — base config (committed, no secrets)
2. `web/sites/default/settings.ddev.php` — DDEV auto-generates this (gitignored); sets DB connection
3. `web/sites/default/settings.local.php` — your local overrides (gitignored)

For development, you can add cache-disabling settings to `settings.local.php`:

```php
// web/sites/default/settings.local.php (create if it doesn't exist)
<?php
// Load development services (Twig debug, relaxed CORS, null caches)
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

// Disable render cache for development
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
```

## Daily Workflow

```bash
# Start your session
ddev start
ddev composer install  # Only if composer.lock changed
ddev drush cr          # Clear caches

# After making changes in the admin interface — always export
ddev drush cex -y
git add config/sync/
git commit -m "Export configuration: <what you changed>"

# After pulling someone else's changes that include config
git pull
ddev drush cim -y
ddev drush cr
```

## Configuration Management

```bash
# Export current configuration to config/sync/
ddev drush config:export --yes    # or: ddev drush cex -y

# Import configuration from config/sync/
ddev drush config:import --yes    # or: ddev drush cim -y

# Check what's different between DB and config/sync/
ddev drush config:status

# Show diff for a specific config item
ddev drush config:diff system.site
```

## Database Operations

```bash
# PostgreSQL CLI (use this — NOT ddev mysql)
ddev psql

# Export database snapshot
ddev export-db --file=backup-$(date +%Y%m%d).sql.gz

# Import a database snapshot
ddev import-db --src=backup.sql.gz

# Drop and reinstall (destructive!)
ddev drush sql:drop -y
ddev drush site:install minimal -y
ddev drush cim -y
```

## API Development & Testing

```bash
# Test JSON:API
curl -s -H "Accept: application/vnd.api+json" \
  https://api.wilkesliberty.dev/jsonapi/node/article | jq '.data | length'

# Test GraphQL
curl -s -X POST https://api.wilkesliberty.dev/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{ nodeArticles { nodes { title } } }"}' | jq

# Get OAuth2 token for authenticated requests
# Configure a consumer in Drupal at /admin/config/services/consumer
# Then request a token:
curl -X POST https://api.wilkesliberty.dev/oauth/token \
  -d "grant_type=client_credentials" \
  -d "client_id=YOUR_CLIENT_ID" \
  -d "client_secret=YOUR_CLIENT_SECRET" | jq
```

## Translations

```bash
# Export interface translations (before committing)
./scripts/export-interface-translations.sh

# Import custom translations
./scripts/import-custom-translations.sh

# Export custom translations
./scripts/export-custom-translations.sh

# Via Drush
ddev drush locale:rebuild
ddev drush cr
```

## Generating Sample Content

```bash
# Enable devel_generate (dev only — never in staging/prod)
ddev drush en devel_generate -y

# Generate sample content
ddev drush genc 10 --types=article
ddev drush genc 5 --types=event
ddev drush genc 5 --types=landing_page

# Disable when done
ddev drush pmu devel_generate -y
```

## Debugging

### Xdebug
```bash
# Enable (configure your IDE to listen on port 9003)
ddev xdebug on

# PhpStorm: Settings > PHP > Servers > add api.wilkesliberty.dev, port 443, Xdebug
# VS Code: install PHP Debug extension, use port 9003

ddev xdebug off   # Disable when not needed (big performance hit)
```

### Drupal Watchdog Logs
```bash
ddev drush watchdog:show           # Recent logs
ddev drush watchdog:show --type=php  # PHP errors only
ddev drush watchdog:show --severity=error
```

### Module Status
```bash
ddev drush pm:list | grep -E "(json|api|rest|graphql|redis|next)"
```

## Custom Module Development

```bash
# Generate a new module scaffold
ddev drush generate:module

# Place in web/modules/custom/
# Follow Drupal 11 coding standards
```

Module layout example:
```
web/modules/custom/wl_example/
├── wl_example.info.yml
├── wl_example.routing.yml
├── wl_example.module
└── src/
    ├── Controller/
    └── Plugin/
```

## Code Quality

```bash
# Check coding standards (PHPCS)
ddev composer phpcs

# Auto-fix fixable violations
ddev composer phpcbf

# Security audit
ddev composer audit
```

## Refreshing Production Data (Database + Files)

For realistic development and testing you often want a recent copy of production content.

**Canonical full staging refresh** (recommended when targeting the real staging environment):

```bash
cd ~/Repositories/infra
make refresh-staging
```

This performs heavy sanitization (email rewriting, password invalidation, Postmark sandbox, Next.js URL rewriting, etc.) and is the production-grade path.

**Quick local or lighter staging refresh** (new convenience script):

```bash
cd webcms
./scripts/refresh-env.sh
```

The script is interactive by default and supports:

- Target selection: **Local (DDEV)** or **Staging**
- Granular choices: database only, files only, or both
- Full sanitization of user data, custom email fields, webforms, watchdog, etc.
- Non-interactive mode: `./scripts/refresh-env.sh --target local --db-only --yes`

### Common examples

```bash
# Fully interactive (recommended first time)
./scripts/refresh-env.sh

# Local DB only, non-interactive (great in CI or scripts)
./scripts/refresh-env.sh --target local --db-only -y --dump=/tmp/prod-latest.dump

# Staging (lighter in-place path — still prefers the make target for full features)
./scripts/refresh-env.sh --target staging --both
```

**Notes for local DDEV**:
- You must first obtain a production dump (usually via the on-prem server + `pg_dump` or the infra tooling).
- Pass it with `--dump=...` or the script will prompt.
- Files sync is intentionally limited locally (most developers only need the DB).

The sanitization PHP scripts (`sanitize_email_fields.php`, `sanitize_webform_emails.php`) live in `scripts/` and are shared with the infra refresh playbook.

## Troubleshooting

| Problem | Solution |
|---------|----------|
| DDEV won't start | `ddev poweroff && ddev start` |
| Port conflict | `ddev stop && sudo lsof -i :80 && ddev start` |
| Config import fails | `ddev drush cim --partial -y` |
| Config UUID mismatch | `ddev drush config:set system.site uuid <UUID>` |
| Module install error | `ddev composer install` then retry |
| PostgreSQL connection | `ddev describe` to see DB credentials; use `ddev psql` |
| Composer memory limit | `ddev composer install --no-dev -o` or `COMPOSER_MEMORY_LIMIT=-1 ddev composer install` |
| Permission issues | `ddev exec chmod -R 755 web/sites/default/files` |

### Getting DDEV Credentials
```bash
ddev describe          # Shows all URLs and service info
ddev describe postgres # PostgreSQL-specific connection info
```

## Additional Resources

- [DDEV Documentation](https://ddev.readthedocs.io/)
- [Drupal 11 API Documentation](https://api.drupal.org/api/drupal/11)
- [JSON:API Specification](https://jsonapi.org/)
- [next-drupal Documentation](https://next-drupal.org)
- [Drupal Slack](https://drupal.slack.com/) — #headless and #drupal-decoupled channels
