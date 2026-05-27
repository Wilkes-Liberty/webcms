# Wilkes & Liberty — Portfolio Audit (Working Document)

**Purpose:** Pre-naming audit. Map Jeremy's new service/platform/solution ideas against the existing catalog. Surface overlaps, gaps, and the Products-vs-Solutions architectural question. All decisions made here flow into NAMING_DECISIONS.md.

**Status:** Portfolio audit complete 2026-05-27. All decisions resolved. Final services catalog: 16 services. See Section I.6 for locked list.
**Last updated:** 2026-05-27

---

## Section A — Architectural Question: Products vs. Solutions

**The question Jeremy raised:** Should "Products" and "Solutions" be the same content type? Or do we keep them separate?

### Current three-tier model

```
Platforms  = standalone deployable platforms (Keel CMS, Squawk Identity, Manifest Data, etc.)
             — the thing; a piece of technology WL ships/deploys
Services   = consulting and managed engagements
             — the doing; a human-delivered capability
Solutions  = branded packages combining 1+ Products + 1+ Services applied to a specific
             audience or outcome
             — the offering; what a buyer actually procures
```

This architecture already works in the sitemap: Solutions are the commercial entry point; Products and Services are the proof layer behind them. A buyer landing on "Inspector General Platforms" sees the solution pitch; clicking through lands on the Keel CMS platform page for the underlying technical depth.

### The case for collapsing Products → Solutions

- Simpler IA: one content type instead of two for "things WL builds"
- Federal buyers think in "solutions" language — agencies buy solutions to problems
- Fewer Drupal content types, simpler Next.js routing
- Less content maintenance burden (no need to maintain both a Product page and a Solution page that say similar things)

### The case for keeping them separate

- **SEO lift:** "Keel CMS" and "Squawk identity platform" capture different search intent than solution-level pages. Product pages rank for product-category keywords; solution pages rank for audience+outcome keywords.
- **Modularity:** Multiple Solutions can reference the same Platform (e.g., Keel CMS appears in Higher Ed, Nonprofit, Federal Civilian, and IG Platform solutions). Keeping Platforms separate avoids duplicating that content.
- **Federal proof layer:** Contracting officers want to understand the underlying capability, not just the packaged offering. A Product page gives them the technical depth; the Solution page gives them the outcome framing.
- **Vendor credibility:** Having distinct Products signals that WL builds reusable platforms — not just custom consulting engagements. This is the difference between a product company and a body shop.

### A third path: Rename "Products" → "Platforms"

"Products" reads commercial-SaaS. "Platforms" is more federal-native (Platform One, Content Management Platform, Identity Platform are all phrases contracting officers recognize). This preserves the two-tier model but shifts the register.

**My recommendation:**

**Keep the separation. Rename "Products" to "Platforms."**

Rationale: The separation does real work (SEO, modularity, proof layer). Collapsing it means Solutions pages have to carry both the outcome pitch and the technical depth — they will either get bloated or the technical depth gets lost. Renaming to "Platforms" costs nothing and aligns better with the federal register.

**If you do collapse to Solutions only:**
The Drupal changes required are:
- Retire the `product` content type (or merge fields into `solution`)
- Update `config/sync/node.type.product.yml` (or remove)
- Update all `field_related_products` references in `solution`, `service`, `case_study` nodes
- Update Next.js `[...slug]/page.tsx` to remove `NodeProduct` branch (or map it to `NodeSolution`)
- Update all `/products/...` URL slugs → 301 redirect to `/platforms/...` (if renaming) or `/solutions/...` (if collapsing)
- Update `SITEMAP_AND_NAVIGATION.md`, `PAGE_INVENTORY.md`, `CONTENT.md`, and `BRAND_VOICE.md` references

**`[DECIDE-A1]`** Keep Products separate (recommend rename to "Platforms") — OR — collapse Products into Solutions?

---

## Section B — Existing Services Catalog vs. Jeremy's New Items

### B.1 Mapping table

| Jeremy's item | Existing equivalent | Assessment | Recommendation |
|---|---|---|---|
| Artificial Intelligence | Service 5: AI Integration & Machine Learning Services | Partial match — existing is narrower ("integration"), Jeremy's vision is broader (organizational transformation, optimization, modern workflows) | **Expand and rename.** Keep as a service but widen scope. See note below. |
| CloudOps / DevOps / DevSecOps | Service 1: Private Infrastructure Engineering & Managed Operations | Significant overlap — "managed operations" covers some of this, but DevOps/DevSecOps pipeline work is not explicitly named | **Restructure.** Service 1 needs splitting or renaming. Combined vs. separate: see note below. |
| Software Development | Service 7: Custom Software Development & Middleware Engineering | Direct match but Jeremy signals the current scope is too narrow ("big one") | **Expand.** Drop "Middleware Engineering" from the name or fold it under a broader Software Development service. |
| Human Centered Design | *None* | Entirely new — no existing service covers design, UX, or CX | **Add new service.** |
| Digital Modernization | Service 6: Digital Modernization & Legacy Systems Migration | Near match | **Keep and tighten name.** "Legacy Systems Migration" is a subset; rename to just "Digital Modernization" or "Digital Transformation." |
| Infrastructure as Code (IaC) | Platform 1: Sabal Infrastructure Platform + Service 1 | Partial — the Platform is IaC-based; the Service delivers it. Jeremy wants a branded, reusable IaC *product/solution* built from the infra repo. | **Product/Platform track, not a service.** Flag for product naming session. As a service: IaC design and delivery is part of CloudOps/DevSecOps above. |
| Content Management / Content Architecture | Platform 2: Keel CMS Platform + Service 2: Headless CMS Implementation | Near match | **Align and broaden.** Current service is Drupal/headless-specific; "Content Architecture" implies a broader strategic service (content modeling, taxonomy, governance) that doesn't require a specific platform. |
| Zero Trust Architecture | Platform 4: Squawk Zero-Trust Identity Platform + Service 4: Zero-Trust Identity & Security Consulting | Partial match — existing service is identity-scoped; Jeremy's framing is full Zero Trust Architecture (network, workload, data, not just identity) | **Expand scope.** Rename to "Zero Trust Architecture" as the service. Identity (Squawk/equivalent) remains a platform. |
| Full Spectrum Cybersecurity | *None explicitly* | Security is embedded in multiple existing services (infrastructure, zero-trust, defense tech) but no standalone cyber service exists | **Add new service.** Distinct from Zero Trust Architecture — ZTA is a framework/posture; Full Spectrum Cyber covers offensive, defensive, compliance, incident response. |
| Emerging Technologies | Service 9: Defense Technology Integration (Aviation & Drone Systems) + Service 8: Cryptocurrency & Digital Asset Solutions | Partial — existing services cover *specific* emerging tech categories; Jeremy's framing is a broader advisory/integration service for what's next | **Restructure.** See note on Services 8 and 9 below. |
| Enterprise Search | Platform 3: Alidade Search Platform + Service 3: Enterprise Search Architecture & Optimization | Direct match | **Keep.** Both exist. Service and Platform both stay. |
| Internationalization | *None* | Entirely new | **Add new service.** Aligns with the site's EN/ES/RU multilingual posture and the `multilingual support` capability in the Keel CMS platform. |
| Software Factory | *None* | Entirely new — Jeremy flags this as a primary *solution* | **Solution/Platform track.** Not a service. See Section C. |
| Cyber Intelligence | Service 10: Intelligence & Actionable Insights Services | Adjacent but narrower. Current service is general analytics/intelligence fusion; Cyber Intelligence specifically means threat intelligence, cyber threat feeds, OSINT, adversary attribution. | **Either expand Service 10 to cover both, or split into two services.** See note below. |

