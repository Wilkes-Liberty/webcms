#!/usr/bin/env bash
#
# refresh-env.sh
# Clone (and sanitize) production Drupal database + public files
# into either the Staging environment or your Local DDEV environment.
#
# Usage:
#   ./scripts/refresh-env.sh
#   ./scripts/refresh-env.sh --target local --db-only --yes
#   ./scripts/refresh-env.sh --target staging
#   ./scripts/refresh-env.sh --target both --fetch -y          # operator: local + staging
#   ./scripts/refresh-env.sh --target local --fetch           # auto-fetch latest backup or live prod
#
# This script is intentionally interactive by default and refuses to run
# without explicit confirmation because it is destructive.
#
# Requirements:
#   - For "local": DDEV must be running (ddev start)
#   - For "staging": You must be on the on-prem host with the Docker stacks up
#   - For "--fetch" (local): SSH access to on-prem (Tailscale) or recent daily backups in ~/Backups/
#   - Drush must be available in the target environment
#   - PostgreSQL client tools (psql, pg_dump, pg_restore) for direct DB ops
#
# Security note for --fetch:
#   The script now STRONGLY prefers sanitized developer snapshots (produced by
#   infra/scripts/create-dev-snapshot.sh, ideally from the staging database).
#   These contain far less sensitive data than raw production dumps.
#   Only when no recent sanitized snapshot is available does it fall back to
#   raw daily backups or a live production dump.
#   Live-fetched raw dumps are auto-deleted after use (unless --keep-dump).
#   Use of raw production data is logged as higher risk.
#
# The heavy sanitization logic lives in the companion PHP scripts in this
# directory (also used by the infra/ansible refresh-staging playbook).

set -euo pipefail

# -----------------------------------------------------------------------------
# Colors & helpers
# -----------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log()  { echo -e "${BLUE}[INFO]${NC} $*" >&2; }
warn() { echo -e "${YELLOW}[WARN]${NC} $*" >&2; }
err()  { echo -e "${RED}[ERROR]${NC} $*" >&2; }
success() { echo -e "${GREEN}[OK]${NC} $*" >&2; }

confirm() {
  local prompt="$1"
  local response
  read -r -p "$prompt (type 'yes' to continue): " response
  [[ "$response" == "yes" ]]
}

# -----------------------------------------------------------------------------
# Defaults & argument parsing
# -----------------------------------------------------------------------------
TARGET=""
DO_DB=1
DO_FILES=0
ASSUME_YES=0
DUMP_FILE=""
FETCH=0
SSH_HOST="${WL_ONPREM_HOST:-wl-onprem}"
REMOTE_STAGING_STATUS="skipped"

# Cleanup tracking for sensitive prod dumps
KEEP_DUMP=0
FETCHED_TEMP_DUMP=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --target)
      TARGET="$2"
      shift 2
      continue
      ;;
    --target=*)
      TARGET="${1#*=}"
      shift
      continue
      ;;
    -t)
      TARGET="$2"
      shift 2
      continue
      ;;
    --db-only)
      DO_FILES=0
      shift
      continue
      ;;
    --files-only)
      DO_DB=0
      DO_FILES=1
      shift
      continue
      ;;
    --both)
      DO_DB=1
      DO_FILES=1
      shift
      continue
      ;;
    --yes|-y)
      ASSUME_YES=1
      shift
      continue
      ;;
    --dump)
      DUMP_FILE="$2"
      shift 2
      continue
      ;;
    --dump=*)
      DUMP_FILE="${1#*=}"
      shift
      continue
      ;;
    -d)
      DUMP_FILE="$2"
      shift 2
      continue
      ;;
    --fetch)
      FETCH=1
      shift
      continue
      ;;
    --ssh-host)
      SSH_HOST="$2"
      shift 2
      continue
      ;;
    --ssh-host=*)
      SSH_HOST="${1#*=}"
      shift
      continue
      ;;
    --keep-dump|--keep)
      KEEP_DUMP=1
      shift
      continue
      ;;
    -h|--help)
      cat <<EOF
Usage: $0 [options]

Options:
  --target local|staging|both
                             Target environment (both = local DDEV + remote staging)
  -t local|staging|both
  --db-only                  Only refresh the database (default for local)
  --files-only               Only sync public files
  --both                     Database + public files
  --dump /path/to/dump.sql   Path to a pre-existing prod DB dump (local only)
  --dump=/path/to/dump.sql
  --fetch                    For local/both: auto-obtain dump. Strongly prefers sanitized
                             dev snapshots (see infra/scripts/create-dev-snapshot.sh).
                             Falls back to raw daily backups, then live prod (with warnings).
  --ssh-host HOST            SSH hostname/alias for on-prem (default: wl-onprem or \$WL_ONPREM_HOST)
  --keep-dump, --keep        Do not delete temporary raw dumps fetched during this run
  --yes, -y                  Skip interactive confirmation prompts

