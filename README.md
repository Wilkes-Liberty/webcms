# Wilkes Liberty CMS

Headless Drupal 11 CMS serving as the API backend for [wilkesliberty.com](https://wilkesliberty.com). Content is delivered to a Next.js frontend via JSON:API and GraphQL. The system is multilingual (English, Spanish, Russian) and uses PostgreSQL 16.

> **Deep guidance for developers and agents**: See [AGENTS.md](AGENTS.md) (and the root [../AGENTS.md](../AGENTS.md) for platform context).

**Current focus**: Continued refinement of content modeling, GraphQL schema stability, and supporting the Keycloak SSO rollout on the infrastructure side.

**New contributors**: Start with [docs/DEVELOPER_SETUP.md](docs/DEVELOPER_SETUP.md).

## Tech Stack

| Component | Version | Role |
|-----------|---------|------|
| Drupal | 11 | Headless CMS |
| PHP | 8.3 | Runtime |
| PostgreSQL | 16 | Database |
| Redis | 7 | Object cache |
| Apache Solr | 9.6 | Full-text search |
| next-drupal | 2.0.0-beta.1 | Next.js integration |

## Local Development

Local development uses **DDEV** with **PostgreSQL 16** (matching production). See [docs/local-development.md](docs/local-development.md) for full setup instructions.

```bash
# Quick start
ddev start
ddev composer install
ddev drush cim -y
ddev launch  # Opens https://api.wilkesliberty.dev
```

## Project Structure

```
webcms/
├── config/sync/        # Drupal configuration (commit all changes here)
├── docs/               # Developer and editorial documentation
├── drush/              # Drush config and custom commands
├── patches/            # Composer patch files
├── scripts/            # Translation and utility scripts
├── translations/       # Interface translation exports
├── web/
│   ├── core/           # Drupal core (Composer-managed, do not edit)
│   ├── modules/
│   │   ├── contrib/    # Contributed modules (Composer-managed)
│   │   └── custom/     # Custom modules (wl_api, wl_language_switcher, etc.)
│   ├── sites/
│   │   └── default/
│   │       ├── settings.php         # Base settings (committed, no secrets)
│   │       ├── settings.docker.php  # Docker env-var settings (committed)
│   │       ├── settings.local.php   # Local overrides (gitignored)
│   │       └── default.services.yml # Default services
│   └── themes/
│       └── custom/     # Any custom theme assets
├── .ddev/config.yaml   # DDEV config (PostgreSQL 16, name: api)
├── .env.example        # Environment variable template
├── composer.json       # PHP dependencies + patches
└── composer.lock
```

## Content Types

The CMS has 9 content types:

| Type | Machine Name | Purpose |
|------|-------------|---------|
| Article | `article` | News, blog posts, press releases |
| Basic Page | `basic_page` | Static pages (About, Policies) |
| Career | `career` | Job postings |
| Case Study | `case_study` | Client success stories |
| Event | `event` | Webinars, conferences |
| Landing Page | `landing_page` | Marketing and campaign pages |
| Person | `person` | Team bios, author profiles |
| Resource | `resource` | Downloadable content (eBooks, guides) |
| Service | `service` | Consulting and service offerings |

See [docs/CONTENT_TYPES_GUIDE.md](docs/CONTENT_TYPES_GUIDE.md) for field-level detail on each type.

## API Endpoints

| Endpoint | URL (local) | Format |
|----------|------------|--------|
| JSON:API | `https://api.wilkesliberty.dev/jsonapi` | JSON:API spec |
| GraphQL | `https://api.wilkesliberty.dev/graphql` | GraphQL |
| REST | `https://api.wilkesliberty.dev/api` | REST |

```bash
# Example: fetch all articles
curl -H "Accept: application/vnd.api+json" \
  https://api.wilkesliberty.dev/jsonapi/node/article | jq

# Example: GraphQL query
curl -X POST https://api.wilkesliberty.dev/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{ nodeArticles { nodes { title } } }"}'
```

## Configuration Management

All configuration is tracked in `config/sync/`. After any admin change, export and commit:

```bash
ddev drush cex -y
git add config/sync/
git commit -m "Export configuration: <what changed>"
```

After pulling changes that include config:

```bash
ddev drush cim -y
ddev drush cr
```

## Custom Modules

| Module | Location | Purpose |
|--------|----------|---------|
| `wl_api` | `web/modules/custom/wl_api/` | Custom REST endpoints |
| `wl_language_switcher` | `web/modules/custom/wl_language_switcher/` | Language switching UI |
| `wl_taxo_nav` | `web/modules/custom/wl_taxo_nav/` | Sync taxonomy to navigation |
| `wl_text_formats` | `web/modules/custom/wl_text_formats/` | Custom text format config |

## Composer Patches

8 active patches are managed via `composer.json` extra.patches. See [docs/PATCHES.md](docs/PATCHES.md) for the rationale and upstream issue links for each patch.

## Environments

| Environment | Access | Branch | Notes |
|-------------|--------|--------|-------|
| Local | `https://api.wilkesliberty.dev` | any | DDEV, PostgreSQL 16 |
| Staging | `https://stg-api.int.wilkesliberty.com` (Tailscale) | `staging` | Docker on on-prem server (auto-deploy) |
| Production | `https://api.wilkesliberty.com` | `master` | Docker on on-prem server (manual deploy) |

The Next.js frontend (in the `ui` repo) connects to Drupal at runtime. In production, the Next.js container runs on the Njalla VPS and reaches Drupal via Tailscale.

## Branch Strategy

Three branches, kept in lockstep automatically by
[`.github/workflows/sync-branches.yml`](.github/workflows/sync-branches.yml):

- `master` — production-ready code. Feature/fix PRs target this branch.
- `staging` — auto-synced from `master` on every push. The push triggers
  the staging deploy in the `infra` repo.
- `development` — auto-synced from `master` on every push. No deploy;
  WIP integration baseline.
- `feature/*` — feature development; pull requests target `master`.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for the full development workflow, coding standards, and pull request process.

Quick reference:
```bash
# Create a feature branch off master
git checkout master
git pull origin master
git checkout -b feature/your-feature

# Work locally, export config, then
ddev drush cex -y
git add config/sync/
git commit -m "feat: add new content type field"
git push origin feature/your-feature
# Open pull request → master (sync-branches.yml will FF staging + development on merge)
```

## Documentation

| Document | Purpose |
|----------|---------|
| [docs/local-development.md](docs/local-development.md) | DDEV setup, PostgreSQL, daily workflow |
| [docs/CONTENT_TYPES_GUIDE.md](docs/CONTENT_TYPES_GUIDE.md) | All content types and fields |
| [docs/FIELD_REFERENCE.md](docs/FIELD_REFERENCE.md) | Authoritative field specifications |
| [docs/PARAGRAPHS.md](docs/PARAGRAPHS.md) | Paragraph/component types |
| [docs/PATCHES.md](docs/PATCHES.md) | Composer patch documentation |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Development workflow |
| [CLAUDE.md](CLAUDE.md) | AI assistant context |
| [README_HEADLESS.md](README_HEADLESS.md) | Headless/decoupled architecture details |

---

**Last Updated**: March 2026
