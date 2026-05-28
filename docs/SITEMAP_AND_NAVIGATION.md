# Sitemap, Navigation, and Page Hierarchy

**Document:** Deliverable 2 of the WL Content Architecture pass  
**Owner:** Jeremy Cerda  
**Last updated:** 2026-05-27  
**Status:** Updated 2026-05-27 — platform names and solution names propagated from naming session  
**Companion docs:** [PAGE_INVENTORY.md](PAGE_INVENTORY.md) · [CONTENT_TYPES_GUIDE.md](CONTENT_TYPES_GUIDE.md) · [CONTENT_DRAFT_V2.md](CONTENT_DRAFT_V2.md) *(forthcoming)*

---

## Table of Contents

1. [Strategic Architecture Decisions](#1-strategic-architecture-decisions)
2. [Full Sitemap](#2-full-sitemap)
3. [Page Hierarchy and Breadcrumbs](#3-page-hierarchy-and-breadcrumbs)
4. [Main Navigation Menu](#4-main-navigation-menu)
5. [Utility Navigation (Header)](#5-utility-navigation-header)
6. [Footer Navigation](#6-footer-navigation)
7. [Audience-Segmented Navigation Patterns](#7-audience-segmented-navigation-patterns)
8. [Mobile Navigation Collapse](#8-mobile-navigation-collapse)
9. [Internal Linking Pattern](#9-internal-linking-pattern)
10. [CTA Hierarchy](#10-cta-hierarchy)
11. [Slug Conventions](#11-slug-conventions)
12. [Buyer Journey Mapping](#12-buyer-journey-mapping)

---

## 1. Strategic Architecture Decisions

These decisions underpin every structural choice in this document. Each is driven by the two-track revenue strategy (federal + commercial) and the site's architectural rules (Platforms and Services as canonical capability inventory; Solutions as audience-segmented packages).

**Solutions are the commercial entry point.** Buyers from higher education, nonprofits, B2B SaaS, and regulated industries enter through `/solutions`. The Solutions index is the primary disambiguation point — a visitor who lands anywhere other than a named Solution page should be able to reach the right Solution within one click.

**`/federal` is a distinct conversion surface.** The federal buyers hub is not a Solutions page. It is a dedicated landing page with UEI, CAGE, NAICS, past performance summary, and capability statement download — copy scoped to contracting officers and prime BD teams. It is surfaced in utility navigation (always visible) and linked from every federal Solution page.

**Platforms and Services are the proof layer.** Every Solution page links back to the Platforms and Services it bundles. A buyer who wants to verify the underlying capability goes to the Platform or Service page; the Solution page does not need to restate those details.

**Case Studies anchor the credibility layer.** The WL corporate past performance (HHS/CMS via Scope Infotec) and the principal's individual record (USPS OIG, pandemicoversight.gov) are the most credible assets the site has. Case Studies cross-link to both Solutions and the Federal hub.

**Drupal Agency Partner Program** is recommended as a discreet `/partners` page (content type: `basic_page`) rather than a public Solution page. The audience (agency principals and teams considering a white-label or teaming relationship) is unlikely to find the site through broad search; they will be referred. A quiet page reachable from the footer and from a direct link in outreach is the right surface. No public Solution node needed.

**Defense Technology Integration** is listed as both a Service and a proposed federal Solution. The Solution wraps the Service plus Platforms (Manifest Data Platform, Lighthouse Observability Platform, Sabal Infrastructure Platform) into a package with defense-specific outcomes. Both nodes exist; the Solution page sells the combination, the Service page sells the engagement.

---

## 2. Full Sitemap

Content type abbreviations follow `PAGE_INVENTORY.md` conventions.

```
/ ................................................. [landing_page]
├── /about ........................................ [basic_page]
├── /contact ...................................... [nextjs + webform]
├── /federal ...................................... [landing_page — federal buyers hub]
│
├── /platforms .................................... [dynamic-index]
│   ├── /platforms/sabal                             [platform]
│   ├── /platforms/keel                              [platform]
│   ├── /platforms/alidade                           [platform]
│   ├── /platforms/squawk                            [platform]
│   ├── /platforms/manifest                          [platform]
│   ├── /platforms/lighthouse                        [platform]
│   └── /platforms/coquina                           [platform]
│
├── /services ..................................... [dynamic-index]
│   ├── /services/private-infrastructure-engineering   [service]
│   ├── /services/headless-cms-implementation          [service]
│   ├── /services/enterprise-search-architecture       [service]
│   ├── /services/zero-trust-identity-consulting       [service]
│   ├── /services/ai-integration                       [service]
│   ├── /services/digital-modernization                [service]
│   ├── /services/custom-software-development          [service]
│   ├── /services/integration-engineering                  [service]
│   ├── /services/digital-asset-solutions              [service]
│   ├── /services/defense-technology-integration       [service]
│   └── /services/intelligence-actionable-insights     [service]
│
├── /solutions .................................... [dynamic-index]
│   │
│   │   ── Commercial Solutions ──
│   ├── /solutions/dotedu                                [solution]
│   ├── /solutions/accord                                [solution]
│   ├── /solutions/palisade                              [solution]
│   ├── /solutions/bulkhead                              [solution]
│   │
│   │   ── Federal Solutions ──
│   ├── /solutions/dotgov                                [solution]
│   ├── /solutions/gazette                               [solution]
│   ├── /solutions/outpost                               [solution]
│   └── /solutions/software-factory                      [solution]
│
├── /case-studies ................................. [dynamic-index]
│   ├── /case-studies/hhs-cms-web-platform               [case_study — WL corporate]
│   ├── /case-studies/usps-oig-drupal-distribution       [case_study — principal]
│   └── /case-studies/pandemicoversight-gov              [case_study — principal]
│
├── /resources .................................... [dynamic-index]
│   ├── /resources/federal-capability-statement          [resource — gated PDF]
│   ├── /resources/headless-cms-federal-playbook         [resource — whitepaper]
│   └── /resources/zero-trust-identity-guide             [resource — guide]
│
├── /articles ..................................... [dynamic-index — wired]
│   ├── /articles/drupal-headless-cms-federal-agencies   [article]
│   ├── /articles/what-is-sovereignty-in-federal-it      [article]
│   └── /articles/iac-driven-infrastructure-for-government [article]
│
├── /press ........................................ [dynamic-index — filtered articles]
│
├── /events ....................................... [dynamic-index]
│   └── /events/{slug}               [event]
│
├── /careers ...................................... [dynamic-index]
│   └── /careers/{slug}              [career]
│
├── /team ......................................... [dynamic-index]
│   └── /team/jeremy-michael-cerda   [person]
│
├── /partners ..................................... [basic_page — discreet, not in primary nav]
│
├── /legal/privacy-policy ......................... [basic_page]
├── /legal/terms-of-service ....................... [basic_page]
├── /legal/cookie-policy .......................... [basic_page]
├── /legal/accessibility-statement ................ [basic_page]
│
└── (system)
    ├── /sitemap.xml                 [nextjs]
    ├── /robots.txt                  [nextjs]
    ├── /404                         [nextjs — wired]
    ├── /api/contact                 [nextjs — wired]
    ├── /api/revalidate              [nextjs — wired]
    ├── /api/draft                   [nextjs — wired]
    └── /api/status                  [nextjs — wired]
```

**Total public pages planned:** 52 named pages + open-ended article/event/career/resource streams.

---

## 3. Page Hierarchy and Breadcrumbs

### Breadcrumb patterns by content type

| Content type | Breadcrumb path |
|---|---|
| Homepage | *(no breadcrumb)* |
| Platforms index | Home |
| Platform detail | Home → Platforms → [Platform Name] |
| Services index | Home |
| Service detail | Home → Services → [Service Name] |
| Solutions index | Home |
| Solution detail | Home → Solutions → [Solution Name] |
| Case Studies index | Home |
| Case Study detail | Home → Case Studies → [Case Study Title] |
| Resources index | Home |
| Resource detail | Home → Resources → [Resource Name] |
| Articles index | Home |
| Article detail | Home → Articles → [Article Title] |
| Federal hub | Home → Federal |
| About | Home → About |
| Team index | Home → About → Team |
| Team detail | Home → About → Team → [Name] |
| Careers index | Home → Careers |
| Career detail | Home → Careers → [Role] |
| Events index | Home → Events |
| Event detail | Home → Events → [Event Name] |
| Legal | Home → Legal → [Page Name] |
| Press | Home → Press |
| Partners | Home → Partners |

### `field_breadcrumb_label` overrides

Use these overrides where the full page title is too long for the breadcrumb rail:

| Page | Full title | Breadcrumb label |
|---|---|---|
| `/platforms/sabal` | Sabal Infrastructure Platform | Sabal Infrastructure |
| `/platforms/keel` | Keel CMS Platform | Keel CMS |
| `/services/private-infrastructure-engineering` | Private Infrastructure Engineering | Infrastructure Engineering |
| `/solutions/dotedu` | DotEDU — Higher Education | Higher Education |
| `/solutions/accord` | Accord — Nonprofit | Nonprofits |
| `/solutions/palisade` | Palisade — Privacy SaaS | B2B SaaS |
| `/solutions/bulkhead` | Bulkhead — Regulated Industries | Regulated Industries |
| `/solutions/dotgov` | DotGov — Federal Civilian | Federal Civilian |
| `/solutions/gazette` | Gazette — IG Platforms | IG Platforms |
| `/solutions/outpost` | Outpost — Defense Tech | Defense |
| `/case-studies/hhs-cms-web-platform` | HHS/CMS Web Platform Modernization | HHS/CMS |
| `/case-studies/usps-oig-drupal-distribution` | USPS OIG Multi-Agency Drupal Distribution | USPS OIG |
| `/case-studies/pandemicoversight-gov` | pandemicoversight.gov | Pandemic Oversight |
| `/legal/privacy-policy` | Privacy Policy | Privacy |
| `/legal/terms-of-service` | Terms of Service | Terms |
| `/legal/accessibility-statement` | Accessibility Statement | Accessibility |

---

## 4. Main Navigation Menu

**Machine name:** `main`  
**Placement:** Global header, persists across all pages.  
**Structure:** Seven top-level items. Solutions, Platforms, Services, and Resources have flyout/dropdown panels. About, Federal, and Contact are flat single links.

### Item order and structure

```
Solutions    Platforms    Services    Resources    About    Federal    Contact
```

Rationale for ordering: Solutions first because it is the primary audience-disambiguation tool for both commercial and federal buyers. Platforms and Services follow as the capability proof layer. Resources clusters the content-marketing and credibility assets. About anchors company context. Federal surfaces the federal buyers hub as a standalone top-level destination — not buried in a dropdown — because federal contracting officers are a primary revenue audience. Contact closes the nav as a conversion point.

**Note:** Federal and Contact also appear as visually distinct CTA buttons in the header utility area (see §5). Their presence as both main nav items *and* utility buttons ensures maximum discoverability across breakpoints.

---

### MENU: Solutions (dropdown)

**URL:** `/solutions`  
**Dropdown label:** Solutions  
**Dropdown groups:** two labeled columns — Commercial and Federal.

```
Solutions
├── ── Commercial ──────────────────
│   ├── DotEDU — Higher Education          /solutions/dotedu
│   ├── Accord — Nonprofit                 /solutions/accord
│   ├── Palisade — Privacy SaaS            /solutions/palisade
│   └── Bulkhead — Regulated Industries    /solutions/bulkhead
│
├── ── Federal ─────────────────────
│   ├── DotGov — Federal Civilian          /solutions/dotgov
│   ├── Gazette — IG Platforms             /solutions/gazette
│   └── Outpost — Defense Tech             /solutions/outpost
│
├── ── Infrastructure ──────────────
│   └── Software Factory                   /solutions/software-factory
│
└── View All Solutions →               /solutions
```

**Mobile behavior:** Both column groups collapse into a single list under "Solutions." Labels "Commercial" and "Federal" remain as non-linked section dividers.

---

### MENU: Platforms (dropdown)

**URL:** `/platforms`

```
Platforms
├── Sabal Infrastructure Platform          /platforms/sabal
├── Keel CMS Platform                      /platforms/keel
├── Alidade Search Platform                /platforms/alidade
├── Squawk Zero-Trust Identity Platform    /platforms/squawk
├── Manifest Data Platform                 /platforms/manifest
├── Lighthouse Observability Platform      /platforms/lighthouse
├── Coquina Software Factory Platform      /platforms/coquina
└── View All Platforms →                  /platforms
```

**Mobile:** Collapses to accordion. All 7 platforms listed.

---

### MENU: Services (dropdown)

**URL:** `/services`  
**Dropdown groups:** two columns — Infrastructure & Security, and Modernization & Integration.

```
Services
├── ── Infrastructure & Security ───────
│   ├── Private Infrastructure Engineering   /services/private-infrastructure-engineering
│   ├── Zero-Trust Identity Consulting       /services/zero-trust-identity-consulting
│   └── Defense Technology Integration       /services/defense-technology-integration
│
├── ── Modernization & Integration ─────
│   ├── Headless CMS Implementation          /services/headless-cms-implementation
│   ├── Enterprise Search Architecture       /services/enterprise-search-architecture
│   ├── AI Integration                       /services/ai-integration
│   ├── Digital Modernization                /services/digital-modernization
│   ├── Custom Software Development          /services/custom-software-development
│   ├── Integration Engineering /services/integration-engineering
│   ├── Digital Asset Solutions              /services/digital-asset-solutions
│   └── Intelligence & Actionable Insights   /services/intelligence-actionable-insights
│
└── View All Services →                    /services
```

---

### MENU: Resources (dropdown)

**URL:** `/resources`

```
Resources
├── Case Studies                    /case-studies
├── Articles & Insights             /articles
├── Downloads & Guides              /resources/downloads-guides
└── Press                           /press
```

---

### MENU: About

**URL:** `/about`  
**No dropdown.** Single link. On hover, preload the About page.

```
About → /about
```

---

### MENU: Federal

**URL:** `/federal`  
**No dropdown.** Single link. Standalone top-level item targeting federal buyers and contracting officers. Also rendered as a pill-style CTA button in the utility nav area (see §5).

```
Federal → /federal
```

---

### MENU: Contact

**URL:** `/contact`  
**No dropdown.** Single link. Final conversion point in the nav. Also rendered as a filled button in the utility nav area (see §5).

```
Contact → /contact
```

---

## 5. Utility Navigation (Header)

**Machine name:** `header-utility`  
**Placement:** Top-right of the global header, above or alongside the primary nav.  
**Items:** Two items always visible.

```
Federal Buyers    Contact Us
```

| Label | URL | Notes |
|---|---|---|
| Federal Buyers | `/federal` | Visually distinguished — different color treatment or tag badge to signal it is a separate audience track. ARIA label: "Federal buyers hub — capabilities, past performance, and capability statement." |
| Contact Us | `/contact` | Standard button style. |

**Visibility:** Both items appear on all pages, including mobile (see §8). The Federal Buyers link is never buried — it is the primary conversion point for the most strategic audience.

---

## 6. Footer Navigation

**Machine name:** `footer`  
**Structure:** Six grouped sections in a multi-column layout, plus a utility bar below.

```
┌─────────────────────────────────────────────────────────────────────────┐
│  CAPABILITIES          SOLUTIONS              FEDERAL                    │
│  ─────────────         ─────────              ───────                    │
│  Platforms             DotEDU — Higher Ed     Federal Buyers Hub         │
│  · Sabal Infra         Accord — Nonprofit     Past Performance           │
│  · Keel CMS            Palisade — Privacy     Capability Statement       │
│  · Alidade Search      Bulkhead — Regulated   Contact for Federal        │
│  · Squawk Identity                                                        │
│  · Manifest Data       DotGov — Federal        COMPANY                   │
│  · Lighthouse Obs      Gazette — IG            ───────                   │
│                        Outpost — Defense       About                     │
│  Services                                      Team                      │
│  · Infrastructure Eng  RESOURCES               Careers                   │
│  · Headless CMS        ─────────               Partners                  │
│  · Zero-Trust          Case Studies            Press                     │
│  · AI Integration      Articles & Insights                               │
│  · Digital Modern.     Downloads                                         │
│  · Custom Software                             LEGAL                     │
│  · Digital Assets      CONNECT                 ─────                     │
│  · Defense Tech        ───────                 Privacy Policy            │
│  · Intelligence        Contact Us              Terms of Service          │
│                        jmcerda@wilkesliberty   Cookie Policy             │
│                        LinkedIn                Accessibility Statement   │
└─────────────────────────────────────────────────────────────────────────┘

© 2026 Wilkes & Liberty, LLC  ·  NAICS 541511  ·  UEI [pending]  ·  CAGE [pending]
South Florida, USA  ·  wilkesliberty.com
```

### Footer menu item specifications

**Section: Capabilities**

| Label | URL |
|---|---|
| Platforms *(section heading)* | `/platforms` |
| Sabal Infrastructure Platform | `/platforms/sabal` |
| Keel CMS Platform | `/platforms/keel` |
| Alidade Search Platform | `/platforms/alidade` |
| Squawk Zero-Trust Identity Platform | `/platforms/squawk` |
| Manifest Data Platform | `/platforms/manifest` |
| Lighthouse Observability Platform | `/platforms/lighthouse` |
| Services *(section heading)* | `/services` |
| Private Infrastructure Engineering | `/services/private-infrastructure-engineering` |
| Headless CMS Implementation | `/services/headless-cms-implementation` |
| Zero-Trust Identity Consulting | `/services/zero-trust-identity-consulting` |
| AI Integration | `/services/ai-integration` |
| Digital Modernization | `/services/digital-modernization` |
| Custom Software Development | `/services/custom-software-development` |
| Integration Engineering | `/services/integration-engineering` |
| Digital Asset Solutions | `/services/digital-asset-solutions` |
| Defense Technology Integration | `/services/defense-technology-integration` |
| Intelligence & Actionable Insights | `/services/intelligence-actionable-insights` |

**Section: Solutions**

| Label | URL |
|---|---|
| DotEDU — Higher Education | `/solutions/dotedu` |
| Accord — Nonprofit | `/solutions/accord` |
| Palisade — Privacy SaaS | `/solutions/palisade` |
| Bulkhead — Regulated Industries | `/solutions/bulkhead` |
| DotGov — Federal Civilian | `/solutions/dotgov` |
| Gazette — IG Platforms | `/solutions/gazette` |
| Outpost — Defense Tech | `/solutions/outpost` |

**Section: Federal**

| Label | URL | Notes |
|---|---|---|
| Federal Buyers Hub | `/federal` | |
| Past Performance | `/federal#past-performance` | Anchor link to section |
| Capability Statement | `/resources/federal-capability-statement` | |
| Contact for Federal | `/contact?audience=federal` | UTM/query param routes to federal-specific inquiry |

**Section: Resources**

| Label | URL |
|---|---|
| Case Studies | `/case-studies` |
| Articles & Insights | `/articles` |
| Downloads & Guides | `/resources` |
| Press | `/press` |

**Section: Company**

| Label | URL |
|---|---|
| About | `/about` |
| Team | `/team` |
| Careers | `/careers` |
| Partners | `/partners` |
| Press | `/press` |

**Section: Connect**

| Label | URL | Notes |
|---|---|---|
| Contact Us | `/contact` | |
| jmcerda@wilkesliberty.com | `mailto:jmcerda@wilkesliberty.com` | `rel="nofollow"` |
| LinkedIn | `https://www.linkedin.com/company/wilkes-liberty` | `target="_blank" rel="noopener noreferrer"` |

**Section: Legal**

| Label | URL |
|---|---|
| Privacy Policy | `/legal/privacy-policy` |
| Terms of Service | `/legal/terms-of-service` |
| Cookie Policy | `/legal/cookie-policy` |
| Accessibility Statement | `/legal/accessibility-statement` |

---

## 7. Audience-Segmented Navigation Patterns

These are recommended secondary navigation and page-level wayfinding patterns for visitors arriving at different depths. They do not require separate menu machines — they are implemented as in-page CTAs, sidebar navigation, or related-content panels.

### 7.1 Federal buyers (contracting officers, prime BD teams)

**Entry points:** `/federal`, direct link from capability statement PDF, DSBS profile, SAM.gov.

**In-page wayfinding needed on:**

- `/federal` — links to all three case studies, capability statement download, all federal Solutions, and contact form with federal routing
- All federal Solution pages — persistent sidebar or bottom-of-page panel: "Federal buyers: View our past performance and capability statement → /federal"
- All case study pages — prominent link to `/federal` and the relevant federal Solution page
- `/resources/federal-capability-statement` — after download, route to `/contact?audience=federal`

**Audience-specific secondary nav (visible on `/federal` and federal Solution pages):**

```
Federal Buyers
├── Federal Capability Statement (PDF)    /resources/federal-capability-statement
├── Past Performance                      /federal#past-performance
├── DotGov — Federal Civilian             /solutions/dotgov
├── Gazette — IG Platforms                /solutions/gazette
├── Outpost — Defense Tech                /solutions/outpost
└── Contact for Federal Engagements       /contact?audience=federal
```

### 7.2 Higher education web services directors / IT modernization PMs

**Entry points:** Organic search on "Drupal higher education headless CMS," referral, LinkedIn.

**In-page wayfinding needed on:**

- `/solutions/dotedu` — links to Keel CMS Platform, Headless CMS Implementation, Squawk Identity, and the relevant case study (HHS/CMS is the best proxy for institutional-scale delivery)
- `/platforms/keel` — related panel linking to the DotEDU solution
- `/services/headless-cms-implementation` — related panel linking to DotEDU and Drupal Modernization solutions

**No separate nav machine required.** Related-content panels and in-page CTAs carry the wayfinding.

### 7.3 Mission-driven nonprofit executive directors / CTOs

**Entry points:** Referral, LinkedIn, organic search on "sovereign hosting nonprofit" or "Drupal nonprofit CMS."

**In-page wayfinding needed on:**

- `/solutions/accord` — links to Keel CMS Platform, Sabal Infrastructure Platform, Private Infrastructure Engineering, and contact
- Homepage hero or solutions module — must surface nonprofit framing within the first scroll
- `/platforms/sabal` — related panel linking to Accord solution

### 7.4 Privacy-conscious B2B SaaS CTOs / heads of platform

**Entry points:** Organic search on "SOC 2 identity management small team" or "self-hosted Keycloak consulting," referral from compliance community.

**In-page wayfinding needed on:**

- `/solutions/palisade` — links to Squawk Identity, Lighthouse Observability, Zero-Trust Identity Consulting, Sabal Infrastructure Platform
- `/platforms/squawk` — related panel linking to Palisade solution and Zero-Trust consulting
- `/services/zero-trust-identity-consulting` — related panel linking to Palisade solution

---

## 8. Mobile Navigation Collapse

**Breakpoint:** Below 768px. The primary nav collapses into a hamburger menu. The utility nav adapts.

### Mobile header bar (always visible, narrow viewport)

```
[WL Logomark]    [Federal Buyers]    [☰]
```

- Logo: links to `/`
- "Federal Buyers" remains visible in the mobile header bar — it is too strategically important to hide behind the hamburger. Displayed as a compact text link or small badge.
- Hamburger (`☰`) opens a full-screen overlay menu.

### Mobile overlay menu structure

```
Close [✕]

Solutions
  └── [Accordion — tap to expand]
       Commercial
        · DotEDU — Higher Education
        · Accord — Nonprofit
        · Palisade — Privacy SaaS
        · Bulkhead — Regulated Industries
       Federal
        · DotGov — Federal Civilian
        · Gazette — IG Platforms
        · Outpost — Defense Tech
       View All Solutions →

Platforms
  └── [Accordion — tap to expand]
       · Sabal Infrastructure Platform
       · Keel CMS Platform
       · Alidade Search Platform
       · Squawk Zero-Trust Identity Platform
       · Manifest Data Platform
       · Lighthouse Observability Platform
       View All Platforms →

Services
  └── [Accordion — tap to expand]
       All 11 services listed
       View All Services →

Resources
  └── [Accordion — tap to expand]
       · Case Studies
       · Articles & Insights
       · Downloads & Guides
       · Press

About

──────────────────
Federal Buyers →
Contact Us →
```

**Items NOT in mobile overlay:** Legal pages, Partners, individual team members, individual social links. These are reachable via the footer only on mobile.

**Active trail:** The currently active top-level section is indicated by a visual marker (accent color left border or underline) on the relevant accordion header.

---

## 9. Internal Linking Pattern

### How Solutions link to Platforms and Services

Every Solution page uses `field_related` to declare which Platforms and Services it bundles. Additionally, the Solution body copy must name the components inline and hyperlink them. Example pattern:

> "The DotEDU — Higher Education solution combines the [Keel CMS Platform](/platforms/keel) with our [Headless CMS Implementation service](/services/headless-cms-implementation) and [Squawk Zero-Trust Identity Platform](/platforms/squawk) for centralized SSO across institutional systems."

Do not use anchor text like "click here" or "learn more." Use the product or service name as the link text.

### How Case Studies link to Platforms, Services, and Solutions

Each Case Study sets `field_related` to the Platforms, Services, and Solution(s) that the engagement demonstrates. In the body, cross-link specifically at first mention. At the bottom of each case study, surface a "Capabilities demonstrated" section listing all related nodes.

### How Platforms link to related Services and Solutions

Each Platform page uses `field_related_services` to surface its companion Services. Additionally, each Platform should reference the Solution(s) it participates in via a "Deployed as part of" callout panel.

### How Articles cluster around capabilities

Articles are tagged with `field_solutions` (taxonomy) to associate them with Solution areas, and with `field_tags` for topical clustering. The articles listing and sidebar "Related reading" panels use these tags to surface relevant Platform, Service, and Solution pages. An article about zero-trust architecture should link to both the Squawk Zero-Trust Identity Platform page and the Zero-Trust Identity Consulting service page — in-body, at first substantive mention.

### Cross-link minimums by content type

| Content type | Minimum internal links |
|---|---|
| Solution | 2 Platforms + 1 Service + 1 Case Study (where available) |
| Platform | 2 Services + 1 Solution |
| Service | 2 Platforms + 1 Solution |
| Case Study | 2 Platforms + 1 Service + 1 Solution + `/federal` (for federal case studies) |
| Article | 1 Platform or Service + 1 Solution |
| Resource | 1 Solution + 1 related Service |
| Federal hub | 3 Case Studies + 3 Federal Solutions + capability statement download |

---

## 10. CTA Hierarchy

### CTA voice principles (from `BRAND_VOICE.md §11.8`)

- **No** "Get Started Today!" / "Click Here" / "Try for Free" / "Request a Demo" (consumer-SaaS register)
- **Yes** to action phrases that name the next step specifically, respect the reader's seniority, and match the formality of the page
- Primary CTAs open a door; secondary CTAs lower the commitment threshold
- Federal-audience CTAs skew toward "briefing," "consultation," and "capability statement"
- Commercial CTAs skew toward "conversation," "consultation," and "see how"

### CTA text glossary (approved patterns)

| Context | Primary CTA | Secondary CTA |
|---|---|---|
| Homepage hero | Schedule a Consultation | Explore Our Work |
| Solution pages (commercial) | Start the Conversation | View Case Studies |
| Solution pages (federal) | Request a Capability Briefing | Download Capability Statement |
| Platform pages | Schedule a Technical Briefing | View Related Services |
| Service pages | Discuss Your Project | View Related Platforms |
| Case Studies | See How We Can Help | View All Case Studies |
| Federal hub | Download Capability Statement | Contact for Federal |
| Resources (gated) | Download [Resource Name] | Explore Our Solutions |
| Articles | Explore Our Work | Subscribe to Insights |
| About | Work With Us | View Our Team |
| Contact | Send Your Message | *(none)* |

### CTA hierarchy by page tier

**Tier 1 — Primary conversion pages** (Homepage, /federal, Solution pages): One prominent primary CTA above the fold. One secondary CTA in the hero. Additional CTAs at natural break points in the body. Never more than two CTAs visible at once in any section.

**Tier 2 — Capability detail pages** (Platforms, Services): Primary CTA after the Mission Impact section. Secondary CTA at page bottom. In-page cross-links to related nodes are informational, not CTAs.

**Tier 3 — Credibility pages** (Case Studies, Articles, Resources): Primary CTA at bottom of page. In-body CTAs only where natural (e.g., end of results section in a case study → "See how we can replicate this for your organization").

**Tier 4 — Reference pages** (About, Team, Careers, Legal): Minimal. A single CTA at the bottom of About and Team. None on legal pages.

---

## 11. Slug Conventions

### General rules

- All lowercase, hyphen-separated words
- No trailing slashes in slugs (Next.js handles trailing-slash normalization)
- No dates in slugs except for events where the date is genuinely disambiguating
- No stop words (a, the, of, for, and) unless removing them breaks meaning
- Drupal path aliases are auto-generated from the node title; override in `field_path_alias` where the auto-generated slug is too long or violates the patterns below

### Per-content-type patterns

| Content type | Pattern | Example |
|---|---|---|
| Platform | `/platforms/{short-platform-name}` | `/platforms/squawk` |
| Service | `/services/{short-service-name}` | `/services/zero-trust-identity-consulting` |
| Solution | `/solutions/{canonical-solution-name}` | `/solutions/dotedu` |
| Case Study | `/case-studies/{client-or-project-descriptor}` | `/case-studies/usps-oig-drupal-distribution` |
| Resource | `/resources/{type-topic}` | `/resources/federal-capability-statement` |
| Article | `/articles/{topic-slug}` | `/articles/what-is-sovereignty-in-federal-it` |
| Event | `/events/{YYYY-MM-short-name}` | `/events/2026-09-drupal-govtech-summit` |
| Career | `/careers/{role-title}` | `/careers/senior-drupal-engineer` |
| Person | `/team/{first-middle-last}` | `/team/jeremy-michael-cerda` |
| Basic Page | `/about`, `/contact`, `/partners`, `/federal` | Flat, one word where possible |
| Legal | `/legal/{page-name}` | `/legal/privacy-policy` |

### URL stability rule

**Do not change existing slugs** after a node is published (even if the title changes). Redirect old slugs with HTTP 301 if a rename is unavoidable.

---

## 12. Buyer Journey Mapping

Four primary buyer journeys, mapped as 2–4 page paths from entry to the contact/conversion event. Each path reflects realistic entry and behavior, not an idealized funnel.

---

### Journey 1 — Federal Contracting Officer / Prime BD Team

**Goal:** Qualify WL as a subcontractor candidate or team partner. Confirm past performance, NAICS, and capability alignment before reaching out.

**Likely entry:** Direct URL from capability statement PDF, DSBS search result, referral from Scope Infotec or ECS Federal contact.

```
Step 1: /federal
  — Sees UEI, CAGE, NAICS, past performance summary, capability statement download
  — Primary action: download capability statement PDF

Step 2: /case-studies/hhs-cms-web-platform
  — Validates HHS/CMS corporate past performance
  — May also visit /case-studies/pandemicoversight-gov for principal record

Step 3: /solutions/federal-civilian-modernization  OR  /solutions/inspector-general-platforms
  — Confirms scope alignment with their pursuit
  — Sees Platforms and Services bundled in the package

Step 4: /contact?audience=federal
  — Submits inquiry with teaming interest or subcontract discussion request
```

**Conversion event:** Contact form submission or direct email to jmcerda@wilkesliberty.com.

**Dropout risk:** Step 1 — if the Federal hub does not clearly display UEI/CAGE/NAICS or does not have the capability statement download ready, contracting officers bounce. This page must be launch-ready before any federal outreach.

---

### Journey 2 — Higher Education Web Services Director / IT Modernization PM

**Goal:** Evaluate WL as a vendor for a Drupal modernization, accessibility remediation, or headless architecture project.

**Likely entry:** Organic search ("Drupal 7 to 11 migration higher education," "headless Drupal university"), referral from a Drupal community contact, LinkedIn.

```
Step 1: /solutions/dotedu
  — Lands directly on the solution page (high-intent search or referral)
  — Reads challenge framing: aging Drupal, accessibility debt, SSO sprawl
  — Sees the package: Keel CMS + Headless CMS Implementation + Squawk Identity

Step 2: /platforms/keel
  — Validates the CMS platform in depth
  — Reads deployment options, sovereignty features, key capabilities

Step 3: /case-studies/hhs-cms-web-platform
  — Institutional-scale delivery validation
  — Sees Challenge → Solution → Results structure

Step 4: /contact
  — Schedules a consultation
```

**Conversion event:** Contact form submission or "Start the Conversation" CTA on the Solution page.

**Dropout risk:** Step 2 — if Keel CMS platform page is thin or reads as feature-list rather than mission/outcome framing, this buyer loses confidence. The platform page must lead with the institutional accessibility and governance outcome, not the Drupal version number.

---

### Journey 3 — Mission-Driven Nonprofit Executive Director or CTO

**Goal:** Evaluate WL for a platform that preserves data sovereignty, is accessible, and does not lock the organization into a cloud vendor's terms.

**Likely entry:** Referral from a peer organization, LinkedIn, organic search ("self-hosted CMS nonprofit," "sovereign infrastructure nonprofit").

```
Step 1: Homepage
  — Likely first stop if referred generically to "wilkesliberty.com"
  — Reads mission framing; identifies the nonprofit solution path from Solutions nav

Step 2: /solutions/accord
  — Recognizes the challenge framing: donor-data sensitivity, accessibility mandates,
    vendor lock-in risk, sovereignty resonance
  — Sees the bundled package and pricing-free CTA

Step 3: /platforms/sabal
  — Validates that "sovereign" means what they think it means (on-prem, no hyperscaler lock)
  — Reads deployment options and sovereignty features

Step 4: /contact
  — Sends inquiry; may reference donor-data sensitivity or specific platform situation
```

**Conversion event:** Contact form submission.

**Dropout risk:** Step 1 → Step 2 transition. If the homepage does not clearly surface the nonprofit Solution path (either in the hero or in a Solutions module within the first two scrolls), this buyer may not self-identify and will exit. The homepage must include at minimum a Solutions teaser section that makes "Accord — Nonprofit" visible without requiring a nav interaction.

---

### Journey 4 — Privacy-Conscious B2B SaaS CTO / Head of Platform

**Goal:** Evaluate WL for IAM/SSO consolidation, SOC 2 readiness support, or a move away from SaaS identity and monitoring tools that create audit risk.

**Likely entry:** Organic search ("Keycloak consulting," "self-hosted SSO SOC 2," "zero-trust identity small team"), referral from security community.

```
Step 1: /solutions/palisade  OR  /platforms/squawk
  — Entry via solution (if referred) or platform page (if searching for Keycloak/SSO specifically)
  — Reads challenge framing: IAM debt, audit logging gaps, SOC 2 mandates

Step 2: /services/zero-trust-identity-consulting
  — Validates the engagement model — consulting, not just product licensing
  — Reads "We [verb]..." service framing; confirms it is a genuine engagement offering

Step 3: /platforms/lighthouse  (optional — if audit logging is the primary pain)
  — May visit to validate the observability stack for compliance use cases

Step 4: /contact
  — Schedules a technical briefing or sends a detailed inquiry describing their stack
```

**Conversion event:** "Schedule a Technical Briefing" CTA on the Squawk Zero-Trust Identity Platform page or the Zero-Trust consulting service page.

**Dropout risk:** Step 2. This buyer is technically sophisticated and will not be sold by feature lists. The Zero-Trust consulting service page must open with a specific, technically honest problem statement — not a general description of "zero-trust" — and must name the actual technologies (Keycloak, OIDC, mTLS, audit log configuration) that WL works with.

---

## Appendix A — Solutions Inventory and Relationship Map

| Solution slug | Audience | Platforms bundled | Services bundled | Case study proof |
|---|---|---|---|---|
| `/solutions/dotedu` | Higher-ed web teams | Keel CMS Platform, Alidade Search Platform, Squawk Identity Platform | Headless CMS Implementation, Digital Modernization | HHS/CMS (proxy) |
| `/solutions/accord` | Nonprofit EDs, CTOs | Keel CMS Platform, Sabal Infrastructure Platform | Private Infrastructure Engineering, Headless CMS Implementation | — |
| `/solutions/palisade` | B2B SaaS CTOs | Squawk Identity Platform, Lighthouse Observability Platform, Sabal Infrastructure Platform | Zero-Trust Identity Consulting, Custom Software Development | — |
| `/solutions/bulkhead` | CISO, Head of Compliance | Sabal Infrastructure Platform, Squawk Identity Platform, Manifest Data Platform | Private Infrastructure Engineering, Zero-Trust Identity Consulting | — |
| `/solutions/dotgov` | Federal civilian agencies | Keel CMS Platform, Alidade Search Platform, Squawk Identity Platform | Headless CMS Implementation, Digital Modernization, Custom Software | HHS/CMS |
| `/solutions/gazette` | Federal OIG offices | Keel CMS Platform, Manifest Data Platform | Headless CMS Implementation, Custom Software Development | USPS OIG, Pandemic Oversight |
| `/solutions/outpost` | Defense contractors, DoD adjacent | Sabal Infrastructure Platform, Coquina Software Factory Platform | Defense Technology Integration, Private Infrastructure Engineering | — |
| `/solutions/software-factory` | Engineering teams, DevSecOps leads | Coquina Software Factory Platform | Custom Software Development, Integration Engineering | — |

---

## Appendix B — Open Questions for Jeremy

**[VERIFY-NAV-01]** ~~Resolved.~~ The three legacy Sovereign solution nodes (Sovereign Mission Edge, Sovereign AI Command Fabric, Sovereign Digital Modernization Platform) have been deleted from Drupal. The eight canonical solutions are the only solution nodes in the system.

**[VERIFY-NAV-02]** The Drupal Agency Partner Program is scoped as a discreet `/partners` basic_page not in primary nav, reachable only from the footer and from direct outreach links. Confirm, or advise if a public Solution page is preferred.

**[VERIFY-NAV-03]** `/federal` is surfaced in the utility nav (header) rather than the primary nav. This keeps the primary nav clean for commercial buyers while ensuring the federal link is always visible. Confirm this placement is correct, or advise if `/federal` should be elevated to a primary nav item.

**[VERIFY-NAV-04]** ~~Resolved.~~ The platform previously named Enterprise Search is now named **Alidade Search Platform** (slug: `/platforms/alidade`). The service slug `/services/enterprise-search-architecture` remains unchanged. The name-collision concern is resolved since the platform name (Alidade) no longer overlaps with the service name (Enterprise Search Architecture).

**[VERIFY-NAV-05]** The footer lists `jmcerda@wilkesliberty.com` as the public contact email. Confirm this is the preferred email for inbound. If a contact alias (e.g., `hello@wilkesliberty.com`) is planned, update here.

**[VERIFY-NAV-06]** The UEI and CAGE codes appear in the footer as `[pending]`. These must be live values before any federal outreach page is published. Confirm status and provide values when available.

**[VERIFY-NAV-07]** Language: `PAGE_INVENTORY.md §Gaps #12` notes i18n (EN/ES/RU) is out of scope for this launch. Confirming the nav structure documented here is English-only at launch, with language prefix paths (`/es/...`, `/ru/...`) deferred to a post-launch sprint.

---

*End of Deliverable 2. Updated 2026-05-27 — platform names and solution names propagated from naming session. Awaiting review and approval before proceeding to Deliverable 3 — page-by-page content drafts.*
