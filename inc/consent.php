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
 * cached, and its dependants would then fail; so code with one is not supported: bl_tracking_code() holds it back and the tracking
 * screen says why (the tools' own current snippets are inline or async/defer).
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
    // Only the opening tags count: text inside a script that merely spells out "<script src" is not one.
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
    $code = trim((string) ($saved[$key] ?? ''));
    return bl_tracking_has_blocking_script($code) ? '' : $code;
}

/** The categories that have any code saved, in banner order; the banner and the footer link exist only for these. */
function bl_consent_categories(): array
{
    $categories = [];
    foreach (BL_TRACKING_SLOTS as $category => $slots) {
        foreach ($slots as $key) if (bl_tracking_code($key) !== '') { $categories[] = $category; break; }
    }
    return $categories;
}
