<?php
/** The Spanish pages (inc/i18n.php): paths, links, the HTML translator and the dictionary's own integrity. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';

$failures = 0;
$check = static function (bool $ok, string $what) use (&$failures): void {
    if (!$ok) { fwrite(STDERR, "FAIL: $what\n"); $failures++; }
};

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

$GLOBALS['bl_lang'] = 'en';
$check(bl_i18n_script_dictionary() === '' && bl_i18n_localize_href('/contact/') === '/contact/', 'English pages are untouched');

if ($failures) exit(1);
echo "i18n ok (" . count($entries) . " Spanish entries)\n";
