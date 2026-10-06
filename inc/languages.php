<?php
declare(strict_types=1);

/**
 * The languages the header switch offers. English lives at the site root; each other language will live under its own
 * folder (/es/, /de/ …). Until those exist the list holds English alone and the switch says more are coming. In WordPress
 * the list is the option bl_languages (REST /wp/v2/settings), rows of code, name and url; a row without a url is shown
 * as not yet available.
 */
function bl_languages(): array
{
    $languages = [['code' => 'en', 'name' => 'English', 'url' => '/']];
    if (function_exists('get_option') && is_array($saved = get_option('bl_languages')) && $saved) $languages = $saved;
    $clean = [];
    foreach ($languages as $language) {
        $code = strtolower(trim((string) ($language['code'] ?? '')));
        $name = trim((string) ($language['name'] ?? ''));
        if (!preg_match('/^[a-z]{2,3}(-[a-z0-9]{2,8})?$/', $code) || $name === '') continue;
        $clean[] = ['code' => $code, 'name' => $name, 'url' => trim((string) ($language['url'] ?? ''))];
    }
    return $clean ?: [['code' => 'en', 'name' => 'English', 'url' => '/']];
}

if (function_exists('add_action')) add_action('init', static function (): void {
    register_setting('bl_languages', 'bl_languages', [
        'type' => 'array',
        'description' => '页眉语言切换器的语言列表：code（如 en、es）、name（显示名，如 Español）、url（该语言首页，如 /es/；留空显示为即将上线）。第一项是英语（根目录）。',
        'default' => [['code' => 'en', 'name' => 'English', 'url' => '/']],
        'sanitize_callback' => static function ($value): array {
            $rows = [];
            foreach (is_array($value) ? $value : [] as $row) {
                if (!is_array($row)) continue;
                $url = trim((string) ($row['url'] ?? ''));
                $rows[] = ['code' => sanitize_key((string) ($row['code'] ?? '')), 'name' => sanitize_text_field((string) ($row['name'] ?? '')), 'url' => preg_match('~^/[a-z0-9/_-]*$~i', $url) ? $url : esc_url_raw($url, ['http', 'https'])]; // a site path such as /es/, or a full address
            }
            return $rows;
        },
        'show_in_rest' => ['schema' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['code' => ['type' => 'string'], 'name' => ['type' => 'string'], 'url' => ['type' => 'string']]]]],
    ]);
});
