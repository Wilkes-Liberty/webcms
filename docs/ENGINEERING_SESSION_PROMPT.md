# Engineering Session — Product → Platform Rename + Platform Node Updates

**Prepared:** 2026-05-27  
**For:** Claude Code session in the `webcms` and `ui` repositories  
**Authority:** `webcms/docs/NAMING_DECISIONS.md` — Deliverable 6 (§6.4–6.9)  
**Full technical spec:** `webcms/docs/PORTFOLIO_AUDIT.md §G`

---

## Context

A naming session completed 2026-05-27 and locked all 15 proper names for the Wilkes & Liberty portfolio. The primary engineering change that results from this session is:

1. The Drupal content type `product` is being renamed to `platform` — machine name, config files, database, GraphQL typenames, and frontend routing all change.
2. The six existing seeded platform nodes get new titles and path aliases (final brand names).
3. A seventh platform node (Coquina Software Factory Platform) is created.
4. Eight solution nodes are created with canonical titles and slugs.
5. Three seeded solution placeholders (nid 21/22/23) are reviewed for disposition.
6. URL redirects are implemented for all old `/products/*` paths.

**Decision reference:** Decision F1-B (confirmed 2026-05-27) — rename the machine name to `platform`, not just the label. Now is the right window: nothing is publicly indexed, no 301 debt yet.

**Security constraint:** SOPS + AGE encryption is in use for all secrets. Never write plaintext credentials to the repo. All secret references use the encrypted `*_secrets.yml` pattern. Do not create, print, or hardcode any secrets in any file.

---

## Repository layout

```
~/Repositories/webcms/        # Drupal 11 — CMS backend
~/Repositories/ui/            # Next.js — frontend
```

These are two separate repos in `~/Repositories/`. Work happens in both.

---

## Reading list (read these first before writing any code)

1. `webcms/docs/NAMING_DECISIONS.md` §6.2–6.9 — canonical name/slug table, full execution plan
2. `webcms/docs/PORTFOLIO_AUDIT.md` §G — detailed Drupal technical change list (G.1–G.5)
3. `webcms/CLAUDE.md` — project-specific Claude instructions for the webcms repo
4. `ui/AGENTS.md` — project-specific instructions for the ui repo
5. `webcms/scripts/seed_products_services.php` — understand the seed script before touching it

---

## Part 1 — Drupal: content type rename (`product` → `platform`)

### 1.1 Config file renames and updates

There are **111** field config files under `webcms/config/sync/` that reference the `product` bundle. The full list:

**Rename these files** (filename pattern: `field.field.node.product.*` → `field.field.node.platform.*`):

```
config/sync/field.field.node.product.*.yml  (all 111 files)
→ config/sync/field.field.node.platform.*.yml
```

Inside each file, update:
- `bundle: product` → `bundle: platform`
- `id: node.product.*` → `id: node.platform.*`
- `dependencies.config` entries that reference `node.type.product` → `node.type.platform`

**Rename the content type file itself:**
```
config/sync/node.type.product.yml → config/sync/node.type.platform.yml
```
Inside `node.type.platform.yml`, update:
- `id: product` → `id: platform`
- `label: 'Platform'` (was `'Product'` or similar — update the label)

**Rename view/form display configs:**
```
config/sync/core.entity_view_display.node.product.default.yml
  → config/sync/core.entity_view_display.node.platform.default.yml
  (inside: update targetEntityType bundle reference)

config/sync/core.entity_form_display.node.product.default.yml
  → config/sync/core.entity_form_display.node.platform.default.yml
  (inside: update targetEntityType bundle reference)
```

**Update the editorial workflow:**
```
config/sync/workflows.workflow.editorial.yml
```
This file references `node.type.product` at two locations (confirmed lines 13 and 100 in the current file). Replace both with `node.type.platform` / `platform` as appropriate.

**Create a pathauto pattern for platforms** (or update if one already exists for products):
If `config/sync/pathauto.pattern.*product*.yml` exists, rename and update. If none exists, create `config/sync/pathauto.pattern.pathauto_platform.yml` with pattern `/platforms/[node:title:slugify]` for node bundle `platform`. Review existing pathauto patterns for the correct YAML structure.

**Cross-reference field `field_related`** on the `product` bundle:
- `config/sync/field.field.node.product.field_related.yml` → `config/sync/field.field.node.platform.field_related.yml`
- Update `bundle: product` → `bundle: platform` inside

