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
 * theme loads the Google tag with Consent Mode v2. Once an administrator confirms that the banner and privacy texts say so
 * (bl_google_regional_confirmed), it loads on every page: until the visitor chooses, visitors in the EEA, the UK and
 * Switzerland start with everything denied (Google then sends only cookieless pings, from which it models conversions) and
 * everyone else starts granted; the banner's choice, kept in the bl_consent cookie, then updates both. Because these tags run
 * without waiting for the banner, the categories they serve are marked as running (window.blConsentRan) only when the cookie
 * agrees, exactly like the gated slots, so assets/js/consent.js reloads and cleans up in the same way.
 */
const BL_CONSENT_DENIED_REGIONS = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'IS', 'LI', 'NO', 'GB', 'CH'];

/**
 * Whether the banner asks only visitors in BL_CONSENT_DENIED_REGIONS (assets/js/consent.js; the footer link still opens it for
 * anyone). That holds only while the Google tag's regional defaults are confirmed, so everyone else already starts granted,
 * and no code is pasted into the slots: pasted code waits for a yes from every visitor, and outside those regions nobody
 * would be asked for one.
 */
function bl_consent_regional_banner(): bool
{
    if (!bl_google_regional_confirmed() || !bl_google_tag_ids()) return false;
    foreach (BL_TRACKING_SLOTS as $slots) foreach ($slots as $key) if (bl_tracking_code($key) !== '') return false;
    return true;
}

/**
 * The valid Google tag IDs saved on the tracking screen (comma, space or newline separated), in order, without duplicates.
 * Only GA4 (G-) and Google Ads (AW-) IDs are accepted: a combined Google tag (GT-) or Floodlight (DC-) ID does not say which
 * consent category it serves, so it would get the wrong banner choice; such tools are pasted into the slots below instead.
 */
function bl_google_tag_ids(): array
{
    if (!function_exists('get_option')) return [];
    $saved = get_option('bl_content_tracking', []);
    $ids = preg_split('~[\s,;]+~', strtoupper((string) (is_array($saved) ? ($saved['google_tag'] ?? '') : '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return array_values(array_unique(array_filter($ids, static fn(string $id): bool => (bool) preg_match('~^(?:G|AW)-[A-Z0-9]{4,20}$~', $id))));
}

/** The Google Ads conversion that a sent enquiry reports ("AW-123456789/AbC-dEf"), or '' when none or malformed. */
function bl_google_conversion(): string
{
    if (!function_exists('get_option')) return '';
    $saved = get_option('bl_content_tracking', []);
    $value = trim((string) (is_array($saved) ? ($saved['google_conversion'] ?? '') : ''));
    return preg_match('~^AW-\d{6,15}/[A-Za-z0-9_-]{4,64}$~', $value) ? $value : '';
}

/** The consent categories the saved Google tags serve: analytics for GA4, advertising for Google Ads. */
function bl_google_categories(): array
{
    $categories = [];
    foreach (bl_google_tag_ids() as $id) $categories[str_starts_with($id, 'G-') ? 'analytics' : 'advertising'] = true;
    return $categories;
}

/**
 * Whether an administrator has confirmed, on the tracking screen, that the banner and privacy texts describe the regional
 * defaults. Until then the Google tag is held back like any other tracking code (nothing at all runs before the visitor
 * agrees), because the texts in use may still promise that, in whatever words or language they were written.
 */
function bl_google_regional_confirmed(): bool
{
    if (!function_exists('get_option')) return false;
    $saved = get_option('bl_content_tracking', []);
    return is_array($saved) && ($saved['google_regional'] ?? '') === '1';
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
        . 'gtag("set","ads_data_redaction",true);gtag("js",new Date());window.blGoogleTag=true;';
    // Confirmed, every tag is configured and Consent Mode decides what it may send. Not confirmed, a tag whose category the
    // visitor has not agreed to stays unconfigured, so it sends nothing at all (window.blConsentRan holds the agreed ones).
    $confirmed = bl_google_regional_confirmed();
    $allowed = static fn(string $category): string => $confirmed ? '' : 'if((window.blConsentRan||{})[' . $json($category) . '])';
    foreach ($ids as $id) $script .= $allowed(str_starts_with($id, 'G-') ? 'analytics' : 'advertising') . 'gtag("config",' . $json($id) . ');';
    $conversion = bl_google_conversion();
    if ($conversion !== '') $script .= $allowed('advertising') . 'window.blGoogleConversion=' . $json($conversion) . ';';
    // gtag.js (and the Google Ads script it pulls in, about 340 KB between them) waits for the visitor's first touch, key,
    // scroll or wheel, or 12 seconds after the page has loaded, so it no longer competes with the page's first paint; the
    // commands above queue in dataLayer until then. A visit from an ad or a tagged link loads it at once, so its landing
    // is counted with the click; the thank-you page calls blLoadGoogleTag itself (assets/js/inquiry.js).
    $source = 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($ids[0]);
    $script .= '(function(){var done=false,events=["pointerdown","keydown","touchstart","scroll","wheel"];'
        . 'function load(){if(done)return;done=true;events.forEach(function(e){removeEventListener(e,load,true)});'
        . 'var s=document.createElement("script");s.async=true;s.src=' . $json($source) . ';document.head.appendChild(s)}'
        . 'window.blLoadGoogleTag=load;'
        . 'if(/[?&](gclid|gbraid|wbraid|utm_[a-z]+)=/.test(location.search)){load();return}'
        . 'events.forEach(function(e){addEventListener(e,load,{capture:true,passive:true})});'
        . 'addEventListener("load",function(){setTimeout(load,12000)})})();';
    $markup = '<script>' . $script . '</script>';
    if ($confirmed) return $markup;
    // Not confirmed: inert until the visitor's cookie agrees to a category the tags serve, then written in as the page is read
    // (the same gate as bl_tracking_markup), with the cookie's choice applied over the defaults.
    $gate = '(function(t,c){try{var m=document.cookie.match(/(?:^|; )bl_consent=([^;]*)/),v=m&&JSON.parse(decodeURIComponent(m[1]));if(v&&v.v===1&&c.some(function(k){return v[k]===true}))document.write(t.innerHTML)}catch(e){}})(document.currentScript.previousElementSibling,' . $json(array_keys($categories)) . ')';
    return '<template data-bl-consent="google">' . $markup . '</template><script>' . $gate . '</script>';
}
