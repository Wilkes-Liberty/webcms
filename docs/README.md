# CMS Documentation

Documentation for the Wilkes Liberty headless Drupal 11 CMS.

**For developers and agents working in this repo**: Start with [../AGENTS.md](../AGENTS.md) for workflows, content modeling rules, and cross-repo coordination.

## For Content Creators

| Document | Purpose |
|----------|---------|
| [CONTENT_TYPES_GUIDE.md](CONTENT_TYPES_GUIDE.md) | All 11 content types, their purpose and field usage |
| [FIELD_REFERENCE.md](FIELD_REFERENCE.md) | Authoritative field specifications (names, formats, requirements) |
| [FIELD_FORMATS_GUIDE.md](FIELD_FORMATS_GUIDE.md) | Text format options and AI-assisted features |
| [PARAGRAPHS.md](PARAGRAPHS.md) | Component/paragraph types for Landing Pages |
| [CONTENT.md](CONTENT.md) | Editorial workflows and content management |
| [PAGE_INVENTORY.md](PAGE_INVENTORY.md) | Working IA punch-list — every public page, status, and owner |

## For Developers

| Document | Purpose |
|----------|---------|
| [DEVELOPER_SETUP.md](DEVELOPER_SETUP.md) | High-level onboarding and first-time setup |
| [local-development.md](local-development.md) | Detailed DDEV setup, PostgreSQL 16, daily workflow, database & API testing, **refreshing prod data** (`scripts/refresh-env.sh --fetch` + auto secure cleanup of live dumps, `--keep-dump`, `--target both`, etc.) |
| [PATCHES.md](PATCHES.md) | Composer patch documentation and upstream issues |
| [SOLR_CONFIG_DEPLOY.md](SOLR_CONFIG_DEPLOY.md) | Runbook for pushing Drupal-generated Solr configsets to the on-prem Solr container |
| [../web/modules/custom/wl_api/CHANGELOG.md](../web/modules/custom/wl_api/CHANGELOG.md) | API module changelog |

## Key Principle

**[FIELD_REFERENCE.md](FIELD_REFERENCE.md)** is the authoritative source for all field specifications. Other documents provide workflow context and usage guidance; when there's a conflict, FIELD_REFERENCE.md wins.

## Document Maintenance

- Export config after any field or content type change: `ddev drush cex -y`
- Update FIELD_REFERENCE.md when fields are added or changed
- Update CONTENT_TYPES_GUIDE.md when workflows or purposes change

---

**Last Updated**: March 2026