> Note: The field storage (`field.storage.node.field_related`) is shared across bundles and does NOT need renaming. Only the per-bundle instance file changes.

**Check for any Views filters** referencing `node.type = product`:
```bash
grep -r "product" config/sync/ --include="*.yml" -l
```
Update any Views that filter on `node_type` = `product` to use `platform`.

### 1.2 Language config files

Check `config/sync/language/es/` — there are Spanish translations of content type configs. If `language/es/node.type.product.yml` or any `language/es/field.field.node.product.*.yml` files exist, apply the same renames there.

---

## Part 2 — Drupal: platform node title + path alias updates

The six existing seeded nodes need new titles and path aliases. Confirm actual nids first by querying the database or running:

```bash
ddev drush sql:query "SELECT nid, title FROM node_field_data WHERE type = 'product' ORDER BY nid;"
```

Then update each node title and path alias to match this canonical table:

| Old title | New title | New path alias |
|---|---|---|
| Sovereign Infrastructure Platform | Sabal Infrastructure Platform | `/platforms/sabal` |
| Liberty Headless CMS | Keel CMS Platform | `/platforms/keel` |
| Enterprise Search | Alidade Search Platform | `/platforms/alidade` |
| Fortis Identity | Squawk Zero-Trust Identity Platform | `/platforms/squawk` |
| Apex Data | Manifest Data Platform | `/platforms/manifest` |
| Vigilance Observability | Lighthouse Observability Platform | `/platforms/lighthouse` |

**Best approach:** Write a Drush script (similar to the existing scripts in `webcms/scripts/`) that:
1. Queries for each node by old title
2. Updates the `title` field on both `node_field_data` and `node_field_revision`
3. Deletes any existing path alias for the node
4. Creates the new path alias via the path alias API

**Do not manually edit the database.** Use the Drupal entity API or Drush script for correctness.

---

## Part 3 — Drupal: database type column update

After the config rename is imported via `drush config:import`, existing nodes in the database still have `type = 'product'`. Update them:

```sql
UPDATE node SET type = 'platform' WHERE type = 'product';
UPDATE node_field_data SET type = 'platform' WHERE type = 'product';
UPDATE node_field_revision SET type = 'platform' WHERE type = 'product';
UPDATE node_revision SET type = 'platform' WHERE type = 'product';
```

**Best executed via a Drush script** (not raw SQL in production) — wrap in a transaction, add error handling. See existing script patterns in `webcms/scripts/`.

---

## Part 4 — Drupal: new platform node (Coquina)

Create a new `platform` node:
- **Title:** Coquina Software Factory Platform
- **Path alias:** `/platforms/coquina`
- **Status:** Draft (not published)
- Fields: populate with placeholder values — content will be entered in a separate content session

---

## Part 5 — Drupal: solution nodes (8 canonical + seeded review)

### 5.1 Seeded solutions (nid 21, 22, 23) — disposition required first

Before creating new solution nodes, pull the current titles of nids 21, 22, 23:

```bash
ddev drush sql:query "SELECT nid, title, field_meta_description_value FROM node_field_data WHERE type = 'solution' ORDER BY nid;"
```

These are the three placeholder solutions:
- nid 21: Sovereign Mission Edge
- nid 22: Sovereign AI Command Fabric  
- nid 23: Sovereign Digital Modernization Platform

**Disposition options per** `webcms/docs/NAMING_DECISIONS.md §6.7`:
- Map to one of the 8 canonical solutions (update title + alias), OR
- Retire (unpublish / delete)

The 8 canonical solutions and which platforms they build on:

| # | Solution name | Full display name | URL slug | Builds on |
|---|---|---|---|---|
| 1 | DotEDU | DotEDU — Higher Education | `/solutions/dotedu` | Keel CMS Platform, Alidade Search Platform |
| 2 | Accord | Accord — Nonprofit | `/solutions/accord` | Keel CMS Platform |
| 3 | Palisade | Palisade — Privacy SaaS | `/solutions/palisade` | Manifest Data Platform, Squawk Identity Platform |
| 4 | Bulkhead | Bulkhead — Regulated Industries | `/solutions/bulkhead` | Sabal Infrastructure Platform, Squawk Identity Platform, Manifest Data Platform |
| 5 | DotGov | DotGov — Federal Civilian | `/solutions/dotgov` | Keel CMS Platform, Alidade Search Platform, Squawk Identity Platform |
| 6 | Gazette | Gazette — IG Platforms | `/solutions/gazette` | Keel CMS Platform, Manifest Data Platform |
| 7 | Outpost | Outpost — Defense Tech | `/solutions/outpost` | Sabal Infrastructure Platform, Coquina Software Factory Platform |
| 8 | Software Factory | Software Factory | `/solutions/software-factory` | Coquina Software Factory Platform |