---

### B.2 Notes on specific decisions

#### AI / Artificial Intelligence

The current "AI Integration & Machine Learning Services" positions WL as an integrator — plugging AI into existing systems. Jeremy's vision is broader: helping organizations transform workflows and infrastructure for the AI era. This is both a service (AI strategy, governance, implementation) and a product play (Sovereign AI — the seeded "Sovereign AI Command Fabric" solution covers the sovereign AI deployment angle).

Recommended service name direction: "AI Strategy & Integration" or "AI Enablement" — broader than just integration, stops short of overselling "transformation" which is §5.2-adjacent. The product/platform play (on-prem LLM deployment, RAG pipelines) stays in the Platform/Solution tier.

#### CloudOps / DevOps / DevSecOps — combined or separate?

These three are related but have distinct buyer conversations:

- **DevOps** — developer productivity, CI/CD, release engineering. Buyer: engineering manager, CTO.
- **DevSecOps** — security baked into the delivery pipeline. Buyer: CISO, security architect, federal compliance lead.
- **CloudOps** — day-2 cloud operations: cost optimization, reliability, scaling, platform engineering. Buyer: infrastructure lead, cloud architect.

Arguments for **one combined service:** WL is a small, high-leverage firm — three separate service pages may overstate headcount depth. A single "Platform Engineering & DevSecOps" or "CloudOps & DevSecOps" service is honest and still covers the space.

Arguments for **three separate services:** SEO clarity, proposal alignment (federal RFPs often ask for specific capabilities), and the ability to cross-reference them against different Products/Solutions.

**`[DECIDE-B1]`** Combined ("Platform Engineering & DevSecOps") or separate (CloudOps / DevOps / DevSecOps as distinct offerings)?

#### Defense Technology Integration + Emerging Technologies

Service 9 (Defense Technology Integration — Aviation & Drone Systems) is very specific. Jeremy's "Emerging Technologies" is broader and could encompass drones, autonomous systems, blockchain, quantum-adjacent, edge computing, etc. The current crypto/digital asset service (Service 8) is also adjacent.

Options:
1. **Consolidate into "Emerging Technologies"** — absorb Service 8 (crypto/digital assets) and Service 9 (defense tech integration) into one broader advisory service. Risk: loses the defense-specific signal and the crypto-specific expertise signal.
2. **Keep Service 9 as "Defense Technology Integration"** and add "Emerging Technologies" as a separate, broader advisory service. Service 8 (crypto) folds into Emerging Technologies.
3. **Retire Service 8** (crypto/digital assets) if it's not a core offering — it's the most unusual item in the current catalog and may not reflect where WL is going.

**`[DECIDE-B2]`** What is the future of crypto/digital assets as a WL service? Retire, keep standalone, or fold into Emerging Technologies?

**`[DECIDE-B3]`** Keep Defense Technology Integration as a named service, or fold into Emerging Technologies?

#### Intelligence & Actionable Insights vs. Cyber Intelligence

"Intelligence & Actionable Insights Services" is the current name (Service 10). It's a broad framing — multi-source data fusion, analytics, decision support. "Cyber Intelligence" is specific — threat intelligence, OSINT, adversary TTPs, cyber threat feeds.

Options:
1. **Rename Service 10 to "Intelligence & Cyber Intelligence Services"** — acknowledges both the broader analytics/fusion work and the cyber-specific thread.
2. **Keep Service 10 as general intelligence/analytics** and add **Cyber Intelligence as a distinct service** — particularly relevant if WL pursues defense and IC-adjacent work.
3. **Fold Cyber Intelligence under Full Spectrum Cybersecurity** — treat threat intelligence as a sub-capability of the broader cyber service rather than a standalone offering.

**`[DECIDE-B4]`** Intelligence: one service covering both general analytics and cyber intelligence, or two separate services?

---

### B.3 Recommended revised services list (pending decisions above)

This is the consolidated list after removing duplicates and applying the recommendations. Items marked `[NEW]` are net additions. Items marked `[RESTRUCTURED]` have changed scope from the current catalog. Items marked `[REMOVED]` are proposed retirements from the current list.

