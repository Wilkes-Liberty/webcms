# Solr Configset Deploy Runbook

How to push Drupal-generated Solr configsets to the WL Solr server when the
`search_api_solr` module asks you to "deploy an updated `config.zip` to your
Solr server."

> **Current state (2026-05-21):** No `search_api.server.*` and no
> `search_api.index.*` entities are configured anywhere — `drush
> search-api:server-list` returns "There are no servers present" against the
> production DB. The `search_api_solr` module is enabled and ships its default
> field-type / cache / request-handler YAML into `config/sync/`, but Drupal is
> not currently writing to or reading from Solr. **The warning therefore does
> not fire today.** Apply this runbook the first time a server entity is
> created and on every subsequent schema-affecting change.

---

## Where Solr lives

| Environment | Container         | Image       | Host port | Bind-mount (host)             | Drupal sees it at      | Operator URL (Tailscale + oauth2-proxy)            |
|-------------|-------------------|-------------|-----------|-------------------------------|------------------------|----------------------------------------------------|
| Local DDEV  | _(not present)_   | —           | —         | —                             | —                      | —                                                  |
| Staging     | `wl_stg_solr`     | `solr:9.6`  | `8993`    | `~/nas_docker_staging/solr`   | `http://solr:8983`     | https://search-stg.int.wilkesliberty.com           |
| Production  | `wl_solr`         | `solr:9.6`  | `8983`    | `~/nas_docker/solr`           | `http://solr:8983`     | https://search.int.wilkesliberty.com               |

Topology notes:

- This is **standalone Solr 9.6**, not SolrCloud. There is no ZooKeeper, no
  collections API, no `solrctl` / `zkcli` path. Configsets live on disk inside
  each core at `/var/solr/data/<core>/conf/`.
- Both containers bind-mount `/var/solr` from the on-prem server's home dir,
  so configset files survive container recreation and can be written directly
  from the host without `docker exec`.
- SSH to the on-prem server is Tailscale-only; there is no public SSH. Browser
  access to Solr Admin UI is via Caddy on `search.int.wilkesliberty.com`
  (operators only, oauth2-proxy + Tailscale CIDR ACL).
- Drupal-to-Solr traffic stays inside the Docker `backend` network using the
  hostname `solr` — there's no firewall hop. See
  `infra/docker/docker-compose.yml` lines 200–229 (prod) and
  `infra/docker/docker-compose.staging.yml.j2` lines 181–210 (staging).

---

## When this procedure must run

The warning fires when Drupal-side schema material changes. In practice that
means **any of**:

- Adding/enabling a new Drupal language (the module ships per-language field
  types like `text_en_7_0_0`, `text_es_7_0_0`, `text_ru_7_0_0`).
- Adding/editing a custom Solr field type (`search_api_solr.solr_field_type.*`
  in `config/sync/`).
- Upgrading `drupal/search_api_solr` to a version that ships new
  cache/request-handler/dispatcher templates.
- Adding new fields to a Drupal `search_api_index` whose backend is Solr (only
  matters once an index exists).

A plain `drush cim` that doesn't touch any of the above does **not** require
running this procedure.

---

## Step 1 — Export the configset from Drupal

Run inside the Drupal container on whichever environment owns the Solr server
you're deploying to. The drush command takes the **server entity ID** (not the
container hostname), an output path, and an optional target Solr version.

```bash
# In DDEV (won't currently work — no server configured locally):
ddev drush search-api-solr:get-server-config <SERVER_ID> /tmp/solr-config.zip 9.6

# On the on-prem server, against the prod Drupal container:
docker exec wl_drupal drush search-api-solr:get-server-config \
  <SERVER_ID> /tmp/solr-config.zip 9.6

# Aliases that work the same way:
#   drush solr-gsc
#   drush sasm-gsc
```

List the available server IDs first if you don't know them:

```bash
docker exec wl_drupal drush search-api:server-list
```

Pull the resulting zip out of the container so you can inspect it locally
before deploying:

```bash
docker cp wl_drupal:/tmp/solr-config.zip ./solr-config.zip
unzip -l solr-config.zip   # sanity-check: expect schema.xml, solrconfig.xml, etc.
```

---

## Step 2 — Deploy the configset to the Solr core

We're on **standalone Solr with a bind-mounted volume**, so the cleanest path
is: drop the unpacked files into the host bind-mount, then ask Solr to reload
the core. `docker exec`/`docker cp` are not needed for the file write — only
for the reload call.

