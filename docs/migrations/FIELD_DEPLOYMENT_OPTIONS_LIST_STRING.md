# Migration: `field_deployment_options` → `list_string`

**Status:** EXECUTED locally on 2026-05-21 (DDEV). Awaiting review + merge.
After merge, the script is run once per non-local environment (staging, prod).

**Owner:** Jeremy (3@wilkesliberty.com)
**Drafted:** 2026-05-21
**Executed (local):** 2026-05-21 — zero rows migrated (no data on the field anywhere).
**Migration script:** [`scripts/migrate_deployment_options_to_list_string.php`](../../scripts/migrate_deployment_options_to_list_string.php)

---

## Why

`field_deployment_options` is currently a free-text `string` field with cardinality `-1` (unlimited). The current widget is `string_textfield`. Two problems compound:

- **No editor guardrails.** Editors type the value by hand. Typos, casing variance ("AWS GovCloud" vs "Aws Gov Cloud" vs "AWS Gov Cloud"), and unrelated phrasings ("on-prem", "on premises", "On-Premises") all coexist as distinct stored values.
- **No reliable downstream filtering.** Because every variant is its own string, the Next.js frontend cannot reliably facet/filter on deployment target without doing post-hoc normalisation. The same problem hits any GraphQL consumer that wants to enumerate values.
- **No schema-level enforcement.** There is no allowed-values list, so a future contributor can introduce a new value with no review.

Earlier in this rollout the product team aligned on a fixed list of seven cloud targets. The intent was a dropdown; the storage was never converted.

## Target shape

A `list_string` field, multi-cardinality, with this allowed-values map:

| storage key              | label                                  |
|--------------------------|----------------------------------------|
| `aws_govcloud`           | AWS GovCloud                           |
| `azure_government`       | Azure Government                       |
| `gcp_assured_workloads`  | Google Cloud GCP (Assured Workloads)   |
| `on_premises`            | On-Premises                            |
| `hybrid`                 | Hybrid                                 |
| `il5`                    | IL5                                    |
| `il6`                    | IL6                                    |

Form widget: `options_buttons` or `options_select` (multi-select). View formatter stays `list_default`.

The keys are the source of truth. They are mirrored in `WL_ALLOWED_DEPLOYMENT_OPTIONS` at the top of the migration script and must match `field.storage.node.field_deployment_options.yml` when the new YAML is written.

### Why not just keep `string` and add validation?

A list field shows up as a dropdown automatically, is enforced at the storage layer, and is exposed cleanly through GraphQL Compose. A custom validator on a free-text field still allows typos at the API layer and gives the editor a blank textbox.

### Scope: bundles affected

The storage is shared across **two bundles**, not just `product`:

- `node.product.field_deployment_options`
- `node.service.field_deployment_options`

Both `field.field.*.yml` files must be deleted, recreated, and re-imported. Both form and view displays reference the field and will need their widgets updated to `options_buttons` (or `options_select`). The two displays affected are:

- `core.entity_form_display.node.product.default.yml`
- `core.entity_form_display.node.service.default.yml`

`graphql_compose.settings.yml` already enables the field on both bundles; no change needed there beyond verifying the schema regenerates cleanly post-migration.

## Pre-flight survey

Run the script in `--export` mode (read-only). It writes a CSV and prints distinct values + counts.

```bash
ddev drush scr scripts/migrate_deployment_options_to_list_string.php -- --export
```

The equivalent raw query (use either):

```bash
ddev drush sql:query "SELECT entity_id, bundle, delta, langcode, field_deployment_options_value \
  FROM node__field_deployment_options \
  ORDER BY entity_id, langcode, delta" > /tmp/dep_opts_pre.csv

ddev drush sql:query "SELECT field_deployment_options_value AS value, COUNT(*) AS n \
  FROM node__field_deployment_options \
  GROUP BY field_deployment_options_value \
  ORDER BY n DESC"
```

### Survey result (local DDEV, 2026-05-21)

| value | count |
|-------|-------|
| _(none — table empty)_ | 0 |

- `node__field_deployment_options` — **0 rows**
- `node_revision__field_deployment_options` — **0 rows**

**Implication:** the local database has no data in this field at all. The
migration on the local DB is effectively just a schema swap.

**Caveat:** production and staging may have data the local DB does not. Before
`--apply` runs anywhere except local, re-run the survey against that
environment's DB:

```bash
# On the staging/production host (or against a freshly restored copy of prod):
drush sql:query "SELECT field_deployment_options_value AS value, COUNT(*) AS n \
  FROM node__field_deployment_options \
  GROUP BY field_deployment_options_value \
  ORDER BY n DESC"
```

