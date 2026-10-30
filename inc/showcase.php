<?php
declare(strict_types=1);

/**
 * The BL-T360-H / BL-T460-H showcase at /bl-t360-t460/: the scroll-driven 3D page built from loader-experience/
 * (node loader-experience/scripts/build_wp.mjs writes its files to assets/bl360/ and its markup to templates/bl360/).
 * The specification table and the contact section are written here from the two product records and the company
 * settings, so the page shows what the product pages show; everything else is the built markup.
 */
const BL_SHOWCASE_PATH = '/bl-t360-t460/';
const BL_SHOWCASE_SLUGS = ['bl-t360-h-mini-skid-steer-loader', 'bl-t460-h-mini-skid-steer-loader'];

/** The two machines the page is about, in page order (found by slug or an earlier slug); empty when either is missing (the page then 404s). */
function bl_showcase_products(): array
{
    $products = [];
    foreach (BL_SHOWCASE_SLUGS as $slug) {
        $product = bl_find_record(bl_products(), $slug);
        if (!$product) return [];
        $products[] = $product;
    }
    return $products;
}

/** Whether a product page leads to the showcase; matched on the records' current slugs, so a renamed model still does. */
function bl_is_showcase_product(array $product): bool
{
    return in_array($product['slug'] ?? '', array_column(bl_showcase_products(), 'slug'), true);
}

/**
 * The comparison table's rows: every published specification of either model, in the groups and order of the product
 * pages (bl_spec_groups), so a figure added or changed on a product record shows here too. Group => label => [small, large].
 */
function bl_showcase_spec_rows(array $products): array
{
    $labels = [];
    foreach ($products as $product) {
        foreach (bl_spec_groups($product['specs'] ?? []) as $group => $specs) foreach (array_keys($specs) as $label) $labels[$group][$label] = true;
    }
    // Groups in product-page order, whichever model introduced them.
    $order = array_flip(['Performance', 'Engine', 'Dimensions', 'Build', 'Details']);
    uksort($labels, static fn($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));
    // One row per figure: names that mean the same figure (Travel speed / Max travel speed, bl_spec_synonyms) share it, as on the comparison pages.
    $rows = $seen = [];
    foreach ($labels as $group => $names) {
        foreach (array_keys($names) as $label) {
            if (isset($seen[$label])) continue;
            foreach (bl_spec_synonyms($label, $products) as $name) $seen[$name] = true;
            foreach ($products as $i => $product) $rows[$group][$label][$i] = trim((string) bl_spec_value($product, $label, $products));
        }
    }
    return $rows;
}

function bl_showcase_spec(array $product, array $keys): string
{
    foreach ($keys as $key) if (trim((string) ($product['specs'][$key] ?? '')) !== '') return trim((string) $product['specs'][$key]);
    return '';
}

/** "1-cylinder, 4-stroke" → Single-cylinder (the column heading under each model name). */
function bl_showcase_cylinders(array $product): string
{
    $type = strtolower(bl_showcase_spec($product, ['Engine type']));
    return str_contains($type, '1-cylinder') || str_contains($type, 'single') ? 'Single-cylinder' : (str_contains($type, '2-cylinder') || str_contains($type, 'twin') ? 'Twin-cylinder' : '');
}

/** One sentence per model from its record: "The BL-T360-H: single-cylinder, 7.6 kW, 930 mm wide, 200 kg rated load." */
function bl_showcase_compare(array $products): string
{
    $lines = [];
    foreach ($products as $product) {
        $facts = array_filter([
            strtolower(bl_showcase_cylinders($product)),
            bl_showcase_spec($product, ['Engine power']),
            ($width = bl_showcase_spec($product, ['Machine width'])) !== '' ? $width . ' wide' : '',
            ($load = bl_showcase_spec($product, ['Rated load'])) !== '' ? $load . ' rated load' : '',
        ]);
        // A figure keeps its unit on the same line ("200 kg").
        $lines[] = 'The ' . bl_escape($product['model'] ?? $product['name']) . ($facts ? ': ' . preg_replace('/(\d) (?=\S)/u', "$1\u{00A0}", bl_escape(implode(', ', $facts))) : '') . '.';
    }
    return implode(' ', $lines);
}

