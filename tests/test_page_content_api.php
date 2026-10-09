<?php
/**
 * 资料下载 and the llms.txt intro are writable through bl_page_content items, and the range story / product summaries
 * count engine brands and choose "a" or "an" correctly. Run: php tests/test_page_content_api.php
 */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
function bl_wp_articles() { return []; }
function bl_wp_application_cases() { return []; }
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-support.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$GLOBALS['posts'] = [1 => new WP_Post(1, 'bl_page_content', 'global'), 2 => new WP_Post(2, 'bl_page_content', 'downloads'), 3 => new WP_Post(3, 'bl_page_content', 'tracking')];
$write = static fn(int $id, array $content) => run_filter('rest_pre_insert_bl_page_content', (object) [], new WP_REST_Request(['id' => $id, 'meta' => ['_bl_content' => $content]]));
$read = static fn(int $id) => run_filter('get_post_metadata', null, $id, '_bl_content', true)[0] ?? null;

// 资料下载: one item, its list replaced whole through the same writer as the screen.
$check(bl_page_content_key(2) === 'downloads' && bl_page_content_key(3) === '', 'downloads not exposed, or tracking exposed');
$check(array_key_exists('downloads', bl_page_content_pages()) && !array_key_exists('tracking', bl_page_content_pages()), 'item list wrong');
$items = $read(2)['items'];
$check(count($items) === 1 && $items[0]['id'] === 'catalogue', 'downloads read does not show the catalogue row');
$url = 'https://bobliftloader.com/wp-content/uploads/2026/10/boblift-compact-equipment-catalogue.pdf';
$items[0]['url'] = $url;
$check(!is_wp_error($write(2, ['items' => $items])), 'downloads REST write failed');
$check(bl_downloads_catalogue_row()['url'] === $url && $read(2)['items'][0]['url'] === $url, 'catalogue URL not saved through the item');
$check(is_wp_error($write(2, ['items' => [['id' => 'manual', 'url' => $url, 'title' => 'Manual', 'type' => 'manual', 'scope' => 'all']]])), 'catalogue row could be dropped');
$check(bl_downloads_catalogue_row()['url'] === $url, 'a rejected write changed the catalogue');
$check(!isset($read(2)['items'][0]['size']), 'read exposes the read-only size');

// Family catalogues: a family's own book first, the whole-range one otherwise, none when neither exists.
$check((bl_family_catalogue('mini-dumpers')['url'] ?? '') === $url, 'whole-range catalogue not offered to a family');
$dumperUrl = 'https://bobliftloader.com/wp-content/uploads/2026/10/dumpers.pdf';
$check(!is_wp_error($write(2, ['items' => [
    ['id' => 'catalogue', 'url' => $url, 'title' => '', 'description' => '', 'type' => 'catalogue', 'scope' => 'skid-steer-loaders', 'models' => ''],
    ['id' => 'dumper-catalogue', 'url' => $dumperUrl, 'title' => 'Mini dumper catalogue', 'description' => '', 'type' => 'catalogue', 'scope' => 'mini-dumpers', 'models' => ''],
]])), 'two family catalogues not saved');
$check((bl_family_catalogue('skid-steer-loaders')['url'] ?? '') === $url && (bl_family_catalogue('mini-dumpers')['url'] ?? '') === $dumperUrl, 'family catalogue picked the wrong book');
$check(!is_wp_error($write(2, ['items' => [['id' => 'catalogue', 'url' => $url, 'type' => 'catalogue', 'scope' => 'skid-steer-loaders']]])), 'loader-only catalogue not saved');
$check(bl_family_catalogue('mini-dumpers') === null, 'a family without a book still offers one');

