# Wilkes & Liberty Brand Voice Guidelines

**Version:** 1.2 (eleven of twelve §11 open questions resolved; product/service/solution naming session pending)
**Last updated:** 2026-05-27
**Source of truth:** `docs/CONTENT.md` (drafted page copy for 6 Products + 10 Services)
**Status:** §11.1, .2, .3 (pattern), .4, .5, .7, .8, .9, .10, .11, .12 resolved on 2026-05-27. §11.6 (career posting voice) parked until first job posting is drafted. §11.3 specific product names deferred to dedicated naming session (Products + Services + Solutions in one sweep). The guide is otherwise considered v1.2-final and ready for editorial enforcement.

**v1.2 changelog (2026-05-27 — second pass):**
- §11.2 resolved → §5.3 rewritten with audience-layered acronym spell-out matrix (federal/expert audiences skip expansion; commercial/mission-driven audiences spell out engineering-niche + regulatory acronyms on first use).
- §11.3 (pattern) resolved → §6.15 added: every product gets a proper name + descriptive suffix. Existing pure-descriptor products flagged for retrofit. **Specific proper names deferred** to dedicated Products + Services + Solutions naming session.
- §11.4 resolved → §6.4 sub-rule added: dial back "mission" density to 1–2 mentions per piece outside of Product / Service pages.
- §11.5 resolved → §6.7 updated: meta descriptions end with a period.
- §11.6 PARKED → decision deferred until first job posting is drafted. Default if/when needed: hold formal voice on cleared roles, soften on commercial/engineering.
- §11.7 resolved → §6.12 added: audience adaptation (federal vs. commercial register) — same voice attributes, vocabulary substitution table. No separate commercial brand voice.
- §11.9 resolved → §6.13 added: customer quotes in case studies kept verbatim; three narrowly-allowed edits (transcription artifacts, >40-word tighten, bracket clarifications).
- §11.12 resolved → §6.14 added: company name usage hierarchy. "Wilkes & Liberty, LLC" formal / "Wilkes & Liberty" public / "WL" internal-only. `W&L` is RETIRED — the ampersand abbreviation is banned. All instances in this guide have been swept to `WL`. Downstream docs (AGENTS.md, CLAUDE.md, business/, infra/, ui/) still contain legacy `W&L` references and will be swept in a follow-up.

**v1.1 changelog (2026-05-27 — first pass):**
- §11.1 resolved → §6.3 rewritten: "we" is canonical; "I"/"Jeremy Cerda" reserved for personal-byline pieces.
- §11.8 resolved → §6.10 added: CTA voice rules with per-audience Primary CTA matrix (federal: "Open an inquiry" / "Request a capability brief"; commercial: "Open a conversation"; universal: "Schedule a working session"). "Demo" added to §5.2 banned vocabulary.
- §11.10 resolved → §6.9 added: US English is canonical, with explicit date/number conventions.
- §11.11 resolved → §6.11 added: "you"/"your" canonical; elevation by audience (federal → institutional language; commercial → stay direct).

---

## 1. How to use this guide

This document is the canonical brand voice reference for anyone writing public-facing copy for Wilkes & Liberty, LLC (WL) — website, marketing assets, RFP responses, sales collateral, press releases, social posts, and case studies.

**When to consult it:**

- Before drafting a new Product or Service page
- Before writing an article, case study, or press release
- When reviewing copy from a contractor, freelancer, or AI assistant
- When deciding between two phrasings that "both sound fine" — the answer is usually in §3 (messaging pillars) or §5 (vocabulary)

**How to apply it:**