Examples:
  ./scripts/refresh-env.sh
  ./scripts/refresh-env.sh --target local --db-only -y
  ./scripts/refresh-env.sh --target staging --both -y
  ./scripts/refresh-env.sh --target=local --dump=/tmp/prod.dump
  ./scripts/refresh-env.sh --target both --fetch -y
  ./scripts/refresh-env.sh --target local --fetch --ssh-host onprem
  ./scripts/refresh-env.sh --target local --fetch --keep-dump
  # Best practice: generate sanitized snapshots via infra/scripts/create-dev-snapshot.sh
EOF
      exit 0
      ;;
    *)
      err "Unknown option: $1"
      exit 1
      ;;
  esac
done

# -----------------------------------------------------------------------------
# Environment detection helpers
# -----------------------------------------------------------------------------
is_ddev() {
  command -v ddev >/dev/null 2>&1 && ddev status >/dev/null 2>&1
}

is_onprem_staging_ready() {
  # Heuristic: look for the staging Drupal container
  docker ps --format '{{.Names}}' | grep -q '^wl_stg_drupal$' 2>/dev/null
}

# Detect whether this script is running ON the on-prem host itself (as opposed
# to a remote dev laptop SSHing into it). When true, the BOTH-mode remote
# staging refresh skips the SSH dance entirely and runs `make` directly — which
# is faster, more reliable, and avoids the SSH non-login-shell PATH problem
# (Homebrew's /opt/homebrew/bin is not in default sshd PATH on macOS).
#
# Two required signals avoid false positives on dev machines that happen to
# have ~/Repositories/infra cloned:
#   1. The staging Drupal container is running on the LOCAL docker daemon
#   2. The infra repo is checked out at the canonical path
is_running_on_onprem() {
  is_onprem_staging_ready && [[ -d "${HOME}/Repositories/infra/ansible/playbooks" ]]
}

# -----------------------------------------------------------------------------
# Prod dump acquisition helpers (for --fetch on local/both)
# -----------------------------------------------------------------------------
find_latest_daily_backup() {
  local base="${BACKUP_BASE_DIR:-$HOME/Backups/wilkesliberty/daily}"
  # Newest drupal_postgres_*.sql.gz anywhere under the daily tree
  find "$base" -path '*/database/drupal_postgres_*.sql.gz' -type f -print0 2>/dev/null \
    | xargs -0 ls -t 2>/dev/null | head -1 || true
}

# Look for sanitized developer snapshots (preferred over raw prod data)
find_latest_sanitized_dev_snapshot() {
  local base="${DEV_SNAPSHOT_BASE_DIR:-$HOME/Backups/dev-snapshots}"
  # Convention: drupal_dev_sanitized_*.sql.gz
  find "$base" -name 'drupal_dev_sanitized_*.sql.gz' -type f -print0 2>/dev/null \
    | xargs -0 ls -t 2>/dev/null | head -1 || true
}

# Try to fetch the latest sanitized dev snapshot from the remote server over SSH
fetch_sanitized_dev_snapshot_over_ssh() {
  local ssh_host="$1"
  local dest_dir="${DEV_SNAPSHOT_BASE_DIR:-$HOME/Backups/dev-snapshots}"
  mkdir -p "$dest_dir"

  local remote_snapshot
  remote_snapshot=$(ssh -o BatchMode=yes -o ConnectTimeout=15 "$ssh_host" \
    'ls -t ~/Backups/dev-snapshots/drupal_dev_sanitized_*.sql.gz 2>/dev/null | head -1' 2>/dev/null || true)

  if [[ -z "$remote_snapshot" ]]; then
    return 1
  fi

  local local_name
  local_name="$(basename "$remote_snapshot")"
  local dest="$dest_dir/$local_name"

  log "Downloading sanitized dev snapshot from $ssh_host: $remote_snapshot"
  if scp -q -o BatchMode=yes "$ssh_host:$remote_snapshot" "$dest" 2>/dev/null; then
    success "Downloaded sanitized snapshot: $dest"
    echo "$dest"
    return 0
  else
    err "Failed to download sanitized snapshot from $ssh_host"
    return 1
  fi
}

