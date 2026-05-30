#!/usr/bin/env bash
#
# deploy.sh — RacerHistory production deployment & audit script
#
# Audits the environment, installs dependencies, runs migrations,
# and validates the deployment is production-ready.
#
# Usage:
#   bin/deploy.sh [--check-only] [--skip-migrations] [--skip-tests]
#
# Options:
#   --check-only       Audit without making changes (dry run)
#   --skip-migrations  Skip database migration step
#   --skip-tests       Skip running the test suite
#
# Exit codes:
#   0  All checks passed and deployment completed
#   1  Fatal error — deployment aborted
#   2  Warnings found (non-fatal) — review output

set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="$(cd -- "${SCRIPT_DIR}/.." && pwd)"

CHECK_ONLY=0
SKIP_MIGRATIONS=0
SKIP_TESTS=0
WARNINGS=0
ERRORS=0

# ─── Helpers ───────────────────────────────────────────────────────────────────

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log()  { printf "${BLUE}[deploy]${NC} %s\n" "$*"; }
ok()   { printf "${GREEN}  ✓${NC} %s\n" "$*"; }
warn() { printf "${YELLOW}  ⚠${NC} %s\n" "$*"; WARNINGS=$((WARNINGS + 1)); }
fail() { printf "${RED}  ✗${NC} %s\n" "$*"; ERRORS=$((ERRORS + 1)); }
hr()   { printf '%*s\n' 60 '' | tr ' ' '─'; }

has_cmd() { command -v "$1" >/dev/null 2>&1; }

# ─── Parse arguments ──────────────────────────────────────────────────────────

for arg in "$@"; do
    case "$arg" in
        --check-only)      CHECK_ONLY=1 ;;
        --skip-migrations) SKIP_MIGRATIONS=1 ;;
        --skip-tests)      SKIP_TESTS=1 ;;
        --help|-h)
            head -18 "$0" | tail -16
            exit 0
            ;;
        *)
            printf "Unknown option: %s\n" "$arg"
            exit 1
            ;;
    esac
done

cd "$APP_ROOT"

printf "\n${BLUE}══════════════════════════════════════════════════════════${NC}\n"
printf "${BLUE}  RacerHistory Production Deploy$([ $CHECK_ONLY -eq 1 ] && echo ' (Audit Only)')${NC}\n"
printf "${BLUE}══════════════════════════════════════════════════════════${NC}\n\n"

# ─── 1. PHP version check ─────────────────────────────────────────────────────

log "Checking PHP version..."
if has_cmd php; then
    PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
    PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
    PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')
    if [ "$PHP_MAJOR" -ge 8 ] && [ "$PHP_MINOR" -ge 1 ]; then
        ok "PHP $PHP_VER"
    else
        fail "PHP $PHP_VER detected — requires 8.1+"
    fi
else
    fail "PHP not found in PATH"
fi

# ─── 2. Required PHP extensions ───────────────────────────────────────────────

log "Checking required PHP extensions..."
REQUIRED_EXTS=(mbstring intl pdo pdo_mysql simplexml)
for ext in "${REQUIRED_EXTS[@]}"; do
    if php -r "exit(extension_loaded('$ext') ? 0 : 1);" 2>/dev/null; then
        ok "ext-$ext"
    else
        fail "Missing PHP extension: $ext"
    fi
done

# ─── 3. Composer check ────────────────────────────────────────────────────────

log "Checking Composer..."
if has_cmd composer; then
    ok "Composer $(composer --version --no-ansi 2>/dev/null | head -1 | sed 's/Composer version //')"
else
    fail "Composer not found — required for dependency install"
fi

# ─── 4. Node.js (optional but recommended) ────────────────────────────────────

log "Checking Node.js (optional)..."
if has_cmd node; then
    NODE_VER=$(node --version 2>/dev/null)
    ok "Node.js $NODE_VER"
else
    warn "Node.js not found — JS tests and linting unavailable"
fi

# ─── 5. Local config file ─────────────────────────────────────────────────────

hr
log "Checking configuration..."