| # | Service name (working title) | Status | Notes |
|---|---|---|---|
| 1 | Platform Engineering & DevSecOps | [RESTRUCTURED] | Combines / restructures Service 1 (Private Infrastructure Engineering) + CloudOps/DevOps/DevSecOps. `[DECIDE-B1]` |
| 2 | Software Development | [RESTRUCTURED] | Expands Service 7 (Custom Software Development & Middleware Engineering). Name tightened. |
| 3 | Human Centered Design | [NEW] | Design, UX, CX arm. No existing equivalent. |
| 4 | Digital Modernization | [RESTRUCTURED] | Trims Service 6 (Digital Modernization & Legacy Systems Migration). |
| 5 | AI Strategy & Integration | [RESTRUCTURED] | Expands Service 5 (AI Integration & Machine Learning Services). |
| 6 | Content Management & Architecture | [RESTRUCTURED] | Expands Service 2 (Headless CMS Implementation) to include content architecture/strategy work. |
| 7 | Zero Trust Architecture | [RESTRUCTURED] | Expands Service 4 (Zero-Trust Identity & Security Consulting) to full ZTA scope. |
| 8 | Full Spectrum Cybersecurity | [NEW] | No existing equivalent. |
| 9 | Enterprise Search Architecture | [KEEP] | Service 3, minor name trim. |
| 10 | Internationalization | [NEW] | No existing equivalent. |
| 11 | Emerging Technologies | [NEW/RESTRUCTURED] | Absorbs or replaces Services 8 and/or 9 depending on `[DECIDE-B2]` and `[DECIDE-B3]`. |
| 12 | Intelligence & Cyber Intelligence | [RESTRUCTURED] | Expands/renames Service 10. Depends on `[DECIDE-B4]`. |
| — | Cryptocurrency & Digital Asset Solutions | [CANDIDATE FOR REMOVAL] | Service 8. Depends on `[DECIDE-B2]`. |
| — | Defense Technology Integration | [CANDIDATE FOR REMOVAL / MERGE] | Service 9. Depends on `[DECIDE-B3]`. |

---

## Section C — Products/Platforms: Existing Catalog + New Items

The existing six products remain. The only net-new product/platform idea from Jeremy's list is the branded IaC solution built from the infra repo. Everything else maps to an existing product.

| # | Platform name (working title) | Status | Jeremy's note |
|---|---|---|---|
| 1 | Sabal Infrastructure Platform | **LOCKED** | Proper name: Sabal |
| 2 | Keel CMS Platform | **LOCKED** | Proper name: Keel |
| 3 | Alidade Search Platform | **LOCKED** | Proper name: Alidade |
| 4 | Squawk Zero-Trust Identity Platform | **LOCKED** | Proper name: Squawk (replaces Fortis) |
| 5 | Manifest Data Platform | **LOCKED** | Proper name: Manifest (replaces Apex) |
| 6 | Lighthouse Observability Platform | **LOCKED** | Proper name: Lighthouse (replaces Vigilance) |
| 7 | Coquina Software Factory Platform | **LOCKED** | Proper name: Coquina (new) |

---

## Section D — Solutions: Existing + New

The "Software Factory" is Jeremy's primary new solution. Everything else maps to existing solution proposals.

| # | Solution (working title) | Status | Notes |
|---|---|---|---|
| 1 | Software Factory | [NEW — PRIMARY] | Jeremy flags as a primary solution. Needs full expansion. See below. |
| 2 | Higher Education Modernization | [EXISTING PROPOSAL] | Audience: higher-ed web teams |
| 3 | Nonprofit & Civic Platforms | [EXISTING PROPOSAL] | Audience: nonprofit EDs, CTOs |
| 4 | Privacy-Conscious B2B SaaS Platforms | [EXISTING PROPOSAL] | Audience: B2B SaaS CTOs, heads of platform |
| 5 | Sovereign Infrastructure — Regulated Industries | [EXISTING PROPOSAL] | Audience: CISO, head of compliance |
| 6 | Federal Civilian Modernization | [EXISTING PROPOSAL] | Audience: federal civilian agencies |
| 7 | Inspector General Platforms | [EXISTING PROPOSAL] | Audience: federal OIG offices. Anchored on Jeremy's OIG Drupal distribution + pandemicoversight.gov lineage. |
| 8 | Defense Technology Modernization | [EXISTING PROPOSAL] | Audience: defense contractors, DoD-adjacent |
| — | Drupal Agency Partner Program | [REMOVE] | Placeholder — Jeremy confirmed remove |
| — | Sovereign Mission Edge | [SEEDED — PLACEHOLDER] | nid 21; URL locked. Review after services/products settled. |
| — | Sovereign AI Command Fabric | [SEEDED — PLACEHOLDER] | nid 22; URL locked. Review after services/products settled. |
| — | Sovereign Digital Modernization Platform | [SEEDED — PLACEHOLDER] | nid 23; URL locked. Review after services/products settled. |

### Software Factory — expansion note

In federal technology, a "Software Factory" is a recognized pattern — a fully automated, security-integrated delivery environment where software is conceived, built, tested, secured, and deployed continuously. The Air Force's Kessel Run, DoD's Platform One, and various agency-level DevSecOps factories have established this vocabulary. WL's version would be:

- A branded, sovereign Software Factory solution — self-hostable, air-gap capable
- Built on: WL's Sabal Infrastructure Platform + DevSecOps pipelines + Keel CMS Platform (for documentation/content layers) + Squawk Identity (for access controls) + Lighthouse Observability Platform
- Applicable to: defense contractors building internal software delivery, federal civilian agencies modernizing legacy delivery, and sophisticated private-sector orgs (regulated industries, large nonprofits)
- Differentiator: sovereign — no SaaS CI/CD dependency, no hyperscaler lock-in, deployable inside a customer's authorization boundary
- Named product: needs a proper name. Strong candidates for the name register: foundational/industrial (Foundry, Mill, Forge) — "Forge" and "Foundry" are the obvious anchors here, both connoting structured manufacturing of durable things

