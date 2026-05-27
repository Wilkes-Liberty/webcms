# Page Inventory

A working IA punch-list for every public page on wilkesliberty.com. Use this to track what's drafted, what's seeded into Drupal, what's wired into the Next.js frontend, and what still needs to be written.

**Last updated:** 2026-05-27
**Owner (default):** Jeremy Cerda
**Companion docs:** [CONTENT_TYPES_GUIDE.md](CONTENT_TYPES_GUIDE.md) · [FIELD_REFERENCE.md](FIELD_REFERENCE.md) · [PARAGRAPHS.md](PARAGRAPHS.md) · [CONTENT.md](CONTENT.md)

---

## How to read this doc

**Status values**

| Status | Meaning |
|---|---|
| `drafted` | Copy exists in `docs/CONTENT.md` (or elsewhere in repo) but is not yet in Drupal |
| `seeded` | Node exists in Drupal (via setup script or manual creation) |
| `wired` | Drupal node + Next.js render path both exist and the URL resolves end-to-end |
| `todo` | Neither drafted nor seeded — needs writing |
| `n/a` | Not a Drupal-backed page (Next.js-only, system, or external) |

**Content type abbreviations** match the machine names in `config/sync/node.type.*.yml`:
`article`, `basic_page`, `career`, `case_study`, `event`, `landing_page`, `person`, `platform`, `resource`, `service`, `solution`, plus `dynamic-index` for Next.js-rendered listing pages and `nextjs` for routes with no Drupal node behind them.

> **Note:** The machine name `product` has been renamed to `platform` (F1-B decision, 2026-05-27). All config files, GraphQL typenames, and URL slugs use `platform` going forward. See `PORTFOLIO_AUDIT.md §G` for the full technical change log.

---

## Site map

```
/                                    [Homepage — landing_page]
├── /about                           [basic_page]
├── /contact                         [Next.js + Drupal webform]
│
├── /platforms                       [dynamic-index]
│   ├── /platforms/sabal
│   ├── /platforms/keel
│   ├── /platforms/alidade
│   ├── /platforms/squawk
│   ├── /platforms/manifest
│   ├── /platforms/lighthouse
│   └── /platforms/coquina
│
├── /services                        [dynamic-index]
│   ├── /services/private-infrastructure-engineering
│   ├── /services/headless-cms-implementation
│   ├── /services/enterprise-search-architecture
│   ├── /services/zero-trust-identity-consulting
│   ├── /services/ai-integration
│   ├── /services/digital-modernization
│   ├── /services/custom-software-development
│   ├── /services/digital-asset-solutions
│   ├── /services/defense-technology-integration
│   └── /services/intelligence-actionable-insights
│
├── /solutions                       [dynamic-index]
│   └── /solutions/{slug}            [solution — branded packages, see §4]
│
├── /case-studies                    [dynamic-index]
│   └── /case-studies/{slug}         [case_study]
│
├── /resources                       [dynamic-index]
│   └── /resources/{slug}            [resource — gated/downloadable]
│
├── /articles                        [dynamic-index — already wired]
│   └── /articles/{slug}             [article]
│
├── /events                          [dynamic-index]
│   └── /events/{slug}               [event]
│
├── /careers                         [dynamic-index]
│   └── /careers/{slug}              [career]
│
├── /team                            [dynamic-index]
│   └── /team/{slug}                 [person]
│
├── /press                           [dynamic-index — filtered Articles]
│
├── /legal/privacy-policy            [basic_page]
├── /legal/terms-of-service          [basic_page]
├── /legal/cookie-policy             [basic_page]
├── /legal/accessibility-statement   [basic_page]
│
└── (system)
    ├── /sitemap.xml                 [nextjs / drupal]
    ├── /robots.txt                  [nextjs]
    └── 404 / 500                    [nextjs — already exist]
```

URL convention: each node-type cluster uses its plural as the parent path (`/platforms/...`, `/services/...`, etc.), with the slug derived from the node title via Drupal's path-alias pattern. The `[...slug]` catch-all on the Next.js side already resolves any path back to a Drupal node — the missing piece is rendering for content types beyond `article` and `basic_page` (see §5).

---

## 1. Marketing & top-level pages