if [ -f config/app_local.php ]; then
    ok "config/app_local.php exists"

    # Check debug is off - inspect the default value in app.php (env override checked separately)
    if grep -qE "'debug'\s*=>\s*true" config/app_local.php 2>/dev/null; then
        fail "Debug mode is explicitly ON in config/app_local.php — set to false for production"
    elif grep -qE "env\('DEBUG',\s*true\)" config/app_local.php 2>/dev/null; then
        warn "Debug defaults to true in config/app_local.php — ensure DEBUG=false in production environment"
    else
        ok "Debug mode is not hardcoded to ON"
    fi
    # Verify via environment
    if [ "${DEBUG:-}" = "true" ] || [ "${DEBUG:-}" = "1" ]; then
        fail "DEBUG environment variable is set to '${DEBUG}' — unset or set to false for production"
    else
        ok "DEBUG environment variable is not set to true"
    fi

    # Check security salt is not default
    if grep -q '__SALT__' config/app_local.php 2>/dev/null; then
        fail "Security salt is still the default '__SALT__' — generate a unique salt"
    else
        ok "Security salt is configured"
    fi

    # Check database host is not localhost for production
    DB_HOST=$(grep -oP "'host'\s*=>\s*'\K[^']+" config/app_local.php 2>/dev/null | head -1 || echo "not_set")
    if [ "$DB_HOST" = "localhost" ] || [ "$DB_HOST" = "127.0.0.1" ]; then
        warn "Database host is '$DB_HOST' — verify this is correct for production"
    elif [ "$DB_HOST" = "not_set" ]; then
        warn "Could not detect database host from config"
    else
        ok "Database host: $DB_HOST"
    fi
else
    fail "config/app_local.php not found — copy from config/app_local.example.php and configure"
    echo ""
    echo "  Quick fix:"
    echo "    cp config/app_local.example.php config/app_local.php"
    echo "    # Then edit config/app_local.php with production credentials"
    echo ""
fi

# ─── 6. Directory permissions ──────────────────────────────────────────────────

hr
log "Checking directory permissions..."

WRITABLE_DIRS=(tmp logs tmp/cache tmp/cache/models tmp/cache/persistent tmp/sessions webroot/img/storage)
for d in "${WRITABLE_DIRS[@]}"; do
    if [ -d "$d" ]; then
        if [ -w "$d" ]; then
            ok "$d/ is writable"
        else
            fail "$d/ exists but is NOT writable"
        fi
    else
        if [ $CHECK_ONLY -eq 0 ]; then
            mkdir -p "$d"
            ok "$d/ created"
        else
            fail "$d/ does not exist"
        fi
    fi
done

# ─── 7. Install dependencies ──────────────────────────────────────────────────

hr
log "Dependencies..."

if [ $CHECK_ONLY -eq 0 ]; then
    log "Installing PHP dependencies (production)..."
    if composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5; then
        ok "Composer dependencies installed (no-dev)"
    else
        fail "Composer install failed"
    fi
else
    if [ -d vendor ]; then
        # Check if dev packages are present
        if [ -d vendor/phpunit ] || [ -d vendor/phpstan ]; then
            warn "Dev dependencies present in vendor/ — run 'composer install --no-dev' for production"
        else
            ok "vendor/ exists (no dev packages detected)"
        fi
    else
        fail "vendor/ not found — run 'composer install --no-dev'"
    fi
fi

# ─── 8. Database migrations ───────────────────────────────────────────────────

hr
log "Database migrations..."

if [ $SKIP_MIGRATIONS -eq 1 ]; then
    warn "Migrations skipped (--skip-migrations)"
elif [ $CHECK_ONLY -eq 1 ]; then
    log "Checking migration status..."
    if php bin/cake migrations status 2>&1 | grep -q 'down'; then
        warn "Pending migrations detected — run 'bin/cake migrations migrate'"
    else
        ok "All migrations are up to date"
    fi
else
    log "Running migrations..."
    if php bin/cake migrations migrate --no-interaction 2>&1; then
        ok "Migrations applied"
    else
        fail "Migration failed — check database connection and migration files"
    fi
fi

# ─── 9. CakePHP cache clear ───────────────────────────────────────────────────

hr
log "Cache management..."

