# Taxonomy Term Population Audit

**Generated:** 2026-05-20
**Method:** Static analysis of `config/sync/` and `scripts/` — DDEV `api` project was paused at audit time, so term counts were not queried from the live database. See "How to verify against the live DB" at the end.

---

## TL;DR

The CMS defines **18 vocabularies** in config. Only **7 of them have seed coverage** via `scripts/taxonomy_setup.php`. The remaining **11 vocabularies have no automated seeding**, meaning unless terms were added manually through the admin UI on a given environment, they are empty.

**Critical gaps** (vocabularies that are heavily referenced by fields but have no seed script):
- `persona` — referenced by `field_personas` on 9+ content types
- `target_sectors` — referenced by `field_target_sectors` on Product, Service, Solution, Case Study (Defense / Federal Government / State & Local / Critical Infrastructure / Enterprise — explicitly mentioned in `CONTENT_TYPES_GUIDE.md`)
- `compliance` — referenced by `field_compliance` on all major content types
- `platforms` — referenced by `field_platform` on Product, Service, Solution, Case Study (vocabulary itself is created by `competitive_enhancements.php` but no terms are seeded)

---

## Vocabulary inventory (from `config/sync/taxonomy.vocabulary.*.yml`)

| # | VID | Label | Seeded? | Seed source | Notes |
|---|---|---|---|---|---|
| 1 | `capabilities` | Capabilities | ✅ | `taxonomy_setup.php` | 9 terms (AI, Blockchain, Cloud Ops, CMS, Cybersecurity, Digital Identity, Digital Modernization, Infrastructure, SW Dev) |
| 2 | `categories` | Categories | ✅ | `taxonomy_setup.php` | 10 terms (Digital Innovation, Privacy Tech, Enterprise Solutions, etc.) |
| 3 | `compliance` | Compliance Frameworks | ❌ | — | **Gap.** Referenced by `field_compliance` on Article, Basic Page, Career, Case Study, Event, Landing Page, Product, Resource, Service, Solution. `competitive_enhancements.php` adds `field_badge_icon` + `field_short_description` to compliance terms but does not seed any. |
| 4 | `department` | Department | ❌ | — | **Gap.** Used by Career and Person (`field_department`). |
| 5 | `event_type` | Event Type | ❌ | — | **Gap.** Used by Event (`field_event_type`). Per `CONTENT_TYPES_GUIDE.md` §5: Webinar, Conference, Workshop, Networking, etc. |
| 6 | `industries` | Industries | ✅ | `taxonomy_setup.php` | 10 terms (A&D, Civil Engineering, Comms & Media, Education, Emerging Markets, Finance, Government, Health, Legal, Travel) |
| 7 | `persona` | Personas | ❌ | — | **Gap — high priority.** Referenced by `field_personas` on nearly every content type. The brand voice + CONTENT.md treat personas as a primary segmentation axis. Note: vid is `persona` (singular), not `personas` — `streamline_architecture.php` documents a prior bug where `field_personas` was incorrectly targeting `personas`. |
| 8 | `platforms` | Platforms | ❌ | Vocab only | **Gap.** Vocabulary is created by `competitive_enhancements.php` but no seeded terms. Referenced by `field_platform` on Product, Service, Solution, Case Study. The Products in CONTENT.md (Sovereign Infrastructure, Liberty CMS, Enterprise Search, Fortis, Apex, Vigilance) are the natural terms to seed. |
| 9 | `resource_type` | Resource Type | ❌ | — | **Gap.** Used by Resource (`field_resource_type`). Per `CONTENT_TYPES_GUIDE.md` §8: eBook, Whitepaper, Checklist, Template, Guide, Report. |
| 10 | `sections` | Sections | ✅ | `taxonomy_setup.php` | 6 terms (Capabilities, Technology, Solutions, Services, Industries, About) — used by `wl_taxo_nav` for main-menu sync. |
| 11 | `seniority` | Seniority | ❌ | — | **Gap.** Used by Career (`field_seniority`). Per `CONTENT_TYPES_GUIDE.md` §3: Entry Level, Mid-Level, Senior, Lead, Executive. |
| 12 | `services` | Services | ✅ | `taxonomy_setup.php` | 4 terms (AI Specialist Support, Cloud Operations, Cybersecurity, Software Development) — note this is the *taxonomy*, distinct from the Service *content type*. |
| 13 | `solutions` | Solutions | ✅ | `taxonomy_setup.php` | 6 terms (Digital Health ID, Digital Modernization, Financial Optimization, Private LLMs, Product AI, Zero Trust) — note this is the *taxonomy*, distinct from the Solution *content type* (see CONTENT_TYPES_GUIDE.md §11). |
| 14 | `tags` | Tags | n/a | Pre-existing | `taxonomy_setup.php` explicitly skips this vocab. Free-form tags — populated by editors as content is created. |
| 15 | `target_sectors` | Target Sectors | ❌ | — | **Gap — high priority.** Referenced by `field_target_sectors` on Product, Service, Solution, Case Study. Per `CONTENT_TYPES_GUIDE.md` Common Field Groups: Defense, Federal Government, State & Local, Critical Infrastructure, Enterprise. |
| 16 | `tech_stack` | Tech Stack | ⚠️ | — | `fix_low_items.php` flags this as potentially unused; check `field_tech_stack` references before seeding. |
| 17 | `technologies` | Technologies | ✅ | `taxonomy_setup.php` | 4 parents + 2 children (AI, Blockchain → XRP Ledger, Content Management → Drupal, Digital Identity). |
| 18 | `topics` | Topics | ❌ | — | **Gap.** Referenced by classification fields. Editorial vocabulary — likely intended to be populated as content grows. |

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

