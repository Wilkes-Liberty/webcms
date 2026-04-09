# CLAUDE.md

This file provides context to Claude (claude.ai) when working with this repository.

## Project Overview

**Headless Drupal 11 CMS** serving as the API backend for [wilkesliberty.com](https://wilkesliberty.com). Content is delivered to a Next.js frontend via JSON:API and GraphQL. The system is multilingual (EN/ES/RU) and uses PostgreSQL 16 as its database.

Key facts:
- **Drupal 11** with PostgreSQL 16 (not MySQL/MariaDB)
- **DDEV** for local development (project name: `api`, FQDN: `api.wilkesliberty.dev`)
- **Docker** for staging/production (built by the infra repo's Dockerfiles)
- Configuration managed in `config/sync/` and exported via Drush
- 8 composer patches active (see `composer.json` extra.patches)

## Local Development — DDEV

### Prerequisites
- DDEV v1.22.0+
- Docker Desktop
- Composer 2

### Setup
```bash
# Start DDEV (PostgreSQL 16 — matches production)
ddev start

# Install PHP dependencies
ddev composer install

# Import configuration
ddev drush cim -y

# Clear caches
ddev drush cr

# Open site
ddev launch          # Main site: https://api.wilkesliberty.dev
ddev launch /admin   # Admin interface
```

### DDEV Database (PostgreSQL 16)
```bash
# PostgreSQL CLI (not mysql!)
ddev psql

# Export database
ddev export-db --file=backup-$(date +%Y%m%d).sql.gz

# Import database
ddev import-db --src=backup.sql.gz
```

### Settings File Hierarchy
1. `web/sites/default/settings.php` — base config (committed, no secrets)
2. `web/sites/default/settings.ddev.php` — DDEV auto-generates this (gitignored)
3. `web/sites/default/settings.local.php` — local overrides (gitignored)

For Docker deployments, `settings.docker.php` is mounted as `settings.local.php` and reads all config from environment variables.

### Configuration Management
```bash
# Export after admin changes (always commit these)
ddev drush cex -y
git add config/sync/
git commit -m "Export configuration: <what changed>"

# Import (after pulling someone else's config changes)
ddev drush cim -y

# Check status
ddev drush config:status
ddev drush config:diff
```

### Translation Scripts
```bash
./scripts/export-interface-translations.sh
./scripts/import-custom-translations.sh
./scripts/export-custom-translations.sh
```

## Content Architecture

### Content Types (9)
| Type | Machine Name | Purpose |
|------|-------------|---------|
| Article | `article` | News, blog posts, announcements |
| Basic Page | `basic_page` | Static pages (About, Policies) |
| Career | `career` | Job postings |
| Case Study | `case_study` | Client success stories |
| Event | `event` | Webinars, conferences |
| Landing Page | `landing_page` | Marketing / campaign pages |
| Person | `person` | Team bios, author profiles |
| Resource | `resource` | Downloadable content |
| Service | `service` | Consulting / service offerings |

### Taxonomy Vocabularies
- Solutions, Technologies, Capabilities, Industries, Topics, Tech Stack, Personas, Compliance

### Field Organization (Tab Pattern)
Content/Media/CTAs/SEO/Technical/Relationships/Classification/Layout/Editorial

## API Endpoints

All endpoints use the DDEV FQDN locally; in Docker the service hostname is `drupal`.

```bash
# JSON:API
https://api.wilkesliberty.dev/jsonapi
https://api.wilkesliberty.dev/jsonapi/node/article

# GraphQL
https://api.wilkesliberty.dev/graphql

# Test endpoints
curl -H "Accept: application/vnd.api+json" https://api.wilkesliberty.dev/jsonapi/node/article | jq
```

## Custom Modules (`web/modules/custom/`)
- **wl_api** — Custom REST endpoints and API helpers
- **wl_language_switcher** — Language switching UI
- **wl_taxo_nav** — Sync taxonomy terms to main menu
- **wl_text_formats** — Custom text format configurations
- **wl_scripts** — Utility Drush scripts

## Key Dependencies
- `drupal/next` — Next.js integration (preview, revalidation)
- `drupal/graphql` — GraphQL schema and queries
- `drupal/redis` — Redis cache backend
- `drupal/openid_connect` — Keycloak SSO integration
- `drupal/ai` — AI content enhancement suite
- `drupal/tmgmt` — Translation management
- `drupal/paragraphs` — Component-based page building

## Environments

| Env | Stack | Drupal URL | Notes |
|-----|-------|-----------|-------|
| Local | DDEV | `https://api.wilkesliberty.dev` | PostgreSQL 16 |
| Staging | Docker (on-prem server) | `http://localhost:8090` | `staging` branch |
| Production | Docker (on-prem server) | Internal; Tailscale to VPS | `main` branch |

## Branch Strategy
- **main** — production-ready code
- **staging** — staging environment deploys from this branch
- **feature/** — feature branches, PR into main

## Common Tasks

### Adding a Content Type
1. Create via admin interface
2. Add fields using tab-based field groups
3. Configure form/view displays
4. Export: `ddev drush cex -y`
5. Commit `config/sync/`

### Working with Patches
Composer patches are in `composer.json` under `extra.patches`. See `docs/PATCHES.md` for rationale on each patch.

### Debugging
```bash
# Enable Xdebug
ddev xdebug on   # then configure your IDE to port 9003

# Check Drupal logs
ddev drush watchdog:show

# Check API module status
ddev drush pm:list | grep -E "(json|api|rest|graphql)"
```

## Troubleshooting

| Problem | Command |
|---------|---------|
| DDEV won't start | `ddev poweroff && ddev start` |
| Config import fails | `ddev drush config:import --partial -y` |
| Config UUID mismatch | `ddev drush config:set system.site uuid <UUID>` |
| Cache issues | `ddev drush cr` |
| PostgreSQL access | `ddev psql` (NOT `ddev mysql`) |
