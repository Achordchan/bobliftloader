<?php
/** The Spanish pages (inc/i18n.php): paths, links, the HTML translator and the dictionary's own integrity. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';

$failures = 0;
$check = static function (bool $ok, string $what) use (&$failures): void {
    if (!$ok) { fwrite(STDERR, "FAIL: $what\n"); $failures++; }
};

// Corrections saved in the settings (REST /wp/v2/settings) keep their placeholders and lose any other markup.
run_hook('init');
$sanitize = $GLOBALS['settings']['bl_i18n_es_strings']['sanitize_callback'] ?? null;
$check(is_callable($sanitize), 'the Spanish corrections setting is registered with a sanitizer');
$saved = $sanitize([
    'Read the <t0 class="x">full   guide</t0> today<br />now' => 'Lea la <T0 onclick="x()">guía completa</t0> hoy<br/>ya<img src=x onerror="alert(1)">',
    'Skid steer loader' => 'Minicargadora compacta',
    '<b>Bold</b> only' => '',
]);
$check($saved === ['Read the <t0>full guide</t0> today<br>now' => 'Lea la <t0>guía completa</t0> hoy<br>ya', 'Skid steer loader' => 'Minicargadora compacta'], 'saved corrections keep <tN> and <br>, drop attributes, other markup and empty ones: ' . json_encode($saved, JSON_UNESCAPED_UNICODE));
$GLOBALS['options']['bl_i18n_es_strings'] = $saved;
$check(str_contains(bl_i18n_translate_html('<p>Read the <a href="/x/">full guide</a> today<br>now</p>', 'es'), '<p>Lea la <a href="/x/">guía completa</a> hoy<br>ya</p>'), 'a saved correction with a placeholder is used on the page');
$check(bl_t('Skid steer loader', 'es') === 'Minicargadora compacta', 'a saved correction overrides the theme dictionary');
$GLOBALS['options']['bl_languages'] = [['code' => 'en', 'name' => 'English', 'url' => '/'], ['code' => 'es', 'name' => 'Castellano', 'url' => 'https://elsewhere.test/'], ['code' => 'de', 'name' => 'Deutsch', 'url' => 'https://de.example.test/'], ['code' => 'bad code', 'name' => 'X', 'url' => '/x/']];
$check(isset($GLOBALS['settings']['bl_languages']['show_in_rest']), 'the older language list setting is still in the REST settings');
$rows = bl_languages('/about/');
$check(array_column($rows, 'code') === ['en', 'es', 'de'] && $rows[1]['name'] === 'Español' && $rows[2]['url'] === 'https://de.example.test/' && !$rows[2]['current'], 'saved languages the theme does not translate still show, after its own: ' . json_encode($rows));
$check($rows[1]['url'] === 'https://elsewhere.test/', 'a saved destination on another website for a theme language is kept: ' . $rows[1]['url']);
$GLOBALS['options']['bl_languages'][1]['url'] = 'https://example.test/es/old/';
$check(bl_languages('/contact/')[1]['url'] === 'https://example.test/es/contact/', 'a saved destination in the theme\'s own /es/ section gives way to the theme pages');
foreach (['https://example.test/', 'https://www.example.test', '/'] as $plain) {
    $GLOBALS['options']['bl_languages'][1]['url'] = $plain;
    $check(bl_languages('/contact/')[1]['url'] === 'https://example.test/es/contact/', 'this site\'s home page saved for Spanish gives way too: ' . $plain);
}
foreach (['https://example.test/latam/' => 'https://example.test/latam/', '/spanish/' => (bl_is_wordpress() ? 'https://example.test/spanish/' : '/spanish/')] as $saved => $expected) {
    $GLOBALS['options']['bl_languages'][1]['url'] = $saved;
    $row = bl_languages('/contact/')[1];
    $check($row['url'] === $expected && !$row['same_page'], 'a custom Spanish section on this site is kept: ' . $saved . ' -> ' . json_encode($row));
}
$GLOBALS['options']['bl_languages'][2]['url'] = '/de/';
$check(bl_languages('/about/')[2]['url'] === (bl_is_wordpress() ? 'https://example.test/de/' : '/de/'), 'a saved language at a path on this site is resolved against the site');
unset($GLOBALS['options']['bl_languages']);
$_GET = ['product' => '500kg-tracked-mini-dumper', 'utm_source' => 'google', 'gclid' => 'x', 'equipment' => ['x'], 'payload' => '2000', 'bl_i18n_audit' => '1'];
$switch = bl_languages('/contact/');
$check(str_ends_with($switch[1]['url'], '/es/contact/?product=500kg-tracked-mini-dumper&payload=2000') && $switch[1]['same_page'], 'the switch keeps the page\'s choices but not tracking tags or admin flags: ' . $switch[1]['url']);
$check(!bl_languages('/about/')[1]['same_page'] && !str_contains(bl_languages('/about/')[1]['url'], '?'), 'a link to a language\'s home page takes no choices along');
$_GET = [];

// Pages and paths.
$check(bl_has_language('/products/mini-dumpers/', 'es') && bl_has_language('/products/mini-dumpers', 'es'), 'the dumper page has Spanish, with or without its slash');
$check(!bl_has_language('/about/', 'es') && bl_has_language('/about/', 'en'), 'a page not translated yet has no Spanish version');
$check(bl_lang_path('/contact/', 'es') === '/es/contact/' && bl_lang_path('/contact/', 'en') === '/contact/', 'language paths');
$check(bl_i18n_alternates('/about/') === ['en' => '/about/', 'es' => ''], 'an untranslated page has no Spanish alternate');
$check(bl_i18n_normalize_path('/about') === '/about/' && bl_i18n_normalize_path('/sitemap.html') === '/sitemap.html' && bl_i18n_normalize_path('/x/?a=1#b') === '/x/', 'paths are normalised');

// Links follow the page's language only where that page exists in it.
$GLOBALS['bl_lang'] = 'es';
$check(bl_i18n_localize_href('/products/mini-dumpers/#models') === '/es/products/mini-dumpers/#models', 'a translated page keeps its anchor');
$check(bl_i18n_localize_href('/contact/?equipment=dumper#enquiry') === '/es/contact/?equipment=dumper#enquiry', 'a translated page keeps its query');
$check(bl_i18n_localize_href('https://example.test/privacy/') === 'https://example.test/es/privacy/', 'an absolute link on this site');
$check(bl_i18n_localize_href('/about/') === '/about/', 'an untranslated page stays English');
$check(bl_i18n_localize_href('https://bobliftcrane.com/') === 'https://bobliftcrane.com/', 'another site is left alone');
$check(bl_i18n_localize_href('/es/contact/') === '/es/contact/', 'a link already in Spanish is not doubled');
$check(bl_i18n_localize_href('/wp-content/uploads/a.pdf') === '/wp-content/uploads/a.pdf', 'files are left alone');
$check(bl_i18n_redirect('https://example.test/contact/') === 'https://example.test/es/contact/', 'a WordPress redirect to a translated page stays in Spanish');
$check(bl_i18n_redirect('https://example.test/about/') === false, 'a WordPress redirect to a page without Spanish is cancelled (the theme answers 404)');
$check(bl_i18n_redirect('https://other.test/about/') === 'https://other.test/about/' && bl_i18n_redirect('https://example.test/wp-content/x.pdf') === 'https://example.test/wp-content/x.pdf', 'redirects to other sites and files are left alone');
$check(bl_url('/thank-you/') === bl_i18n_localize_href(bl_url('/thank-you/')) && str_contains(bl_url('/thank-you/'), '/es/thank-you/'), 'bl_url localises');

// The translator: text runs with inline markup, attributes, meta, structured data and links.
$html = '<main><h2>Rated load</h2><p>For a comparison with the wheelbarrow, full-hopper weights by material and how to order one, read our <a href="/news/power-barrow-guide/">500 kg power barrow guide</a>.</p>'
    . '<img src="/a.webp" alt="Petrol engine"><a href="/products/mini-dumpers/">Mini dumpers</a><a href="/products/mini-dumpers/" hreflang="en">English</a>'
    . '<p>A sentence the dictionary does not have.</p></main>';
$out = bl_i18n_translate_html($html, 'es');
$check(str_contains($out, '<h2>Carga nominal</h2>'), 'a heading is translated');
$check(str_contains($out, '<a href="/news/power-barrow-guide/">guía del power barrow de 500 kg</a>'), 'an inline link keeps its markup inside the translated sentence');
$check(str_contains($out, 'alt="Motor de gasolina"'), 'alt text is translated');
$check(str_contains($out, '<a href="/es/products/mini-dumpers/">Minidumpers</a>'), 'a link to a translated page goes to its Spanish version');
$check(str_contains($out, '<a href="/products/mini-dumpers/" hreflang="en">'), 'the language switch link to English is kept');
$check(str_contains($out, 'A sentence the dictionary does not have.') && in_array('A sentence the dictionary does not have.', bl_i18n_missing(), true), 'unknown text stays English and is reported');
$meta = bl_i18n_translate_html('<meta name="description" content="Rated load"><script type="application/ld+json">{"@type":"BreadcrumbList","itemListElement":[{"@type":"ListItem","name":"Mini dumpers","item":"https://example.test/products/mini-dumpers/"}]}</script>', 'es');
$check(str_contains($meta, 'content="Carga nominal"'), 'the meta description is translated');
$check(str_contains($meta, '"name":"Minidumpers"') && str_contains($meta, 'https:\/\/example.test\/es\/products\/mini-dumpers\/') || str_contains($meta, 'https://example.test/es/products/mini-dumpers/'), 'breadcrumb names and addresses follow the language');
$gallery = bl_i18n_translate_html('<button data-gallery-alt="Petrol engine"></button><li data-alt="Petrol engine" data-caption="Rated load"></li>', 'es');
$check(str_contains($gallery, 'data-gallery-alt="Motor de gasolina"') && str_contains($gallery, 'data-alt="Motor de gasolina" data-caption="Carga nominal"'), 'the gallery and lightbox texts are translated');
$search = bl_i18n_translate_html('<script type="application/json" id="site-search-index">' . json_encode([['kind' => 'Mini dumper', 'title' => 'BL-T500-HD', 'text' => '500 kg Tracked Mini Dumper', 'url' => '/product/500kg-tracked-mini-dumper/'], ['kind' => 'Page', 'title' => 'About us', 'text' => 'Company and factory in Xuzhou', 'url' => '/about/']], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>', 'es');
$index = json_decode(preg_replace('~^<script[^>]*>|</script>$~', '', $search), true);
$check(($index[0]['kind'] ?? '') === 'Minidumper' && ($index[0]['text'] ?? '') === bl_t('500 kg Tracked Mini Dumper', 'es') && ($index[0]['url'] ?? '') === '/es/product/500kg-tracked-mini-dumper/' && ($index[0]['en']['text'] ?? '') === '500 kg Tracked Mini Dumper' && !isset($index[0]['en']['title']), 'a search result for a Spanish page is translated and links to it: ' . $search);
$check(($index[1]['url'] ?? '') === '/about/' && !in_array('Company and factory in Xuzhou', bl_i18n_missing(), true), 'a search result for an English-only page keeps its address and is not reported as missing');
$wa = bl_i18n_translate_html('<a href="https://wa.me/8615852499415?text=' . rawurlencode('Hello BOB-LIFT, I am interested in your mini dumpers.') . '">WhatsApp</a>', 'es');
$check(str_contains($wa, rawurlencode('Hola, BOB-LIFT. Me interesan sus minidumpers.')), 'the WhatsApp message is translated');
$check(sprintf(bl_t('Hello BOB-LIFT, I am interested in the %1$s (%2$s).'), bl_t('500 kg Tracked Mini Dumper'), 'BL-T500-HD') === 'Hola, BOB-LIFT. Me interesa el Minidumper sobre orugas de 500 kg (BL-T500-HD).', 'the product WhatsApp line');
$check(bl_t('Rated load', 'en') === 'Rated load', 'English is never translated');

// The enquiry plugin's messages and the scripts' strings all have Spanish.
foreach (['Invalid inquiry request. Please refresh and try again.', 'Please enter a valid email address and project details.', 'Please complete the verification and try again.', 'Too many inquiries were sent from your network. Please wait a few minutes and try again, or email us directly.', 'The inquiry could not be sent. Please try again.', 'Inquiry sent. Our team will contact you soon.'] as $message) {
    $check(bl_t($message) !== $message, "enquiry message has Spanish: $message");
}
foreach (BL_I18N_SCRIPT_STRINGS as $text) $check(bl_t($text) !== $text, "script string has Spanish: $text");
$island = bl_i18n_script_dictionary();
$check(str_starts_with($island, '<script type="application/json" id="bl-i18n">') && str_contains($island, 'Cualquiera'), 'the script dictionary is printed');

// The dictionary: every translation keeps its key's placeholders, and no prices.
$entries = require __DIR__ . '/../inc/i18n/es.php';
$marks = static function (string $text): array { preg_match_all('~</?t\d+>|<br>|%\d\$s~', $text, $m); sort($m[0]); return $m[0]; };
foreach ($entries as $key => $text) {
    $check(trim($text) !== '', "empty translation: $key");
    $check($marks((string) $key) === $marks($text), "placeholders differ: $key");
    $check(!preg_match('~(US\$|USD|€|\$\s?\d|precio desde)~i', $text), "a price in: $text");
}

// The audit comment (?bl_i18n_audit=1) cannot be closed or broken out of by the page text it lists.
$_GET['bl_i18n_audit'] = '1';
$audited = bl_i18n_output('<p>&#45;&#45;&#45;&gt;&lt;img src=x onerror=alert(1)&gt; and --!&gt; too</p>');
unset($_GET['bl_i18n_audit']);
$comment = substr($audited, (int) strpos($audited, '<!-- bl-i18n-missing'));
$check(substr_count($comment, '-->') === 1 && str_ends_with($comment, '-->') && !str_contains($comment, '--!>') && !str_contains($comment, '<img'), 'the audit comment escapes what it lists: ' . $comment);

$searchText = bl_product_search_text(['name' => '500 kg Tracked Mini Dumper', 'model' => 'BL-T500-HD']);
$check(str_contains($searchText, 'sobre orugas') && str_contains($searchText, 'tracked') && bl_search_fold('CUCHARÓN Año') === 'cucharon ano', 'the catalogue searches Spanish and English names, accents folded: ' . $searchText);

// The email-draft enquiry form (inquiry plugin off) and its review dialog have Spanish for every text and control.
if (!bl_inquiry_live()) {
    $selectedRecord = ['name' => '500 kg Tracked Mini Dumper', 'model' => 'BL-T500-HD']; $selectedFamily = 'dumper'; $prefillMessage = '';
    ob_start();
    require __DIR__ . '/../templates/partials/inquiry-form.php';
    eval('?>' . strstr((string) file_get_contents(__DIR__ . '/../templates/dialogs.php'), '<?php require', true)); // the review dialog, without the search after it
    $fallback = (string) ob_get_clean();
    $before = bl_i18n_missing();
    $spanish = bl_i18n_translate_html($fallback, 'es');
    $left = array_values(array_filter(array_diff(bl_i18n_missing(), $before), static fn($k) => !str_contains($k, 'Tracked Mini Dumper')));
    $check(str_contains($spanish, 'Preparar consulta') && str_contains($spanish, 'placeholder="¿Dónde usará el equipo?"') && str_contains($spanish, 'País / región'), 'the email-draft form is in Spanish');
    $check(!$left, 'nothing of the email-draft form or its review is left in English: ' . implode(' | ', $left));
} else {
    $check(false, 'the fixture should have the inquiry plugin off');
}

$GLOBALS['bl_lang'] = 'en';
$check(bl_i18n_script_dictionary() === '' && bl_i18n_localize_href('/contact/') === '/contact/', 'English pages are untouched');

if ($failures) exit(1);
echo "i18n ok (" . count($entries) . " Spanish entries)\n";