**`[DECIDE-D1]`** Confirm Software Factory as a primary solution. Should it be positioned as a Solution (audience-packaged offering) or a Platform (deployable product)? Or both — a Platform that is deployed as part of a Solution?

---

## Section E — Decisions Log

| ID | Decision | Answer | Notes |
|---|---|---|---|
| `DECIDE-A1` | Products vs. Solutions: keep separate or collapse? | **Keep separate. Rename "Products" → "Platforms" everywhere — code, config, DB, docs.** | Machine name question: see Section F. |
| `DECIDE-B1` | CloudOps / DevOps / DevSecOps: combined or separate? | **Split into two services: (1) DevOps & DevSecOps combined; (2) Cloud Operations separate.** | |
| `DECIDE-B2` | Crypto/Digital Assets: retire, keep, or fold? | **Fold into Emerging Technologies.** | |
| `DECIDE-B3` | Defense Tech Integration: keep or fold? | **Fold into Emerging Technologies.** | |
| `DECIDE-B4` | Intelligence + Cyber Intelligence + Full Spectrum Cybersecurity: separate or combined? | **Fold Cyber Intelligence under Full Spectrum Cybersecurity. Open question: fold Intelligence & Actionable Insights in too?** | See Section F, question F2. |
| `DECIDE-D1` | Software Factory: Solution, Platform, or both? | **Both.** | Toolchain = Platform; audience-packaged offering = Solution built on that Platform. |

---

## Section F — Two Open Questions (Awaiting Jeremy)

### F1 — Drupal machine name for the Platforms rename

Renaming "Products" → "Platforms" everywhere requires a decision on the Drupal content type machine name. Two paths:

**Option F1-A: Change the label only** (machine name stays `product`)
- Drupal admin UI shows "Platform" everywhere
- Field configs (`field.field.node.product.*`), GraphQL typenames (`NodeProduct`), and existing path patterns all stay the same internally
- Docs and copy say "Platform"; code/config still says `product` internally
- Minimal code change — mostly doc/copy updates
- Downside: inconsistency between public language and codebase; future engineers see `NodeProduct` in GraphQL and are confused

**Option F1-B: Change the machine name to `platform`**
- Complete consistency: `node.type.platform`, `NodePlatform` in GraphQL, `/platforms/` URL prefix, `field_related_platforms` on other content types
- This is the right long-term call while nodes are still in `drafted`/`seeded` status and no URLs are publicly indexed
- Requires: content type migration, field config rename sweep, GraphQL query updates in Next.js, URL redirect setup, all cross-referencing field renames
- The full technical change list is in Section G below

**My recommendation: F1-B (rename machine name to `platform`)** — now is the right window. Nothing is publicly indexed; no 301 redirect debt yet. Once pages go live, this change gets significantly more expensive.

**Decision: F1-B — change machine name to `platform`.** Pre-launch window confirmed; do it freely everywhere. 2026-05-27.

---

### F2 — Intelligence & Actionable Insights: fold into Full Spectrum Cybersecurity or keep separate?

Jeremy asked: "should we just combine all of them and then fold under full spectrum?"

The "all of them" = Intelligence & Actionable Insights (current Service 10) + Cyber Intelligence + Full Spectrum Cybersecurity.

**The case for combining (one "Full Spectrum Cybersecurity & Intelligence" service):**
- Simpler service catalog — fewer pages to maintain
- For a small firm, three closely related services can look like padding
- "Full Spectrum" already implies completeness — intelligence is part of the full spectrum

**The case for keeping them separate:**
- The buyer personas are different: a CISO buys cybersecurity; an intelligence analyst or program manager buys intelligence analytics and decision support
- Jeremy's strongest past performance (OIG Drupal distribution, pandemicoversight.gov) is specifically in the *oversight and public intelligence* space — this is a distinct credential that deserves its own surface
- In federal procurement, "intelligence services" and "cyber services" are different contract types and often different funding streams
- "Full Spectrum Cybersecurity" has military/federal resonance that could get diluted if it also carries "analytics and decision support"

**My recommendation: Keep two services** — Full Spectrum Cybersecurity (security posture, ZTA, incident response, threat defense) and Intelligence & Cyber Intelligence (data fusion, decision support, OSINT, threat intelligence). Cyber Intelligence lives under the intelligence service, not the cybersecurity service — because "cyber threat intelligence" is an *intelligence* function, not a security-engineering function.

**Decision: Keep as two separate services.** Intelligence & Cyber Intelligence is a standalone service. 2026-05-27.

---

## Section G — Technical Change Plan: Products → Platforms Rename

*This section documents all the places that need updating. Actual changes happen in a dedicated engineering task AFTER the naming session.*

### G.1 Drupal configuration (if machine name changes — F1-B)

| File / config key | Change required |
|---|---|
| `config/sync/node.type.product.yml` | Rename file to `node.type.platform.yml`; update `id: product` → `id: platform`; update label |
| `config/sync/field.field.node.product.*.yml` | Rename all to `field.field.node.platform.*.yml`; update `entity_type: node`, `bundle: product` → `bundle: platform` in each |
| `config/sync/core.entity_view_display.node.product.*.yml` | Rename and update bundle reference |
| `config/sync/core.entity_form_display.node.product.*.yml` | Rename and update bundle reference |
| `config/sync/node.field_storage.*` that reference `product` bundle | Update bundle references |
| Views referencing `node.type = product` | Update filter condition |
| `workflows.workflow.editorial.yml` | Update node type reference from `product` to `platform` |
| Drupal path alias pattern | Update auto-alias from `/products/[node:title]` to `/platforms/[node:title]` |
| Cross-reference fields on `service`, `solution`, `case_study` nodes | Rename `field_related_products` → `field_related_platforms` (field storage + field instance) |

### G.2 Next.js / frontend (if machine name changes — F1-B)

| File | Change required |
|---|---|
| `ui/app/(app)/[...slug]/page.tsx` | Add `NodePlatform` branch (currently no product renderer exists — but typename changes) |
| Any GraphQL fragments or queries referencing `NodeProduct` | Update to `NodePlatform` |
| Any hardcoded `/products/` path strings | Update to `/platforms/` |
| Navigation component (once built) | Update menu paths |

