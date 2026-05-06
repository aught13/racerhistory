#!/usr/bin/env bash
#
# run-release-migrations.sh
#
# Cron-safe migration runner for shared hosting.
# Runs migrations only when a new deployed release marker is detected.

set -euo pipefail

# Cron often has a minimal PATH; include common binary locations.
export PATH="/usr/local/bin:/usr/bin:/bin:${PATH:-}"

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="$(cd -- "${SCRIPT_DIR}/.." && pwd)"
RELEASE_FILE="${APP_ROOT}/.release_sha"
STATE_DIR="${APP_ROOT}/tmp/deploy"
STATE_FILE="${STATE_DIR}/.last_migrated_release"
LOCK_DIR="${STATE_DIR}/.migrate_lock"

log() {
    printf '%s [release-migrate] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

if ! command -v php >/dev/null 2>&1; then
    log "php binary not found in PATH"
    exit 1
fi

if [[ ! -f "${RELEASE_FILE}" ]]; then
    log "no .release_sha marker found, skipping"
    exit 0
fi

current_release="$(tr -d '[:space:]' < "${RELEASE_FILE}" || true)"
if [[ -z "${current_release}" ]]; then
    log "empty .release_sha marker, skipping"
    exit 0
fi

mkdir -p "${STATE_DIR}"

last_release=""
if [[ -f "${STATE_FILE}" ]]; then
    last_release="$(tr -d '[:space:]' < "${STATE_FILE}" || true)"
fi

if [[ "${current_release}" == "${last_release}" ]]; then
    log "release ${current_release} already migrated"
    exit 0
fi

if ! mkdir "${LOCK_DIR}" 2>/dev/null; then
    log "another migration run is in progress, skipping"
    exit 0
fi
trap 'rmdir "${LOCK_DIR}" >/dev/null 2>&1 || true' EXIT

cd "${APP_ROOT}"
log "new release detected (${current_release}); running migrations"
php bin/cake migrations migrate --no-interaction
php bin/cake cache clear_all || true
printf '%s\n' "${current_release}" > "${STATE_FILE}"
log "migration run complete for release ${current_release}"
