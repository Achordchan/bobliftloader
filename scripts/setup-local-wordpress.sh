#!/usr/bin/env bash
# Builds the local WordPress under .wordpress/ with this repository as its theme, then imports the site content.
# Safe to run again: existing core files, config, database and content are kept; only what is missing is added.
set -Eeuo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORDPRESS_ROOT="${PROJECT_ROOT}/.wordpress"
THEME_SLUG="bobliftloader"
DB_NAME="${DB_NAME:-bobliftloader_wp}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_HOST="${DB_HOST:-127.0.0.1}"
SITE_URL="${SITE_URL:-http://localhost:8090}"
ADMIN_USER="${ADMIN_USER:-bobadmin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"
LOGIN_FILE="${WORDPRESS_ROOT}/admin-login.txt"

fail() {
    printf 'Error: %s\n' "$1" >&2
    exit 1
}

command -v php >/dev/null 2>&1 || fail "PHP is unavailable. Install it with: brew install php"
command -v wp >/dev/null 2>&1 || fail "WP-CLI is unavailable. Install it with: brew install wp-cli"
command -v mysql >/dev/null 2>&1 || fail "MySQL client is unavailable. Install it with: brew install mysql"

# WP-CLI's bundled libraries print deprecation notices on newer PHP; they are noise, not errors.
wpcli() {
    wp --path="${WORDPRESS_ROOT}" "$@" 2>&1 | sed '/Deprecated:/d; /^$/d'
}

mysql_args=(-u"${DB_USER}" -h"${DB_HOST}")
[[ -n "${DB_PASSWORD}" ]] && mysql_args+=(-p"${DB_PASSWORD}")
mysql "${mysql_args[@]}" -e 'SELECT 1' >/dev/null 2>&1 ||
    fail "Cannot reach MySQL at ${DB_HOST} as ${DB_USER}. Start it with: brew services start mysql"

if [[ ! -f "${WORDPRESS_ROOT}/wp-load.php" ]]; then
    printf 'Downloading WordPress into .wordpress/ ...\n'
    wpcli core download --skip-content
fi
mkdir -p "${WORDPRESS_ROOT}/wp-content/themes" "${WORDPRESS_ROOT}/wp-content/plugins" "${WORDPRESS_ROOT}/wp-content/uploads"

if [[ ! -f "${WORDPRESS_ROOT}/wp-config.php" ]]; then
    wpcli config create --dbname="${DB_NAME}" --dbuser="${DB_USER}" --dbpass="${DB_PASSWORD}" --dbhost="${DB_HOST}" \
        --skip-check --extra-php <<'PHP'
// Local only: follow whatever host and port the page was opened on, so ./start.sh <port> works without editing the database.
if (!empty($_SERVER['HTTP_HOST'])) {
    define('WP_HOME', 'http://' . $_SERVER['HTTP_HOST']);
    define('WP_SITEURL', 'http://' . $_SERVER['HTTP_HOST']);
}
define('WP_ENVIRONMENT_TYPE', 'local');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('DISALLOW_FILE_EDIT', true);
PHP
fi

wpcli db create >/dev/null 2>&1 || true

# The theme is this repository itself, linked in so edits show up without copying.
theme_link="${WORDPRESS_ROOT}/wp-content/themes/${THEME_SLUG}"
if [[ ! -e "${theme_link}" ]]; then
    ln -s ../../.. "${theme_link}"
fi

if ! wpcli core is-installed >/dev/null 2>&1; then
    admin_password="$(php -r 'echo bin2hex(random_bytes(10));')"
    wpcli core install --url="${SITE_URL}" --title="BOB-LIFT Compact Equipment" \
        --admin_user="${ADMIN_USER}" --admin_password="${admin_password}" --admin_email="${ADMIN_EMAIL}" --skip-email
    umask 077
    printf 'WordPress admin (local only)\nURL: %s/wp-admin/\nUser: %s\nPassword: %s\n' \
        "${SITE_URL}" "${ADMIN_USER}" "${admin_password}" >"${LOGIN_FILE}"
    # A fresh install keeps the preview out of search engines and uses the site's time zone.
    wpcli option update blog_public 0 >/dev/null
    wpcli option update timezone_string Asia/Shanghai >/dev/null
    # WordPress's own sample post, sample page and draft privacy page are not part of this site.
    wpcli post delete 1 2 3 --force >/dev/null 2>&1 || true
fi

# Chinese admin screens. Fetched with curl because WordPress's own downloader refuses the fake-IP addresses
# some local proxies resolve wordpress.org to. Without the pack the admin simply stays in English.
languages_dir="${WORDPRESS_ROOT}/wp-content/languages"
if [[ ! -f "${languages_dir}/zh_CN.mo" ]]; then
    language_zip="$(mktemp)"
    mkdir -p "${languages_dir}"
    if curl -fsSL -o "${language_zip}" "https://downloads.wordpress.org/translation/core/$(wpcli core version)/zh_CN.zip" &&
        unzip -oq "${language_zip}" -d "${languages_dir}"; then
        printf 'Installed the Chinese language pack.\n'
    else
        printf 'Note: Chinese language pack not available; the admin stays in English.\n'
    fi
    rm -f "${language_zip}"
fi
[[ -f "${languages_dir}/zh_CN.mo" ]] && wpcli site switch-language zh_CN >/dev/null

wpcli theme activate "${THEME_SLUG}" >/dev/null
# The theme's own importer: adds the products, news and pages that are missing, never overwrites existing ones.
wpcli eval 'print_r(bl_import_initial_content());'
# The footer's privacy link and WordPress's privacy settings point at the same page.
wpcli eval '$page = get_page_by_path("privacy"); if ($page) update_option("wp_page_for_privacy_policy", $page->ID);'
wpcli rewrite flush >/dev/null

printf '\nLocal WordPress is ready. Start it with ./start.sh, then open %s/wp-admin/\n' "${SITE_URL}"
if [[ -f "${LOGIN_FILE}" ]]; then
    printf 'Login details: %s\n' "${LOGIN_FILE#"${PROJECT_ROOT}"/}"
fi
