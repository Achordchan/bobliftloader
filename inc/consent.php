<?php
declare(strict_types=1);

/**
 * Tracking codes by consent category. Analytics keeps the original three slots, so code saved before the categories
 * existed stays where it was; advertising has three of its own. Every code is held back until the visitor agrees to
 * its category (assets/js/consent.js), so nothing here loads on a first visit.
 */
const BL_TRACKING_SLOTS = [
    'analytics' => ['head' => 'head', 'body_open' => 'body_open', 'footer' => 'footer'],
    'advertising' => ['head' => 'ads_head', 'body_open' => 'ads_body_open', 'footer' => 'ads_footer'],
];

/**
 * One saved code, as printed in the page: an inert <template> holding the code, then a few lines that write it into the page
 * (while the page is still being read, so it behaves exactly as if it had been pasted there) only when the visitor's cookie
 * has agreed to the category (and noting in window.blConsentRan that the category's code is running on this page). Without agreement nothing in it runs. The cookie name and version are those of assets/js/consent.js.
 */
function bl_tracking_markup(string $category, string $code): string
{
    $gate = '(function(t,c){try{var m=document.cookie.match(/(?:^|; )bl_consent=([^;]*)/),v=m&&JSON.parse(decodeURIComponent(m[1]));if(v&&v.v===1&&v[c]===true){(window.blConsentRan=window.blConsentRan||{})[c]=true;document.write(t.innerHTML)}}catch(e){}})(document.currentScript.previousElementSibling,"' . $category . '")';
    return '<template data-bl-consent="' . $category . '">' . $code . '</template><script>' . $gate . '</script>';
}

/**
 * Whether a saved code loads an external script the browser must wait for (a <script src> with neither async nor defer, and not a
 * module or nomodule). Written into the page through document.write, Chrome may skip such a script on a very slow connection when it is not
 * cached, and its dependants would then fail. Nothing is changed for it (it runs in order, as pasted, on every ordinary connection);
 * the tracking screen only tells the editor which slot has one, so a tool that offers an async/defer form can use that.
 */
/** A script tag's attributes that make it harmless here: a module (deferred by the browser), or a type the browser does not run. */
function bl_tracking_script_is_deferred_or_inert(string $attributes): bool
{
    if (!preg_match('~(?:^|\s)type\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))~i', $attributes, $type)) return false;
    $value = strtolower(trim($type[1] . ($type[2] ?? '') . ($type[3] ?? '')));
    return $value === 'module' || ($value !== '' && !str_contains($value, 'javascript') && !str_contains($value, 'ecmascript'));
}

function bl_tracking_has_blocking_script(string $code): bool
{
    // Only real script elements count: a commented-out tag, or text inside a script that merely spells out "<script src", is not one.
    $code = preg_replace('~<!--.*?(?:-->|$)~s', '', $code);
    if (!preg_match_all('~<script\b([^>]*)>(?:.*?</script\s*>|.*$)~is', $code, $tags)) return false;
    foreach ($tags[1] as $attributes) {
        if (preg_match('~(?:^|\s)src\s*=~i', $attributes) && !preg_match('~(?:^|\s)(?:async|defer|nomodule)(?=[\s=/]|$)~i', $attributes) && !bl_tracking_script_is_deferred_or_inert($attributes)) return true;
    }
    return false;
}

/**
 * The raw code saved for one slot key ('head', 'ads_footer' …); empty outside WordPress or when nothing is saved. Code in the
 * three original slots predates the two groups, and nothing says whether it is analytics or advertising: it is held back
 * (not printed at all) until the tracking screen has been saved once, which marks it as checked ('grouped').
 */
function bl_tracking_code(string $key): string
{
    if (!function_exists('get_option')) return '';
    $saved = get_option('bl_content_tracking', []);
    if (!is_array($saved)) return '';
    if (in_array($key, BL_TRACKING_SLOTS['analytics'], true) && ($saved['grouped'] ?? '') !== '1') return '';
    return trim((string) ($saved[$key] ?? ''));
}

/** The categories that have any code saved, in banner order; the banner and the footer link exist only for these. */
function bl_consent_categories(): array
{
    $categories = [];
    $google = bl_google_categories();
    foreach (BL_TRACKING_SLOTS as $category => $slots) {
        if (isset($google[$category])) { $categories[] = $category; continue; }
        foreach ($slots as $key) if (bl_tracking_code($key) !== '') { $categories[] = $category; break; }
    }
    return $categories;
}