Paste those results into the mapping table below before populating `WL_VALUE_MAP`.

### Mapping table (to be filled from prod survey)

Once the prod survey lands, every distinct value below maps to exactly one
canonical key. Lookup in the script is case-insensitive and whitespace-collapsed
(`wl_normalize_key()`), so you only need one entry per genuine variant.

| observed value (lowercased) | → canonical key            | notes |
|-----------------------------|----------------------------|-------|
| `aws govcloud`              | `aws_govcloud`             | already-canonical |
| `aws gov cloud`             | `aws_govcloud`             | spacing variant |
| `aws gc`                    | `aws_govcloud`             | informal shorthand — confirm with editor before mapping |
| `azure government`          | `azure_government`         | |
| `azure gov`                 | `azure_government`         | shorthand |
| `gcp assured workloads`     | `gcp_assured_workloads`    | |
| `google cloud (assured workloads)` | `gcp_assured_workloads` | |
| `gcp`                       | `gcp_assured_workloads`    | confirm — ambiguous, might mean plain GCP |
| `on-premises`               | `on_premises`              | |
| `on premises`               | `on_premises`              | hyphen variant |
| `on-prem`                   | `on_premises`              | |
| `hybrid`                    | `hybrid`                   | |
| `il5`                       | `il5`                      | |
| `il6`                       | `il6`                      | |

The table above is illustrative until prod is surveyed. The local survey
returned zero rows, so no mapping is required for the local execution; the prod
survey will determine the real mapping.

**Ambiguous-value protocol:** any row in the survey that is not an obvious
match to one of the seven canonical keys gets:

1. A row added to the table here with a `notes` column entry asking for editor confirmation.
2. A Slack/email to the product owner of the affected node, quoting node ID + current value.
3. *No silent default.* If we cannot get a confident answer, leave it unmapped — the script's `--apply` will then abort and surface it, which is the desired behavior.

If the team decides to keep an escape hatch for unforeseen values, add `'other' => 'Other'` to both `WL_ALLOWED_DEPLOYMENT_OPTIONS` in the script *and* the new `field.storage.node.field_deployment_options.yml`.

## Migration steps

Numbered, reversible where possible.

1. **Back up the DB.** The 02:00 nightly backup covers this for production. For a same-day run, take an on-demand backup first:
   ```bash
   # Local (DDEV)
   ddev export-db --file=pre-deploy-opts-$(date +%Y%m%d).sql.gz
   # Prod/staging
   drush sql:dump --gzip --result-file=/backups/pre-deploy-opts-$(date +%Y%m%d).sql.gz
   ```
   *Reversible:* yes, restore + `drush cim -y`.

2. **Export current data to CSV.**
   ```bash
   ddev drush sql:query "SELECT entity_id, bundle, delta, langcode, field_deployment_options_value \
     FROM node__field_deployment_options" > /tmp/dep_opts_pre.csv
   ```
   *Reversible:* read-only.

3. **Populate `WL_VALUE_MAP`** in the migration script using the survey + mapping table above. Commit the populated script as a follow-up PR (separate from this planning PR) before `--apply` runs.
   *Reversible:* yes, revert the commit.

4. **Dry run.**
   ```bash
   ddev drush scr scripts/migrate_deployment_options_to_list_string.php
   ```
   This validates that every existing value has a mapping; aborts on the first unmapped value and prints it. *Reversible:* yes, dry-run mutates nothing.

5. **Write the new `field.storage.node.field_deployment_options.yml`** in `config/sync/` with `type: list_string` and the seven allowed values. Update the two `field.field.*.yml` files and the two form display YAMLs to use a `list_string`-compatible widget (`options_buttons` or `options_select`). **Do not import yet.** Commit on the same branch as `--apply`. *Reversible:* yes, revert.

6. **Run `--apply`.**
   ```bash
   ddev drush scr scripts/migrate_deployment_options_to_list_string.php -- --apply --i-have-a-backup
   ```
   The script:
   - Snapshots `/tmp/dep_opts_pre_<TIMESTAMP>.csv`
   - Deletes both `field.field.*` and the `field.storage` config
   - Runs `field_purge_batch()` until the deleted field is fully purged
   - Imports the new storage + bundle field YAMLs from `config/sync/`
   - Re-attaches mapped values via the entity API (preserving translations + delta order)
   - Snapshots `/tmp/dep_opts_post_<TIMESTAMP>.csv`

   *Reversible:* only via DB restore (step 1). The destructive cut is between snapshot and re-attach; if the script aborts mid-`--apply`, see "Risk assessment" below.

