# Sentinel — Product Brief & Content Strategy

**Status:** Draft — for review before adding to CONTENT.md and live CMS
**Date:** 2026-05-29
**Author:** Jeremy Michael Cerda

---

## 1. What Sentinel Is

Sentinel is the AI-to-Drupal interface layer built on the Model Context Protocol (MCP). It consists of two open-source components that work together:

| Component | Repo | Ecosystem | License |
|-----------|------|-----------|---------|
| `drupal-mcp-server` | github.com/wilkes-liberty/drupal-mcp-server | Node.js / npm | MIT |
| `mcp_sentinel` | drupal.org/project/mcp_sentinel | Drupal 10.3+ / 11 | GPL |

Together they give any MCP-compatible AI client (Claude Desktop, Claude Code, Cowork, third-party agents) governed, auditable, role-constrained access to a Drupal site's full content and administrative surface.

The connector (`drupal-mcp-server`) provides **51 MCP tools** across 8 modules: content CRUD, taxonomy, users, media, GraphQL, generic entity access, audit reporting, and a Drush SSH bridge. The module (`mcp_sentinel`) adds the enterprise governance layer Drupal-side: security presets, entity access controls, field-level PII redaction, full audit logging, content locks, and HMAC-signed webhooks.

---

## 2. Product Positioning Within the W&L Stack

Sentinel slots naturally alongside **Keel CMS Platform** — it is the AI-native interface to a sovereign Drupal deployment, and Keel is that deployment.

| Layer | Platform |
|-------|----------|
| Infrastructure | Sabal |
| CMS (content operations) | **Keel** |
| **AI-native CMS interface** | **Sentinel** |
| Search | Alidade |
| Identity | Squawk |
| Data | Manifest |
| Observability | Lighthouse |
| Software delivery | Coquina |

Sentinel can be deployed independently against any Drupal site, but its natural home is alongside a Keel deployment. For defense and government customers, the combination gives them both a sovereign content platform and an auditable AI operations layer — no commercial SaaS AI tools touching their content.

---

## 3. Why "Sentinel" Fits the W&L Naming Convention

The existing platform names are drawn from nautical, geographic, or mission-operational vocabulary:
- **Sabal** — Florida native palm; resilience
- **Keel** — structural spine of a ship
- **Alidade** — navigation sighting instrument
- **Squawk** — aviation transponder code
- **Manifest** — ship's cargo inventory
- **Lighthouse** — navigation beacon
- **Coquina** — Florida limestone; resilience through composition

**Sentinel** (a guard standing watch) fits this vocabulary precisely. The module already carries the name; the platform name follows naturally. It connotes exactly what the product does: standing watch over AI access to your content.

---

## 4. Target Personas

**Primary:**
- Drupal agencies and integrators building AI-augmented editorial workflows
- Federal web programs running Drupal who need auditability for AI operations
- Defense contractors using Keel CMS who want AI to accelerate content operations without losing control
- Higher education institutions with large Drupal content estates (DotEDU customers)

**Secondary:**
- Site builders and developers looking to automate bulk Drupal operations via Claude or other AI agents
- DevOps/platform engineers who need AI access to Drush operations without exposing SSH credentials broadly
- Content teams at any organization needing AI-assisted publishing without manual copy-paste between tools

---

## 5. Differentiators vs. WordPress MCP (the obvious comparison)

The README already documents this comparison. The headline: every enterprise governance feature (security presets, PII redaction, entity allowlists, audit logging, content locks, multi-site) is absent from WordPress MCP and present in Sentinel.

For defense and government audiences, the framing is different: this is not "AI that makes your CMS faster" — it is "AI access to your content systems that meets the same zero-trust and auditability standards as the rest of your stack."

---

## 6. Open Source Strategy

Both components are already open-source and designed for community adoption. The value W&L captures is:

1. **Implementation services** — deploying, configuring, and integrating Sentinel into existing Drupal environments
2. **Keel + Sentinel bundled engagements** — sovereign headless CMS + AI governance in one scoped engagement
3. **Enterprise hardening** — custom security presets, integration with Squawk (Keycloak SSO), audit log forwarding to SIEM systems
4. **Support and sustainment** — ongoing module maintenance, Drupal core compatibility, connector updates

This mirrors the pattern used by companies like Acquia (Drupal commercial layer), Lullabot, and Palantir.net — contribute to open source, sell implementation and enterprise expertise.

---

## 7. Relationship to Existing Services

- **Headless CMS Implementation** — Sentinel is the AI interface layer; this service deploys it
- **Integration Engineering** — Sentinel is itself an integration: AI ↔ Drupal
- **AI Integration & Machine Learning Services** — Sentinel is a concrete deliverable within this service