do_live_prod_dump_fetch() {
  local ssh_host="$1"
  local tmp_dump="/tmp/wl-prod-refresh-$(date +%Y%m%d-%H%M%S).sql.gz"

  warn "LIVE PROD DUMP REQUESTED"
  echo "  This will stream a fresh copy of the production database (including PII)"
  echo "  over SSH to this machine. The dump will be sanitized on import."
  echo "  Any temporary file created by this fetch will be automatically and"
  echo "  securely deleted after the refresh (unless --keep-dump is used)."
  echo
  echo "  Target SSH host : $ssh_host"
  echo "  Destination     : $tmp_dump"
  echo

  if [[ $ASSUME_YES -eq 0 ]]; then
    confirm "Really fetch a live production dump now?" || { err "Fetch aborted."; return 1; }
  fi

  log "Connecting to $ssh_host and streaming pg_dump | gzip ..."
  if ssh -o BatchMode=yes -o ConnectTimeout=15 -o StrictHostKeyChecking=accept-new \
      "$ssh_host" 'docker exec wl_postgres pg_dump -U drupal -d drupal | gzip' > "$tmp_dump" 2>/tmp/wl-ssh-dump.err; then
    local size
    size=$(du -h "$tmp_dump" | cut -f1)
    success "Live prod dump fetched: $size → $tmp_dump"
    echo "  This file contains raw production data (PII). It will be securely"
    echo "  deleted after the refresh completes (unless --keep-dump is used)."
    FETCHED_TEMP_DUMP="$tmp_dump"
    DUMP_FILE="$tmp_dump"
    return 0
  else
    err "SSH fetch from $ssh_host failed (see /tmp/wl-ssh-dump.err for details)."
    echo "  Common fixes:"
    echo "    - Ensure Tailscale is up and you can: ssh $ssh_host 'echo ok'"
    echo "    - Add an alias in ~/.ssh/config:  Host wl-onprem ... HostName <tailscale-ip>"
    echo "    - Set WL_ONPREM_HOST or use --ssh-host"
    echo "    - Or manually place a backup in ~/Backups/wilkesliberty/daily/"
    rm -f "$tmp_dump"
    return 1
  fi
}

obtain_prod_dump_for_local() {
  # If user already gave --dump, respect it
  [[ -n "$DUMP_FILE" ]] && return 0
  [[ $FETCH -eq 0 ]] && return 0   # only auto when --fetch (or interactive enhancement later)

  log "Auto-fetch mode (--fetch) enabled for local target."

  # 1. STRONGLY PREFERRED: Sanitized developer snapshot (least sensitive)
  local latest_sanitized
  latest_sanitized=$(find_latest_sanitized_dev_snapshot)
  if [[ -n "$latest_sanitized" && -f "$latest_sanitized" ]]; then
    local age_days=$(( ( $(date +%s) - $(stat -f %m "$latest_sanitized" 2>/dev/null || stat -c %Y "$latest_sanitized") ) / 86400 ))
    log "Found local sanitized dev snapshot: $latest_sanitized (age: ${age_days}d)"
    if [[ $age_days -le 14 || $ASSUME_YES -eq 1 ]]; then
      DUMP_FILE="$latest_sanitized"
      success "Using sanitized developer snapshot (recommended)."
      return 0
    else
      warn "Sanitized snapshot is ${age_days} days old."
      if confirm "Use this older sanitized snapshot?"; then
        DUMP_FILE="$latest_sanitized"
        return 0
      fi
    fi
  fi

  # 2. Try to fetch a recent sanitized snapshot from the server over SSH
  if [[ -n "$SSH_HOST" ]]; then
    local fetched_sanitized
    if fetched_sanitized=$(fetch_sanitized_dev_snapshot_over_ssh "$SSH_HOST" 2>/dev/null); then
      DUMP_FILE="$fetched_sanitized"
      success "Using freshly downloaded sanitized dev snapshot."
      return 0
    fi
  fi

  # 3. Fall back to raw daily backup (still better than live prod)
  local latest_backup
  latest_backup=$(find_latest_daily_backup)
  if [[ -n "$latest_backup" && -f "$latest_backup" ]]; then
    local age_days=$(( ( $(date +%s) - $(stat -f %m "$latest_backup" 2>/dev/null || stat -c %Y "$latest_backup") ) / 86400 ))
    log "Found local daily backup (raw prod data): $latest_backup (age: ${age_days}d)"
    if [[ $age_days -le 7 || $ASSUME_YES -eq 1 ]]; then
      warn "Using raw production backup (contains real PII until sanitized on import)."
      DUMP_FILE="$latest_backup"
      return 0
    else
      warn "Local raw backup is ${age_days} days old."
      if confirm "Use this older raw production backup?"; then
        DUMP_FILE="$latest_backup"
        return 0
      fi
    fi
  fi

  # 4. Last resort: live fetch from production (highest risk)
  echo
  warn "No sanitized dev snapshot or recent daily backup found."
  echo "  The only remaining option is a live dump directly from production."
  echo "  This transfers raw PII to this machine temporarily."
  echo

  if ! confirm "Fetch a live raw production dump over SSH now?"; then
    err "No acceptable data source available — aborting."
    exit 1
  fi

  do_live_prod_dump_fetch "$SSH_HOST" || exit 1
}

