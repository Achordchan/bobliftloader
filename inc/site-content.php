<?php
declare(strict_types=1);

/** Additional screens share the same P0 resolver and difference-based persistence. */
function bl_site_content_definition(string $page): array
{
    static $definitions = [];
    if (!in_array($page, ['global', 'about', 'contact', 'privacy', 'news', '404', 'loaders', 'dumpers'], true)) return [];
    if (!isset($definitions[$page])) {
        $definitions[$page] = require __DIR__ . '/content-' . $page . '.php';
        if ($page === 'global') {
            $nav = require __DIR__ . '/content-navigation.php';
            $definitions[$page]['defaults'] = array_merge($definitions[$page]['defaults'], $nav['defaults']);
            $sections = $definitions[$page]['sections'];
            $definitions[$page]['sections'] = array_merge(array_slice($sections, 0, 2), $nav['sections'], array_slice($sections, 2));
        }
    }
    return $definitions[$page];
}

function bl_content_value(string $page, string $path): string
{
    $value = bl_editable($page);
    foreach (explode('.', $path) as $key) $value = $value[$key] ?? '';
    return is_scalar($value) ? (string) $value : '';
}

/** Unchanged literal text keeps historical entity spelling; edited text is always escaped. */
function bl_content_text(string $page, string $path): string
{
    $value = bl_content_value($page, $path);
    $default = bl_content_defaults($page);
    foreach (explode('.', $path) as $key) $default = $default[$key] ?? '';
    return $value === $default ? $value : bl_escape($value);
}

function bl_content_html(string $page, string $path): string
{
    $value = bl_content_value($page, $path);
    $default = bl_content_defaults($page);
    foreach (explode('.', $path) as $key) $default = $default[$key] ?? '';
    if ($value === $default) return $value;
    return function_exists('wp_kses_post') ? wp_kses_post($value) : $value;
}

function bl_content_asset(string $page, string $path, string $fallback): string
{
    return bl_content_value($page, $path) ?: bl_asset($fallback);
}

function bl_brand_logo(): string
{
    return bl_content_asset('global', 'brand.logo', 'images/logo.png');
}

function bl_brand_icon(string $fallback, int $size): string
{
    if (bl_is_wordpress() && get_option('site_icon')) return (string) get_site_icon_url($size);
    return bl_content_asset('global', 'brand.icon', $fallback);
}

function bl_company(string $key): string
{
    $company = bl_editable('global')['company'];
    return match ($key) {
        'phone_url' => 'tel:+' . preg_replace('/\D/', '', $company['phone']),
        'phone_seo' => $company['phone'] === '+86 158 5249 9415' ? '+86-15852499415' : preg_replace('/\s+/', '', $company['phone']),
        'sales_whatsapp_url' => 'https://wa.me/' . $company['sales_whatsapp'],
        'sales_whatsapp_label' => $company['sales_whatsapp'] === '8615852499415' ? '+86 158 5249 9415' : ($company['sales_whatsapp'] !== '' ? '+' . $company['sales_whatsapp'] : ''),
        default => (string) ($company[$key] ?? ''),
    };
}

/** Company destinations are protocol-checked at output, including historical option values. */
function bl_company_url(string $key): string
{
    $url = bl_company($key);
    if (function_exists('esc_url')) return esc_url($url, ['http', 'https', 'mailto', 'tel']);
    // The standalone preview has no editable options and does not load WordPress escaping helpers.
    return preg_match('~^(?:https?://|mailto:|tel:)~i', $url) ? bl_escape($url) : '';
}

/** Preserve the original two-line address while deriving both lines from the shared address. */
function bl_company_address_part(int $line): string
{
    $street = bl_company('street');
    $position = strrpos($street, ', ');
    $parts = $position === false ? [$street, ''] : [substr($street, 0, $position + 1), substr($street, $position + 2)];
    return bl_escape($line === 0 ? $parts[0] : implode(', ', array_filter([$parts[1], bl_company('city'), bl_company('region'), bl_company('country')])));
}

function bl_company_phone_hours(): string
{
    $timezone = bl_company('timezone');
    if (preg_match('/\(([^)]+)\)/', $timezone, $match)) $timezone = '(' . $match[1] . ')';
    return str_replace(' · ', ', ', bl_company('hours')) . ' ' . $timezone;
}

function bl_privacy_body(string $section): string
{
    $body = bl_content_html('privacy', 'body.' . $section);
    $body = preg_replace_callback('/\{company\.([a-z_]+)\}/', static fn($match) => bl_escape(bl_company($match[1])), $body);
    return function_exists('wp_kses_post') ? wp_kses_post($body) : $body;
}

function bl_seo_content_page(array $route): string
{
    if ($route['type'] === 'products') return match ($route['category'] ?? '') {
        'skid-steer-loaders' => 'loaders', 'mini-dumpers' => 'dumpers', default => 'home',
    };
    return in_array($route['type'], ['home', 'support', 'applications', 'about', 'contact', 'privacy', 'news'], true) ? $route['type'] : 'home';
}

/** Preserve active states independently of menu order. */
function bl_header_active(string $id, string $type): bool
{
    $types = ['equipment' => ['products', 'product'], 'applications' => ['applications', 'application', 'application_case'], 'support' => ['support'], 'about' => ['about'], 'news' => ['news', 'article']];
    return in_array($type, $types[$id] ?? [], true);
}

function bl_site_switch_items(): array
{
    $items = bl_editable('global')['switcher']['sites'];
    foreach ($items as &$item) if ($item['url'] === '') $item['domain'] = bl_company('domain');
    unset($item);
    return $items;
}

/** Editable prose references shared company values without copying them into every page. */
function bl_company_text(string $page, string $path): string
{
    return preg_replace_callback('/\{company\.([a-z_]+)\}/', static fn($match) => bl_escape(bl_company($match[1])), bl_escape(bl_content_value($page, $path)));
}
