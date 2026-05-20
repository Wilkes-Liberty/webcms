# Developer Environment Setup

This guide helps you get a local development environment running for the Wilkes Liberty headless Drupal 11 CMS (webcms).

**For the full platform picture** (how this repo relates to infra and ui, deployment, secrets, etc.), read:
- [AGENTS.md](../AGENTS.md)
- The root [../../AGENTS.md](../../AGENTS.md)

---

## First-Time Setup Checklist

1. Install prerequisites (Docker Desktop + DDEV + Composer)
2. Clone the three sibling repositories
3. Start DDEV and run the initial setup commands
4. Run `make check` in the sibling `infra/` repo (if you also work on deployment)
5. Configure any needed OAuth2 / revalidation secrets for local full-stack testing with the UI

---

## Prerequisites

| Tool              | Recommended Version | Notes |
|-------------------|---------------------|-------|
| Docker Desktop    | Recent              | Required by DDEV |
| DDEV              | 1.22.0+             | Primary local environment |
| Composer          | 2.x                 | PHP dependency management |
| PHP               | 8.3+                | Matches production |
| Git               | Recent              | — |

### macOS Quick Install

```bash
brew install --cask docker
brew install ddev
brew install composer
```

After installing Docker Desktop, start it and wait for the daemon to be ready.

---

## Clone the Repositories

For full-stack local development (recommended), clone all three repositories as siblings:

```bash
mkdir -p ~/Repositories
cd ~/Repositories

git clone git@github.com:Wilkes-Liberty/infra.git
git clone git@github.com:Wilkes-Liberty/webcms.git
git clone git@github.com:Wilkes-Liberty/ui.git
```

The `ui` repo needs to point at your local DDEV Drupal instance via environment variables.

---

## Initial Local Setup (DDEV)

```bash
cd webcms

ddev start                    # Creates PostgreSQL 16 + nginx-fpm stack
ddev composer install         # Install PHP dependencies
ddev drush cim -y             # Import configuration from config/sync/
ddev drush cr                 # Clear caches

ddev launch                   # Open the site
ddev launch /admin            # Open the admin interface
```

**Important**: This project uses **PostgreSQL 16**, not MySQL. Always use `ddev psql`.

See [local-development.md](local-development.md) for the full detailed guide (DDEV config, settings files, database operations, API testing, etc.).

---

## Daily Workflow

```bash
cd webcms

ddev start
ddev composer install          # only when composer.lock changes
ddev drush cr

# After any admin/config change:
ddev drush cex -y
git add config/sync/
git commit -m "Export configuration: <description>"
```

After pulling changes from others:
```bash
git pull
ddev drush cim -y
ddev drush cr
```

---

## Cross-Repo Considerations

- When testing the Next.js frontend locally, point `ui/.env.local` at your DDEV URL (`https://api.wilkesliberty.dev`).
- Some features in the UI (authenticated GraphQL queries, revalidation, preview mode) require OAuth2 consumer credentials created in Drupal.
- Deployment of this codebase is handled from the `infra/` repository. See the root [AGENTS.md](../../AGENTS.md) and `infra/docs/DEVELOPER_SETUP.md` for how builds and deploys work.

---

## Configuration Management Discipline

**Always export configuration** after making changes through the Drupal admin UI:

```bash
ddev drush cex -y
```

Commit the resulting files in `config/sync/`. This is the single source of truth for the project.

See [docs/CONFIG_EXPORT.md](../docs/CONFIG_EXPORT.md) (in infra) and the content modeling guides in this repo for more context.

---

## Useful Scripts

Located in `scripts/`:

- Translation import/export scripts
- Various one-off maintenance and data scripts

Run them directly when needed (they usually assume you are inside a DDEV shell or have the right environment).

---

## Troubleshooting

- **DDEV won't start** → `ddev poweroff && ddev start`
- **Config import fails** → `ddev drush config:import --partial -y`
- **Database issues** → Use `ddev psql`, not MySQL tools
- **Missing modules after pull** → `ddev composer install`

For deeper DDEV-specific help, see [local-development.md](local-development.md).

---

## Next Steps

- Read the full [local-development.md](local-development.md)
- Explore the content modeling documentation in `docs/` (especially `FIELD_REFERENCE.md`)
- Read [AGENTS.md](../AGENTS.md) for workflows, custom modules, and cross-repo coordination
- If you also work on deployment or secrets, read the infrastructure [DEVELOPER_SETUP.md](../../infra/docs/DEVELOPER_SETUP.md)

Welcome to the CMS team. The most important habit is **always exporting configuration** after admin changes.