# Run a sanitizer PHP script safely inside the DDEV container.
#
# Implementation note: DDEV bind-mounts the host project tree at
# /var/www/html in the web container, so the sanitizer at
# <project>/scripts/<name>.php on the host is already visible inside the
# container at /var/www/html/scripts/<name>.php — no copy is needed.
#
# Drush's `scr` resolves script paths against the Drupal root (it
# concatenates the docroot to whatever path we pass — even absolute paths
# get joined, producing things like `/var/www/html//tmp/foo.php`), so we
# pass a project-relative path that resolves cleanly.
#
# Returns 1 (and increments SANITIZER_FAILURES) on failure so callers can
# decide whether to abort or continue.
SANITIZER_FAILURES=0
run_ddev_sanitizer() {
  local host_script="$1"
  local script_name
  script_name="$(basename "$host_script")"

  if [[ ! -f "$host_script" ]]; then
    warn "Sanitizer not found at $host_script — skipping"
    SANITIZER_FAILURES=$((SANITIZER_FAILURES + 1))
    return 1
  fi

  if ! ddev drush scr "scripts/$script_name"; then
    err "Sanitizer $script_name FAILED — local DB may still contain unsanitized PII for fields this script targets."
    SANITIZER_FAILURES=$((SANITIZER_FAILURES + 1))
    return 1
  fi
}

# Securely remove a temporary prod dump we fetched during this run.
# We only ever delete files we created ourselves (in /tmp), never
# pre-existing daily backups or user-supplied --dump paths.
cleanup_fetched_dump() {
  [[ -z "$FETCHED_TEMP_DUMP" ]] && return 0
  [[ $KEEP_DUMP -eq 1 ]] && {
    warn "Keeping temporary prod dump at $FETCHED_TEMP_DUMP (--keep-dump)"
    return 0
  }
  [[ ! -f "$FETCHED_TEMP_DUMP" ]] && return 0

  log "Securely removing temporary production dump (contains raw PII)..."

  if command -v srm >/dev/null 2>&1; then
    srm -v "$FETCHED_TEMP_DUMP" 2>/dev/null || true
  else
    # macOS rm -P overwrites before unlinking (good enough baseline)
    rm -P "$FETCHED_TEMP_DUMP" 2>/dev/null || rm -f "$FETCHED_TEMP_DUMP"
  fi

  if [[ ! -f "$FETCHED_TEMP_DUMP" ]]; then
    success "Temporary prod dump removed."
  else
    warn "Could not fully remove $FETCHED_TEMP_DUMP — please delete it manually."
  fi
}

# Best-effort cleanup of any live-fetched sensitive dump, even on error/abort.
trap cleanup_fetched_dump EXIT

# -----------------------------------------------------------------------------
# Target selection (interactive if not provided)
# -----------------------------------------------------------------------------
if [[ -z "$TARGET" ]]; then
  echo
  echo "=== Wilkes & Liberty — Production Data Refresh ==="
  echo
  echo "This operation is DESTRUCTIVE. It will overwrite the target environment."
  echo
  echo "Select target environment:"
  echo "  1) Local development (DDEV)     — recommended for most developers"
  echo "  2) Staging (on-prem Docker)     — full production-grade sanitization"
  echo "  3) Both (local + staging)       — operator workflow (requires SSH + DDEV)"
  echo
  read -r -p "Choice [1-3]: " choice
  case "$choice" in
    1) TARGET="local" ;;
    2) TARGET="staging" ;;
    3) TARGET="both" ;;
    *) err "Invalid choice"; exit 1 ;;
  esac
fi

# Normalize "both" (operator convenience: local first, then remote staging via infra tooling)
DO_BOTH=0
if [[ "$TARGET" == "both" ]]; then
  DO_BOTH=1
  TARGET="local"
  log "Target mode: BOTH (local DDEV + remote staging refresh)"
fi