/** A figure for the key-figure row: the number large, its unit small ("16.8 kW" → 16.8 + kW). */
function bl_showcase_figure(string $value): string
{
    if (preg_match('/^([\d.,–-]+)\s*(\S+)$/u', $value, $m)) return bl_escape($m[1]) . '<small>' . bl_escape($m[2]) . '</small>';
    return bl_escape($value);
}

function bl_showcase_specs_html(array $products): string
{
    [$small, $large] = $products;
    $model = static fn(array $p): string => bl_escape($p['model'] ?? $p['name']);
    $html = '<section class="exp-specs" id="specs" aria-labelledby="specs-title"><div class="exp-specs-inner">';
    $html .= '<div class="exp-specs-heading"><div><span class="exp-section-number">05 / Specifications</span><h2 id="specs-title">One design,<br>two power levels.</h2></div>';
    $html .= '<p>' . bl_showcase_compare($products) . '</p></div>';
    $html .= '<dl class="exp-specs-key">';
    foreach (['Rated power' => ['Engine power'], 'Rated load' => ['Rated load'], 'Lifting capacity' => ['Lifting capacity']] as $label => $keys) {
        $html .= '<div><dt>' . $label . '</dt><dd>';
        foreach ($products as $product) $html .= '<span>' . $model($product) . '<b>' . bl_showcase_figure(bl_showcase_spec($product, $keys)) . '</b></span>';
        $html .= '</dd></div>';
    }
    $html .= '</dl>';
    $html .= '<div class="exp-specs-bar"><h3>Full specifications</h3><button class="exp-specs-filter" id="specs-filter" type="button" aria-pressed="false" aria-controls="specs-table"><i aria-hidden="true"></i>Differences only</button></div>';
    $html .= '<table class="exp-specs-table" id="specs-table"><caption class="exp-sr">Specifications of the ' . $model($small) . ' and ' . $model($large) . ' stand-on mini skid steer loaders</caption><colgroup><col class="label"><col><col></colgroup>';
    $html .= '<thead><tr><th scope="col">Model</th>';
    foreach ($products as $product) $html .= '<th scope="col"><a href="' . bl_escape(bl_url('/product/' . $product['slug'] . '/')) . '">' . $model($product) . '</a><small>' . bl_showcase_cylinders($product) . '</small></th>';
    $html .= '</tr></thead>';
    foreach (bl_showcase_spec_rows($products) as $group => $rows) {
        $body = '';
        foreach ($rows as $label => $values) {
            [$a, $b] = [$values[0] ?? '', $values[1] ?? ''];
            if ($a === '' && $b === '') continue;
            $body .= $a === $b
                ? '<tr class="is-same"><th scope="row">' . bl_escape($label) . '</th><td colspan="2">' . bl_escape($a) . '</td></tr>'
                : '<tr class="is-diff"><th scope="row">' . bl_escape($label) . '</th><td>' . bl_escape($a !== '' ? $a : '—') . '</td><td>' . bl_escape($b !== '' ? $b : '—') . '</td></tr>';
        }
        if ($body !== '') $html .= '<tbody><tr><th scope="rowgroup" colspan="3">' . bl_escape($group) . '</th></tr>' . $body . '</tbody>';
    }
    $html .= '</table><p class="exp-specs-note">Figures are the published specifications on each model’s product page. Engine brand and fuel depend on the chosen configuration; the machine as delivered is definitive.</p>';
    return $html . '</div></section>';
}

