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
 * On the offers' last day the offers page and the home page (its banner) are built for every request and never stored,
 * so no copy with the live offers outlives the deadline at midnight (China time). Called before the page is sent.
 */
function bl_promo_cache_rules(array $route, ?string $today = null): bool
{
    if (!in_array($route['type'], ['promo', 'home'], true)) return false;
    $today ??= (new DateTimeImmutable('now', new DateTimeZone('Asia/Shanghai')))->format('Y-m-d');
    if (bl_promo_dates()['ends'] !== $today) return false;
    if (function_exists('batcache_cancel')) batcache_cancel();
    if (!headers_sent()) header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
    return true;
}