# -----------------------------------------------------------------------------
# Main logic branches
# -----------------------------------------------------------------------------
case "$TARGET" in
  local|ddev)
    TARGET="local"
    if ! is_ddev; then
      err "DDEV does not appear to be running."
      echo "Please run: ddev start"
      exit 1
    fi

    DRUSH="ddev drush"
    DB_CONTAINER="db"          # DDEV's default DB service
    DRUPAL_ROOT="/var/www/html/web"
    FILES_DIR="web/sites/default/files"

    log "Target: LOCAL (DDEV)"
    [[ $DO_DB -eq 1 ]] && log "  - Database + sanitization: yes"
    [[ $DO_FILES -eq 1 ]] && warn "  - Files sync: limited support in local mode (usually skipped)"

    if [[ $ASSUME_YES -eq 0 ]]; then
      echo
      warn "You are about to WIPE your local DDEV database (and optionally files)."
      echo "  Preferred data source: sanitized dev snapshots (least sensitive)."
      confirm "Really proceed with local refresh?" || { log "Aborted by user."; exit 0; }
    fi

    if [[ $DO_DB -eq 1 ]]; then
      obtain_prod_dump_for_local

      if [[ -z "$DUMP_FILE" ]]; then
        echo
        echo "You did not provide a dump file."
        echo "Best practice: Use sanitized dev snapshots (generated by infra/scripts/create-dev-snapshot.sh)."
        echo "  These are the least sensitive option for local development."
        echo
        echo "Other options:"
        echo "  - Recent daily backup from ~/Backups/wilkesliberty/daily/"
        echo "  - Re-run with --fetch (will prefer sanitized snapshots)"
        echo
        read -r -p "Path to dump file (or leave empty to abort): " DUMP_FILE
        [[ -z "$DUMP_FILE" ]] && { err "No dump supplied — aborting."; exit 1; }
      fi

      if [[ ! -f "$DUMP_FILE" ]]; then
        err "Dump file not found: $DUMP_FILE"
        exit 1
      fi

      log "Dropping local database..."
      $DRUSH sql:drop -y || true

      # drush sql:drop only drops tables; user-defined functions (rand,
      # substring_index, etc. — Drupal-on-Postgres compatibility shims)
      # persist between refreshes and cause "function already exists"
      # errors when the dump re-runs CREATE FUNCTION on import. Drop all
      # user functions in public schema so the dump can recreate them
      # cleanly. Idempotent and safe (only affects user-defined functions
      # — built-in pg_catalog functions are untouched).
      log "Dropping user-defined functions in public schema..."
      ddev exec --service db psql -U db -d db -At -c "
        DO \$\$
        DECLARE r RECORD;
        BEGIN
          FOR r IN
            SELECT n.nspname, p.proname,
                   pg_get_function_identity_arguments(p.oid) AS args
            FROM pg_proc p
            JOIN pg_namespace n ON p.pronamespace = n.oid
            WHERE n.nspname = 'public'
          LOOP
            EXECUTE 'DROP FUNCTION IF EXISTS '
              || quote_ident(r.nspname) || '.' || quote_ident(r.proname)
              || '(' || r.args || ') CASCADE';
          END LOOP;
        END
        \$\$;" >/dev/null 2>&1 || warn "Function cleanup query returned non-zero (continuing)."

      log "Importing dump: $DUMP_FILE"
      # Support both plain SQL and custom-format (pg_dump -F c) or gzipped.
      # For large plain SQL .gz files we bypass drush sql:cli (which warns about
      # stdin performance) and use psql directly inside the db service. We also
      # defensively filter the "function already exists" messages — these
      # should be eliminated by the pre-import function drop above, but the
      # filter remains as a backstop in case the dump adds new compat shims.
      if [[ "$DUMP_FILE" == *.gz ]]; then
        gunzip -c "$DUMP_FILE" \
          | ddev exec --service db psql -U db -d db --quiet 2>&1 \
          | grep -vE 'ERROR:  function "[^"]+" already exists with same argument types' \
          || true
      elif file "$DUMP_FILE" | grep -q "PostgreSQL custom"; then
        # Custom format needs pg_restore inside the db container (already efficient)
        ddev exec -s db pg_restore -U db -d db --no-owner --no-acl < "$DUMP_FILE" || true
      else
        $DRUSH sql:cli < "$DUMP_FILE"
      fi

      log "Running initial cache rebuild (so Drush can bootstrap)..."
      $DRUSH cr

      # Compute once so the helper and any later code can use it
      SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

      log "Sanitizing user emails + password hashes..."
      $DRUSH sql:query "UPDATE users_field_data SET mail = 'noreply+local-' || uid || '@wilkesliberty.com' WHERE uid > 0"
      $DRUSH sql:query "UPDATE users_field_data SET pass = '\$2y\$10\$local.locked.out.' || uid WHERE uid > 1"

      log "Running custom email field sanitizer..."
      run_ddev_sanitizer "$SCRIPT_DIR/sanitize_email_fields.php"

      log "Running webform email sanitizer..."
      run_ddev_sanitizer "$SCRIPT_DIR/sanitize_webform_emails.php"

      log "Truncating watchdog..."
      $DRUSH sql:query "TRUNCATE watchdog" || true

      log "Updating Next.js integration URLs for local (https — DDEV serves trusted TLS via mkcert)..."
      $DRUSH config:set -y next.next_site.wilkesliberty_ui base_url "https://api.wilkesliberty.dev" || true
      $DRUSH config:set -y next.next_site.wilkesliberty_ui preview_url "https://api.wilkesliberty.dev/api/draft" || true
      $DRUSH config:set -y next.next_site.wilkesliberty_ui revalidate_url "https://api.wilkesliberty.dev/api/revalidate" || true

      log "Setting a known local admin password (admin / admin)"
      $DRUSH user:password admin admin || true

      log "Final cache rebuild + DB updates..."
      $DRUSH updatedb -y || true
      $DRUSH cr

      if [[ $SANITIZER_FAILURES -gt 0 ]]; then
        echo
        err "Local DB refresh COMPLETED WITH SANITIZER FAILURES ($SANITIZER_FAILURES script(s) failed)."
        warn "User emails + non-admin password hashes WERE sanitized (direct SQL above)."
        warn "Custom email fields and/or webform submission emails may NOT have been sanitized."
        warn "If those fields contain real prod data, your local DB is currently storing raw PII."
        echo "  Investigate the error output above, then re-run the failed sanitizers manually:"
        echo "    cd ~/Repositories/webcms && ddev drush scr scripts/sanitize_email_fields.php"
        echo "    cd ~/Repositories/webcms && ddev drush scr scripts/sanitize_webform_emails.php"
        echo
      else
        success "Local database refresh complete (all sanitizers ran cleanly)."
      fi
      echo "  Login: https://api.wilkesliberty.dev/user/login"
      echo "         https://api.ddev.site/user/login  (also valid)"
      echo "  user: admin / pass: admin   (change immediately!)"

      # Clean up any temporary prod dump we fetched (unless --keep-dump)
      cleanup_fetched_dump
    fi

    if [[ $DO_FILES -eq 1 ]]; then
      warn "File sync for local DDEV is not fully automated."
      echo "If you have Tailscale + SSH access to the on-prem host you can do:"
      echo "  rsync -a --delete user@onprem:~/nas_docker/drupal/files/ web/sites/default/files/"
      echo "Then run: ddev drush cr"
    fi

    # --- BOTH mode: after successful local, run the full staging refresh ---
    if [[ $DO_BOTH -eq 1 ]]; then
      echo
      INFRA_DIR="${HOME}/Repositories/infra"

      # If we ARE the on-prem host, skip the SSH dance entirely. Running make
      # in this same interactive shell inherits the proper PATH (Homebrew,
      # ansible-playbook, sops, etc.) without needing a login shell wrapper.
      if is_running_on_onprem; then
        log "This machine IS the on-prem host (staging containers + infra repo local) — running make directly, no SSH."
        echo "  Working dir: $INFRA_DIR"
        echo

        if [[ ! -d "$INFRA_DIR" ]]; then
          err "Expected infra repo at $INFRA_DIR but it is missing."
          REMOTE_STAGING_STATUS="failed"
        elif [[ $ASSUME_YES -eq 1 ]]; then
          if printf "yes\n" | make -C "$INFRA_DIR" refresh-staging; then
            success "Staging refresh completed successfully (local make, no SSH)."
            REMOTE_STAGING_STATUS="success"
          else
            warn "Staging refresh command returned non-zero (check output above)."
            REMOTE_STAGING_STATUS="failed"
          fi
        else
          if make -C "$INFRA_DIR" refresh-staging; then
            success "Staging refresh completed."
            REMOTE_STAGING_STATUS="success"
          else
            warn "Staging refresh did not finish cleanly."
            REMOTE_STAGING_STATUS="failed"
          fi
        fi
      else
        # Remote operator path: SSH to the on-prem host.
        log "Local DDEV refresh finished. Proceeding to remote staging refresh (operator mode)..."
        echo "  This will SSH to $SSH_HOST and run the full infra make refresh-staging"
        echo "  (battle-tested path with SOPS secrets, Postmark sandbox, etc.)."
        echo

        # Quick reachability check (non-interactive)
        if ! ssh -o BatchMode=yes -o ConnectTimeout=10 "$SSH_HOST" 'echo "SSH OK"' >/dev/null 2>&1; then
          err "SSH to $SSH_HOST failed (BatchMode)."
          echo "  Configure ~/.ssh/config alias 'wl-onprem' (or set WL_ONPREM_HOST / --ssh-host)."
          # If Tailscale is up, try to surface a known-good on-prem hostname.
          if command -v tailscale >/dev/null 2>&1; then
            ts_onprem=$(tailscale status 2>/dev/null \
              | awk '/wl-(onprem|localhost|nas)/ && $5 != "offline" {print $2; exit}')
            if [[ -n "$ts_onprem" ]]; then
              echo
              echo "  Tailscale shows an on-prem host reachable now: $ts_onprem"
              echo "  Quick options:"
              echo "    - One-off:   $0 --target both --fetch -y --ssh-host $ts_onprem"
              echo "    - Per-shell: export WL_ONPREM_HOST=$ts_onprem"
              echo "    - Permanent: add to ~/.ssh/config:"
              echo "        Host wl-onprem"
              echo "          HostName $ts_onprem"
              echo "          User    \$YOUR_USER"
            fi
          fi
          echo
          echo "  Then run manually: ssh $SSH_HOST 'cd ~/Repositories/infra && make refresh-staging'"
          REMOTE_STAGING_STATUS="failed"
        else
          # Wrap the remote command in `bash -lc` so it runs under a LOGIN
          # shell. Without this, sshd hands /usr/bin:/bin:/usr/sbin:/sbin and
          # Homebrew binaries (ansible-playbook, sops, etc.) are not on PATH
          # — make refresh-staging then fails with
          # "ansible-playbook: No such file or directory".
          if [[ $ASSUME_YES -eq 1 ]]; then
            log "Running non-interactive remote staging refresh..."
            if ssh -T "$SSH_HOST" 'bash -lc "echo yes | make -C \"\$HOME/Repositories/infra\" refresh-staging"'; then
              success "Remote staging refresh completed successfully."
              REMOTE_STAGING_STATUS="success"
            else
              warn "Remote staging refresh command returned non-zero (check output above)."
              REMOTE_STAGING_STATUS="failed"
            fi
          else
            log "Starting interactive remote staging refresh (you will be prompted on the remote)..."
            if ssh -t "$SSH_HOST" 'bash -lc "make -C \"\$HOME/Repositories/infra\" refresh-staging"'; then
              success "Remote staging refresh completed."
              REMOTE_STAGING_STATUS="success"
            else
              warn "Remote staging refresh did not finish cleanly."
              REMOTE_STAGING_STATUS="failed"
            fi
          fi
        fi
      fi
    fi
    ;;

  staging)
    if ! is_onprem_staging_ready; then
      err "Staging containers (wl_stg_*) do not appear to be running on this host."
      echo "This target is intended to be run on the on-prem production server."
      echo "On the server, prefer:  make -C ~/Repositories/infra refresh-staging"
      exit 1
    fi

    log "Target: STAGING (on-prem Docker)"
    [[ $DO_DB -eq 1 ]] && log "  - Full database + heavy sanitization"
    [[ $DO_FILES -eq 1 ]] && log "  - Public files rsync (no private files)"

    if [[ $ASSUME_YES -eq 0 ]]; then
      echo
      warn "This will COMPLETELY REPLACE the staging environment with sanitized prod data."
      echo "All current staging content, users, and logs will be lost."
      confirm "Proceed with staging refresh?" || { log "Aborted."; exit 0; }
    fi

    # For a full production-grade staging refresh (Postmark sandbox, SOPS secrets, etc.)
    # the canonical path is still the Ansible playbook.
    echo
    log "For the complete, battle-tested staging refresh (including Postmark sandbox token,"
    log "SOPS-loaded secrets, proper file paths, etc.) we strongly recommend:"
    echo
    echo "    cd ~/Repositories/infra && make refresh-staging"
    echo
    warn "The script below performs a lighter in-place version using Docker exec."
    echo

    if ! confirm "Continue with the lighter in-script staging path anyway?"; then
      log "Falling back to make target recommended."
      exit 0
    fi

    # Lighter in-script path (good enough for many cases, no SOPS dependency here)
    PROD_PG="wl_postgres"
    STG_PG="wl_stg_postgres"
    STG_DRUPAL="wl_stg_drupal"
    DB_NAME="drupal"
    DB_USER="drupal"
    DUMP_PATH="/tmp/wl-staging-refresh.dump"

    log "Dumping production database..."
    docker exec -e PGPASSWORD="${DRUPAL_DB_PASSWORD:-}" "$PROD_PG" \
      pg_dump -U "$DB_USER" -F c -f "$DUMP_PATH" "$DB_NAME" || {
        err "Dump failed. Make sure DRUPAL_DB_PASSWORD is in your environment or export it."
        exit 1
      }

    log "Dropping and recreating staging database..."
    docker exec -e PGPASSWORD="${STG_DRUPAL_DB_PASSWORD:-}" "$STG_PG" dropdb -U "$DB_USER" --if-exists "$DB_NAME" || true
    docker exec -e PGPASSWORD="${STG_DRUPAL_DB_PASSWORD:-}" "$STG_PG" createdb -U "$DB_USER" "$DB_NAME"

    docker cp "$DUMP_PATH" "$STG_PG":"$DUMP_PATH"
    docker exec -e PGPASSWORD="${STG_DRUPAL_DB_PASSWORD:-}" "$STG_PG" \
      pg_restore -U "$DB_USER" -d "$DB_NAME" --no-owner --no-acl "$DUMP_PATH" || true

    log "Granting privileges to staging runtime user (belt + suspenders)..."
    # Run as the real postgres superuser without forcing PGPASSWORD.
    # This has proven most reliable. We grant broadly so drush cr / updatedb
    # do not fail after a --no-owner --no-acl restore.
    docker exec "$STG_PG" psql -U postgres -d "$DB_NAME" -v ON_ERROR_STOP=1 <<'EOSQL' || true