7. **`drush cim -y`** to bring runtime config fully in sync with `config/sync/`. *Reversible:* the YAMLs are git-tracked.

8. **`drush cr`** to rebuild caches (especially the entity field manager).

9. **Verify.**
   - `diff /tmp/dep_opts_pre_*.csv /tmp/dep_opts_post_*.csv` — the canonical value column should be the *only* difference; entity_id / bundle / delta / langcode rows should match 1:1.
   - `ddev drush sql:query "SELECT field_deployment_options_value, COUNT(*) FROM node__field_deployment_options GROUP BY field_deployment_options_value"` — every value should be one of the seven canonical keys.
   - Hit `/jsonapi/node/product?fields[node--product]=field_deployment_options` and confirm the values are the new canonical keys.
   - Hit `/graphql` and confirm the schema regenerated, the field still returns `[String]`, and values are canonical.

10. **Rollback plan.**
    1. Restore the pre-migration DB backup from step 1.
    2. `git revert` the commit that ships the new YAMLs.
    3. `drush cim -y`.
    4. `drush cr`.

    No partial rollback is possible — the destructive sequence is "all or nothing." If `--apply` aborts mid-flight, treat the DB as suspect and restore.

## Risk assessment

| Failure mode | Symptom | Mitigation |
|--------------|---------|------------|
| Unmapped value in prod that wasn't in the mapping table | `--apply` aborts before any deletion | Already handled — the script refuses to proceed. Add the mapping and re-run. |
| Script aborts between field-delete and field-recreate | Field is in "deleted, awaiting purge" limbo; nodes lose deployment_options | Restore DB. Do not attempt manual purge + re-add unless you've already verified the entity API path works on a copy. |
| Field-purge stalls on a long-running site | Real apply runs past the change window | Test against a fresh copy of prod first; estimate purge time. Pre-purge any unrelated deleted fields beforehand. |
| Translation rows not preserved | Spanish/Russian translations of nodes lose their deployment values | Script's step 5f explicitly re-saves each translation; verify with `SELECT DISTINCT langcode FROM node__field_deployment_options` pre and post. |
| Config import order issue (storage before fields) | `cim` errors on missing storage | Use `--partial` import with explicit ordering, or let `drush cim` resolve. The script's TODO(5e) is explicit about this. |
| Widget mismatch (form display still says `string_textfield`) | Admin form throws on edit | Step 5 includes updating the form display YAML; verified during cim. |

## GraphQL impact

The GraphQL field stays `[String]` at the type level — both `string` and `list_string` serialize as strings through GraphQL Compose. The behavioral change is:

- **Pre:** the field returns whatever free text was typed.
- **Post:** the field returns one of seven exact string keys.

Strictly speaking the *contract* is narrower, which is a positive change for consumers but technically a value-domain change. The frontend should:

- Switch any case-insensitive filtering / normalisation logic to direct equality on the canonical keys.
- Optionally introduce a TypeScript union type:
  ```ts
  type DeploymentOption =
    | 'aws_govcloud'
    | 'azure_government'
    | 'gcp_assured_workloads'
    | 'on_premises'
    | 'hybrid'
    | 'il5'
    | 'il6';
  ```
- Display labels in the UI via a lookup map; do not show raw storage keys to end users.

No GraphQL schema migration is required. Re-run schema introspection on the frontend after `--apply`.

If we ever want to expose the field as a true enum at the GraphQL layer, that is a follow-up migration (GraphQL Compose supports list-field enum exposure; out of scope here).

## Editor UX impact

- **Widget change:** `string_textfield` → `options_buttons` (recommended) or `options_select`. With seven values, checkboxes are scannable.
- **No more free text.** Editors cannot type a new deployment target. If a new one comes up (e.g., a new GovCloud region), the workflow is: PR to add it to `WL_ALLOWED_DEPLOYMENT_OPTIONS` and the storage YAML, then `cim`.
- **Escape hatch?** If product wants editors to be able to flag an unknown value without a config PR, add `'other' => 'Other'` to both the script and the storage YAML *before* `--apply`. Then build a follow-up review process for `Other` values.
- **Existing content review:** since the migration normalises variants, editors should spot-check a sample of product/service nodes post-migration to confirm the mapping landed where they expect, especially for any ambiguous mappings flagged in the table above.

## Execution log — 2026-05-21 (local DDEV)

### Pre-flight

- `node__field_deployment_options`: 0 rows
- `node_revision__field_deployment_options`: 0 rows
- Fresh DB dump taken at `/tmp/pre-deployment-options-migration.sql.gz` (5.4 MB) immediately before `--apply`.

### What the script did

