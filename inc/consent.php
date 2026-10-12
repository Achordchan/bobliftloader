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

/** The raw code saved for one slot key ('head', 'ads_footer' …); empty outside WordPress or when nothing is saved. */
function bl_tracking_code(string $key): string
{
    if (!function_exists('get_option')) return '';
    $saved = get_option('bl_content_tracking', []);
    return is_array($saved) ? trim((string) ($saved[$key] ?? '')) : '';
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