Total seeded by script: ~47 terms across 7 vocabularies.

---

## Recommendation

**Adopt a single seed-script pattern.** `scripts/taxonomy_setup.php` is already idempotent and uses the right `Vocabulary::load` / `Term::create` shape. Extend the `$map` array in `wl_setup_taxonomy()` to cover the missing vocabularies instead of inventing a parallel mechanism.

### Suggested next seed additions (in priority order)

1. **`target_sectors`** — content type docs already enumerate the terms:
   - Defense, Federal Government, State & Local, Critical Infrastructure, Enterprise
2. **`persona`** — pull names from `docs/BRAND_VOICE.md` and `docs/CONTENT.md` audience sections.
3. **`platforms`** — exactly the 6 Products in `docs/CONTENT.md`:
   - Sovereign Infrastructure Platform, Liberty Headless CMS Platform, Enterprise Search Platform, Fortis Zero-Trust Identity Platform, Apex Secure Data Platform, Vigilance Mission Observability Suite
4. **`compliance`** — small fixed list with `field_badge_icon` + `field_short_description` already wired up in config:
   - FedRAMP, FISMA, CMMC, SOC 2, HIPAA, ITAR, GDPR (curate per audience)
5. **`event_type`** — Webinar, Conference, Workshop, Networking, Roundtable, Briefing.
6. **`resource_type`** — eBook, Whitepaper, Checklist, Template, Guide, Report (per `CONTENT_TYPES_GUIDE.md` §8).
7. **`seniority`** — Entry Level, Mid-Level, Senior, Lead, Executive.
8. **`department`** — match HR / org chart; defer until that source-of-truth is named.
9. **`topics`** — defer; editorial vocabulary, populate organically as articles ship.
10. **`tech_stack`** — investigate `fix_low_items.php` finding before seeding; may be retired.

### Why PHP seed script, not config-export

Terms are content, not configuration. `drush cex` does not export taxonomy terms by default. Options were:
- **PHP seed script** (the existing `taxonomy_setup.php` pattern) — already idempotent, version-controlled, runs via `drush scr`. ✅ Recommended.
- **Default Content / Migrate** — heavier dependency; overkill for ~50 terms.
- **Admin UI only** — does not produce a reproducible artifact across environments.

Stick with the seed script.

---

## How to verify against the live DB

Once a Drupal environment is running (DDEV, staging, or prod), run from the project root:

```bash
# Per-vocabulary term count
for vid in capabilities categories compliance department event_type industries \
           persona platforms resource_type sections seniority services \
           solutions tags target_sectors tech_stack technologies topics; do
  count=$(ddev drush term:list "$vid" --format=json 2>/dev/null | jq 'length' 2>/dev/null || echo "?")
  printf "%-20s %s\n" "$vid" "$count"
done

# Or, for a quick sanity check on a single vocab:
ddev drush term:list target_sectors
ddev drush term:list persona
ddev drush term:list platforms
```

If a vocabulary returns 0 terms, run the seed script after extending it:

```bash
ddev drush scr scripts/taxonomy_setup.php
```

---

## Blocker noted

This audit could not run against the live database — the DDEV `api` project was paused at audit time, mapped to `~/Repositories/webcms` (not the worktree path), and starting it from the worktree would conflict with the registered project location. The findings above are accurate against `config/sync/` and `scripts/` as of commit HEAD on `issue/solution-drift-and-content`, but term counts on any given environment should be verified with the `drush term:list` block above before acting on them.