if [ $CHECK_ONLY -eq 0 ]; then
    if php bin/cake cache clear_all 2>&1 | head -5; then
        ok "Application caches cleared"
    else
        warn "Cache clear reported issues (non-fatal)"
    fi
else
    log "Skipping cache clear (audit mode)"
fi

# ─── 10. Security audit ───────────────────────────────────────────────────────

hr
log "Security checks..."

# Check for debug files that shouldn't be deployed
DEBUG_FILES=(debug_service.php tmp/debug_collect.php tmp/debug_tags.php tmp/check_rosters.php)
for f in "${DEBUG_FILES[@]}"; do
    if [ -f "$f" ]; then
        warn "Debug file present: $f — consider removing for production"
    fi
done

# Check .env file is not exposed in webroot
if [ -f webroot/.env ]; then
    fail ".env file found in webroot/ — this would be publicly accessible!"
fi

# Check app_local.php is not in webroot
if [ -f webroot/app_local.php ]; then
    fail "app_local.php found in webroot/ — credentials exposed!"
fi

# Verify .htaccess exists for Apache
if [ -f webroot/.htaccess ]; then
    ok "webroot/.htaccess present"
else
    warn "webroot/.htaccess missing — required for Apache URL rewriting"
fi

# ─── 11. Test suite (optional) ────────────────────────────────────────────────

hr
if [ $SKIP_TESTS -eq 1 ]; then
    warn "Test suite skipped (--skip-tests)"
elif [ $CHECK_ONLY -eq 1 ]; then
    log "Test suite check skipped in audit mode"
else
    log "Running test suite..."
    if has_cmd php && [ -f vendor/bin/phpunit ]; then
        if php vendor/bin/phpunit --no-coverage 2>&1 | tail -5; then
            ok "PHPUnit tests passed"
        else
            fail "PHPUnit tests failed — do not deploy"
        fi
    else
        warn "PHPUnit not available (expected with --no-dev install)"
    fi
fi

# ─── 12. Asset check ──────────────────────────────────────────────────────────

hr
log "Checking frontend assets..."

CRITICAL_ASSETS=(
    webroot/js/admin.js
    webroot/js/admin.mjs
    webroot/js/image-selector.js
    webroot/js/games_sport_dynamic.js
    webroot/js/sport-aware-game-form.js
    webroot/css/cake.css
    webroot/dist/manifest.json
)
for asset in "${CRITICAL_ASSETS[@]}"; do
    if [ -f "$asset" ]; then
        ok "$asset"
    else
        fail "Missing critical asset: $asset"
    fi
done

# Ensure the Vite runtime entry exists in the build manifest.
if grep -q '"js/main.js"' webroot/dist/manifest.json 2>/dev/null; then
    ok "Vite manifest contains js/main.js entry"
else
    fail "Vite manifest missing js/main.js entry"
fi

# Check TinyMCE is present
if [ -d webroot/js/tinymce ]; then
    ok "TinyMCE library present"
else
    warn "webroot/js/tinymce/ not found — admin rich text editors will fail"
fi

# ─── Summary ───────────────────────────────────────────────────────────────────

hr
printf "\n"
printf "${BLUE}══════════════════════════════════════════════════════════${NC}\n"
printf "${BLUE}  Deployment Summary${NC}\n"
printf "${BLUE}══════════════════════════════════════════════════════════${NC}\n\n"

if [ $ERRORS -gt 0 ]; then
    printf "${RED}  ERRORS:   %d${NC}\n" "$ERRORS"
fi
if [ $WARNINGS -gt 0 ]; then
    printf "${YELLOW}  WARNINGS: %d${NC}\n" "$WARNINGS"
fi

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    printf "${GREEN}  All checks passed — production ready!${NC}\n"
    EXIT_CODE=0
elif [ $ERRORS -eq 0 ]; then
    printf "\n${YELLOW}  Deployment can proceed with warnings.${NC}\n"
    printf "${YELLOW}  Review warnings above before going live.${NC}\n"
    EXIT_CODE=2
else
    printf "\n${RED}  Deployment blocked — fix errors above before proceeding.${NC}\n"
    EXIT_CODE=1
fi

printf "\n"
exit $EXIT_CODE