**Recommended mapping** (review before executing — confirm with Jeremy if unsure):
- nid 22 (Sovereign AI Command Fabric) may map loosely to Outpost or be retired
- nid 23 (Sovereign Digital Modernization Platform) may map to Bulkhead or DotGov or be retired
- nid 21 (Sovereign Mission Edge) may map to Outpost or be retired

### 5.2 Create the 8 canonical solution nodes

For any solutions not covered by updating the seeded nodes, create new `solution` nodes with:
- Title and path alias per the table above
- Status: Draft
- Placeholder content — full copy to be entered in a separate content session

---

## Part 6 — Drupal: seed script update

Update `webcms/scripts/seed_products_services.php`:

1. **Line 14** (path alias comment): `/products/{slug}` → `/platforms/{slug}`
2. **Line 553** (bundle → path prefix map): `'product' => '/products'` → `'platform' => '/platforms'`
3. **Line 385** bundle check: `if ($bundle === 'product')` → `if ($bundle === 'platform')`
4. **Lines 625/631/682–685/713**: All `'product'` string literals in the summary/iteration section → `'platform'`
5. **ROOT_FIELDS equivalent in the script** (if it maps bundle names to DB/API identifiers) — update `product` key → `platform`
6. **The content array key** `$parsed['products']` → `$parsed['platforms']` (and the corresponding parser section that extracts the `'products'` section key from CONTENT.md-style input)
7. Update node title data — the six existing platform titles need to match the final names from Part 2 above

After updating the script, run a dry-run to confirm no errors:
```bash
ddev drush scr scripts/seed_products_services.php -- --dry-run
```

---

## Part 7 — Next.js (`ui` repo): `NodeProduct` → `NodePlatform`

### 7.1 `lib/queries/node-listing.ts`

The `ROOT_FIELDS` map (line ~18) maps bundle names to GraphQL root field names. Update:

```typescript
// Before:
product: "nodeProducts",

// After:
platform: "nodeProducts",  // NOTE: graphql_compose pluralizes 'platform' as 'nodePlatforms'
                           // Verify the actual GraphQL field name after Drupal config:import
```

> **Important:** After renaming the Drupal content type to `platform`, graphql_compose will generate the root field as `nodePlatforms` (not `nodeProducts`). Confirm by introspecting the schema after config import. Update the value accordingly.

### 7.2 `app/(app)/products/page.tsx`

This file needs to be renamed and its internals updated:

**Rename:** `app/(app)/products/page.tsx` → `app/(app)/platforms/page.tsx`

Inside the new `app/(app)/platforms/page.tsx`:
```typescript
// Before:
const { nodes } = await getNodeListing("product")

// After:
const { nodes } = await getNodeListing("platform")
```

Also update any display strings:
- `"No products published yet."` → `"No platforms published yet."`
- `"Self-deployable sovereign technology platforms..."` — review and update as needed

### 7.3 `lib/queries/node-by-path.ts`

The inline GraphQL fragment around line 141:
```graphql
// Before:
... on NodeProduct {
  ${COMMON_NODE_FIELDS}
  ...fields...
}

// After:
... on NodePlatform {
  ${COMMON_NODE_FIELDS}
  ...fields...
}
```

> **Verify the exact typename** after running `drush config:import` in Drupal — graphql_compose derives the typename from the bundle machine name. With machine name `platform`, the typename will be `NodePlatform`.

### 7.4 `components/drupal/NodeRenderer.tsx`

Update the switch case:
```typescript
// Before:
case "NodeProduct":

// After:
case "NodePlatform":
```

### 7.5 `types/index.d.ts`

Update the type definition:
```typescript
// Before (line ~99):
__typename: "NodeProduct"

// After:
__typename: "NodePlatform"
```

Review the full `NodeProduct` type block and rename it to `NodePlatform` throughout.

### 7.6 Any hardcoded `/products/` path strings in `ui/`

Search and replace:
```bash
grep -r "/products" app/ components/ lib/ types/ --include="*.ts" --include="*.tsx" -n
```
Update any hardcoded `/products/` references to `/platforms/`.

---

