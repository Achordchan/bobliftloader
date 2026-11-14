<?php
declare(strict_types=1);

/**
 * The header's language switch (templates/partials/language-switch.php): every language of the site (inc/i18n.php),
 * the current one first, each with the address of this page in it, else that language's home page, else none (shown as
 * not yet available). Rows saved in the older option bl_languages (REST /wp/v2/settings: code, name, url) for a code the
 * theme does not translate itself still follow, as links to wherever they point (another site, say); a row for a code
 * the theme has is ignored, since the theme knows where that language's pages are.
 */
function bl_languages(string $path = '/'): array
{
    $current = bl_lang();
    $rows = [];
    foreach (BL_I18N_LANGUAGES as $code => $language) {
        $url = bl_has_language($path, $code) ? bl_lang_path($path, $code) . bl_i18n_switch_query() : (bl_has_language('/', $code) ? bl_lang_path('/', $code) : '');
        $row = ['code' => $code, 'name' => $language['name'], 'url' => $url === '' ? '' : (bl_is_wordpress() ? home_url($url) : $url), 'current' => $code === $current];
        if ($code === $current) array_unshift($rows, $row); else $rows[] = $row;
    }
    $saved = function_exists('get_option') ? get_option('bl_languages') : null;
    foreach (is_array($saved) ? $saved : [] as $language) {
        if (!is_array($language)) continue;
        $code = strtolower(trim((string) ($language['code'] ?? '')));
        $name = trim((string) ($language['name'] ?? ''));
        if (!preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', $code) || $name === '' || isset(BL_I18N_LANGUAGES[$code]) || in_array($code, array_column($rows, 'code'), true)) continue;
        $url = trim((string) ($language['url'] ?? ''));
        if (str_starts_with($url, '/') && !str_starts_with($url, '//') && bl_is_wordpress()) $url = home_url($url); // a path on this site, as a subfolder install has it
        $rows[] = ['code' => $code, 'name' => $name, 'url' => $url, 'current' => false];
    }
    return $rows;
}

/**
 * The choices a page was opened with that its counterpart should keep: the enquiry's product and equipment, and the
 * catalogue's filters and search. Anything else (tracking tags, say) stays with the address it came on.
 */
function bl_i18n_switch_query(): string
{
    $keep = [];
    foreach (['product', 'equipment', 'application', 'topic', 'category', 'load', 'ground', 'width', 'q'] as $name) {
        $value = $_GET[$name] ?? null;
        if (is_string($value) && function_exists('wp_unslash')) $value = wp_unslash($value);
        if (is_string($value) && $value !== '') $keep[$name] = $value;
    }
    return $keep ? '?' . http_build_query($keep, '', '&', PHP_QUERY_RFC3986) : '';
}

if (function_exists('add_action')) add_action('init', static function (): void {
    register_setting('bl_languages', 'bl_languages', [
        'type' => 'array',
        'description' => '页眉语言切换器里主题之外的语言：code（如 de）、name（显示名，如 Deutsch）、url（该语言的网址，可以是别的网站；留空显示为即将上线）。英语和西班牙语由主题自己管（inc/i18n.php），这里写了也不用。',
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