function bl_showcase_contact_html(array $products): string
{
    $links = implode(' and ', array_map(static fn(array $p): string => '<a href="' . bl_escape(bl_url('/product/' . $p['slug'] . '/')) . '">' . bl_escape($p['model'] ?? $p['name']) . '</a>', $products));
    $phone = bl_company('sales_whatsapp_label');
    $items = [
        '<a class="exp-contact-item" href="' . bl_escape(bl_url('/contact/') . '?product=' . rawurlencode($products[0]['slug']) . '#enquiry') . '"><span class="exp-contact-label">Enquiry</span><b>Enquiry form</b><span class="exp-contact-action">Write to us ↗</span></a>',
        '<a class="exp-contact-item" href="' . bl_escape(bl_company('sales_whatsapp_url')) . '" target="_blank" rel="noopener"><span class="exp-contact-label">WhatsApp</span><b>' . bl_escape($phone) . '</b><span class="exp-contact-action">Message ↗</span></a>',
        '<button class="exp-contact-item" type="button" data-copy="' . bl_escape($phone) . '"><span class="exp-contact-label">WeChat</span><b>' . bl_escape($phone) . '</b><span class="exp-contact-action">Copy number</span><i class="exp-contact-hint" aria-live="polite"></i></button>',
        '<a class="exp-contact-item" href="' . bl_escape(bl_mailto(bl_company('email'))) . '"><span class="exp-contact-label">Email</span><b>' . bl_escape(bl_company('email')) . '</b><span class="exp-contact-action">Send email ↗</span></a>',
    ];
    return '<section class="exp-contact" id="contact" aria-labelledby="contact-title"><div class="exp-contact-copy"><span class="exp-section-number">Contact</span><h2 id="contact-title">Quotes &amp; custom builds,<br>straight from us.</h2>'
        . '<p>Prices, configurations, custom colours or dealership — reach us whichever way suits you. Every specification and photo of each model is on its own page: the ' . $links . '.</p></div>'
        . '<ul class="exp-contact-list"><li>' . implode('</li><li>', $items) . '</li></ul></section>';
}

/**
 * The built markup with the theme's parts filled in: the site header above the page's own chapter bar ($siteHeader),
 * and the model names, figures and contact details from the product records and company settings.
 */
function bl_showcase_markup(string $file, array $products = [], string $siteHeader = ''): string
{
    $markup = (string) file_get_contents(__DIR__ . '/../templates/bl360/' . $file);
    $imports = json_decode((string) file_get_contents(__DIR__ . '/../templates/bl360/importmap.json'), true) ?: [];
    $base = bl_asset('bl360/');
    foreach ($imports as $name => $path) $imports[$name] = $base . preg_replace('~^\./~', '', $path);
    // One import map for the page: three.js for the showcase, and the site scripts (header, search, cookie banner, attribution).
    $siteMap = json_decode(bl_script_import_map(), true)['imports'] ?? [];
    // The site header above carries the logo, so the chapter bar names the two models instead (back to the start of the page).
    if ($products) $markup = (string) preg_replace('~<a class="exp-brand" href="#overview"[^>]*>.*?</a>~s', '<a class="exp-brand exp-brand-title" href="#overview" aria-label="Back to the start of the page">BL-T360-H / BL-T460-H</a>', $markup, 1);
    $names = $products ? ['BL-T360-H' => bl_escape($products[0]['model'] ?? 'BL-T360-H'), 'BL-T460-H' => bl_escape($products[1]['model'] ?? 'BL-T460-H')] : [];
    return strtr($markup, $names + [
        '{{BASE}}' => $base,
        // The site's own header first, so the page leads back to the rest of the site; the chapter bar stays below it.
        '<header class="exp-header">' => $siteHeader . '<header class="exp-header">',
        '{{IMPORTMAP}}' => '<script type="importmap">' . json_encode(['imports' => $imports + $siteMap], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>',
        // The site's privacy notice, and the control that reopens the cookie choices (as in the site footer).
        '</footer>' => '<nav class="exp-footer-links" aria-label="Site"><a href="' . bl_escape(bl_application_link(bl_content_value('global', 'footer.privacy_url'))) . '">' . bl_content_text('global', 'footer.privacy_label') . '</a>' . (bl_consent_categories() ? '<button type="button" data-bl-consent-open hidden>' . bl_content_text('global', 'consent.settings') . '</button>' : '') . '</nav></footer>',
        '{{SPECS}}' => $products ? bl_showcase_specs_html($products) : '',
        '{{CONTACT}}' => $products ? bl_showcase_contact_html($products) : '',
        '{{COMPARE}}' => $products ? bl_showcase_compare($products) : '',
    ]);
}