-- Runtime application user (what the container actually connects as)
GRANT ALL PRIVILEGES ON DATABASE "$DB_NAME" TO "wl_app";
GRANT ALL ON SCHEMA public TO "wl_app";
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO "wl_app";
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO "wl_app";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO "wl_app";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO "wl_app";

-- Owner / restore user
GRANT ALL PRIVILEGES ON DATABASE "$DB_NAME" TO "$DB_USER";
GRANT ALL ON SCHEMA public TO "$DB_USER";
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO "$DB_USER";
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO "$DB_USER";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO "$DB_USER";
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO "$DB_USER";
EOSQL

    log "Verifying that the staging app user (wl_app) can read the config table..."
    docker exec -e PGPASSWORD="${STG_DRUPAL_DB_PASSWORD:-}" "$STG_PG" \
      psql -U "wl_app" -d "$DB_NAME" -v ON_ERROR_STOP=0 \
      -c "SELECT COUNT(*) FROM config WHERE name = 'core.extension';" || true

    if [[ $DO_FILES -eq 1 ]]; then
      log "Rsyncing public files (this may take a while)..."
      rsync -a --delete ~/nas_docker/drupal/files/ ~/nas_docker_staging/drupal/files/
    fi

    log "Initial Drush bootstrap..."
    docker exec "$STG_DRUPAL" drush cr

    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

    log "Applying sanitization (emails, passwords, custom fields, webforms)..."
    docker exec "$STG_DRUPAL" drush sql:query "UPDATE users_field_data SET mail = 'noreply+stg-' || uid || '@wilkesliberty.com' WHERE uid > 0"
    docker exec "$STG_DRUPAL" drush sql:query "UPDATE users_field_data SET pass = '\$2y\$10\$staging.locked.out.' || uid WHERE uid > 1"

    docker cp "$SCRIPT_DIR/sanitize_email_fields.php" "$STG_DRUPAL:/tmp/sanitize_email_fields.php"
    docker exec "$STG_DRUPAL" drush scr /tmp/sanitize_email_fields.php
    docker exec "$STG_DRUPAL" rm -f /tmp/sanitize_email_fields.php

    docker cp "$SCRIPT_DIR/sanitize_webform_emails.php" "$STG_DRUPAL:/tmp/sanitize_webform_emails.php"
    docker exec "$STG_DRUPAL" drush scr /tmp/sanitize_webform_emails.php
    docker exec "$STG_DRUPAL" rm -f /tmp/sanitize_webform_emails.php

    docker exec "$STG_DRUPAL" drush sql:query "TRUNCATE watchdog" || true

    # Basic Next.js + site config for staging
    docker exec "$STG_DRUPAL" drush config:set -y system.site mail "staging+drupal@wilkesliberty.com" || true
    docker exec "$STG_DRUPAL" drush config:set -y next.next_site.wilkesliberty_ui base_url "https://stg.int.wilkesliberty.com" || true

    docker exec "$STG_DRUPAL" drush user:password admin "${STG_DRUPAL_ADMIN_PASSWORD:-ChangeMeNow!}" || true

    docker exec "$STG_DRUPAL" drush updatedb -y || true
    docker exec "$STG_DRUPAL" drush cr

    success "Lighter staging refresh complete."
    echo "  URL:    https://api-stg.int.wilkesliberty.com"
    echo "  Admin:  admin / (password from your env or the one you just set)"
    echo
    warn "For full Postmark sandbox + all SOPS-managed secrets, run the make target instead."
    ;;

  *)
    err "Unknown target: $TARGET (valid: local, staging)"
    exit 1
    ;;
esac

success "Refresh operation finished."
echo
if [[ $DO_BOTH -eq 1 ]]; then
  if [[ "$REMOTE_STAGING_STATUS" == "success" ]]; then
    echo "Both local DDEV and remote staging were refreshed successfully."
  else
    echo "Local DDEV refresh completed."
    echo "Remote staging refresh was skipped or failed (see messages above)."
    echo "  Manual command: ssh $SSH_HOST 'cd ~/Repositories/infra && make refresh-staging'"
  fi
else
  echo "Next steps:"
  echo "  - Verify the site loads"
  echo "  - Change any default passwords you accepted"
  echo "  - Run 'ddev drush cex -y' (local) or equivalent if you need to capture config changes"
fi
echo
