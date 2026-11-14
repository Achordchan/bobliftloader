<?php
declare(strict_types=1);

/**
 * The header's language switch (templates/partials/language-switch.php): every language of the site (inc/i18n.php),
 * the current one first, each with the address of this page in it, else that language's home page, else none (shown as
 * not yet available). Rows saved in the older option bl_languages (REST /wp/v2/settings: code, name, url) for a code the
 * theme does not translate itself still follow, as links to wherever they point (another site, say). A row for a code
 * the theme has counts when it points somewhere other than the theme's own section for that language (/es/) or the home page.
 */
function bl_languages(string $path = '/'): array
{
    $current = bl_lang();
    $saved = function_exists('get_option') ? get_option('bl_languages') : null;
    $saved = is_array($saved) ? array_values(array_filter($saved, 'is_array')) : [];
    // A saved row that sends one of the theme's languages somewhere of its own (a Spanish site kept elsewhere, or a section
    // such as /spanish/ on this site) still wins in the switch: someone chose that destination. Only the theme's own section
    // for that language (/es/…) and this site's home page give way, since the theme now knows where those pages are.
    $custom = [];
    $siteHost = static fn(string $host): string => preg_replace('/^www\./', '', strtolower($host));
    $home = function_exists('home_url') ? (string) parse_url(home_url('/'), PHP_URL_HOST) : '';
    $homePath = function_exists('home_url') ? rtrim((string) parse_url(home_url('/'), PHP_URL_PATH), '/') : '';
    foreach ($saved as $language) {
        $code = strtolower(trim((string) ($language['code'] ?? '')));
        $url = trim((string) ($language['url'] ?? ''));
        if ($code === 'en' || !isset(BL_I18N_LANGUAGES[$code]) || $url === '') continue;
        $host = (string) parse_url($url, PHP_URL_HOST);
        if ($host !== '') {
            if (!preg_match('~^https?://~i', $url)) continue;
            if ($home === '' || $siteHost($host) !== $siteHost($home)) { $custom[$code] = $url; continue; }
        } elseif (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            continue;
        }
        $target = (string) parse_url($url, PHP_URL_PATH);
        if ($homePath !== '' && ($target === $homePath || str_starts_with($target, $homePath . '/'))) $target = substr($target, strlen($homePath));
        if (trim($target, '/') === '' || preg_match('~^/' . preg_quote($code, '~') . '(/|$)~i', $target)) continue;
        $custom[$code] = $host === '' && bl_is_wordpress() ? home_url($url) : $url;
    }
    $rows = [];
    foreach (BL_I18N_LANGUAGES as $code => $language) {
        $samePage = bl_has_language($path, $code) && !isset($custom[$code]);
        $url = bl_has_language($path, $code) ? bl_lang_path($path, $code) . bl_i18n_switch_query() : (bl_has_language('/', $code) ? bl_lang_path('/', $code) : '');
        $url = $url === '' ? '' : (bl_is_wordpress() ? home_url($url) : $url);
        if (isset($custom[$code]) && $code !== $current) $url = $custom[$code];
        // same_page: this page in that language, so the switch may take the page's choices along (language-suggest.js).
        $row = ['code' => $code, 'name' => $language['name'], 'url' => $url, 'current' => $code === $current, 'same_page' => $samePage];
        if ($code === $current) array_unshift($rows, $row); else $rows[] = $row;
    }
    foreach ($saved as $language) {
        $code = strtolower(trim((string) ($language['code'] ?? '')));
        $name = trim((string) ($language['name'] ?? ''));
        if (!preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', $code) || $name === '' || isset(BL_I18N_LANGUAGES[$code]) || in_array($code, array_column($rows, 'code'), true)) continue;
        $url = trim((string) ($language['url'] ?? ''));
        if (str_starts_with($url, '/') && !str_starts_with($url, '//') && bl_is_wordpress()) $url = home_url($url); // a path on this site, as a subfolder install has it
        $rows[] = ['code' => $code, 'name' => $name, 'url' => $url, 'current' => false, 'same_page' => false];
    }
    return $rows;
}

/**
 * The choices a page was opened with, for its counterpart in another language: the enquiry's product and equipment, the
 * catalogue's filters and search, the range's load and width (assets/js/catalog.js, ruler.js) and whatever the theme adds
 * later. Tracking tags, previews and the theme's own admin flags stay with the address they came on.
 * assets/js/language-suggest.js keeps the same rule in the browser.
 */
const BL_I18N_SWITCH_DROP = '~^(utm_|gad_|_gl$|gclid$|gbraid$|wbraid$|dclid$|fbclid$|msclkid$|ttclid$|yclid$|srsltid$|mc_|_ga$|bl_|preview|_wpnonce$|p$|page_id$)~i';

function bl_i18n_switch_query(): string
{
    $keep = [];
    foreach ($_GET as $name => $value) {
        if (!is_string($name) || !is_string($value) || $value === '' || preg_match(BL_I18N_SWITCH_DROP, $name)) continue;
        $keep[$name] = function_exists('wp_unslash') ? wp_unslash($value) : $value;
    }
    return $keep ? '?' . http_build_query($keep, '', '&', PHP_QUERY_RFC3986) : '';
}

if (function_exists('add_action')) add_action('init', static function (): void {
    register_setting('bl_languages', 'bl_languages', [
        'type' => 'array',
        'description' => '页眉语言切换器里主题之外的语言：code（如 de）、name（显示名，如 Deutsch）、url（该语言的网址，可以是别的网站；留空显示为即将上线）。英语和西班牙语由主题自己管（inc/i18n.php）；西班牙语这里写了别的网址（别的网站或本站其他栏目，如 /spanish/）时切换器用它，写 /es/、首页或留空则用主题的 /es/ 页面。',
        'default' => [],
        'sanitize_callback' => static function ($value): array {
            $rows = [];
            foreach (is_array($value) ? $value : [] as $row) {
                if (!is_array($row)) continue;
                $url = trim((string) ($row['url'] ?? ''));
                $rows[] = ['code' => sanitize_key((string) ($row['code'] ?? '')), 'name' => sanitize_text_field((string) ($row['name'] ?? '')), 'url' => preg_match('~^/[a-z0-9/_-]*$~i', $url) ? $url : esc_url_raw($url, ['http', 'https'])]; // a site path such as /de/, or a full address
            }
            return $rows;
        },
        'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['code' => ['type' => 'string'], 'name' => ['type' => 'string'], 'url' => ['type' => 'string']]]]],
    ]);
    foreach (array_keys(BL_I18N_LANGUAGES) as $code) {
        if ($code === 'en') continue;
        register_setting('bl_i18n', 'bl_i18n_' . $code . '_pages', [
            'type' => 'array',
            'description' => '这个语言（' . $code . '）已上线的页面，写英文页的路径（如 /products/mini-dumpers/）。留空用主题默认清单。只有列出的页面有 /' . $code . '/ 网址和 hreflang。',
            'default' => [],
            'sanitize_callback' => static fn($value): array => array_values(array_filter(array_map(static fn($path) => is_string($path) && preg_match('~^/[a-z0-9/._-]*$~i', $path) ? bl_i18n_normalize_path($path) : '', is_array($value) ? $value : []))),
            'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'string']]],
        ]);
        register_setting('bl_i18n', 'bl_i18n_' . $code . '_strings', [
            'type' => 'object',
            'description' => '这个语言（' . $code . '）的译文修正：键是英文原文（页面上看到的文字，行内标签写成 <t0>…</t0>，换行写 <br>），值是译文。覆盖主题自带词典 inc/i18n/' . $code . '.php 的同名条目。',
            'default' => [],
            'sanitize_callback' => 'bl_i18n_sanitize_strings', // plain text with the <tN> placeholders and <br> kept (wp_kses_post would strip them)
            'show_in_rest' => ['schema' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']]],
        ]);
    }
});
