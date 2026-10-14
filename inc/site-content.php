<?php
declare(strict_types=1);
require_once __DIR__ . '/content-family.php';

/** Additional screens share the same P0 resolver and difference-based persistence. */
function bl_site_content_definition(string $page): array
{
    static $definitions = [];
    if (!in_array($page, ['global', 'about', 'contact', 'privacy', 'news', '404', 'loaders', 'dumpers'], true)) return [];
    if (!isset($definitions[$page])) {
        $definitions[$page] = require __DIR__ . '/content-' . $page . '.php';
        if (in_array($page, ['loaders', 'dumpers'], true)) {
            $family = bl_family_content_definition($page === 'loaders');
            $definitions[$page]['defaults'] = array_replace_recursive($definitions[$page]['defaults'], $family['defaults']);
            $definitions[$page]['sections'] = [...$family['sections'], ...$definitions[$page]['sections']];
        }
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

/**
 * Unchanged literal text keeps historical entity spelling; edited text is always escaped. A few defaults are written with
 * &amp;, and an editor who keeps it while rewording would otherwise see it double-encoded, so edited text is decoded first.
 */
function bl_content_text(string $page, string $path): string
{
    $value = bl_content_value($page, $path);
    $default = bl_content_defaults($page);
    foreach (explode('.', $path) as $key) $default = $default[$key] ?? '';
    return $value === $default ? $value : bl_escape(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
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

/** The social profiles the company has set, in display order; a network with no web address is left out. */
function bl_social_links(): array
{
    $links = [];
    foreach (['youtube' => 'YouTube', 'facebook' => 'Facebook'] as $key => $name) {
        $url = bl_company($key . '_url');
        if (preg_match('~^https?://~i', $url)) $links[$key] = ['name' => $name, 'url' => $url];
    }
    return $links;
}

/** The network's mark, in the inline-SVG style of the contact bar's WhatsApp and Email icons. */
function bl_social_icon(string $key): string
{
    return match ($key) {
        'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" fill-rule="evenodd" d="M21.6 7.2a2.5 2.5 0 0 0-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 7.2C2 8.8 2 12 2 12s0 3.2.4 4.8a2.5 2.5 0 0 0 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8c.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8ZM10 15V9l5.2 3L10 15Z"/></svg>',
        'facebook' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.5 21v-7.8h2.6l.4-3.1h-3V8.2c0-.9.3-1.5 1.5-1.5h1.6V3.9c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.3H7.8v3.1h2.6V21h3.1Z"/></svg>',
        default => '',
    };
}

/** A mailto: address with the characters a URL reads as syntax (%, ?, #, &) escaped; the address shown stays as typed. */
function bl_mailto(string $address): string
{
    return 'mailto:' . str_replace(['%', '?', '#', '&'], ['%25', '%3F', '%23', '%26'], $address);
}

/** Company destinations are protocol-checked at output, including historical option values. */
function bl_company_url(string $key): string
{
    $url = bl_company($key);
    if (function_exists('esc_url')) return esc_url($url, ['http', 'https', 'mailto', 'tel']);
    // The standalone preview has no editable options and does not load WordPress escaping helpers.
    return preg_match('~^(?:https?://|mailto:|tel:)~i', $url) ? bl_escape($url) : '';
}

/** Preserve the original two-line address while deriving both lines from the shared address; $key names the street field. */
function bl_company_address_part(int $line, string $key = 'street'): string
{
    $street = bl_company($key);
    $position = strrpos($street, ', ');
    $parts = $position === false ? [$street, ''] : [substr($street, 0, $position + 1), substr($street, $position + 2)];
    return bl_escape($line === 0 ? $parts[0] : implode(', ', array_filter([$parts[1], bl_company('city'), bl_company('region'), bl_company('country')])));
}

/** One full address on one line (street, city, region, country), or '' when that street field is empty. */
function bl_company_full_address(string $key): string
{
    return bl_company($key) === '' ? '' : implode(', ', array_filter([bl_company($key), bl_company('city'), bl_company('region'), bl_company('country')]));
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
    // An address inside a mailto: link is escaped as a URL first; everywhere else the placeholder shows the text as typed.
    $body = preg_replace_callback('/mailto:\{company\.([a-z_]+)\}/', static fn($match) => bl_escape(bl_mailto(bl_company($match[1]))), $body);
    $body = preg_replace_callback('/\{company\.([a-z_]+)\}/', static fn($match) => bl_escape(bl_company($match[1])), $body);
    return function_exists('wp_kses_post') ? wp_kses_post($body) : $body;
}

/** Native page content replaces the general policy; the inquiry mode remains centrally maintained. */
function bl_privacy_content(): string
{
    $mode = bl_privacy_body(bl_inquiry_form_id() ? 'submitted' : 'draft');
    if (function_exists('bl_page_editor_content') && function_exists('get_queried_object')) {
        $post = get_queried_object();
        if ($post instanceof WP_Post && $post->post_type === 'page' && trim($post->post_content) !== '') {
            // A password-protected page shows WordPress's password form, never its body.
            if (post_password_required($post)) return get_the_password_form($post);
            return bl_page_editor_content() . $mode;
        }
    }
    // Do not filter the legacy concatenation through the_content: its exact bytes are a compatibility contract.
    return bl_privacy_body('intro') . $mode . bl_privacy_body('ending');
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