### G.3 URL redirects

All existing seeded product nodes have paths under `/products/`. Once renamed:
- `/products/sovereign-infrastructure-platform` → 301 → `/platforms/sabal`
- `/products/liberty-headless-cms` → 301 → `/platforms/keel`
- `/products/enterprise-search` → 301 → `/platforms/alidade`
- `/products/fortis-identity` → 301 → `/platforms/squawk`
- `/products/apex-data` → 301 → `/platforms/manifest`
- `/products/vigilance-observability` → 301 → `/platforms/lighthouse`

*Note: None of these are publicly indexed yet, so redirect debt is minimal — confirms this is the right window.*

### G.4 Drupal database (existing seeded nodes)

Seeded product nodes in the database (nids confirmed seeded per PAGE_INVENTORY.md) need their `type` value updated from `product` to `platform` in the `node` and `node_field_data` tables. Best handled via a Drush update script or a PHP migration, not manual DB edits.

### G.5 Seed scripts

| Script | Change required |
|---|---|
| `scripts/seed_products_services.php` (or equivalent) | Update `node.type = 'product'` → `'platform'` references |
| Any fixture/migration YAML referencing the product content type | Update bundle references |

### G.6 Documentation files (Deliverable 6 of the naming session — deferred)

| File | Scope of change |
|---|---|
| `webcms/docs/CONTENT.md` | All "Product" section headings → "Platform"; all product page copy that names the content type |
| `webcms/docs/PAGE_INVENTORY.md` | URL slugs `/products/` → `/platforms/`; content type column `product` → `platform`; rollup table |
| `webcms/docs/SITEMAP_AND_NAVIGATION.md` | Sitemap tree, nav menu structure, footer menu, breadcrumb patterns, slug conventions table |
| `webcms/docs/BRAND_VOICE.md` | §6.4 capitalization examples; §6.15 product naming convention (retitle to "Platform naming convention") |
| `webcms/docs/PORTFOLIO_AUDIT.md` | This document — update all "product/Product" references once decision is confirmed |
| `business/strategy/FEDERAL_CONTRACTING_READINESS.md` | §6 FedRAMP references to product names (Liberty, Fortis, Apex, Vigilance) |
| Any `AGENTS.md`, `CLAUDE.md`, `README.md` files referencing the product content type | Sweep and update |

---

## Section H — Emerging Services List (post-decisions, two open items pending)

This is the services list as it stands with all confirmed decisions applied. Two rows are conditional on F2.

| # | Service (working title) | Basis | Status |
|---|---|---|---|
| 1 | Cloud Operations | From Service 1 (Private Infrastructure Engineering) — CloudOps split | Restructured |
| 2 | DevOps & DevSecOps | From Service 1 — DevOps/DevSecOps split | Restructured |
| 3 | Software Development | From Service 7 (Custom Software Dev & Middleware Engineering) — scope expanded | Restructured |
| 4 | Human Centered Design | New | New |
| 5 | Digital Modernization | From Service 6 — name tightened | Restructured |
| 6 | AI Strategy & Integration | From Service 5 — scope expanded | Restructured |
| 7 | Content Management & Architecture | From Service 2 — scope expanded beyond just Headless CMS Implementation | Restructured |
| 8 | Zero Trust Architecture | From Service 4 — scope expanded from identity-only to full ZTA | Restructured |
| 9 | Full Spectrum Cybersecurity *(+ Cyber Intelligence)* | New; absorbs Cyber Intelligence | New |
| 10 | Enterprise Search Architecture | From Service 3 — minor name trim | Keep |
| 11 | Internationalization | New | New |
| 12 | Emerging Technologies | New; absorbs Service 8 (Crypto/Digital Assets) + Service 9 (Defense Tech Integration) | New/Restructured |
| 13 *(conditional on F2)* | Intelligence & Cyber Intelligence | From Service 10 — expanded; if kept separate from Full Spectrum Cyber | Conditional |

**If F2 = combine:** 12 services total (row 13 removed; intelligence folds into row 9 as "Full Spectrum Cybersecurity & Intelligence").
**If F2 = keep separate:** 13 services total.

### Services retired from the current catalog

| Retired service | Absorbed into |
|---|---|
| Private Infrastructure Engineering & Managed Operations (Service 1) | Cloud Operations + DevOps & DevSecOps |
| Headless CMS Implementation (Service 2) | Content Management & Architecture |
| Zero-Trust Identity & Security Consulting (Service 4) | Zero Trust Architecture |
| AI Integration & Machine Learning Services (Service 5) | AI Strategy & Integration |
| Digital Modernization & Legacy Systems Migration (Service 6) | Digital Modernization |
| Custom Software Development & Middleware Engineering (Service 7) | Software Development |
| Cryptocurrency & Digital Asset Solutions (Service 8) | Emerging Technologies |
| Defense Technology Integration — Aviation & Drone Systems (Service 9) | Emerging Technologies |
| Intelligence & Actionable Insights Services (Service 10) | Intelligence & Cyber Intelligence (if F2 = separate) OR Full Spectrum Cybersecurity (if F2 = combine) |

---

## Section I — Infrastructure Audit: Service and Platform Ideas from `~/Repositories/infra`

**Purpose:** Audit the built infra stack for (a) services WL already practices and can credibly offer, (b) net-new service ideas not yet in the catalog, (c) platform concepts surfaced by the infrastructure, and (d) differentiators for capability statements and federal proposals.

**Source files read:** `infra/README.md`, `infra/CLAUDE.md`, `infra/docs/TAILSCALE_ACL_DESIGN.md`, `infra/docs/SECRETS_MANAGEMENT.md`, `infra/docs/SECURITY_CHECKLIST.md`.

---

### I.1 — Technologies in the infra stack, mapped to the locked services catalog

This confirms that the stated service offerings are backed by real, operating infrastructure. Each entry can be cited in proposals as evidence of hands-on practice.

