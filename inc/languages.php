<?php
declare(strict_types=1);

/**
 * The header's language switch (templates/partials/language-switch.php): every language of the site (inc/i18n.php),
 * the current one first, each with the address of this page in it, else that language's home page, else none (shown as
 * not yet available).
 */
function bl_languages(string $path = '/'): array
{
    $current = bl_lang();
    $rows = [];
    foreach (BL_I18N_LANGUAGES as $code => $language) {
        $url = bl_has_language($path, $code) ? bl_lang_path($path, $code) : (bl_has_language('/', $code) ? bl_lang_path('/', $code) : '');
        $row = ['code' => $code, 'name' => $language['name'], 'url' => $url === '' ? '' : (bl_is_wordpress() ? home_url($url) : $url), 'current' => $code === $current];
        if ($code === $current) array_unshift($rows, $row); else $rows[] = $row;
    }
    return $rows;
}

if (function_exists('add_action')) add_action('init', static function (): void {
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
            'sanitize_callback' => static function ($value): array {
                $clean = [];
                foreach (is_array($value) ? $value : [] as $original => $text) if (is_string($text) && trim((string) $original) !== '') $clean[wp_kses_post((string) $original)] = wp_kses_post($text);
                return $clean;
            },
            'show_in_rest' => ['schema' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']]],
        ]);
    }
});
