#!/usr/bin/env bash
# Runs the local WordPress (with its admin at /wp-admin/) in the foreground. Stop with Ctrl+C.
# First time: scripts/setup-local-wordpress.sh
set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORDPRESS_ROOT="${PROJECT_ROOT}/.wordpress"
ROUTER_SCRIPT="${PROJECT_ROOT}/scripts/local-wordpress-router.php"
RUNTIME_HOST="${HOST:-127.0.0.1}"
RUNTIME_PORT="${1:-${PORT:-8090}}"

fail() {
    printf 'Error: %s\n' "$1" >&2
    exit 1
}

(( $# <= 1 )) || fail "Usage: ./start.sh [port]"
[[ "${RUNTIME_PORT}" =~ ^[0-9]+$ ]] && (( RUNTIME_PORT >= 1 && RUNTIME_PORT <= 65535 )) ||
    fail "Port must be an integer between 1 and 65535."
[[ -f "${WORDPRESS_ROOT}/wp-config.php" ]] || fail "Local WordPress is not set up yet. Run: scripts/setup-local-wordpress.sh"
command -v php >/dev/null 2>&1 || fail "PHP is unavailable. Install it with: brew install php"

if lsof -nP -iTCP:"${RUNTIME_PORT}" -sTCP:LISTEN >/dev/null 2>&1; then
    lsof -nP -iTCP:"${RUNTIME_PORT}" -sTCP:LISTEN >&2 || true
    fail "Port ${RUNTIME_PORT} is in use. Choose another, for example: ./start.sh $((RUNTIME_PORT + 1))"
fi
lsof -nP -iTCP:3306 -sTCP:LISTEN >/dev/null 2>&1 || fail "MySQL is not running. Start it with: brew services start mysql"

printf 'BOB-LIFT local WordPress: http://%s:%s/  (admin: /wp-admin/)\n' "${RUNTIME_HOST}" "${RUNTIME_PORT}"
printf 'Press Ctrl+C to stop.\n'
exec env PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}" php \
    -S "${RUNTIME_HOST}:${RUNTIME_PORT}" -t "${WORDPRESS_ROOT}" "${ROUTER_SCRIPT}"
