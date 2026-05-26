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
#
# This script is intentionally interactive by default and refuses to run
# without explicit confirmation because it is destructive.
#
# Requirements:
#   - For "local": DDEV must be running (ddev start)
#   - For "staging": You must be on the on-prem host with the Docker stacks up
#   - Drush must be available in the target environment
#   - PostgreSQL client tools (psql, pg_dump, pg_restore) for direct DB ops
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
    -h|--help)
      cat <<EOF
Usage: $0 [options]

Options:
  --target local|staging     Target environment (also supports --target=local)
  -t local|staging
  --db-only                  Only refresh the database (default for local)
  --files-only               Only sync public files
  --both                     Database + public files
  --dump /path/to/dump.sql   Path to a pre-existing prod DB dump (local only)
  --dump=/path/to/dump.sql
  --yes, -y                  Skip interactive confirmation prompts

Examples:
  ./scripts/refresh-env.sh
  ./scripts/refresh-env.sh --target local --db-only -y
  ./scripts/refresh-env.sh --target staging --both -y
  ./scripts/refresh-env.sh --target=local --dump=/tmp/prod.dump
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
  echo
  read -r -p "Choice [1-2]: " choice
  case "$choice" in
    1) TARGET="local" ;;
    2) TARGET="staging" ;;
    *) err "Invalid choice"; exit 1 ;;
  esac
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
      confirm "Really proceed with local refresh?" || { log "Aborted by user."; exit 0; }
    fi

    if [[ $DO_DB -eq 1 ]]; then
      if [[ -z "$DUMP_FILE" ]]; then
        echo
        echo "You did not provide a dump file."
        echo "Common ways to obtain a prod dump:"
        echo "  1. From the on-prem server: docker exec wl_postgres pg_dump -U drupal -F c drupal > prod.dump"
        echo "  2. Then scp it to your machine and pass --dump=/path/to/prod.dump"
        echo
        read -r -p "Path to existing prod dump file (or leave empty to abort): " DUMP_FILE
        [[ -z "$DUMP_FILE" ]] && { err "No dump supplied — aborting."; exit 1; }
      fi

      if [[ ! -f "$DUMP_FILE" ]]; then
        err "Dump file not found: $DUMP_FILE"
        exit 1
      fi

      log "Dropping local database..."
      $DRUSH sql:drop -y || true

      log "Importing dump: $DUMP_FILE"
      # Support both plain SQL and custom-format (pg_dump -F c) or gzipped
      if [[ "$DUMP_FILE" == *.gz ]]; then
        gunzip -c "$DUMP_FILE" | $DRUSH sql:cli
      elif file "$DUMP_FILE" | grep -q "PostgreSQL custom"; then
        # Custom format needs pg_restore inside the db container
        ddev exec -s db pg_restore -U db -d db --no-owner --no-acl < "$DUMP_FILE" || true
      else
        $DRUSH sql:cli < "$DUMP_FILE"
      fi

      log "Running initial cache rebuild (so Drush can bootstrap)..."
      $DRUSH cr

      # Run sanitizers (they live next to this script)
      SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

      log "Sanitizing user emails + password hashes..."
      $DRUSH sql:query "UPDATE users_field_data SET mail = 'noreply+local-' || uid || '@wilkesliberty.com' WHERE uid > 0"
      $DRUSH sql:query "UPDATE users_field_data SET pass = '\$2y\$10\$local.locked.out.' || uid WHERE uid > 1"

      log "Running custom email field sanitizer..."
      $DRUSH scr "$SCRIPT_DIR/sanitize_email_fields.php" || true

      log "Running webform email sanitizer..."
      $DRUSH scr "$SCRIPT_DIR/sanitize_webform_emails.php" || true

      log "Truncating watchdog..."
      $DRUSH sql:query "TRUNCATE watchdog" || true

      log "Updating Next.js integration URLs for local..."
      $DRUSH config:set -y next.next_site.wilkesliberty_ui base_url "https://api.wilkesliberty.dev" || true
      $DRUSH config:set -y next.next_site.wilkesliberty_ui preview_url "https://api.wilkesliberty.dev/api/draft" || true
      $DRUSH config:set -y next.next_site.wilkesliberty_ui revalidate_url "https://api.wilkesliberty.dev/api/revalidate" || true

      log "Setting a known local admin password (admin / admin)"
      $DRUSH user:password admin admin || true

      log "Final cache rebuild + DB updates..."
      $DRUSH updatedb -y || true
      $DRUSH cr

      success "Local database refresh complete."
      echo "  Login: https://api.wilkesliberty.dev/user/login"
      echo "  user: admin / pass: admin   (change immediately!)"
    fi

    if [[ $DO_FILES -eq 1 ]]; then
      warn "File sync for local DDEV is not fully automated."
      echo "If you have Tailscale + SSH access to the on-prem host you can do:"
      echo "  rsync -a --delete user@onprem:~/nas_docker/drupal/files/ web/sites/default/files/"
      echo "Then run: ddev drush cr"
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
echo "Next steps:"
echo "  - Verify the site loads"
echo "  - Change any default passwords you accepted"
echo "  - Run 'ddev drush cex -y' (local) or equivalent if you need to capture config changes"
echo