---

## 8. Potential Solution Tie-ins

Consider a future Solution that packages Keel + Sentinel for a specific outcome:

**"Meridian" — AI-Accelerated Federal Content Operations**
*(Keel CMS Platform + Sentinel AI-Native CMS Platform)*
For federal civilian agencies and defense programs that need both a sovereign Drupal CMS and auditable AI-assisted content operations.

This follows the existing pattern: DotGov bundles Keel + Alidade + Squawk for federal civilian web. Meridian would bundle Keel + Sentinel for AI-native federal content.

---

## 9. CONTENT.md Platform Entry (Ready to Paste)

See section below — formatted to match the existing 7 platform entries exactly.

---
---

# CONTENT.md Addition — Platform Entry

*Paste this after the Coquina Software Factory Platform entry and before the Services section.*

---

### 8. Sentinel AI-Native CMS Platform

**SEO Title:** Sentinel AI-Native CMS Platform | Governed AI Access for Drupal

**Meta Description:** Open-source MCP connector and Drupal governance module that gives AI agents secure, auditable, role-constrained access to Drupal content operations — with zero-trust access controls, full audit logging, and content locks.

**Summary:** A sovereign AI-to-CMS interface layer built on the Model Context Protocol — giving AI agents governed, auditable access to Drupal content operations without sacrificing control, auditability, or data sovereignty.

**Full Page Copy:**

#### Sentinel AI-Native CMS Platform

Drupal powers some of the most consequential content environments in government and defense — federal health platforms, Inspector General oversight sites, multi-agency publishing networks. The teams that operate these platforms need AI to accelerate content operations: bulk audits, SEO remediation, content creation, taxonomy cleanup, workflow management. What they cannot accept is AI access that is ungoverned, unaudited, or incompatible with the zero-trust principles that govern the rest of their stack.

The Sentinel AI-Native CMS Platform provides the governed interface between AI agents and Drupal that defense and government content teams require. Built on the Model Context Protocol — the open standard for AI-to-system connectivity — Sentinel exposes Drupal's full content and administrative surface to AI agents through a structured, permission-controlled, fully auditable tool layer. Every AI operation is scoped to exactly what the security policy permits. Every write is logged. Every entity access is governed by the same access controls that govern human access.

Sentinel ships as two open-source components: a Node.js MCP connector (`drupal-mcp-server`) that gives any MCP-compatible AI agent access to 51 Drupal tools, and a companion Drupal module (`mcp_sentinel`) that enforces the enterprise governance layer Drupal-side. The two components can be deployed independently but are designed to work together as a complete, sovereign AI interface for any Drupal site.

**Key Capabilities**

- 51 MCP tools covering content CRUD, taxonomy, users, media, GraphQL queries and mutations, generic entity access, audit reporting, and a Drush SSH bridge — giving AI agents access to the full Drupal operational surface
- Security presets — `read-only`, `auditor`, `content-editor`, `production-strict` — that establish the correct access posture for each deployment context without manual configuration
- Entity type allow and deny lists with field-level PII redaction — ensuring AI agents can only see and operate on the content types and fields the security policy explicitly permits
- Full audit log of every MCP operation with user identity, IP address, entity type, and operation context — providing the evidentiary record required for compliance and incident response
- Content locks that prevent AI edits on content actively being edited by humans, eliminating the race conditions and version conflicts that uncoordinated AI access creates
- HMAC-signed HTTPS webhooks on AI-triggered entity changes — providing downstream systems with cryptographically verifiable notification of every content modification
- Multi-site support with independent security configurations per site — enabling AI access to a portfolio of Drupal sites while maintaining strict isolation between them
- Designed to work alongside Drupal's native access control system, not replace it — Sentinel's checks are additive, and both must allow an operation for it to proceed

**Mission Impact**

Defense and government organizations running Drupal cannot treat AI access to their content systems as an exception to their security posture. They need AI that operates within the same access controls, auditability requirements, and sovereignty constraints as every other system touching their content. Sentinel provides that interface — giving content teams the AI-accelerated operations they need to work at pace while giving security officers and program managers the audit trail, access controls, and webhook notifications required to treat AI as a governed, accountable participant in the content workflow rather than an uncontrolled external actor.

**Open Source**

Both Sentinel components are published as open-source software. `drupal-mcp-server` is available on GitHub under the MIT license. `mcp_sentinel` is available on Drupal.org under the GPL. Wilkes & Liberty maintains both projects and provides implementation, enterprise hardening, and sustainment services for organizations deploying Sentinel in federal and defense environments.

---