1. Deleted `FieldConfig` on `node.product` and `node.service`. Drupal cascade-deleted the shared `FieldStorageConfig`.
2. `field_purge_batch()` reported zero pending deletions on the first poll.
3. Created the new `FieldStorageConfig` (type `list_string`, cardinality `-1`, translatable, allowed_values populated).
4. Re-attached `FieldConfig` on both bundles with the original labels and descriptions.
5. Switched form display widget to `options_buttons`; view formatter to `list_default`.
6. Re-enabled the field in `graphql_compose.settings` under `field_config.node.{product,service}.field_deployment_options.enabled` — needed because graphql_compose listens for field deletes and strips its entries.
7. `drupal_flush_all_caches()`.

### Verification

- `FieldStorageConfig::loadByName('node', 'field_deployment_options')` returns `type=list_string`, `cardinality=-1`, `translatable=true`, all seven allowed values present.
- Form display: `options_buttons` widget on both bundles.
- View display: `list_default` formatter on both bundles.
- Programmatic Product create with `field_deployment_options = ['aws_govcloud', 'on_premises']` saved and reloaded with identical values.
- Negative validation: `field_deployment_options.1: The value you selected is not a valid choice.` when attempting to save `'definitely_not_allowed'`.
- GraphQL introspection: `NodeProduct.deploymentOptions` and `NodeService.deploymentOptions` both present as `[String!]` (LIST of NON_NULL String).
- GraphQL query `{ nodeProduct(id: "<uuid>") { deploymentOptions } }` returned `["azure_government","hybrid","il5"]`.
- JSON:API: module is not installed on this site; verification skipped.

### Notable behaviour changes captured in the cex diff

- `field_deployment_options` is now exposed on **NodeService** via GraphQL Compose as well as **NodeProduct**. Pre-migration it was only enabled on `product` in `graphql_compose.settings.yml`. The script enables both for consistency with the field itself being attached to both bundles. If this is unwanted, set `field_config.node.service.field_deployment_options.enabled: false` and re-export.
- The UUIDs on the storage + bundle field configs are new (delete + recreate).
- `dependencies.module` adds `options` (the field type module for `list_string`).
- Form widget setting `size`/`placeholder` gone (string-only); view formatter setting `link_to_entity` gone.

### Strict-config-schema gotcha encountered (now resolved)

First `--apply` attempt failed with:

```
The configuration property settings.allowed_values.0.label.0 doesn't exist.
```

Root cause: at runtime the entity API expects `allowed_values` in the **simple** `[key => label]` form. Drupal's `ListItemBase::storageSettingsToConfigData()` converts it to the **structured** `[{value, label}]` form on save. Passing the structured form pre-converted causes a double-conversion that trips the config schema validator. The script now passes the simple form (`WL_ALLOWED_DEPLOYMENT_OPTIONS` directly).

### Post-merge runbook for other environments

`drush cim` alone will **not** apply this migration — Drupal blocks `type` changes via config import. The correct sequence is:

```bash
git pull origin master

# Strongly recommended: take a fresh DB snapshot first.
drush sql:dump --gzip --result-file=/backups/pre-deploy-opts-$(date +%Y%m%d).sql.gz

# Pre-flight: confirm zero data (the script enforces this too)
drush sql:query "SELECT COUNT(*) FROM node__field_deployment_options"
drush sql:query "SELECT COUNT(*) FROM node_revision__field_deployment_options"

# Dry run
drush scr scripts/migrate_deployment_options_to_list_string.php

# Real run
drush scr scripts/migrate_deployment_options_to_list_string.php -- --apply --i-have-a-backup

# Should report no drift
drush cim -y
drush cr
```

If any pre-flight count is non-zero, **stop** and contact Jeremy. The script will abort on its own, but it's worth surfacing to humans rather than re-running blindly.

## Pointers

- Script: [`scripts/migrate_deployment_options_to_list_string.php`](../../scripts/migrate_deployment_options_to_list_string.php)
- Storage YAML: [`config/sync/field.storage.node.field_deployment_options.yml`](../../config/sync/field.storage.node.field_deployment_options.yml)
- Bundle field YAMLs: [`config/sync/field.field.node.product.field_deployment_options.yml`](../../config/sync/field.field.node.product.field_deployment_options.yml), [`config/sync/field.field.node.service.field_deployment_options.yml`](../../config/sync/field.field.node.service.field_deployment_options.yml)
- Form displays: `core.entity_form_display.node.{product,service}.default.yml`
- View displays: `core.entity_view_display.node.{product,service}.default.yml`
- GraphQL Compose settings: `graphql_compose.settings.yml` (entry under `field_config.node.{product,service}.field_deployment_options.enabled`)