## Part 8 — URL redirects

Implement 301 redirects for all old product URLs. Use the Drupal `redirect` module (already installed — `config/sync/redirect.settings.yml` exists).

Create redirect config entries or use a Drush script to add redirect entities:

| From | To | Type |
|---|---|---|
| `/products/sovereign-infrastructure-platform` | `/platforms/sabal` | 301 |
| `/products/liberty-headless-cms` | `/platforms/keel` | 301 |
| `/products/enterprise-search` | `/platforms/alidade` | 301 |
| `/products/fortis-identity` | `/platforms/squawk` | 301 |
| `/products/apex-data` | `/platforms/manifest` | 301 |
| `/products/vigilance-observability` | `/platforms/lighthouse` | 301 |
| `/products` | `/platforms` | 301 |

**Also add a catch-all** in the Caddy config (in the `infra` repo, not `webcms`):
```
redir /products/* /platforms/{path} 301
```
Check `~/Repositories/infra/` for the correct Caddyfile location and pattern.

---

## Part 9 — Execution sequence

Run in this order to avoid intermediate broken states:

```
1. Rename all config files (Part 1) — git mv for each
2. Edit content of renamed files (bundle: product → platform etc.)
3. ddev drush config:import  — import the renamed config; Drupal now knows 'platform' bundle
4. Run the database type column update script (Part 3)
5. Run the platform node title/alias update script (Part 2)
6. Create Coquina platform node (Part 4)
7. Review seeded solution nids 21/22/23 (Part 5.1) — confirm disposition
8. Create/update solution nodes (Part 5.2)
9. Update seed script (Part 6) — then dry-run
10. Update ui/ repo (Part 7) — all NodeProduct → NodePlatform changes
11. Implement URL redirects (Part 8)
12. Run full test — verify /platforms/sabal, /platforms/keel, etc. render correctly
13. Verify /products/sovereign-infrastructure-platform → 301 → /platforms/sabal
```

> Steps 1–4 must complete in sequence before Steps 5–9 are possible.  
> Steps 10–11 can be done in parallel with Steps 5–9.

---

## Part 10 — Verification checklist

After all changes:

- [ ] `drush config:import` completes with no errors
- [ ] `drush cr` (cache rebuild) completes cleanly
- [ ] All 7 platform nodes render at their new `/platforms/[name]` paths
- [ ] `/platforms` listing page shows all 7 platforms
- [ ] All 8 solution nodes exist at `/solutions/[name]`
- [ ] GraphQL introspection: `NodePlatform` typename exists; `NodeProduct` does not
- [ ] `getNodeListing("platform")` returns nodes from `nodePlatforms` root field
- [ ] `/products/sovereign-infrastructure-platform` → 301 → `/platforms/sabal`
- [ ] All 6 old product URLs return 301 (not 404)
- [ ] `seed_products_services.php --dry-run` runs without errors
- [ ] `types/index.d.ts` has `NodePlatform`, no `NodeProduct`
- [ ] TypeScript compilation passes: `cd ui && npx tsc --noEmit`

---

## Known flags and constraints

- **Outpost** (Solution 7 — Defense Tech): AWS Outposts naming flag. Do not use this name in any public-facing context until attorney USPTO review is complete. Create the node in Draft status only.
- **All solutions**: Draft status until attorney review completes.
- **SOPS + AGE**: All secrets remain encrypted. No plaintext credentials anywhere in the codebase.
- **No new external dependencies** without discussion.
- **The `ui` repo is a separate git repo** at `~/Repositories/ui/` — commits there are independent of `~/Repositories/webcms/`.

---

## Reference: final locked platform names

| # | Proper name | Full display name | Slug |
|---|---|---|---|
| 1 | Sabal | Sabal Infrastructure Platform | `/platforms/sabal` |
| 2 | Keel | Keel CMS Platform | `/platforms/keel` |
| 3 | Alidade | Alidade Search Platform | `/platforms/alidade` |
| 4 | Squawk | Squawk Zero-Trust Identity Platform | `/platforms/squawk` |
| 5 | Manifest | Manifest Data Platform | `/platforms/manifest` |
| 6 | Lighthouse | Lighthouse Observability Platform | `/platforms/lighthouse` |
| 7 | Coquina | Coquina Software Factory Platform | `/platforms/coquina` |

Full authority: `webcms/docs/NAMING_DECISIONS.md §5.4` (decisions table) and `§6.2` (name/slug reference).