| URL | Content type | Purpose | Status | Owner | Notes |
|---|---|---|---|---|---|
| `/` | landing_page | Homepage — hero, mission, status pill, CTA | **seeded** | Jeremy | Path alias `/homepage`; `system.site.page.front` set; fallback hard-coded in `ui/app/(marketing)/page.tsx` |
| `/about` | basic_page | Company overview, mission, history | todo | Jeremy | Write before launch — no draft yet |
| `/contact` | nextjs + webform | Contact form + inquiry email | seeded | Jeremy | Webform created by `scripts/create_contact_webform.php`; Next.js route at `app/(marketing)/contact/page.tsx` |

## 2. Platforms

Copy for all seven platforms is drafted in [CONTENT.md §Platforms](CONTENT.md#platforms). Each needs a `platform` node with `field_mission_impact`, `field_key_capabilities` (Paragraphs → Capability), and a Primary CTA per `CONTENT_TYPES_GUIDE.md §10`.

> **301 redirects required:** old `/products/*` slugs must redirect to new `/platforms/*` slugs — see `NAMING_DECISIONS.md §6.6`.

| URL | Content type | Purpose | Status | Owner | Notes |
|---|---|---|---|---|---|
| `/platforms` | dynamic-index | All Platforms listing | todo | Jeremy | Needs Next.js route; consider grouping by deployment model |
| `/platforms/sabal` | platform | Sabal Infrastructure Platform | **drafted** | Jeremy | Copy in CONTENT.md §1; redirects from `/products/sovereign-infrastructure-platform` |
| `/platforms/keel` | platform | Keel CMS Platform | **drafted** | Jeremy | Copy in CONTENT.md §2; redirects from `/products/liberty-headless-cms`; SEO-critical |
| `/platforms/alidade` | platform | Alidade Search Platform | **drafted** | Jeremy | Copy in CONTENT.md §3; redirects from `/products/enterprise-search` |
| `/platforms/squawk` | platform | Squawk Identity Platform | **drafted** | Jeremy | Copy in CONTENT.md §4; redirects from `/products/fortis-identity` |
| `/platforms/manifest` | platform | Manifest Data Platform | **drafted** | Jeremy | Copy in CONTENT.md §5; redirects from `/products/apex-data` |
| `/platforms/lighthouse` | platform | Lighthouse Observability Platform | **drafted** | Jeremy | Copy in CONTENT.md §6; redirects from `/products/vigilance-observability` |
| `/platforms/coquina` | platform | Coquina Software Factory Platform | todo | Jeremy | New entry — copy not yet written |

**Dependencies for all seven:** Capability paragraph data must be authored (CONTENT.md has prose `Key Capabilities` bullets that need restructuring into `paragraph:capability` instances), `target_sectors` taxonomy terms must be seeded, hero imagery sourced.

## 3. Services

Copy for all ten services drafted in [CONTENT.md §Services](CONTENT.md#services). Each needs a `service` node — Mission Impact required (`CONTENT_TYPES_GUIDE.md §9`).

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/services` | dynamic-index | todo | Jeremy | All-services listing route |
| `/services/private-infrastructure-engineering` | service | **drafted** | Jeremy | CONTENT.md §Services 1 |
| `/services/headless-cms-implementation` | service | **drafted** | Jeremy | CONTENT.md §Services 2 |
| `/services/enterprise-search-architecture` | service | **drafted** | Jeremy | CONTENT.md §Services 3 |
| `/services/zero-trust-identity-consulting` | service | **drafted** | Jeremy | CONTENT.md §Services 4 |
| `/services/ai-integration` | service | **drafted** | Jeremy | CONTENT.md §Services 5 |
| `/services/digital-modernization` | service | **drafted** | Jeremy | CONTENT.md §Services 6 |
| `/services/custom-software-development` | service | **drafted** | Jeremy | CONTENT.md §Services 7 |
| `/services/digital-asset-solutions` | service | **drafted** | Jeremy | CONTENT.md §Services 8; check that "Cryptocurrency" framing aligns with brand voice doc when it lands |
| `/services/defense-technology-integration` | service | **drafted** | Jeremy | CONTENT.md §Services 9 |
| `/services/intelligence-actionable-insights` | service | **drafted** | Jeremy | CONTENT.md §Services 10 |

**Cross-linking:** every Service should set `field_related_platforms` to the supporting Platform nodes, per `CONTENT_TYPES_GUIDE.md §9`. (Field was previously named `field_related_products` — rename is part of the F1-B engineering task in `PORTFOLIO_AUDIT.md §G`.)

## 4. Solutions (branded packages)

The `solution` content type is fully implemented in config. Eight canonical solutions are locked (naming session 2026-05-27). Three legacy seeded nodes exist and require disposition review per `NAMING_DECISIONS.md §6.7`.

**Canonical solutions (copy pending — see CONTENT.md §Solutions):**

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/solutions` | dynamic-index | todo | Jeremy | Listing route (Next.js) |
| `/solutions/dotedu` | solution | todo | Jeremy | Higher Education — Keel + Alidade; DotEDU Drupal distribution |
| `/solutions/accord` | solution | todo | Jeremy | Nonprofit — Keel |
| `/solutions/palisade` | solution | todo | Jeremy | Privacy SaaS — Manifest + Squawk |
| `/solutions/bulkhead` | solution | todo | Jeremy | Regulated Industries — Sabal + Squawk + Manifest |
| `/solutions/dotgov` | solution | todo | Jeremy | Federal Civilian — Keel + Alidade + Squawk; DotGov Drupal distribution |
| `/solutions/gazette` | solution | todo | Jeremy | IG Platforms / Fraud, Waste & Abuse — Keel + Manifest |
| `/solutions/outpost` | solution | todo | Jeremy | Defense Tech Modernization — Sabal + Coquina; ⚠ AWS Outposts name flag — attorney review |
| `/solutions/software-factory` | solution | todo | Jeremy | Software Factory — Coquina |

**Legacy seeded solutions (pending disposition — `NAMING_DECISIONS.md §6.7`):**

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/solutions/sovereign-mission-edge` | solution | **seeded** (prod nid 21) | Jeremy | Pre-naming placeholder; review against canonical list — map or retire |
| `/solutions/sovereign-ai-command-fabric` | solution | **seeded** (prod nid 22) | Jeremy | Pre-naming placeholder; review against canonical list — map or retire |
| `/solutions/sovereign-digital-modernization-platform` | solution | **seeded** (prod nid 23) | Jeremy | Pre-naming placeholder; review against canonical list — map or retire |

**Next:** Resolve nid 21/22/23 disposition; flesh out `field_outcomes`, Key Capabilities paragraphs, and `field_related_platforms` / `field_related_services` links for canonical solutions.

## 5. Case Studies

Type defined, no copy drafted. Real client engagements only (HHS/CMS via Scope Infotec is the principal candidate per project memory — verify what's shareable under contract).

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/case-studies` | dynamic-index | todo | Jeremy | Listing route |
| `/case-studies/{slug}` | case_study | todo | Jeremy | Follow Challenge → Solution → Results → Metrics structure (CONTENT_TYPES_GUIDE.md §4) |

## 6. Resources (gated / downloadable)

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/resources` | dynamic-index | todo | Jeremy | Listing with filters by `resource_type` |
| `/resources/{slug}` | resource | todo | Jeremy | Whitepapers, eBooks, checklists; gated form mechanism TBD |

## 7. Articles & Press

`/articles` is the only listing route currently wired in Next.js (`app/(app)/articles/page.tsx` — fetches first 10 via GraphQL). Article detail pages render via the catch-all and are the only working node-render path for non-landing content.

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/articles` | dynamic-index | **wired** | Jeremy | Lists 10 most recent articles |
| `/articles/{slug}` | article | **wired** | Jeremy | Catch-all already handles `NodeArticle` |
| `/press` | dynamic-index | todo | Jeremy | Filtered view of Articles where `field_news_category = "Press Release"` |
| (article content) | article | todo | Jeremy | No actual articles authored yet |

## 8. Events

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/events` | dynamic-index | todo | Jeremy | Default filter: upcoming first |
| `/events/{slug}` | event | todo | Jeremy | Speaker engagements / webinars; honor `field_event_date` timezone |

## 9. Careers

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/careers` | dynamic-index | todo | Jeremy | Job board listing |
| `/careers/{slug}` | career | todo | Jeremy | Department / seniority / location filters; `field_apply_url` for external ATS |

WL is not actively hiring (per memory: business-continuity-only access patterns), so this can be deferred or render an empty-state until needed.

## 10. Team / People

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/team` | dynamic-index | todo | Jeremy | Filter by `field_show_in_directory = true` |
| `/team/{slug}` | person | todo | Jeremy | Jeremy's own bio is the first to draft |

## 11. Legal & Compliance

All `basic_page`. Sensitive — Jeremy should review/draft personally given defense/government audience expectations.

| URL | Content type | Status | Owner | Notes |
|---|---|---|---|---|
| `/legal/privacy-policy` | basic_page | todo | Jeremy | Required pre-launch; coordinate with brand-voice scope |
| `/legal/terms-of-service` | basic_page | todo | Jeremy | Required pre-launch |
| `/legal/cookie-policy` | basic_page | todo | Jeremy | Required pre-launch if any analytics ship |
| `/legal/accessibility-statement` | basic_page | todo | Jeremy | WCAG 2.1 AA commitment statement |

## 12. System routes (Next.js, no Drupal node)

| URL | Type | Status | Notes |
|---|---|---|---|
| `/404` | nextjs | **wired** | `app/not-found.tsx` |
| `/_error` | nextjs | **wired** | `app/error.tsx` |
| `/sitemap.xml` | nextjs | todo | Generate from JSON:API/GraphQL node enumeration |
| `/robots.txt` | nextjs | todo | Public site rules + noindex for `field_noindex = true` nodes |
| `/api/contact` | nextjs | **wired** | Contact form submission proxy |
| `/api/revalidate` | nextjs | **wired** | Drupal → Next on-demand ISR |
| `/api/draft` · `/api/disable-draft` | nextjs | **wired** | Preview mode |
| `/api/status` | nextjs | **wired** | Healthcheck |

---

## Content type ↔ status rollup

| Content type | Total pages planned | drafted | seeded (prod) | wired (frontend) | Notes |
|---|---:|---:|---:|---:|---|
| platform | 7 | 7 (6 updated + 1 new) | 6 | partial | Machine name renamed from `product` to `platform` (F1-B, 2026-05-27); Coquina is new — needs creation |
| service | 10 | 10 | 10 | partial | Seeded on prod + DDEV |
| solution | 8 canonical + 3 legacy | 8 stubs in CONTENT.md | 3 legacy | none | 8 canonical solutions locked 2026-05-27; 3 legacy nodes (nid 21/22/23) pending disposition |
| article | open | many | some | listing+detail | Ongoing |
| case_study | TBD | 0 | 0 | none | Real client work only |
| basic_page | ~5 (About, 4 legal) | 0 | 0 | detail wired | 5 |
| landing_page | 1 (Homepage) | 1 | 1 | 1 | 0 |
| case_study | TBD | 0 | 0 | 0 | TBD |
| event | n (open-ended) | 0 | 0 | 0 | n |
| career | n (deferred) | 0 | 0 | 0 | n |
| person | ~team size | 0 | 0 | 0 | n |
| resource | TBD | 0 | 0 | 0 | TBD |

---

## Next.js route mapping

What's actually wired in `ui/app/` today:

| Route | File | Handles |
|---|---|---|
| `/` | `app/(marketing)/page.tsx` | `NodeLandingPage` at path `/homepage` + hardcoded fallback paragraphs |
| `/contact` | `app/(marketing)/contact/page.tsx` | Contact form (Next-rendered, posts to `/api/contact`) |
| `/articles` | `app/(app)/articles/page.tsx` | Article listing via GraphQL `nodeArticles(first: 10)` |
| `/{...any-slug}` | `app/(app)/[...slug]/page.tsx` | GraphQL `route(path:$path)` → renders `NodeArticle` or `NodePage` only |

**Implication:** Of the 11 content types in config, only `article`, `basic_page` (NodePage), and the homepage `landing_page` have working frontend renderers. Before Product / Service / Solution / Case Study / Resource / Event / Career / Person pages can go live, the `[...slug]/page.tsx` switch needs new branches and matching components in `ui/components/drupal/`, plus GraphQL fields exposed for each typename.

This is a coordinated webcms ↔ ui change — track it as a launch dependency.

---

## §4 resolution — `solution` content type drift

**Decision: keep the `solution` content type. Document it. Do not retire.**

**Evidence:**

- `config/sync/node.type.solution.yml` exists with a clear, intentional description: *"Branded, deployable solution packages bridging Products and Services. Similar to GDIT Digital Accelerators or Palantir Offerings."*
- 33 fields are attached, including the same Mission-Impact-first set used by Products and Services (`field_mission_impact`, `field_key_capabilities`, `field_outcomes`, `field_primary_cta`, `field_target_sectors`, `field_industries`, `field_personas`, etc.) plus differentiators like `field_outcomes` (Outcome paragraphs) and `field_primary_capability`.
- `solution` is included in `workflows.workflow.editorial.yml` alongside the other ten node types.
- Form display, view display, and base-field overrides are all present in config — this is a fully implemented type, not a stub.
- A distinct **`solutions` taxonomy** also exists and is referenced by every other content type via `field_solutions`. The two are complementary: the **content type** holds the branded package; the **taxonomy** tags supporting content with the solution it relates to.

**Why it's not a duplicate of Product or Service:**

- Platform = a self-deployable technology platform we license/install (the things — Sabal, Keel, Alidade, Squawk, Manifest, Lighthouse, Coquina).
- Service = a consulting/managed engagement (the doing — implementation, advisory, ops).
- Solution = a packaged, branded combination of one or more Platforms + Services applied to a specific outcome or sector (the offering — e.g., DotGov bundling Keel + Alidade + Squawk for federal .gov modernization).

**Required follow-up doc work** (not in this inventory's scope — flag for next pass):

1. Add a §11 entry to `docs/CONTENT_TYPES_GUIDE.md` describing the Solution content type and when to use it vs. Product/Service.
2. Update `docs/CONTENT_TYPES_GUIDE.md` Overview table to read "11 content types" (currently says 10).
3. Define the first 2–3 Solution packages (naming + which Products/Services they bundle) before drafting copy.

---

## Gaps & follow-ups (punch-list)

**Documentation gaps**

1. `CONTENT_TYPES_GUIDE.md` doesn't document the `solution` content type (see §4 above).
2. No brand voice / style guide doc yet (separate workstream in flight).
3. CONTENT.md only covers Products + Services — no drafts for About, Legal, Case Studies, Resources, team bios.

**Seeding / config gaps**

4. Taxonomy term population unverified — `target_sectors`, `personas`, `industries`, `compliance` vocabularies are defined but term counts not confirmed. Run `ddev drush ev "print_r(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'target_sectors']));"` (or similar) before authoring.
5. No seed scripts exist for any non-homepage node — Products/Services will be authored either via admin UI or via a new `scripts/seed_products.php` + `scripts/seed_services.php`. Decision needed.
6. Capability paragraphs need to be authored alongside each Product/Service — CONTENT.md has them as Markdown bullets and needs restructuring.

**Frontend wiring gaps**

7. `app/(app)/[...slug]/page.tsx` only resolves `NodeArticle` and `NodePage`. Needs branches for `NodePlatform`, `NodeService`, `NodeSolution`, `NodeCaseStudy`, `NodeResource`, `NodeEvent`, `NodeCareer`, `NodePerson`.
8. No index routes exist for `/platforms`, `/services`, `/solutions`, `/case-studies`, `/resources`, `/events`, `/careers`, `/team`, `/press`. Each needs a Next.js page + a GraphQL collection query.
9. No global navigation component published — header/footer link structure should follow this sitemap.
10. `/sitemap.xml` and `/robots.txt` generators not implemented.

**Other**

11. Search results page — Alidade is one of the Platforms but no on-site search UX is planned in this inventory. Decide whether to ship a `/search` route at launch.
12. i18n: site is multilingual (EN/ES/RU per CLAUDE.md) — every URL in this inventory needs a language-prefix plan (`/es/...`, `/ru/...`) before launch. Out of scope here; flag for IA v2.

---

## Working-doc protocol

Update this file when:
- A page moves between status states (todo → drafted → seeded → wired).
- A new page is added to the plan.
- A page is killed off (mark it explicitly rather than deleting — useful for audit).

Keep the rollup table in sync. When the Solution packages are defined, replace `TBD` rows with named entries.
