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

/** The two machines the page is about, in page order; empty when either record is missing (the page then 404s). */
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

function bl_is_showcase_product(array $product): bool
{
    return in_array($product['slug'] ?? '', BL_SHOWCASE_SLUGS, true);
}

/** Specification groups of the comparison table: label shown => record keys tried in turn. */
function bl_showcase_spec_groups(): array
{
    return [
        'Performance' => ['Rated load' => ['Rated load'], 'Lifting capacity' => ['Lifting capacity'], 'Bucket capacity' => ['Bucket capacity'], 'Travel speed' => ['Max travel speed', 'Travel speed'], 'Hydraulic pressure' => ['Hydraulic pressure'], 'Gradeability' => ['Gradeability'], 'Machine weight' => ['Machine weight']],
        'Engine' => ['Engine type' => ['Engine type'], 'Rated power' => ['Engine power'], 'Power by engine' => ['Power by engine'], 'Engine options' => ['Engine options'], 'Fuel' => ['Fuel'], 'Fuel tank' => ['Fuel tank'], 'Starting' => ['Starting'], 'Emission standard' => ['Emission standard']],
        'Dimensions' => ['Working height' => ['Working height'], 'Max dumping height' => ['Max dumping height'], 'Height with arms raised' => ['Height with arms raised'], 'Length with bucket' => ['Length with bucket'], 'Length without bucket' => ['Length without bucket'], 'Machine width' => ['Machine width'], 'Bucket width' => ['Bucket width'], 'Overall height' => ['Overall height'], 'Ground clearance' => ['Ground clearance']],
    ];
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
    $html .= '<p>The ' . $model($small) . ' runs a single-cylinder engine. The ' . $model($large) . ' moves up to a twin-cylinder engine with more power, on a wider body, with a higher rated load and lifting capacity.</p></div>';
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
    foreach (bl_showcase_spec_groups() as $group => $rows) {
        $body = '';
        foreach ($rows as $label => $keys) {
            [$a, $b] = [bl_showcase_spec($small, $keys), bl_showcase_spec($large, $keys)];
            if ($a === '' && $b === '') continue;
            $body .= $a === $b
                ? '<tr class="is-same"><th scope="row">' . $label . '</th><td colspan="2">' . bl_escape($a) . '</td></tr>'
                : '<tr class="is-diff"><th scope="row">' . $label . '</th><td>' . bl_escape($a !== '' ? $a : '—') . '</td><td>' . bl_escape($b !== '' ? $b : '—') . '</td></tr>';
        }
        if ($body !== '') $html .= '<tbody><tr><th scope="rowgroup" colspan="3">' . $group . '</th></tr>' . $body . '</tbody>';
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

/** The built markup with the theme's parts filled in. */
function bl_showcase_markup(string $file, array $products = []): string
{
    $markup = (string) file_get_contents(__DIR__ . '/../templates/bl360/' . $file);
    $imports = json_decode((string) file_get_contents(__DIR__ . '/../templates/bl360/importmap.json'), true) ?: [];
    $base = bl_asset('bl360/');
    foreach ($imports as $name => $path) $imports[$name] = $base . preg_replace('~^\./~', '', $path);
    // One import map for the page: three.js for the showcase, and the site scripts the cookie banner and attribution use.
    $siteMap = json_decode(bl_script_import_map(), true)['imports'] ?? [];
    return strtr($markup, [
        '{{BASE}}' => $base,
        // The wordmark leads back to the site (the chapter links stay in the header).
        '<a class="exp-brand" href="#overview" aria-label="BOB-LIFT, back to the start">' => '<a class="exp-brand" href="' . bl_escape(bl_url('/')) . '" aria-label="BOB-LIFT Loader home">',
        '{{IMPORTMAP}}' => '<script type="importmap">' . json_encode(['imports' => $imports + $siteMap], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>',
        // The site's privacy notice, and the control that reopens the cookie choices (as in the site footer).
        '</footer>' => '<nav class="exp-footer-links" aria-label="Site"><a href="' . bl_escape(bl_application_link(bl_content_value('global', 'footer.privacy_url'))) . '">' . bl_content_text('global', 'footer.privacy_label') . '</a>' . (bl_consent_categories() ? '<button type="button" data-bl-consent-open hidden>' . bl_content_text('global', 'consent.settings') . '</button>' : '') . '</nav></footer>',
        '{{SPECS}}' => $products ? bl_showcase_specs_html($products) : '',
        '{{CONTACT}}' => $products ? bl_showcase_contact_html($products) : '',
    ]);
}
