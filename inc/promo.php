<?php
declare(strict_types=1);

/**
 * The year-end offers (/year-end-specials/, words in content-promo.php): the page, the home page banner and the mark an
 * enquiry from the page carries. The offers' dates decide everything: before the first day and after the last (China time)
 * the banner is gone; after the last day the page stays at its address, says the offers have ended, is noindex and leaves
 * the sitemap, and an enquiry sent from an old copy of the page no longer carries the offer mark.
 */

const BL_PROMO_PATH = '/year-end-specials/';
// The value the page's form sends, and what the sales team reads in the mail, DingTalk and the 询盘 list.
const BL_PROMO_CODE = 'year-end-2026';
const BL_PROMO_LABEL = '年终活动 2026（满减券 / 定金膨胀）';

/** The offers' first and last day as saved (Y-m-d, China time). */
function bl_promo_dates(): array
{
    $dates = bl_editable('promo')['dates'] ?? [];
    return ['starts' => trim((string) ($dates['starts'] ?? '')), 'ends' => trim((string) ($dates['ends'] ?? ''))];
}

/** Whether the offers run today. */
function bl_promo_live(?string $today = null): bool
{
    $dates = bl_promo_dates();
    return $dates['ends'] !== '' && bl_notice_live($dates['starts'], $dates['ends'], $today);
}

/** Whether the last day has passed (a missing or malformed end date counts as ended, so nothing runs by mistake). */
function bl_promo_ended(?string $today = null): bool
{
    $ends = bl_promo_dates()['ends'];
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $ends);
    if (!$date || $date->format('Y-m-d') !== $ends) return true;
    $today ??= (new DateTimeImmutable('now', new DateTimeZone('Asia/Shanghai')))->format('Y-m-d');
    return $today > $ends;
}

/** The two lines of a coupon or deposit row, filled in from what is saved; a row without its amounts is left out. */
function bl_promo_rows(string $group, array $required): array
{
    $items = bl_editable('promo')[$group]['items'] ?? [];
    return array_values(array_filter(is_array($items) ? $items : [], static function ($row) use ($required): bool {
        if (!is_array($row)) return false;
        foreach ($required as $key) if (trim((string) ($row[$key] ?? '')) === '') return false;
        return true;
    }));
}

/** One terms line per row. */
function bl_promo_terms(): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) (bl_editable('promo')['terms']['items'] ?? '')) ?: [])));
}

/**
 * WordPress has no post at the page's address (the page is the theme's own), so keep it from guessing a redirect to a post
 * with a similar name and answer 200 (index.php draws the page). /es/year-end-specials/ arrives here as the English path.
 */
function bl_wp_promo_page(): void
{
    if (trim((string) ($GLOBALS['wp']->request ?? ''), '/') !== trim(BL_PROMO_PATH, '/')) return;
    remove_action('template_redirect', 'redirect_canonical');
    remove_action('template_redirect', 'wp_old_slug_redirect');
    $GLOBALS['wp_query']->is_404 = false;
    status_header(200);
}

/** The offer mark on an enquiry: kept only when the page's form sent it and the offers still run. */
function bl_promo_enquiry_mark(string $sent, ?string $today = null): string
{
    return $sent === BL_PROMO_CODE && bl_promo_live($today) ? BL_PROMO_LABEL : '';
}

/**
 * The midnight (China time) that begins a day, plus whole days, as a timestamp; null for a missing or malformed date.
 */
function bl_promo_midnight(string $day, int $plus_days = 0): ?int
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $day, new DateTimeZone('Asia/Shanghai'));
    if (!$date || $date->format('Y-m-d') !== $day) return null;
    return $date->modify('+' . $plus_days . ' days')->getTimestamp();
}

/**
 * How long the offers page and the home page (its banner) may be kept in a cache, so that no stored copy outlives a
 * change of the offers: the first day beginning (the banner appears) or the last day ending (the offers stop).
 *  - On the last day (China time) they are built for every request and never stored, so nothing with the live offers is
 *    still served after midnight, whatever an upstream cache does with an expired copy.
 *  - Before that, the page cache's lifetime (batcache) and the Cache-Control sent with the page are cut so every copy
 *    expires before the next change: the first day, or the start of the last day where the rule above takes over.
 * Returns what it did: 'no-store', 'max-age=N', or '' (another page, or the offers have ended and nothing changes again).
 * Called before the page is sent.
 */
function bl_promo_cache_rules(array $route, ?int $now = null): string
{
    if (!in_array($route['type'], ['promo', 'home'], true)) return '';
    $now ??= time();
    $dates = bl_promo_dates();
    $starts = bl_promo_midnight($dates['starts']);
    $last_day = bl_promo_midnight($dates['ends']);
    if ($last_day !== null && $last_day <= $now && $now < $last_day + 86400) {
        if (function_exists('batcache_cancel')) batcache_cancel();
        if (!headers_sent()) header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
        return 'no-store';
    }
    $next = null;
    foreach ([$starts, $last_day] as $at) if ($at !== null && $at > $now) { $next = $at; break; }
    if ($next === null) return '';
    $batcache = $GLOBALS['batcache'] ?? null;
    $lifetime = is_object($batcache) && isset($batcache->max_age) ? max(1, (int) $batcache->max_age) : 300;
    $lifetime = min($lifetime, $next - $now);
    if (is_object($batcache) && isset($batcache->max_age)) $batcache->max_age = $lifetime;
    // A response already marked not to be stored (a signed-in editor, a preview) keeps that.
    $private = (bool) preg_grep('/^cache-control:.*(no-store|no-cache|private)/i', headers_list());
    if (!$private && !headers_sent()) header('Cache-Control: max-age=' . $lifetime . ', must-revalidate');
    return 'max-age=' . $lifetime;
}

/**
 * Sites that had already saved their own list of Spanish pages (option bl_i18n_es_pages) before the offers page existed
 * get the page added to it once, every other page they chose kept; the defaults (no saved list) already have it. Taking
 * the page out of the list afterwards sticks: the option bl_promo_es_added remembers that this was done.
 */
function bl_promo_add_spanish_page(): bool
{
    if (!function_exists('get_option') || get_option('bl_promo_es_added') === BL_PROMO_CODE) return false;
    $saved = get_option('bl_i18n_es_pages');
    if (is_array($saved) && $saved && !in_array(BL_PROMO_PATH, $saved, true)) {
        $saved[] = BL_PROMO_PATH;
        if (!update_option('bl_i18n_es_pages', array_values($saved))) return false;
    }
    update_option('bl_promo_es_added', BL_PROMO_CODE, false);
    return true;
}
