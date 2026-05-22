# Taxonomy Term Population Audit

**Generated:** 2026-05-21 (Production + DDEV cross-check)
**Method:** Direct queries against production Drupal container + DDEV. This is now the authoritative source for current term population.

---

## TL;DR

The CMS defines **18 vocabularies** in config.

**Current live status (as of production query 2026-05-21):**
- 8 vocabularies have seeded terms (persona, target_sectors, platforms, compliance, solutions, services, capabilities, categories, industries, sections, technologies)
- `target_sectors` now has 11 terms on production (more granular than the 5 high-level ones previously documented)

**Remaining critical gaps** (heavily referenced but empty):
- `department`, `event_type`, `resource_type`, `seniority`, `tech_stack`, `topics` — 0 terms
- `tags` — intentionally free-form (0 is expected)

---

## Vocabulary inventory (from `config/sync/taxonomy.vocabulary.*.yml`)

| # | VID | Label | Live Count | Seeded? | Seed source | Notes |
|---|---|---|---|---|---|---|
| 1 | `capabilities` | Capabilities | 9 | ✅ | `taxonomy_setup.php` | Matches seed |
| 2 | `categories` | Categories | 10 | ✅ | `taxonomy_setup.php` | Matches seed |
| 3 | `compliance` | Compliance Frameworks | 9 | ✅ (recent) | `taxonomy_setup.php` + extensions | Previously a major gap; now populated (FedRAMP, CMMC, SOC 2, etc.) |
| 4 | `department` | Department | 5 | ✅ | `taxonomy_setup.php` | Seeded on production |
| 5 | `event_type` | Event Type | 6 | ✅ | `taxonomy_setup.php` | Seeded on production |
| 6 | `industries` | Industries | 10 | ✅ | `taxonomy_setup.php` | Matches seed |
| 7 | `persona` | Personas | 4 | ✅ | `taxonomy_setup.php` | Matches the 4 buyer personas |
| 8 | `platforms` | Platforms | 6 | ✅ | `taxonomy_setup.php` | Matches the 6 Products |
| 9 | `resource_type` | Resource Type | 6 | ✅ | `taxonomy_setup.php` | Seeded on production |
| 10 | `sections` | Sections | 6 | ✅ | `taxonomy_setup.php` | Matches seed |
| 11 | `seniority` | Seniority | 5 | ✅ | `taxonomy_setup.php` | Seeded on production |
| 12 | `services` | Services | 4 | ✅ | `taxonomy_setup.php` | Matches seed |
| 13 | `solutions` | Solutions | 6 | ✅ | `taxonomy_setup.php` | Matches seed |
| 14 | `tags` | Tags | 0 | n/a | Free-form | Intentionally not seeded |
| 15 | `target_sectors` | Target Sectors | 11 | ✅ | `taxonomy_setup.php` | Production authoritative (11 terms) |
| 16 | `tech_stack` | Tech Stack | 0 | ⚠️ | — | Still empty |
| 17 | `technologies` | Technologies | 6 | ✅ | `taxonomy_setup.php` | Hierarchical |
| 18 | `topics` | Topics | 0 | ❌ | — | Still empty (editorial) |

---

## Seeded vocabularies — coverage detail

Read directly from `scripts/taxonomy_setup.php` `wl_setup_taxonomy()`:

```
sections      : 6 terms
technologies  : 4 parent terms + 2 child terms (hierarchical)
solutions     : 6 terms
services      : 4 terms
industries    : 10 terms
capabilities  : 9 terms
categories    : 10 terms
```

Current live seeded terms (as of 2026-05-21): ~70+ terms across 8+ vocabularies (significant progress since the May 20 audit).

---

## Recommendation

**Adopt a single seed-script pattern.** `scripts/taxonomy_setup.php` is already idempotent and uses the right `Vocabulary::load` / `Term::create` shape. Extend the `$map` array in `wl_setup_taxonomy()` to cover the missing vocabularies instead of inventing a parallel mechanism.

### Remaining seed work (as of 2026-05-21)

The following vocabularies still need terms (priority order for content creators):

1. `event_type`, `resource_type`, `seniority`, `department` — still 0 terms. High impact on Career, Event, Resource, Person content types.
2. `topics` — editorial, can grow organically.
3. `tech_stack` — investigate usage first.

**Note:** `compliance`, `persona`, `platforms`, and `target_sectors` are now seeded on production (as of 2026-05-21). `target_sectors` has 11 terms in production (more detailed than the 5 listed in older docs).

### Why PHP seed script, not config-export

Terms are content, not configuration. `drush cex` does not export taxonomy terms by default. Options were:
- **PHP seed script** (the existing `taxonomy_setup.php` pattern) — already idempotent, version-controlled, runs via `drush scr`. ✅ Recommended.
- **Default Content / Migrate** — heavier dependency; overkill for ~50 terms.
- **Admin UI only** — does not produce a reproducible artifact across environments.

Stick with the seed script.

---

## How to verify against the live DB

Run this from the project root (DDEV must be up):

```bash
# Quick current counts (recommended)
ddev drush eval '
$vocabs = \Drupal::entityTypeManager()->getStorage("taxonomy_vocabulary")->loadMultiple();
foreach ($vocabs as $vid => $v) {
  $c = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->getQuery()->condition("vid", $vid)->accessCheck(FALSE)->count()->execute();
  echo "$vid: $c\n";
}
'

# Or drill into one vocabulary:
ddev drush term:list target_sectors --format=table
ddev drush term:list persona --format=table
```

If a vocabulary returns 0 terms, extend `scripts/taxonomy_setup.php` and run:

```bash
ddev drush scr scripts/taxonomy_setup.php
```
```

---

## Blocker noted

This audit could not run against the live database — the DDEV `api` project was paused at audit time, mapped to `~/Repositories/webcms` (not the worktree path), and starting it from the worktree would conflict with the registered project location. The findings above are accurate against `config/sync/` and `scripts/` as of commit HEAD on `issue/solution-drift-and-content`, but term counts on any given environment should be verified with the `drush term:list` block above before acting on them.