1. Start with §3 (messaging pillars) and §4 (personas) to confirm what you're writing supports the right outcome for the right audience.
2. Draft using §5 (vocabulary) and §6 (style rules).
3. Self-edit against §9 (Do / Don't) — every Product/Service paragraph should pass that test.
4. If you're unsure, check the parallel example in `docs/CONTENT.md` for the same content type and copy the pattern.

**What this guide is not:**

- Not a content strategy document. For *what* to write, see `docs/CONTENT_TYPES_GUIDE.md`.
- Not a field reference. For field-level specs, see `docs/FIELD_REFERENCE.md`.
- Not a final standard. v1.0 was extracted from existing marketing copy only — not from sales calls, customer research, or external brand work. Treat open questions in §11 as load-bearing.

---

## 2. Voice attributes

Five attributes define how WL sounds. Each is anchored on observed patterns in `docs/CONTENT.md`.

| Attribute | Description | Why it's the right choice for WL |
|---|---|---|
| **Mission-anchored** | Every claim ties to a mission outcome — resilience, decision velocity, sovereignty, operational continuity. We do not describe technology in isolation. | Audience (defense, federal civilian, mission-critical operations) judges vendors by mission impact, not feature lists. Memory note: WL's real past performance is HHS/CMS via Scope Infotec; current direction is federal/defense work where this framing is table stakes. |
| **Confident, restrained** | Declarative sentences. No hedging ("we believe," "we think we can"). No hype ("revolutionary," "game-changing"). | The audience reads consumer-startup hype as a credibility signal *against* you. Restraint reads as seriousness. |
| **Sovereignty-forward** | Foregrounds independence, control, and self-determination — for the customer, not for us. | Differentiator vs. the dominant hyperscaler narrative. Aligns with defense/federal preference for on-prem, air-gapped, and private-cloud options. |
| **Technically literate** | Uses terms like *zero-trust*, *Infrastructure-as-Code*, *air-gapped*, *zero-trust architecture*, *headless* without apology — but defines them in passing when used in marketing-facing copy. | Audience expects technical fluency; over-explaining looks junior. |
| **Outcome-led, not feature-led** | Capability bullets describe *what the system does for the mission*, not *what the engineer configured*. | Buyers (mission owners, CIOs, contracting officers) make decisions on outcomes. Engineers see through feature-stuffing. |

**Register and pace:**

- **Formality:** High. Closer to government white-paper than SaaS blog. No contractions in body copy. No second-person breeziness ("you'll love how easy...").
- **Sentence length:** Mid-to-long. Compound sentences with one subordinate clause are the norm. Short sentences are reserved for emphasis after a long one.
- **Voice:** Active dominant. Passive only when the actor genuinely doesn't matter ("Backups are retained for...").

---

## 3. Messaging pillars

Five pillars recur across every Product and Service page. New copy should explicitly carry at least one — usually two or three.

### 3.1 Mission impact

**Anchor quote (CONTENT.md, Sovereign Infrastructure Platform):**

> "By removing reliance on external cloud providers and giving you full sovereignty over your infrastructure, we help defense contractors and government organizations maintain control, reduce risk, and focus resources on core mission objectives rather than technology maintenance."

The "Mission Impact" block is a hard-required field on Product and Service content (per `docs/CONTENT_TYPES_GUIDE.md` §9, §10). It is also the rhetorical center of the voice: every other claim is in service of it.

### 3.2 Sovereignty

**Anchor quote (CONTENT.md, Apex Secure Data Platform):**

> "A sovereign data foundation built to enhance data availability and support mission-critical applications."

Sovereignty in WL copy means: customer-controlled, deployable anywhere (on-prem, private cloud, hybrid, air-gapped), independent of any single vendor or hyperscaler, with data and identity under the customer's authority. It is *not* a political claim — it's an operational and architectural one.

### 3.3 Security as enabler, not obstacle

**Anchor quote (CONTENT.md, Fortis Zero-Trust Identity Platform):**

> "Security and usability must work together in support of the mission. The Fortis Zero-Trust Identity Platform provides enterprise-grade single sign-on and access control while enforcing strict security standards."

WL's security pitch is *not* "we are paranoid and slow." It is "we make zero-trust operationally invisible." Always pair security claims with a velocity / usability claim.

### 3.4 Defense & government readiness

**Anchor quote (CONTENT.md, Cryptocurrency & Digital Asset Solutions):**

> "All solutions are built with defense-grade security and regulatory standards in mind."

Even outside defense-specific offerings (the crypto example above is not a defense product), copy ties back to defense-grade rigor as a quality bar. This is consistent across the source material.

### 3.5 Force multiplier / operational independence

**Anchor quote (CONTENT.md, Custom Software Development & Middleware Engineering):**

> "Our custom solutions become force multipliers that increase efficiency and reduce reliance on fragmented commercial tools."

The "force multiplier" framing — small, well-designed capability creates outsized operational leverage — recurs across the Services section. This is the voice's small-vendor advantage: WL is not pretending to be a hyperscaler; it is positioned as the high-leverage specialist.

---

## 4. Personas (audience model)

Four buyer personas, anchored in the language of `docs/CONTENT.md` and WL's actual past-performance footprint (HHS/CMS, USPS OIG, EPA — see memory `project_wl_past_performance.md` and `user_federal_experience.md`).

### 4.1 Mission Owner

- **Examples:** program manager, mission director, branch chief, division chief, contracting officer's representative.
- **Cares about:** mission execution, operational tempo, decision velocity, risk to mission objectives.
- **Reads:** Mission Impact section first. Skips feature lists. Wants to understand outcome before architecture.
- **Pet peeves:** vendors who talk about themselves before talking about the mission.

### 4.2 Agency CIO / IT Director

- **Examples:** federal civilian agency CIO, deputy CIO, IT services branch chief, enterprise architect.
- **Cares about:** sovereignty, compliance posture, vendor lock-in risk, operations cost, total cost of ownership over multi-year procurement cycles.
- **Reads:** Key Capabilities, Deployment Options, Sovereignty Features.
- **Pet peeves:** hidden hyperscaler dependencies; "cloud-native" used as a substitute for "AWS-only."

### 4.3 Defense Contractor IT / Security Lead

- **Examples:** systems engineering lead at a prime or sub, CISO, security architect at a defense integrator.
- **Cares about:** zero-trust architecture, air-gapped deployability, CMMC / NIST 800-171 / FedRAMP alignment, supply-chain risk.
- **Reads:** zero-trust, identity, observability, and infrastructure copy.
- **Pet peeves:** vague compliance claims without architectural evidence.

### 4.4 Federal Contracting Officer / Procurement

- **Examples:** contracting officer, procurement specialist, capture lead at a prime considering a sub.
- **Cares about:** past performance, key personnel, capability statements, RFP-able language they can paste.
- **Reads:** Services pages, capability bullets, and any "Defense & Government Relevance" language. Wants RFP-ready phrasing.
- **Pet peeves:** marketing fluff in capability statements; superlatives without evidence.

> **Note on persona scope.** These four are extracted from the existing content's stated audience ("defense contractors and government organizations") and WL's federal past-performance footprint. The personas do *not* cover commercial / enterprise / SMB buyers. See §11.7 — open question on whether the brand pursues commercial work and how the voice would shift.

---

## 5. Vocabulary

### 5.1 Preferred terms

These appear repeatedly in `docs/CONTENT.md`. Reuse them — consistency builds the voice.

| Term | Use it for |
|---|---|
| **Mission impact** | The named section on every Product/Service page. Also a noun used inline. |
| **Mission-critical** | Adjective for systems, information, communications, workflows. |
| **Mission resilience** | The capacity to keep executing under adverse conditions. |
| **Mission execution** | The act of getting mission outcomes done. Preferred over "operations" alone. |
| **Mission velocity** | The speed of mission throughput (used adjacent to security). |
| **Mission readiness** | State of being prepared to execute. |
| **Sovereignty / sovereign** | Customer control of data, infrastructure, identity. |
| **Operational resilience / continuity / tempo / superiority** | Outcome-level descriptions of what the customer gains. |
| **Decision velocity / decision cycles / decision speed** | The cognitive output of good information systems. |
| **Zero-trust** | Always hyphenated, lowercase except when in a product name (e.g., *Fortis Zero-Trust Identity Platform*). |
| **Air-gapped** | Hyphenated. Used in deployment options. |
| **On-premises / on-prem** | Hyphenated. "On-prem" is acceptable shorthand inline; "on-premises" preferred in headings and meta descriptions. |
| **Defense and government** | Canonical audience phrase. Sometimes "defense contractors and government organizations" for the longer form. |
| **Defense-grade** | Quality bar adjective. Reserve for compliance / security claims. |
| **Information superiority** | Borrowed from defense doctrine. Used sparingly. |
| **Situational awareness** | Outcome of observability and intelligence offerings. |
| **Force multiplier** | Small capability, outsized mission leverage. |
| **Enhance / streamline / facilitate** | The three preferred outcome verbs in CONTENT.md. Use them. |
| **Enterprise-grade** | Quality marker. Acceptable. |
| **Headless** | Used without explanation in technical copy; explain in marketing copy ("a headless architecture — where content is managed centrally and delivered to any frontend"). |
| **Infrastructure-as-Code** | Capitalized as in CONTENT.md. Spell out on first use in marketing copy. |

### 5.2 Terms to avoid

These conflict with the established voice. Either replace with a preferred term from §5.1 or restructure.

| Term | Why to avoid | Replace with |
|---|---|---|
| Delight, magical, amazing, incredible | Consumer-SaaS hype; reads as unserious to federal buyers. | "Enhance," "streamline," or a concrete outcome. |
| 10x, supercharge, turbocharge | Same. | Specific multiplier with evidence, or omit. |
| Revolutionary, game-changing, disruptive, next-gen | Hype without substance. | Describe the actual change. |
| Cutting-edge, bleeding-edge | Hype + suggests instability. | "Modern" or describe the specific capability. |
| Best-in-class, world-class | Unsubstantiated superlative. | Specific evidence or omit. |
| Ninja, rockstar, wizard | Casual tech-startup register. | Use the actual role title. |
| AWS, Azure, GCP (as positive framing) | Conflicts with sovereignty pillar (§3.2) — implies hyperscaler dependence. Reference only when contrasting against sovereign alternatives. | Reframe around private cloud / on-prem / hybrid. |
| Cloud-native (as a sole positioning claim) | Ambiguous and often means "hyperscaler-locked." | "Cloud-portable," "sovereign cloud," "private-cloud-native" where accurate. |
| "Solutions" (used vaguely) | Filler. | A specific noun: "platform," "service," "capability." (Note: "solutions" as a real noun for a product line is fine — *Defense Technology Integration solutions*.) |
| Synergy, leverage (as a verb) | Corporate jargon without meaning. | A concrete verb. |
| "Empower" (used hollow) | Acceptable when followed by a concrete outcome; avoid when not. | Either follow with the outcome or replace with "enable." |
| "Just," "simply," "easily" (as adjectives in body copy) | Undercuts seriousness; the work is rarely simple. | Omit. |
| Emoji in body copy | The published voice is restrained; emoji breaks register. (Note: emoji used as visual section markers in internal docs like `CONTENT_TYPES_GUIDE.md` is fine — that is *internal* editorial UX, not public voice.) | Omit. |
| **"Demo"** (as a noun or verb in any customer-facing copy) | Reads SaaS-sales. Federal and mission-driven buyers expect substance, not a sales motion. | "Working session," "technical walk-through," "live deployment review." |

### 5.3 Acronyms

Spell-out behavior depends on **two axes**: the acronym category, AND the page's primary audience (per §6.11 audience taxonomy).

**Acronym categories:**
- **Industry-standard technical** — *CMS, VPN, AI/ML, KPI, SSO, API, REST, JSON, SQL, HTTP, TLS*. Never expanded.
- **Engineering-niche / specialist** — *SBOM, OIDC, ATO, mTLS, OPA, RBAC, CSPM, EDR, SBOM, IaC*. Expand based on audience (see below).
- **Regulatory / compliance** — *FedRAMP, CMMC, NIST 800-171, FISMA, HIPAA, SOC 2, GDPR, Section 508*. Expand based on audience.

**Audience-layered spell-out rule:**

| Page audience | Industry-standard | Engineering-niche | Regulatory |
|---|---|---|---|
| Federal CO + program-manager personas | Skip | Skip (assume technical fluency) | Skip (assume regulatory fluency) |
| Higher ed / nonprofit / commercial / regulated SaaS | Skip | **Spell out on first use** | **Spell out on first use** |
| Mixed audience | Skip | Spell out on first use (lowest-friction default) | Spell out on first use |

Examples:
- Federal capability statement: "Our FedRAMP-authorized deployment supports SBOM generation per EO 14028." — no expansions.
- Higher-ed Solution page: "FedRAMP (Federal Risk and Authorization Management Program) authorization isn't required for higher-ed deployments, but the underlying controls — SBOM (Software Bill of Materials) provenance, NIST 800-171–aligned access controls — are equally valuable for audit readiness." — expansions on first use.

Resolved 2026-05-27 (§11.2).

---

## 6. Style rules

### 6.1 Sentence and paragraph structure

- **Sentence length.** Mid-to-long (15–35 words) is the default. Short sentences are reserved for emphasis after a longer one.
- **Paragraph length.** 2–4 sentences. Single-sentence paragraphs are acceptable as the opening line of a Mission Impact block.
- **Opening sentence.** Set context, not the company. Compare:
  - ✅ "In high-stakes environments where mission success depends on technology you truly control, the Sovereign Infrastructure Platform delivers the foundation you need." (CONTENT.md)
  - ❌ "Wilkes & Liberty is excited to announce..."

### 6.2 Active vs. passive voice

- Active by default. Use "We design, deploy, and continuously manage..." (CONTENT.md, Service 1) rather than "Sovereign infrastructure environments are designed, deployed, and managed by us."
- Passive is acceptable when the actor is genuinely irrelevant or unknown (e.g., "Backups are retained for 90 days").

### 6.3 First-person plural ("we") — canonical

- **Use "we"** as WL's voice across all public-facing copy: Products, Services, Solutions, capability statement, case studies, landing pages, and most articles. "We" implies organizational capability, which is honest when subcontractor and partner relationships extend the principal's reach for federal delivery.
- **Use "I" (or "Jeremy Cerda")** only in personal-byline thought-leadership articles or founder-narrative posts where the principal speaks in his own voice. These are the exception, not the default.
- WL is currently a single-principal LLC (memory: `user_identity.md`); "we" is forward-looking and inclusive of partners and future hires, not a fabrication.
- Resolved 2026-05-27.

### 6.4 Capitalization

- **Product names:** Title Case, including the descriptive part (*Sovereign Infrastructure Platform*, *Liberty Headless CMS Platform*, *Fortis Zero-Trust Identity Platform*, *Apex Secure Data Platform*, *Vigilance Mission Observability Suite*).
- **Service names:** Title Case (*Private Infrastructure Engineering & Managed Operations*).
- **Section headings within page copy** (*Key Capabilities*, *Mission Impact*): Title Case, bolded.
- **"Mission impact"** as an inline noun phrase: lowercase. As a section heading: Title Case ("Mission Impact").
- **"Zero-trust"** as a descriptor: lowercase. As part of a product name (*Fortis Zero-Trust Identity Platform*): Title Case.
- **"Sovereignty," "sovereign":** lowercase.

#### "Mission" density rule (outside Product / Service pages)

Product and Service pages saturate "mission" because Mission Impact is a required field. **All other content types dial back to 1–2 mentions per piece** — typically once in the opening hook or closing graf, optionally a second mention where it lands naturally. Use the messaging pillars from §3 implicitly (with concrete outcome language) rather than vocabulary-stuffing.

Applies to: Articles, case studies, blog posts, resources, landing pages, Solution pages targeting commercial audiences (federal-audience Solutions retain higher density per §6.12), press releases, capability statement narrative sections that aren't named "Mission Impact."

Resolved 2026-05-27 (§11.4).

### 6.5 Punctuation

- **Em dashes (—):** Used liberally for parenthetical asides and emphasis. CONTENT.md uses spaces around them: "purpose-built to **enhance operational resilience**, **streamline infrastructure management**, and **facilitate uninterrupted mission execution**." Continue with spaces around em dashes.
- **Serial (Oxford) comma:** Used in CONTENT.md ("maintain control, reduce risk, and focus resources"). Required.
- **Bold for emphasis:** CONTENT.md uses `**bold**` inline to call out specific outcome phrases (e.g., **enhance operational resilience**). Use sparingly — 0–3 bolded phrases per page section. Never bold whole sentences.
- **Quotation marks:** Straight double quotes in source Markdown; rendered as smart quotes in the published frontend.

### 6.6 SEO title format

CONTENT.md is consistent:

> `[Product or Service Name] | [Tagline]`

Examples:
- "Sovereign Infrastructure Platform | Mission-Controlled Technology"
- "Liberty Headless CMS Platform | Secure Content Sovereignty"
- "Vigilance Mission Observability Suite | Real-Time System Intelligence"

Pipe (`|`) separator with a space on each side. Tagline is 3–6 words, Title Case, anchored on a messaging pillar.

### 6.7 Meta description format

- **Length:** 150–160 characters (SEO preview truncates beyond ~155).
- **Opens with an imperative verb** that names the action the reader will take or the outcome they'll get.
- **Ends with a period.** Always. Reads as a complete sentence; matches the formal register. Resolved 2026-05-27 (§11.5).
- One or two sentences max. Prefer one well-built sentence; allow two when the first is the hook and the second names the outcome.

Example (one-sentence):
> "Deploy secure, sovereign infrastructure anywhere — built to enhance mission resilience, eliminate vendor dependency, and give defense and government organizations full control over their technology environment."

Example (two-sentence):
> "Deploy secure, sovereign infrastructure anywhere. Designed to enhance mission resilience, eliminate vendor dependency, and give your organization full control over its technology environment."

### 6.8 Page structure (Product / Service)

The structural pattern from CONTENT.md is:

1. **Context-setter** (one sentence, sometimes two). Frames the mission tension the product resolves.
2. **Product description** (one paragraph). What it is, what it does at the level a non-engineer can repeat.
3. **Key Capabilities** (bulleted list, 4–6 items). Each capability is a noun phrase, 4–10 words.
4. **Mission Impact** (one paragraph). The named section. Ties back to mission outcomes for defense and government.

Service pages compress this — sometimes only a single paragraph plus an optional Mission Impact block.

### 6.9 English variant — US English (canonical)

- **US English** is canonical across all public-facing copy: `optimize`, `organization`, `customize`, `defense`, `analyze`, `program` (verb), `behavior`, `color`. Not `optimise` / `organisation` / `defence` / `analyse` / `programme` / `behaviour` / `colour`.
- Date format: ISO `2026-05-27` in technical/internal docs; `May 27, 2026` in body prose.
- Number/currency format: US conventions (`$1,250.00`, not `1.250,00 €`).
- The Spanish and Russian translation source files in `translations/` are sibling-translated from the US English source (per `README.md`); they are not separately authored.
- Resolved 2026-05-27.

### 6.10 Calls to action (CTAs)

CTAs match the brand register: serious, direct, no consumer-SaaS reflexes. One Primary CTA per page; one Secondary; tertiary inline links as needed. Consistency across same-type pages is more important than per-page creativity.

**Primary CTA — by audience:**

| Audience | Primary CTA wording |
|---|---|
| Federal / defense pages (Federal landing, Capability Statement, federal-targeted Solutions) | **Request a capability brief** |
| All other federal-adjacent touchpoints (general "contact" surface for federal buyers) | **Open an inquiry** |
| Commercial Solution pages (higher ed, mission-driven nonprofits, privacy-conscious B2B, regulated industries) | **Open a conversation** |
| Product / Service pages (any audience) | **Schedule a working session** |
| RFP / proposal touchpoints | **Request past performance** |

**Secondary CTA — alternate paths for buyers not ready for a meeting:**

| Context | Secondary CTA wording |
|---|---|
| Federal pages | **Download the capability statement** (the 1–2 page PDF) |
| Product pages | **Read the architecture overview** |
| Service pages | **Read past performance** |
| Solution pages (commercial) | **Download our capabilities overview** |
| Case studies | **Read the case study** |

**Important distinction (federal-procurement primitives):**
- **Capability statement** = the 1–2 page PDF artifact (universal federal-procurement deliverable; lives at a stable URL).
- **Capability brief** = the meeting / working session walking a customer through the statement and how it maps to their need.
- The two are complementary, not redundant. Federal buyers know the difference. Use both on federal landing pages — brief as the Primary CTA, statement as the Secondary.

**Tertiary / inline link CTAs:**

- **Learn more about [Service]**
- **See how it deploys**
- **Read the technical deep-dive**
- **Contact Jeremy directly** (used where single-principal honesty is the right signal — e.g., federal buyer page footer)

**Rules:**

1. **Imperative + outcome** preferred over imperative-only. "See how Fortis enforces zero-trust" beats "Get started."
2. **No exclamation marks.** Ever.
3. **No urgency language.** No "limited time," "act now," "today only."
4. **No "free," "freemium," "no credit card required."** Consumer-SaaS register.
5. **Sentence case** for buttons and links. Product names retain Title Case when embedded.
6. **No "click here" / "this link" / "find out more."** Always verb + meaningful object.
7. **CTA consistency across same-type pages.** All Product pages use the same Primary; all Service pages use the same Primary. Pattern recognition is a trust signal.
8. **"Demo" is banned** (see §5.2). Replace with "working session," "technical walk-through," or "live deployment review."
9. **No "let's talk"** — reads improvised. Use the canonical CTAs above.

Resolved 2026-05-27.

### 6.11 Pronoun for the customer

**Default: "you" / "your"** (direct second-person address). This is already de facto across CONTENT.md.

**Elevation by audience** (not by content type):

- **Federal / defense pages:** layer in "your organization," "your mission," "your contracting team," "your evaluators," "your program office" when the addressee is the institutional decision-maker. Match the register of capability statements and RFP language.
- **Commercial / mission-driven pages:** keep the default direct address — "your team," "your platform," "your users," "your members," "your audit needs." Same warmth as the default; no institutional layering.
- **Mixed-audience pages** (rare): default to the buyer's pronoun ("you"); layer "your team" / "your mission" only where naturally fitting. Do not stack both registers in the same paragraph.

**Banned:**

- "The customer," "the client" — third-person abstraction reads cold. The buyer is right here.
- "Users" as the buyer noun — depersonalizes. ("Users" can describe end users of the deployed system; never the buyer.)
- "You guys" — too casual.
- "The prospect," "the lead" — sales-CRM language; never in customer-facing copy.

**Pronoun-audience binding lives in page taxonomy.** Each page declares its primary audience (Federal Civilian, Higher Ed, Mission-Driven Nonprofit, etc.) in its persona / audience taxonomy field; the voice register follows from that.

Resolved 2026-05-27.

### 6.12 Audience adaptation (federal vs. commercial register)

The brand voice attributes from §2 (mission-anchored, confident-restrained, sovereignty-forward, technically literate, outcome-led) are **identical across all audiences**. What adapts is vocabulary density and which messaging pillars (§3) are surfaced explicitly vs. implied.

**On federal / defense pages**, the voice operates at full saturation:
- "Mission" framing appears in every section, including the named Mission Impact block.
- "Defense and government organizations" / "federal civilian agencies" appears as the canonical audience phrasing.
- Sovereignty pillar is framed around **operational continuity in contested environments**, **air-gapped deployment**, **independence from hyperscaler dependencies**.
- Federal-procurement primitives appear by name: *capability statement*, *capability brief*, *past performance*, *contracting officer*, *ATO sponsor*, *FedRAMP*, *CMMC*.

**On commercial / mission-driven pages** (higher ed, nonprofits, privacy-conscious B2B SaaS, regulated industries), the voice is the same — only the vocabulary substitutes:

| Federal phrasing | Commercial substitute |
|---|---|
| "defense and government organizations" | The specific vertical: "higher ed institutions," "mission-driven nonprofits," "regulated SaaS platforms," "legal-tech and fintech teams" |
| "mission" (saturated) | "mission" appears 1–2 times per page max (see §6.4 mission density) — usually once in the hero context-setter, optionally once in the closing. Use the messaging pillars implicitly elsewhere. |
| "operational continuity in contested environments" | "operational continuity through outages, vendor changes, and audit cycles" |
| "sovereignty" framed as defense-independence | "sovereignty" framed as **data residency**, **regulatory independence**, **no hyperscaler lock-in**, **portable across CSPs** |
| "in high-stakes environments where mission success depends on technology you truly control" | "in environments where data sensitivity, audit posture, and vendor lock-in are real business risks" |
| "contracting officer" / "program manager" persona language | Vertical-specific buyer language: "web services director," "head of platform," "executive director," "CISO" |

**Do not write a separate commercial brand voice.** Treat commercial copy as the same voice with a vocabulary substitution layer. A federal contracting officer landing on a commercial Solution page should still recognize WL by tone; a higher-ed CIO landing on a federal page should not bounce because the language is unreadable.

**Page-level audience flag** (taxonomy field) is the canonical signal. The substitution rules above apply automatically to that page based on its declared audience.

Resolved 2026-05-27 (§11.7).

### 6.13 Customer quotes in case studies

Customer voices are kept **verbatim**. Authenticity trumps voice consistency.

**Allowed edits** (these only):
1. Remove obvious transcription artifacts: "uh," "you know," repeated words, false starts.
2. **Tighten if a quote runs >40 words** and a shorter version preserves the meaning. Prefer two short quotes over one long one.
3. Add bracketed clarifications `[Drupal]`, `[FedRAMP]`, `[the migration]` where pronouns or context would confuse a reader who didn't witness the interview.

**Never:**
- Paraphrase a customer to match brand voice.
- Insert WL vocabulary ("sovereignty," "mission impact," "outcome-led") into a customer's mouth.
- Combine separate sentences into a synthetic single quote.
- Edit for grammar unless the original is incomprehensible.

The voice mismatch between brand and customer is the authenticity signal. Federal evaluators read manicured customer quotes as marketing-manufactured. Real quotes — slightly rough, in the customer's own register — carry more weight.

Resolved 2026-05-27 (§11.9).

### 6.14 Company name — usage hierarchy

How to name the company across contexts:

| Form | When to use |
|---|---|
| **Wilkes & Liberty, LLC** | Formal / legal / capability statement / press release / executed contracts / federal-procurement responses. First mention in any legal or formal context. |
| **Wilkes & Liberty** | All other public-facing copy: website body, articles, marketing collateral, social media, case studies, Solution pages, public capability narratives. Second mention onward in formal docs. |
| **WL** | **Internal docs only** — this brand voice guide, AGENTS.md / CLAUDE.md / README.md inside repos, runbooks, compliance docs, deployment guides. Never customer-facing. |
| **"Wilkes Liberty"** (no ampersand) | Accepted informal alias when the ampersand causes URL or filename friction (e.g., domain `wilkesliberty.com`, GitHub org `Wilkes-Liberty`). Acceptable in domain/path contexts; do not use as a substitute for the formal name in copy. |

**Banned form:** `W&L` (ampersand abbreviation) — retired 2026-05-27. Was the previous internal shorthand; replaced by `WL`. The ampersand version now appears nowhere — internal docs use `WL`, public copy uses `Wilkes & Liberty` (with ampersand, full name only).

**Practical application:**
- A capability statement opens with "Wilkes & Liberty, LLC" and uses "Wilkes & Liberty" thereafter.
- An article on the public site uses "Wilkes & Liberty" throughout — never "WL."
- A docs README at `webcms/docs/` may use "WL" once introduced (it's an internal doc).

Resolved 2026-05-27 (§11.12).

### 6.15 Product naming convention

**Pattern (canonical):** Every product gets a **proper name + descriptive suffix**.

Examples of the pattern:
- *Liberty Headless CMS Platform* (proper name: Liberty)
- *Fortis Zero-Trust Identity Platform* (proper name: Fortis) — **name TBD, see naming queue below**
- *Apex Secure Data Platform* (proper name: Apex) — **name TBD, see naming queue below**
- *Vigilance Mission Observability Suite* (proper name: Vigilance) — **name TBD, see naming queue below**

**Existing pure-descriptor products to retrofit:**
- *Sovereign Infrastructure Platform* → needs proper name (TBD)
- *Enterprise Search Platform* → needs proper name (TBD)

**Why the pattern:**
- Proper names are trademarkable, memorable in conversation ("our Bastion platform" vs. "our Sovereign Infrastructure Platform"), and carry brand equity over time.
- The descriptive suffix keeps the name navigable for federal evaluators who scan for capability category.

**Naming session pending:** All current proper names (*Fortis*, *Apex*, *Vigilance*, *Liberty*) are flagged as **TBD pending dedicated naming session**. Liberty is the most defensible of the current set; the others may be replaced. Until that session lands, current names remain in CONTENT.md as placeholders.

Resolved 2026-05-27 (§11.3) — pattern confirmed; specific names deferred to naming session.

---

## 7. Tone-by-context matrix

How the core voice (§2) adjusts across content types. All cells inherit the §2 attributes; this matrix shows the *delta*.

| Content type | Formality | Sentence length | Mission framing | Persona lean | Notes |
|---|---|---|---|---|---|
| **Product page** | High | Mid-long | Required (named section) | Mission Owner + CIO | Lead with context tension, end with mission impact. |
| **Service page** | High | Mid | Required (named section, even if brief) | Contracting Officer + IT Lead | Tighter than Product. Opens "We [verb]..." |
| **Capability statement / RFP language** | Highest | Mid-long | Heavy | Contracting Officer | Maximum density of preferred vocabulary. Past performance language. |
| **Article (thought leadership)** | High | Varied | Moderate (not every paragraph) | Mission Owner + CIO | Can carry a single argument over 600–1200 words. "We" voice still applies. |
| **Article (news / press release)** | High | Short-mid | Light | All four personas | Inverted-pyramid; date and fact at top. Mission framing in the closing graf. |
| **Case study** | High | Mid | Heavy (in Results / Mission Impact framing) | Contracting Officer + Mission Owner | Follow CONTENT_TYPES_GUIDE.md §4 structure: Challenge → Solution → Results → Metrics. |
| **Landing page** | High | Short-mid | Moderate | Whichever persona the campaign targets | Single conversion goal. Subhead carries the pillar; body proves it. |
| **Career posting** | Medium-high | Mid | Light | Candidates (out of persona scope) | Voice softens — see §11.6 for open question. |
| **Person bio** | Medium-high | Mid | Light | All four personas | Third person. Lead with role + credentials. |
| **Resource (whitepaper, guide)** | High | Long | Heavy | CIO + Defense IT Lead | Permission to go deep on architecture; still outcome-led at the section level. |
| **Event** | Medium-high | Short-mid | Moderate | Whichever persona the event targets | Logistics blocks are utilitarian; framing copy follows the voice. |
| **Social post (LinkedIn)** | Medium | Short | Moderate | Mission Owner + CIO | First sentence carries the pillar; no consumer-style emoji-as-hook. |

---

## 8. "We are / We are not" — fast positioning check

| ✅ We are | ❌ We are not |
|---|---|
| A specialist firm anchored on mission-critical, sovereign technology. | A generalist consultancy. |
| A high-leverage small vendor — a force multiplier. | A hyperscaler reseller or systems integrator at scale. |
| Federal / defense-aware by default. | A commercial-first SaaS company. (See §11.7.) |
| Confident, technically literate, restrained. | Hypey, casual, or consumer-startup-flavored. |
| Outcome-led — every claim ties to mission impact. | Feature-led. |
| Sovereignty-forward in posture and architecture. | Cloud-native-as-AWS-only. |
| Direct and declarative. | Hedging, salesy, or jargon-stuffing without substance. |

---

## 9. Do / Don't — concrete before/after

Every example below is anchored on `docs/CONTENT.md`. The "Don't" column is a rewrite that *violates* the established voice; the "Do" column is the source text.

### 9.1 Opening sentence — set context, not yourself

| ❌ Don't (off-voice rewrite) | ✅ Do (CONTENT.md source) |
|---|---|
| "Wilkes & Liberty is proud to introduce the Sovereign Infrastructure Platform, our exciting new infrastructure-as-code offering." | "In high-stakes environments where mission success depends on technology you truly control, the Sovereign Infrastructure Platform delivers the foundation you need." (Product 1) |

**Why:** The opening sentence does work — it establishes the mission context the customer is in. Announcing the company before the customer's context fails the test.

### 9.2 Capabilities — noun phrases tied to outcomes

| ❌ Don't | ✅ Do (CONTENT.md, Product 1) |
|---|---|
| "Super-fast deployments that will blow you away" | "Automated deployment and configuration management" |
| "Best-in-class security" | "Secure network segmentation and zero-trust architecture" |
| "We back up your data magically" | "Encrypted backup systems with long-term retention" |

**Why:** Capability bullets are nouns. They describe what the system *is or does*, not how impressive it is.

### 9.3 Mission impact — outcome, not feature

| ❌ Don't | ✅ Do (CONTENT.md, Product 6 — Vigilance) |
|---|---|
| "Vigilance has tons of cool dashboards and integrates with everything." | "We streamline operations by giving your teams immediate insight into system health, allowing faster response to issues and greater confidence that your infrastructure will support — rather than hinder — mission objectives." |

**Why:** Mission Impact is where the customer learns *what changes for them*. Feature lists belong in Key Capabilities, not here.

### 9.4 Security framing — pair with velocity

| ❌ Don't | ✅ Do (CONTENT.md, Product 4 — Fortis) |
|---|---|
| "Lock down your network with our paranoid zero-trust architecture." | "Security and usability must work together in support of the mission. The Fortis Zero-Trust Identity Platform provides enterprise-grade single sign-on and access control while enforcing strict security standards." |

**Why:** Security pitched as friction is a non-starter for mission owners. Always pair the security claim with what it *enables*.

### 9.5 Service descriptions — open with "We [verb]..."

| ❌ Don't | ✅ Do (CONTENT.md, Service 1) |
|---|---|
| "Our team has decades of experience helping clients with infrastructure projects." | "We design, deploy, and continuously manage sovereign infrastructure environments tailored to the unique requirements of defense contractors and government organizations." |

**Why:** Service pages open with action — what we do — not credentials. Credentials belong in capability statements and Person pages.

### 9.6 Force multiplier framing

| ❌ Don't | ✅ Do (CONTENT.md, Service 7) |
|---|---|
| "We write a lot of custom software." | "Our custom solutions become force multipliers that increase efficiency and reduce reliance on fragmented commercial tools." |

**Why:** "Force multiplier" is a doctrinal phrase that signals fluency with the audience's vocabulary. Use it.

### 9.7 Sovereignty pitch — concrete, not abstract

| ❌ Don't | ✅ Do (CONTENT.md, Product 1) |
|---|---|
| "Our platform respects your data and privacy." | "By removing reliance on external cloud providers and giving you full sovereignty over your infrastructure, we help defense contractors and government organizations maintain control, reduce risk, and focus resources on core mission objectives rather than technology maintenance." |

**Why:** "Respects your data" is a consumer-grade claim. Sovereignty means: no external cloud provider dependency, customer-controlled, deployable anywhere.

### 9.8 Emphasis — bold for outcome phrases, not whole sentences

| ❌ Don't | ✅ Do (CONTENT.md, Product 1) |
|---|---|
| **"This is the best infrastructure platform on the market, with all the features you need!"** | "purpose-built to **enhance operational resilience**, **streamline infrastructure management**, and **facilitate uninterrupted mission execution**." |

**Why:** Bold is for outcome phrases inside a sentence. Bolding a whole sentence breaks the visual rhythm and reads as shouting.

---

## 10. Quick-reference cheatsheet

Use this when self-editing a draft.

- [ ] Does the opening sentence set the customer's mission context (not announce WL)?
- [ ] Is at least one of the five messaging pillars (§3) explicit in the copy?
- [ ] Are the Key Capabilities bullets noun phrases, not feature brags?
- [ ] Does the Mission Impact paragraph describe an outcome, not a feature?
- [ ] Are security claims paired with a velocity / enablement claim?
- [ ] No banned vocabulary (§5.2)?
- [ ] Preferred verbs (*enhance, streamline, facilitate*) used where appropriate?
- [ ] Active voice as default?
- [ ] Em dashes spaced; serial commas used; no contractions in body copy?
- [ ] SEO title in `Name | Tagline` format?
- [ ] Meta description 150–160 chars, opens with imperative verb?
- [ ] No emoji in body copy?

---

## 11. Open questions for Jeremy

These are the calls v1.0 could not make from the source material alone. Each one will sharpen the voice once decided.

### 11.6 Career posting voice softening — **PARKED**

**Status:** Decision deferred until first job posting is drafted. No active career-posting copy exists, so the choice has no live impact.

**Default if/when needed:** Hold the formal voice on cleared / federal-facing roles; soften noticeably on commercial / engineering roles. Revisit this question concretely when the first posting is written.

### 11.X — Product / Service / Solution naming session (forthcoming)

§11.3 (product naming convention) is resolved → see §6.15: every product gets a proper name + descriptive suffix. **Specific proper names are flagged TBD pending a dedicated naming session.** Current names (*Fortis*, *Apex*, *Vigilance*, *Sovereign Infrastructure*, *Enterprise Search*) are placeholders and will be revisited together with Service and Solution naming as a single sweep.

---

## 12. Confidence notes (transparency on what v1.0 knows)

| Section | Confidence | Why |
|---|---|---|
| §2 Voice attributes | High | Strong, consistent signal across 16 pieces of long-form copy in CONTENT.md. |
| §3 Messaging pillars | High | Each pillar appears in 5+ CONTENT.md examples. |
| §4 Personas | Medium | Personas extracted from stated audience ("defense contractors and government organizations") plus memory-confirmed federal footprint. Not validated against actual customer interviews. |
| §5 Vocabulary | High (preferred) / Medium (avoid) | Preferred list directly extracted. Avoid list is inferred from the *absence* of consumer-startup register — a strong signal, but not an explicit prohibition by Jeremy. |
| §6 Style rules | High | Patterns are visually consistent in CONTENT.md. Punctuation has minor inconsistencies (§11.5). |
| §7 Tone-by-context matrix | Medium | Only Product, Service, and Article contexts have actual examples in CONTENT.md. Other rows are extrapolated from the voice attributes plus the editorial intent in `CONTENT_TYPES_GUIDE.md`. |
| §8 We are / We are not | High | Synthesized directly from §2–§3. |
| §9 Do / Don't | High | All "Do" examples are direct quotes from CONTENT.md. |
| §11 Open questions | n/a | These exist *because* the source material did not settle them. |

---

## 13. Maintenance

- **Re-anchor on new evidence.** When new long-form copy ships (a major article series, a published case study, a sales deck), re-read this doc and revise sections that drift.
- **Resolve open questions in §11 incrementally.** As Jeremy makes calls, move them out of §11 and into the relevant section, deleting the question.
- **Version this doc.** When a substantive change lands, bump the version number at the top and add a one-line entry under "Last updated."
- **Source files** (not to be edited by this guide): `docs/CONTENT.md`, `docs/CONTENT_TYPES_GUIDE.md`, `docs/FIELD_REFERENCE.md`. This guide is downstream of those.