| Technology / capability in infra | Confirms or strengthens |
|---|---|
| **Ansible** — full `wl-onprem` role, multi-playbook automation (onprem, vps, staging, bootstrap) | Cloud Operations; DevOps & DevSecOps |
| **Terraform** — DNS management for Njalla via `records.tf` | Cloud Operations; DevOps & DevSecOps |
| **Docker Compose** — production + staging stacks, named volumes, env separation | Cloud Operations; DevOps & DevSecOps; Software Development |
| **GitHub Actions CI/CD** — branch model: `development` → staging auto-deploy; `master` → manual production promote | DevOps & DevSecOps |
| **Tailscale mesh VPN** — on-prem + VPS + device endpoints; ACL-enforced least privilege; tag taxonomy | Zero Trust Architecture |
| **Keycloak 26** — OIDC/OAuth2 SSO; per-environment Drupal clients; realm-level group gating; `setup-realm.sh` automation | Zero Trust Architecture |
| **oauth2-proxy (×2)** — shared forward-auth proxy `:4180` + ops-tier proxy `:4181`; layered auth above Tailscale ACL | Zero Trust Architecture |
| **SOPS + AGE encryption** — all secrets in encrypted `*_secrets.yml`; AGE key management; no plaintext in repo | DevSecOps; Full Spectrum Cybersecurity |
| **Caddy** — reverse proxy on both VPS (public TLS) and on-prem (internal TLS); custom binary with `mholt/caddy-ratelimit`; rate limiting on all public endpoints | Cloud Operations; Full Spectrum Cybersecurity |
| **Let's Encrypt TLS automation** — public endpoints; manual internal TLS for on-prem Caddy | Cloud Operations |
| **CoreDNS with Tailscale split DNS** — internal resolution (`*.wl.internal`) routed through CoreDNS; external resolution unchanged | Zero Trust Architecture; Cloud Operations |
| **Prometheus + Grafana + Alertmanager** — 16 alert rules; email + Slack notification channels; cAdvisor, Node Exporter, Postgres Exporter all instrumented | Cloud Operations; *(future Observability Platform)* |
| **Uptime Kuma** — external uptime/status monitoring | Cloud Operations |
| **Apache Solr 9.6** — running in the production Docker Compose stack | Enterprise Search Architecture; *(Alidade Search Platform)* |
| **PostgreSQL 16** — production database; Postgres Exporter instrumented for metrics | Cloud Operations; Software Development |
| **Redis 7** — caching layer; production and staging | Cloud Operations; Software Development |
| **Drupal 11 multilingual (EN/ES/RU)** — live multilingual site wired and running | Internationalization |
| **NIST 800-171 compliance posture** — SSP in progress; controls documented in `business/compliance/`; compliance artifacts retained from HHS/CMS engagement | Full Spectrum Cybersecurity; *(compliance sub-capability — see I.2)* |
| **Backup system with encryption** — automated, Proton Drive sync for off-site | Cloud Operations; *(data sovereignty positioning — see I.3)* |
| **UFW firewall + SSH hardening + fail2ban + security headers** — documented in SECURITY_CHECKLIST.md | Full Spectrum Cybersecurity |
| **CORS configuration on Drupal API** — headless CMS delivering to Next.js with proper CORS control | Software Development; Content Management & Architecture |
| **Synology DSM integration** — OIDC attempt; `known-OIDC-popup-limitation` documented | Cloud Operations |

---

### I.2 — Net-new service ideas surfaced by the infra work

These are ideas **not yet in the 13-service catalog** that the infra stack either demonstrates or strongly implies. Each is presented as a candidate — Jeremy decides whether to add, fold into an existing service, or hold.

---

#### I.2-A — Compliance Engineering (NIST 800-171 / CMMC)

**What the infra shows:** WL has a live, operating infrastructure stack that is being deliberately built to NIST 800-171 posture — SSP in progress, compliance artifacts retained from the HHS/CMS engagement, documented security checklist, SOPS+AGE secrets management, SSH hardening, and role-based access controls. This is not just advisory familiarity; it's implemented.

**The service idea:** A technical compliance engineering service — not "assess and report" consulting, but hands-on implementation and documentation of NIST 800-171 / CMMC Level 1 controls for small businesses and subcontractors who need to demonstrate compliance in federal bids. Deliverables would be a working SSP, a POA&M, and a system configuration that passes audit.

**Why this is distinct from Full Spectrum Cybersecurity:** Cybersecurity is about defense posture. Compliance Engineering is about producing the artifacts — SSPs, POA&Ms, control mappings — that contracting officers and auditors require before award. The buyer is a contracts/business development lead or CEO preparing for federal subcontracting, not a CISO.

**Fit with WL's positioning:** Extremely tight. Returning federal performer preparing small businesses for federal subcontracting is a credible, differentiated niche. Jeremy's own compliance build-out is a live reference implementation.

**`[DECIDE-I1]`** Add "Compliance Engineering" (or similar name) as a 14th service? Or fold into Full Spectrum Cybersecurity as a named sub-capability?

---

#### I.2-B — Managed Identity & Access Management (Self-Hosted IAM)

**What the infra shows:** A fully operational Keycloak 26 deployment — realm configuration, per-environment OIDC clients for Drupal, per-service oauth2-proxy instances, group-based authorization, OIDC integration with Synology DSM (with documented limitations), and Ansible-automated setup via `setup-realm.sh`. This is production IAM that WL runs for itself.

**The service idea:** Design, deploy, and operate self-hosted identity infrastructure for organizations that want OIDC/SSO without SaaS dependency (Okta, Azure AD, Google Identity). Target buyers: privacy-conscious B2B SaaS companies, regulated-sector firms needing on-prem identity, defense contractors with air-gap requirements, organizations rationalizing their identity sprawl after a merger or platform consolidation.

