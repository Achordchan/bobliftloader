<?php
/** News stories that carry their own translation (post meta _bl_i18n): they get a page in that language, the others do not. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';
function bl_wp_application_cases(): array { return []; }
function wp_insert_post($post) { return 0; }
function bl_wp_articles(): array { return [['slug' => 'mini-dumper-specs'], ['slug' => 'mini-skid-steer-specs']]; }

$failures = 0;
$check = static function (bool $ok, string $what) use (&$failures): void {
    if (!$ok) { fwrite(STDERR, "FAIL: $what\n"); $failures++; }
};

run_hook('init');
$schema = $GLOBALS['post_meta_schema']['bl_news'][BL_I18N_NEWS_META] ?? null;
$check(is_array($schema) && $schema['type'] === 'object' && isset($schema['show_in_rest']['schema']['properties']['es']), 'the news translation meta is registered for REST with a Spanish part');
$clean = $schema['sanitize_callback'](['es' => ['Mini Dumper Specs <b>Explained</b>' => 'Especificaciones del <i>minidumper</i>', 'Read the <t0 class="x">guide</t0>' => 'Lea la <t0 onclick="x()">guía</t0>', 'Empty' => ''], 'xx' => ['A' => 'B'], 'en' => ['A' => 'B']]);
$check($clean === ['es' => ['Mini Dumper Specs Explained' => 'Especificaciones del minidumper', 'Read the <t0>guide</t0>' => 'Lea la <t0>guía</t0>']], 'only the site\'s other languages are kept, cleaned like the settings corrections: ' . json_encode($clean, JSON_UNESCAPED_UNICODE));

$GLOBALS['posts'] = [
    501 => new WP_Post(501, 'bl_news', 'mini-dumper-specs', 'Mini Dumper Specs Explained'),
    502 => new WP_Post(502, 'bl_news', 'mini-skid-steer-specs', 'Mini Skid Steer Specs Explained'),
    503 => new WP_Post(503, 'bl_news', 'draft-story', 'Draft', 'draft'),
];
$GLOBALS['meta'][501][BL_I18N_NEWS_META] = ['es' => ['Mini Dumper Specs Explained' => 'Especificaciones del minidumper explicadas', 'Rated load comes first.' => 'La carga nominal va primero.']];
$GLOBALS['meta'][502][BL_I18N_NEWS_META] = ['es' => ['Only blank' => '  ']];
$GLOBALS['meta'][503][BL_I18N_NEWS_META] = ['es' => ['Draft' => 'Borrador']];

$check(bl_has_language('/news/mini-dumper-specs/', 'es') && bl_has_language('/news/mini-dumper-specs', 'es'), 'a story with Spanish entries has a Spanish page');
$check(!bl_has_language('/news/mini-skid-steer-specs/', 'es'), 'a story whose entries are all blank has none');
$check(!bl_has_language('/news/draft-story/', 'es'), 'an unpublished story has none');
$check(!bl_has_language('/news/', 'es'), 'the news list stays English only');
$check(bl_has_language('/products/mini-dumpers/', 'es'), 'the listed pages are still there');
$check(bl_i18n_alternates('/news/mini-dumper-specs/') === ['en' => '/news/mini-dumper-specs/', 'es' => '/es/news/mini-dumper-specs/'], 'both languages are alternates of the translated story');
$check(in_array('/es/news/mini-dumper-specs/', bl_sitemap_paths(), true) && !in_array('/es/news/mini-skid-steer-specs/', bl_sitemap_paths(), true), 'the Spanish story is in the sitemap, an untranslated one is not');

$GLOBALS['bl_lang'] = 'es';
$out = bl_i18n_translate_html('<main><h1>Mini Dumper Specs Explained</h1><p>Rated load comes first.</p><a href="/news/mini-dumper-specs/">Mini Dumper Specs Explained</a><a href="/news/mini-skid-steer-specs/">x</a></main>', 'es');
$check(str_contains($out, '<h1>Especificaciones del minidumper explicadas</h1>') && str_contains($out, '<p>La carga nominal va primero.</p>'), 'the story\'s own entries translate its page: ' . $out);
$check(str_contains($out, '<a href="/es/news/mini-dumper-specs/">Especificaciones del minidumper explicadas</a>'), 'a link to the translated story goes to its Spanish page, with its title translated on cards elsewhere');
$check(str_contains($out, '<a href="/news/mini-skid-steer-specs/">'), 'a link to an untranslated story stays English');
$GLOBALS['options']['bl_i18n_es_strings'] = ['Rated load comes first.' => 'Primero, la carga nominal.'];
$check(bl_t('Rated load comes first.', 'es') !== '' , 'lookups still work with settings corrections present');

exit($failures ? 1 : 0);
