#!/usr/bin/env bash
set -euo pipefail

# Lightweight helper to reproduce CI-style E2E runs locally.
# - starts a MySQL Docker container (if needed)
# - runs migrations + seeds (E2eSeedData)
# - builds frontend assets (Vite)
# - starts CakePHP dev server
# - runs Playwright with any args you pass through
# Non-destructive: will reuse an existing container if present; pass
# --cleanup-db to remove a container created by this script afterwards.

show_help() {
    cat <<EOF
Usage: $0 [options] -- [playwright args]

Options:
  --db-container-name NAME   Docker container name (default: rh-e2e-mysql)
  --db-port PORT             Preferred host port to map MySQL (default: 3306)
  --db-root-pass PASS        MySQL root password (default: root)
  --db-user USER             DB user (default: test_user)
  --db-pass PASS             DB user password (default: test_password)
  --db-name NAME             Database name (default: racerhistory_test)
  --no-build                 Skip `npm ci` and `npm run build`
  --no-install               Skip `npm ci`
  --no-seed                  Skip running `bin/cake seeds run E2eSeedData`
  --no-start-server          Skip starting CakePHP dev server
  --cleanup-db               Stop and remove a DB container created by this script
  -h, --help                 Show this help

Any args after `--` are forwarded to `npx playwright test`.
EOF
}

# Defaults (can be overridden by environment)
DB_CONTAINER_NAME=${DB_CONTAINER_NAME:-rh-e2e-mysql}
DB_PORT=${DB_PORT:-3306}
DB_ROOT_PASS=${DB_ROOT_PASS:-root}
DB_USER=${DB_USER:-test_user}
DB_PASS=${DB_PASS:-test_password}
DB_NAME=${DB_NAME:-racerhistory_test}

CAKE_HOST=${CAKE_HOST:-127.0.0.1}
CAKE_PORT=${CAKE_PORT:-8765}
PLAYWRIGHT_USER=${PLAYWRIGHT_E2E_USERNAME:-e2e}
PLAYWRIGHT_PASS=${PLAYWRIGHT_E2E_PASSWORD:-Racersbb1952!}

NO_BUILD=false
NO_INSTALL=false
NO_SEED=false
NO_START_SERVER=false
CLEANUP_DB=false

while [[ $# -gt 0 ]]; do
    case "$1" in
        --db-container-name)
            DB_CONTAINER_NAME="$2"; shift 2;;
        --db-port)
            DB_PORT="$2"; shift 2;;
        --db-root-pass)
            DB_ROOT_PASS="$2"; shift 2;;
        --db-user)
            DB_USER="$2"; shift 2;;
        --db-pass)
            DB_PASS="$2"; shift 2;;
        --db-name)
            DB_NAME="$2"; shift 2;;
        --no-build)
            NO_BUILD=true; shift;;
        --no-install)
            NO_INSTALL=true; shift;;
        --no-seed)
            NO_SEED=true; shift;;
        --no-start-server)
            NO_START_SERVER=true; shift;;
        --cleanup-db)
            CLEANUP_DB=true; shift;;
        -h|--help)
            show_help; exit 0;;
        --)
            shift; break;;
        *)
            # stop parsing options; remaining args are for playwright
            break;;
    esac
done

PLAYWRIGHT_ARGS=("$@")

log() { echo "[e2e-local] $*"; }

cleanup() {
    if [[ -n "${SERVER_PID:-}" ]]; then
        if kill -0 "$SERVER_PID" >/dev/null 2>&1; then
            log "Stopping CakePHP dev server (PID $SERVER_PID)";
            kill "$SERVER_PID" || true;
            wait "$SERVER_PID" 2>/dev/null || true;
        fi
        rm -f server.pid || true
    fi

    if [[ "$CLEANUP_DB" == "true" && "${DB_CREATED_BY_SCRIPT:-false}" == "true" ]]; then
        log "Stopping and removing DB container $DB_CONTAINER_NAME";
        docker rm -f "$DB_CONTAINER_NAME" >/dev/null 2>&1 || true
    fi
}

trap cleanup EXIT

command -v docker >/dev/null 2>&1 || { echo "docker not found; please install Docker."; exit 1; }
command -v php >/dev/null 2>&1 || { echo "php not found; please install PHP CLI."; exit 1; }

# Find a free host port starting at DB_PORT
is_port_in_use() { (echo > /dev/tcp/127.0.0.1/$1) >/dev/null 2>&1 && return 0 || return 1; }
if is_port_in_use "$DB_PORT"; then
    for p in $(seq $DB_PORT $((DB_PORT+20))); do
        if ! is_port_in_use "$p"; then
            log "Port $DB_PORT in use, selecting $p for DB mapping";
            DB_PORT=$p; break;
        fi
    done
fi