**Why this is distinct from Zero Trust Architecture:** ZTA is the framework and posture — network segmentation, workload identity, data access policies. Self-hosted IAM is a specific deployment and operational service: stand up the Keycloak stack, configure realms and clients, integrate with existing applications, hand off a running system with runbooks.

**`[DECIDE-I2]`** Add "Managed Identity & Access Management" as a 15th service? Or keep it as a sub-capability of Zero Trust Architecture?

---

#### I.2-C — Private / Sovereign Infrastructure Hosting (Managed Operations)

**What the infra shows:** WL operates a full production web stack on self-owned hardware (on-prem macOS server, 13 CPUs, 24 GB RAM) plus a Njalla VPS — deliberately avoiding hyperscaler dependency. All DNS managed via Terraform on Njalla (a privacy-first registrar). Backups go to Proton Drive. The design is intentionally sovereignty-forward at every layer.

**The service idea:** A managed platform operations service for organizations that want their web infrastructure off AWS/GCP/Azure — either for cost reasons (SMBs, nonprofits), sovereignty reasons (defense contractors, classified-adjacent orgs), or privacy reasons (journalists, advocacy organizations, privacy-conscious B2B SaaS). WL would deploy, monitor, and operate a production stack on customer-controlled hardware or a customer-selected VPS, using the same toolchain used internally.

**Why this is distinct from Cloud Operations:** Cloud Operations as listed covers cloud-native operations (AWS, GCP, Azure EKS/ECS, GitLab CI/CD on cloud). This is the complementary private-cloud / bare-metal operations capability. Together they cover the full spectrum.

**Note:** This overlaps with the IaC Platform concept (Section C, item 1). The managed operations service would deploy the IaC Platform on the customer's infrastructure. They are complementary: the Platform is the product, the managed operations service is how WL runs it for customers post-deployment.

**`[DECIDE-I3]`** Add "Sovereign / Private Infrastructure Operations" as a distinct managed-operations service? Or fold into Cloud Operations (which then covers both cloud-native and sovereign-private)?

---

#### I.2-D — Private AI Infrastructure (On-Premises LLM Deployment)

**What the infra shows:** The on-prem server (13 CPUs, 24 GB RAM) is substantial for a small firm — it exceeds minimum specs for running quantized LLMs locally (Ollama, llama.cpp, vLLM). The sovereign/privacy-forward infrastructure posture, SOPS-based secrets management, and air-gap capability are directly applicable to a pattern buyers increasingly want: run the LLM inside their authorization boundary, not against an external API.

**The service idea:** Design and deploy private AI inference infrastructure — on-prem or customer-owned cloud — for organizations that cannot or will not send data to OpenAI/Anthropic APIs. Deliverables: hardware/spec recommendations, LLM runtime deployment (Ollama or equivalent), RAG pipeline setup (vector store, document ingestion), access-controlled inference endpoint, integration with existing applications. The "Sovereign AI Command Fabric" seeded solution (nid 22) already gestures at this.

**Why this matters now:** The demand signal is strong in defense, IC, healthcare, and legal sectors — all of which have data types that cannot leave their boundary. WL's sovereign infrastructure posture is a genuine differentiator here vs. cloud-native AI integrators.

**`[DECIDE-I4]`** Surface "Private AI Infrastructure" as a distinct sub-capability under AI Strategy & Integration? Or is it just a deployment pattern within the existing AI service? (Note: this may also inform whether nid 22 Sovereign AI Command Fabric gets renamed or developed further.)

---

### I.3 — Differentiators for capability statements and federal proposals

These are characteristics of the WL infra stack that should be called out explicitly in proposals and capability statements, because they are not common at the small-business level and directly address federal buyer concerns:

**Sovereignty-forward design at every layer.** DNS via Njalla (privacy-first registrar), backups to Proton Drive, on-prem production hardware, no hyperscaler dependency in the core stack. This is not a cost decision — it is an architectural principle that federal and regulated-sector buyers will recognize and value.

**Layered zero-trust enforcement — not just claimed.** Tailscale ACL (network reach) + Keycloak oauth2-proxy (application auth gate) + device-level local credentials = three independent enforcement layers that WL operates in production. Most small firms claim "zero trust"; WL runs it.

**NIST 800-171 compliance artifacts in hand.** The SSP is being actively built, and WL retained compliance artifacts from the HHS/CMS subcontract. This is a significant differentiator when competing for federal subcontract opportunities against firms that have never operated under flow-down requirements.

**Secrets management discipline from the start.** SOPS+AGE is a professional-grade secrets management approach — the same pattern used in federal environments with Vault or KMS. No plaintext credentials have ever been committed to the repo. This signals security maturity that contracting officers and prime security reviewers look for.

**Multi-environment automation with audit trail.** Branch-based CI/CD (development → staging auto → master manual production), Ansible-managed deployment, Terraform-managed infrastructure state — the stack is reproducible, documented, and auditable. This is explicitly what CMMC and FedRAMP assessors look for.

**Multilingual capability demonstrated in production.** The live stack runs EN/ES/RU Drupal. For any federal RFP with a multilingual requirement (and many have them — federal public-facing sites must often accommodate Title VI language access obligations), WL has a live reference implementation.

---

### I.4 — Platform ideas surfaced by the infra stack

Three of the current six platforms have their technical foundations directly visible in the infra repo. Two additional platform concepts emerge from the infra work.