/**
 * Google's own tags (GA4 "G-…", Google Ads "AW-…") are not pasted as code: their IDs are saved on the tracking screen and the
 * theme loads the Google tag on every page with Consent Mode v2. Until the visitor chooses, visitors in the EEA, the UK and
 * Switzerland start with everything denied (Google then sends only cookieless pings, from which it models conversions) and
 * everyone else starts granted; the banner's choice, kept in the bl_consent cookie, then updates both. Because these tags run
 * without waiting for the banner, the categories they serve are marked as running (window.blConsentRan) only when the cookie
 * agrees, exactly like the gated slots, so assets/js/consent.js reloads and cleans up in the same way.
 */
const BL_CONSENT_DENIED_REGIONS = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'IS', 'LI', 'NO', 'GB', 'CH'];

/** The valid Google tag IDs saved on the tracking screen (comma, space or newline separated), in order, without duplicates. */
function bl_google_tag_ids(): array
{
    if (!function_exists('get_option')) return [];
    $saved = get_option('bl_content_tracking', []);
    $ids = preg_split('~[\s,;]+~', strtoupper((string) (is_array($saved) ? ($saved['google_tag'] ?? '') : '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return array_values(array_unique(array_filter($ids, static fn(string $id): bool => (bool) preg_match('~^(?:G|AW|GT|DC)-[A-Z0-9]{4,20}$~', $id))));
}

/** The Google Ads conversion that a sent enquiry reports ("AW-123456789/AbC-dEf"), or '' when none or malformed. */
function bl_google_conversion(): string
{
    if (!function_exists('get_option')) return '';
    $saved = get_option('bl_content_tracking', []);
    $value = trim((string) (is_array($saved) ? ($saved['google_conversion'] ?? '') : ''));
    return preg_match('~^AW-\d{6,15}/[A-Za-z0-9_-]{4,64}$~', $value) ? $value : '';
}

/** The consent categories the saved Google tags serve: analytics for GA4, advertising for Google Ads and Floodlight. */
function bl_google_categories(): array
{
    $categories = [];
    foreach (bl_google_tag_ids() as $id) $categories[str_starts_with($id, 'G-') ? 'analytics' : 'advertising'] = true;
    return $categories;
}

/** The Google tag with its consent defaults, for the top of <head>; '' when no Google tag ID is saved. */
function bl_google_tag_markup(): string
{
    $ids = bl_google_tag_ids();
    if (!$ids) return '';
    $categories = bl_google_categories();
    $denied = ['ad_storage' => 'denied', 'ad_user_data' => 'denied', 'ad_personalization' => 'denied', 'analytics_storage' => 'denied'];
    $granted = array_map(static fn() => 'granted', $denied);
    $json = static fn($value): string => (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $script = 'window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
        . 'gtag("consent","default",' . $json($denied + ['region' => BL_CONSENT_DENIED_REGIONS, 'wait_for_update' => 500]) . ');'
        . 'gtag("consent","default",' . $json($granted) . ');'
        . '(function(c){try{var m=document.cookie.match(/(?:^|; )bl_consent=([^;]*)/),v=m&&JSON.parse(decodeURIComponent(m[1]));if(!v||v.v!==1)return;'
        . 'var r=window.blConsentRan=window.blConsentRan||{};c.forEach(function(k){if(v[k]===true)r[k]=true});'
        . 'var a=v.analytics===true?"granted":"denied",d=v.advertising===true?"granted":"denied";'
        . 'gtag("consent","update",{analytics_storage:a,ad_storage:d,ad_user_data:d,ad_personalization:d})}catch(e){}})(' . $json(array_keys($categories)) . ');'
        . 'gtag("set","ads_data_redaction",true);gtag("js",new Date());';
    foreach ($ids as $id) $script .= 'gtag("config",' . $json($id) . ');';
    $conversion = bl_google_conversion();
    if ($conversion !== '') $script .= 'window.blGoogleConversion=' . $json($conversion) . ';';
    return '<script>' . $script . '</script><script async src="https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($ids[0]) . '"></script>';
}