```bash
# On the on-prem server, from the directory containing solr-config.zip:

CORE=<core-name>                                 # e.g. drupal, content, ...
SOLR_HOME=~/nas_docker/solr                      # staging: ~/nas_docker_staging/solr
CORE_CONF="$SOLR_HOME/data/$CORE/conf"

# 1. Stage the new config in a temp dir so the swap is atomic.
TMP=$(mktemp -d)
unzip -o solr-config.zip -d "$TMP"

# 2. Back up the running conf so a fast rollback is possible.
sudo cp -a "$CORE_CONF" "$CORE_CONF.bak.$(date +%Y%m%d-%H%M%S)"

# 3. Replace the conf directory contents.
sudo rsync -a --delete "$TMP/" "$CORE_CONF/"

# 4. Make sure the solr user (uid 8983 in the official image) owns the files.
sudo chown -R 8983:8983 "$CORE_CONF"

# 5. Reload the core via the running container.
docker exec wl_solr curl -fsS \
  "http://localhost:8983/solr/admin/cores?action=RELOAD&core=$CORE"
```

For staging, swap `wl_solr` → `wl_stg_solr` and `nas_docker` → `nas_docker_staging`.

### When the core doesn't exist yet

First-time core creation needs the configset on disk **before** the core is
created — Solr refuses to create a core against an empty conf dir, and the
`search_api_solr` module specifically warns against creating cores without a
proper Drupal-generated configset (see
`web/modules/contrib/search_api_solr/README.md` line 180).

```bash
# Stage conf as above, then:
docker exec wl_solr solr create_core -c "$CORE" -d "/var/solr/data/$CORE/conf"
```

---

## Step 3 — Verify

```bash
# Core is loaded and not in an error state.
docker exec wl_solr curl -fsS \
  "http://localhost:8983/solr/admin/cores?action=STATUS&core=$CORE" \
  | jq '.status[].index | {numDocs, lastModified}'

# Schema reflects what we just pushed.
docker exec wl_solr curl -fsS \
  "http://localhost:8983/solr/$CORE/schema/fields" | jq '.fields | length'

# From the Drupal side — confirm the module no longer flags an outdated schema.
docker exec wl_drupal drush status-report --severity=1 --format=table \
  | grep -i -A2 solr   # expect no rows
```

If everything looks healthy, re-index from Drupal:

```bash
docker exec wl_drupal drush search-api:index <INDEX_ID>
```

### Rollback (if Solr starts erroring)

```bash
# Find the most recent backup and restore.
ls -t "$SOLR_HOME/data/$CORE/"conf.bak.* | head -1
sudo rsync -a --delete "<that-backup>/" "$CORE_CONF/"
docker exec wl_solr curl -fsS \
  "http://localhost:8983/solr/admin/cores?action=RELOAD&core=$CORE"
```

---

## Why this isn't fully automated

- Drupal config sync (`config/sync/`) only ships the *inputs* the module uses
  to assemble a configset — it does not ship the assembled zip itself, and it
  has no way to reach into Solr's filesystem.
- We don't run SolrCloud, so there's no ZooKeeper-managed configset that
  `search-api-solr:upload-configset` could push to.
- `search-api-solr:upload-configset` exists and is what you'd use in a
  SolrCloud setup — leaving it documented here so future-you doesn't waste
  time re-discovering that it doesn't apply to our topology.

Automating the Step 2 file copy is a worthwhile follow-up — an Ansible task in
`infra/ansible/roles/wl-onprem/` that takes a zip uploaded to the on-prem
server and runs the rsync + reload would mean operators only need to run
Step 1 + push.

---

## Quick reference

```bash
# One-shot (on-prem server, prod, after solr-config.zip is in the cwd):
CORE=<core>
TMP=$(mktemp -d) && unzip -o solr-config.zip -d "$TMP"
sudo cp -a ~/nas_docker/solr/data/$CORE/conf ~/nas_docker/solr/data/$CORE/conf.bak.$(date +%s)
sudo rsync -a --delete "$TMP/" ~/nas_docker/solr/data/$CORE/conf/
sudo chown -R 8983:8983 ~/nas_docker/solr/data/$CORE/conf
docker exec wl_solr curl -fsS \
  "http://localhost:8983/solr/admin/cores?action=RELOAD&core=$CORE"
```

---

**Related**

- Module README: `web/modules/contrib/search_api_solr/README.md` (sections on
  standalone Solr and Solr Cloud).
- Production compose: `infra/docker/docker-compose.yml` (`solr` service).
- Staging compose template: `infra/docker/docker-compose.staging.yml.j2`.
- Caddy ingress: `infra/ansible/roles/wl-onprem/templates/Caddyfile.internal.j2`
  (`search.int.wilkesliberty.com`, `search-stg.int.wilkesliberty.com`).