// llms.txt intro: the global item wins over the older setting (bl_editable() caches per request, so write before reading).
$check(str_contains(bl_llms_txt_default_intro(), 'stand-on and seated models, on tracks or wheels') && !str_contains(bl_llms_txt_default_intro(), 'tracked and wheeled seated'), 'default intro still has the old range wording');
$GLOBALS['options']['bl_llms_txt_intro'] = 'Older setting intro.';
$check(!is_wp_error($write(1, ['llms' => ['intro' => "Intro from the item.\n\n- Fact one"]])), 'global llms write failed');
$llms = bl_llms_txt();
$check(str_contains($llms, "Intro from the item.\n\n- Fact one") && !str_contains($llms, 'Older setting intro.'), 'item intro not used first');
$check(($read(1)['llms']['intro'] ?? '') === "Intro from the item.\n\n- Fact one", 'llms intro not readable');

// Engine brands: model codes and product lines do not count as extra brands.
$p = static fn(string $options) => ['specs' => ['Engine options' => $options]];
$engines = bl_engine_brands([$p('KOOP / Rato / Briggs & Stratton'), $p('Zongshen / KOOP / Rato / Briggs & Stratton'), $p('Laidong / Yanmar / Kubota'), $p('Laidong 385B / Yanmar 3TNV80 / Kubota D1105'), $p('KOOP / Rato / Briggs & Stratton Vanguard')]);
$check($engines === ['KOOP', 'Rato', 'Briggs & Stratton', 'Zongshen', 'Laidong', 'Yanmar', 'Kubota'], 'engine brands miscounted: ' . implode(', ', $engines));
$check(bl_engine_brands([$p('Yanmar / Kubota')]) === ['Yanmar', 'Kubota'], 'plain brands changed');
$check(bl_engine_brands([$p('Zongshen / Rato / Briggs & Stratton / KOOP / electric version')]) === ['Zongshen', 'Rato', 'Briggs & Stratton', 'KOOP'], 'legacy electric option counted as a brand');
$legacy = ['Engine options' => 'Zongshen / Rato / Briggs & Stratton / KOOP / electric version', 'Starting' => 'Electric start', 'Fuel' => 'Petrol / diesel'];
$check(bl_without_retired_engine_options($legacy) === ['Engine options' => 'Zongshen / Rato / Briggs & Stratton / KOOP', 'Starting' => 'Electric start', 'Fuel' => 'Petrol / diesel'], 'retired electric option kept on an imported product');
$check(bl_without_retired_engine_options(['Engine options' => 'Yanmar / Kubota']) === ['Engine options' => 'Yanmar / Kubota'], 'plain engine options changed');
$check(bl_support_faq_answer('Several loaders offer more than one engine brand, and some have an electric version. Each model lists its choices under Engine options on its specification sheet.') === 'Several loaders offer more than one engine brand. Each model lists its choices under Engine options on its specification sheet.', 'saved stock engine answer still offers an electric version');
$check(bl_support_faq_answer('Our own answer.') === 'Our own answer.', 'edited FAQ answer changed');

// Articles follow how the figure is read aloud, and old "a 18.6 kW" summaries still count as generated.
foreach (['18.6 kW' => 'an', '8 kW' => 'an', '11 kW' => 'an', '800 kg' => 'an', '11,000 kg' => 'an', '7.6 kW' => 'a', '1,000 kg' => 'a', '180 kg' => 'a', '0.15 m³' => 'a', '110 kg' => 'a'] as $figure => $article) {
    $check(bl_indefinite_article($figure) === $article, "wrong article for $figure");
}
$product = ['name' => 'BL-T750-H Loader', 'model' => 'BL-T750-H', 'category' => 'skid-steer-loaders', 'specs' => ['Rated load' => '410 kg', 'Bucket capacity' => '0.15 m³', 'Engine power' => '18.6 kW']];
$check(bl_spec_phrase($product) === 'a 410 kg rated load, a 0.15 m³ bucket and an 18.6 kW engine', 'spec phrase: ' . bl_spec_phrase($product));
$check(bl_is_generated_product_excerpt('BL-T750-H Loader with a 410 kg rated load, a 0.15 m³ bucket and a 18.6 kW engine. Explore published specifications and discuss your application with BOB-LIFT.', ['BL-T750-H Loader']), 'old generated summary no longer recognised');
$check(bl_is_generated_product_excerpt(bl_product_excerpt($product), ['BL-T750-H Loader']), 'new generated summary not recognised');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "page content API, engine brands and articles ok\n";