# Create or reuse Docker MySQL container
if docker ps -a --format '{{.Names}}' | grep -qx "$DB_CONTAINER_NAME"; then
    if docker inspect -f '{{.State.Running}}' "$DB_CONTAINER_NAME" 2>/dev/null | grep -q true; then
        log "Reusing existing running container $DB_CONTAINER_NAME";
        # read host port (if mapped)
        portmap=$(docker port "$DB_CONTAINER_NAME" 3306/tcp 2>/dev/null || true)
        if [[ -n "$portmap" ]]; then
            hostport=$(echo "$portmap" | sed -n 's/.*://p')
            if [[ -n "$hostport" ]]; then DB_PORT=$hostport; fi
        fi
    else
        log "Starting existing container $DB_CONTAINER_NAME";
        docker start "$DB_CONTAINER_NAME" >/dev/null
        DB_CREATED_BY_SCRIPT=false
    fi
else
    log "Creating MySQL container $DB_CONTAINER_NAME (host port $DB_PORT)";
    docker run --name "$DB_CONTAINER_NAME" -e MYSQL_ROOT_PASSWORD="$DB_ROOT_PASS" \
        -e MYSQL_DATABASE="$DB_NAME" -e MYSQL_USER="$DB_USER" -e MYSQL_PASSWORD="$DB_PASS" \
        -p "${DB_PORT}:3306" -d mysql:8.0 --default-authentication-plugin=mysql_native_password >/dev/null
    DB_CREATED_BY_SCRIPT=true
fi

log "Waiting for MySQL to become available in container $DB_CONTAINER_NAME..."
for i in $(seq 1 60); do
    if docker exec "$DB_CONTAINER_NAME" mysqladmin ping -h 127.0.0.1 -uroot -p"$DB_ROOT_PASS" >/dev/null 2>&1; then
        log "MySQL ready"; break;
    fi
    sleep 1
    if [[ $i -eq 60 ]]; then echo "Timed out waiting for MySQL"; exit 1; fi
done

# Ensure DB and user privileges
docker exec "$DB_CONTAINER_NAME" mysql -uroot -p"$DB_ROOT_PASS" -e \
    "CREATE DATABASE IF NOT EXISTS ${DB_NAME}; CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}'; GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%'; FLUSH PRIVILEGES;" >/dev/null

export DATABASE_URL="mysql://${DB_USER}:${DB_PASS}@127.0.0.1:${DB_PORT}/${DB_NAME}"
export DATABASE_TEST_URL="$DATABASE_URL"
log "Using DATABASE_URL=$DATABASE_URL"

# Install/build frontend
if [[ "$NO_INSTALL" != true ]]; then
    if [[ ! -d node_modules ]]; then
        log "Installing npm dependencies (npm ci)";
        npm ci
    else
        log "node_modules present; skipping npm ci (use --no-install to skip entirely)";
    fi
fi

if [[ "$NO_BUILD" != true ]]; then
    log "Building frontend assets (npm run build)";
    npm run build
fi

# Run migrations + seeds
log "Running migrations against test DB"
DATABASE_TEST_URL="$DATABASE_TEST_URL" php bin/cake.php migrations migrate

if [[ "$NO_SEED" != true ]]; then
    log "Seeding E2E test data (E2eSeedData)"
    DATABASE_URL="$DATABASE_URL" php bin/cake.php seeds run E2eSeedData || true
fi

# Clear caches
rm -rf tmp/cache/models/* tmp/cache/persistent/* || true

# Start Cake dev server
if [[ "$NO_START_SERVER" != true ]]; then
    log "Starting CakePHP dev server on ${CAKE_HOST}:${CAKE_PORT}"
    DATABASE_URL="$DATABASE_URL" php bin/cake.php server -p "$CAKE_PORT" -H "$CAKE_HOST" > /tmp/cake-server.log 2>&1 &
    SERVER_PID=$!
    echo "$SERVER_PID" > server.pid

    for i in $(seq 1 30); do
        if curl -s -o /dev/null "http://$CAKE_HOST:$CAKE_PORT/"; then
            log "CakePHP server responding"; break
        fi
        sleep 1
        if ! kill -0 "$SERVER_PID" >/dev/null 2>&1; then
            echo "Cake server died; see /tmp/cake-server.log"; tail -n +1 /tmp/cake-server.log || true; exit 1
        fi
        if [[ $i -eq 30 ]]; then echo "Server failed to start"; tail -n +1 /tmp/cake-server.log || true; exit 1; fi
    done
fi

# Run Playwright
export PLAYWRIGHT_BASE_URL="http://$CAKE_HOST:$CAKE_PORT"
export PLAYWRIGHT_E2E_USERNAME="$PLAYWRIGHT_USER"
export PLAYWRIGHT_E2E_PASSWORD="$PLAYWRIGHT_PASS"

log "Running Playwright: npx playwright test ${PLAYWRIGHT_ARGS[*]:-}";
npx playwright test "${PLAYWRIGHT_ARGS[@]}"

log "E2E run complete"