| Platform idea | Infra evidence | Status in catalog |
|---|---|---|
| Sabal Infrastructure Platform (Platform 1) | Full Ansible + Terraform + Docker Compose stack; `wl-onprem` role; designed to be reproducible and deployable to any on-prem or VPS target | In catalog; **LOCKED: Sabal** |
| Lighthouse Observability Platform (Platform 6) | Prometheus + Grafana + Alertmanager + Uptime Kuma + cAdvisor + Node Exporter + Postgres Exporter — all running in production, 16 alert rules configured | In catalog (currently "Vigilance Observability Suite"); **LOCKED: Lighthouse** |
| Alidade Search Platform (Platform 3) | Solr 9.6 in production Docker Compose stack; integrated with Drupal; search config managed in code | In catalog; **LOCKED: Alidade** |
| **Zero-Trust Access Platform** *(not yet in catalog as distinct platform)* | Keycloak + oauth2-proxy + Tailscale ACL — all three layers are Ansible-automated and running; this is a deployable IAM+network-access stack, not just a service | **Candidate: split from Zero Trust Architecture service into a distinct deployable Platform.** Currently this capability is split between Platform 4 (Squawk/identity platform) and the Zero Trust Architecture service. The infra work shows they belong together as a single deployable IAM+ZTA stack. |
| **Sovereign Observability + Alerting Stack** *(refinement of Platform 6)* | Email + Slack alerting channels; multi-target scraping; 16 alert rules covering container health, DB performance, disk, TLS expiry, process counts | Already covered by Platform 6 above — this is a depth note, not a separate platform. |

**`[DECIDE-I5]`** Should the Keycloak + oauth2-proxy + Tailscale configuration be positioned as a distinct deployable "Zero-Trust Access Platform" (replacing or supplementing the current Squawk identity platform concept)? Or does Squawk/equivalent remain the identity-only platform and ZTA remains a service-side capability?

---

### I.5 — Decisions: Section I

| ID | Decision | Answer | Notes |
|---|---|---|---|
| `DECIDE-I1` | Compliance Engineering: 14th service, or sub-capability of Full Spectrum Cybersecurity? | **Both: standalone service (#14) AND listed as a sub-capability within Full Spectrum Cybersecurity.** | Two entry points: a dedicated Compliance Engineering service page for buyers specifically seeking NIST 800-171/CMMC help; also cross-referenced from Full Spectrum Cyber as a formal sub-capability. |
| `DECIDE-I2` | Managed IAM: 15th service, or sub-capability of ZTA? | **Both: standalone service (#15) AND listed as a sub-capability within Zero Trust Architecture.** | Same dual-positioning as I1 — its own page and entry point, but clearly nested under the ZTA umbrella. |
| `DECIDE-I3` | Sovereign/private infrastructure ops: distinct service, or expand Cloud Operations? | **Both: standalone service (#16) AND Cloud Operations expanded to make explicit it covers sovereign/private environments alongside cloud-native.** | Cloud Operations and Sovereign Infrastructure Operations are related but have different buyer conversations: cloud-native buyers (cloud architects, CTOs) vs. organizations deliberately leaving hyperscalers or requiring on-prem (compliance-driven orgs, defense-adjacent, privacy-conscious). |
| `DECIDE-I4` | Private AI infrastructure: standalone service or delivery pattern within AI Strategy & Integration? | **Standalone named offering AND a delivery pattern within AI Strategy & Integration.** | Not a full 17th top-level service — surface it as a named sub-service / delivery pattern under AI Strategy & Integration with explicit positioning (on-prem LLM deployment, RAG pipelines, within-authorization-boundary inference). Gets its own prominent callout rather than a footnote. |
| `DECIDE-I5` | Zero-Trust Access Platform: new combined platform, or keep identity platform + ZTA service separate? | **Keep separate. Platform 4 (Squawk) remains identity-only (Keycloak + oauth2-proxy). ZTA is the service-side conversation.** | Recommended by Claude and accepted. Rationale: (1) Tailscale is a third-party product WL deploys but doesn't ship — bundling it into a platform would limit applicability and create a misleading product claim; (2) in federal procurement, network segmentation and identity/IAM are different conversations with different buyers; (3) a deployable identity platform with no network-layer dependency is more broadly applicable across customer environments. The ZTA service covers the full framework; Platform 4 is the deployable identity artifact. |

---

### I.6 — Revised services list (post Section I decisions)

The confirmed 13-service catalog from Section H expands to 16 services with three new standalone entries from infra findings. Private AI Infrastructure is a named sub-offering under AI Strategy & Integration, not a 17th top-level service.

| # | Service | Status | Notes |
|---|---|---|---|
| 1 | Cloud Operations | Restructured + expanded | Explicitly covers both cloud-native (AWS/GCP/Azure EKS/ECS/GitLab) and sovereign/private infrastructure ops environments. |
| 2 | DevOps & DevSecOps | Restructured | |
| 3 | Software Development | Restructured | |
| 4 | Human Centered Design | New | |
| 5 | Digital Modernization | Restructured | |
| 6 | AI Strategy & Integration | Restructured | Includes Private AI Infrastructure as a named delivery pattern/sub-offering. |
| 7 | Content Management & Architecture | Restructured | |
| 8 | Zero Trust Architecture | Restructured | Includes Managed IAM as a named sub-capability. |
| 9 | Full Spectrum Cybersecurity | New | Includes Compliance Engineering as a named sub-capability. |
| 10 | Enterprise Search Architecture | Keep | |
| 11 | Internationalization | New | |
| 12 | Emerging Technologies | New/Restructured | |
| 13 | Intelligence & Cyber Intelligence | Restructured | |
| 14 | Compliance Engineering | **New — from infra audit** | Standalone service; also cross-referenced as sub-capability of Full Spectrum Cybersecurity. NIST 800-171 / CMMC implementation and documentation for small businesses entering federal subcontracting. |
| 15 | Managed Identity & Access Management | **New — from infra audit** | Standalone service; also cross-referenced as sub-capability of Zero Trust Architecture. Self-hosted IAM deployment and operations (Keycloak-based). |
| 16 | Sovereign Infrastructure Operations | **New — from infra audit** | Standalone service; Cloud Operations expanded scope also covers this. Managed ops for organizations running on their own hardware, privacy-first VPS, or off-hyperscaler environments. |

**Note on catalog size:** 16 services is a substantial catalog for a small firm. This list reflects the full scope of what WL *can* do, grounded in real infrastructure and past performance — not an inflated pitch list. As WL grows, lower-priority services can be surfaced only in proposals and removed from the public website. The naming session (Deliverable 2+) will address whether all 16 need full pages or whether some should be grouped under parent services for the public-facing catalog.
