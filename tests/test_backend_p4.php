<?php
/** P4 fields: real schema, classic/REST validators and content persistence against in-memory WordPress boundaries. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-product-page.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';
function selected($a, $b, $echo = true) { return $a === $b ? ' selected' : ''; }
function bl_wp_application_cases() { return []; }
$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$GLOBALS['posts'] = [1 => new WP_Post(1, 'bl_product', 'machine'), 2 => new WP_Post(2, 'bl_page_content', 'loaders'), 3 => new WP_Post(3, 'bl_page_content', 'dumpers')];

// The preset inventory must include every key explicitly consumed by grouping, charts and comparison code.
$keys = [];
foreach (['inc/helpers.php', 'inc/product-page.php', 'templates/partials/compare-table.php', 'templates/partials/range-story.php'] as $file) {
    $source = file_get_contents(__DIR__ . '/../' . $file);
    preg_match_all("/\\['specs'\\]\\['([^']+)'\\]/", $source, $matches);
    $keys = [...$keys, ...$matches[1]];
}
foreach (bl_read_data('products') as $product) {
    $keys = [...$keys, ...array_keys($product['specs'])];
    $check(bl_specs_error($product['specs']) === '', 'existing product rejected: ' . $product['model']);
}
foreach (array_unique([...$keys, 'Max travel speed', 'Wheelbase', 'Engine model', 'Hopper capacity', 'Drive', 'Brakes']) as $key) $check(isset(bl_spec_choices()[$key]), 'missing preset ' . $key);
$html = bl_product_spec_row('Rated load', '1,000 kg');
$check(str_contains($html, '<select') && str_contains($html, '额定载荷 · Rated load') && str_contains($html, '自定义参数') && str_contains($html, '1,000 kg'), 'known row missing choice, Chinese label or value');
$check(str_contains(bl_product_spec_row('Custom test', 'Special value'), 'value="Custom test"'), 'custom key not preserved');
$check(bl_product_posted_specs(['key' => ['stale', 'Custom'], 'value' => ['250 kg', 'yes'], 'choice' => ['Rated load', '']]) === [['key' => 'Rated load', 'value' => '250 kg'], ['key' => 'Custom', 'value' => 'yes']], 'choice resolution depends on JS');

$invalid = [
    ['Rated load' => '1 t'], ['Rated load' => '1,00 kg'], ['Rated load' => '12.5 kg'], ['Rated load' => '1000001 kg'],
    ['Bucket capacity' => '0.5 m3'], ['Bucket capacity' => '0.5 M³'], ['Bucket capacity' => '1.2.3 m³'], ['Bucket capacity' => '0 m³'],
    ['Engine power' => '20 hp'], ['Machine width' => '0.96 m'], ['Bucket width' => '90 cm'], ['Overall height' => '1.2 m'],
    ['Standard dimensions' => '2050 x 880 x 1300 mm'], ['Standard dimensions' => '2050 × 880 mm'],
    ['Running gear' => '履带'], ['Operator station' => '站驾'], ['rated load' => '250 kg'], ['<b>Rated load</b>' => '3 tons'], ['Rated load' => []],
];
foreach ($invalid as $specs) {
    $check(bl_specs_error($specs) !== '', 'invalid accepted: ' . json_encode($specs));
    $before = $GLOBALS['meta'];
    $request = new WP_REST_Request(['id' => 1, 'meta' => ['_bl_record' => ['specs' => $specs, 'model' => 'Must not save']]]);
    $result = run_filter('rest_pre_insert_bl_product', (object) [], $request);
    $check(is_wp_error($result) && $GLOBALS['meta'] === $before, 'REST did not refuse invalid input before write');
}
$valid = ['Rated load' => '1,500 kg', 'Bucket capacity' => '0.8 m³', 'Engine power' => '17.8 kW', 'Machine width' => '960 mm', 'Standard dimensions' => '2050 × 880 × 1300 mm', 'Custom' => 'Keeps its display text'];
$request = new WP_REST_Request(['id' => 1, 'meta' => ['_bl_record' => ['specs' => $valid]]]);
$check(!is_wp_error(run_filter('rest_pre_insert_bl_product', (object) [], $request)), 'valid REST specs refused');
$record = $request->get_param('meta')['_bl_record'];
$check($record['specs'] === $valid && $record['load'] === 1500 && $record['capacity'] === '0.8' && $record['power'] === '17.8', 'display strings or derived metrics changed');
$GLOBALS['meta'][1]['_bl_record'] = ['model' => 'Original', 'specs' => ['Rated load' => '250 kg'], 'legacy_field' => ['keep']];
$_POST = ['action' => 'editpost', 'post_ID' => '1', 'post_title' => 'Retain <title>', 'content' => '<p>Retain body & text</p>', 'excerpt' => 'Retain excerpt', 'bl_product_complete' => '1', 'bl_record_nonce' => 'bl_save_record', 'bl_page_nonce' => 'bl_save_product_page', 'bl_page' => ['faq' => ['q' => ['New question'], 'a' => ['New answer']]], 'bl_record' => ['model' => 'New', 'specs' => ['key' => ['Rated load'], 'value' => ['3 tons']]]];
$before = $GLOBALS['meta'];
try { run_hook('load-post.php'); $check(false, 'invalid classic form accepted'); } catch (RuntimeException $error) {
    $response = $error->getMessage();
    foreach (['额定载荷', '3 tons', 'Retain &lt;title&gt;', '&lt;p&gt;Retain body &amp; text&lt;/p&gt;', 'Retain excerpt', 'New question', 'New answer', 'bl_product_complete', '修正并保存'] as $text) $check(str_contains($response, $text), 'retry lost ' . $text);
    $check(strpos($response, 'bl_product_complete') > strpos($response, '3 tons'), 'retry completeness marker is not last');
}
$check($GLOBALS['meta'] === $before, 'classic preflight wrote part of record');
$_POST['bl_record']['specs']['value'] = ['300 kg'];
run_hook('load-post.php'); run_hook('save_post_bl_product', 1); run_hook('save_post', 1);
$record = get_post_meta(1, '_bl_record', true);
$check($record['specs'] === ['Rated load' => '300 kg'] && $record['load'] === 300 && $record['legacy_field'] === ['keep'] && $record['faq'][0]['q'] === 'New question', 'retry failed to save valid complete input');
$_POST['bl_record']['specs'] = ['key' => ['Rated load', 'Rated load'], 'value' => ['200 kg', '300 kg']];
try { run_hook('load-post.php'); $check(false, 'duplicate silently overwritten'); } catch (RuntimeException $error) { $check(str_contains($error->getMessage(), '重复'), 'duplicate error missing'); }

// Every family field travels through the same difference-based writer; old options and explicit empties survive.
foreach (['loaders' => 2, 'dumpers' => 3] as $page => $id) {
    $defaults = bl_content_defaults($page);
    $schema = bl_content_schema($page); $paths = [];
    foreach ($schema as $section) foreach ($section['fields'] as $field) $paths[] = $field['key'];
    $check(count($paths) === count(array_unique($paths)), $page . ' has duplicate schema paths');
    $check(bl_content_resolve($page, bl_content_prepare($page, [], $defaults, true)) === $defaults, $page . ' default form round trip changed data');
    $copySection = array_values(array_filter($schema, static fn($section) => $section['title'] === '产品页通用模块'))[0];
    $check(in_array('product.copy.warranty_scope', array_column($copySection['fields'], 'key'), true), $page . ' new copy not inside existing product module');
    $request = new WP_REST_Request(['id' => $id, 'meta' => ['_bl_content' => ['catalog' => ['hero_intro' => 'Edited intro'], 'catalog_filters' => ['gear' => ['label' => 'Edited gear', 'options' => ['tracked' => 'Edited tracks']]], 'product' => ['copy' => ['warranty_scope' => '{model}: {warranty_months} months', 'faq_intro' => '']]]]]);
    $check(!is_wp_error(run_filter('rest_pre_insert_bl_page_content', (object) [], $request)), $page . ' content REST write failed');
    $saved = get_option('bl_content_' . $page);
    $resolved = bl_content_resolve($page, $saved);
    $check($resolved['catalog']['hero_intro'] === 'Edited intro' && $resolved['catalog_filters']['gear']['options']['tracked'] === 'Edited tracks' && $resolved['product']['copy']['faq_intro'] === '', $page . ' edited labels or explicit empty lost');
    $check($saved['product']['copy']['warranty_scope'] === '{model}: {warranty_months} months', 'expanded variable persisted');
    $old = ['legacy_block' => ['keep' => 'original'], 'product' => ['factory' => ['text' => 'Old factory text']]];
    $merged = bl_content_prepare($page, $old, ['catalog' => ['empty_title' => 'Edited empty']]);
    $check($merged['legacy_block'] === $old['legacy_block'] && bl_content_resolve($page, $merged)['product']['factory']['text'] === 'Old factory text', 'legacy data overwritten');
}
$sections = array_column(bl_content_schema('loaders'), 'title');
$check(array_search('系列介绍与喷漆定制', $sections) < array_search('属具', $sections) && array_search('属具', $sections) < array_search('制造流程', $sections), 'loader sections not in frontend order');
$steps = bl_content_defaults('loaders')['making']['steps'];
$check(count($steps) === 6 && array_keys($steps[0]) === ['image', 'title', 'text', 'alt'], 'six manufacturing steps missing fields');
$reordered = array_reverse($steps); $reordered[0]['image'] = 'https://media.example.test/custom.jpg'; $reordered[1]['image'] = '';
$prepared = bl_content_prepare('loaders', [], ['making' => ['steps' => $reordered]]);
$resolvedSteps = bl_content_resolve('loaders', $prepared)['making']['steps'];
$check(array_column($resolvedSteps, 'title') === array_column($reordered, 'title') && $resolvedSteps[0]['image'] === 'https://media.example.test/custom.jpg', 'manufacturing reorder or custom photo lost');
$check(bl_content_resolve('loaders', bl_content_prepare('loaders', $prepared, ['making' => ['steps' => []]]))['making']['steps'] === [], 'cleared steps restored defaults');

$GLOBALS['options']['bl_content_loaders']['making'] = $prepared['making'];

// Resolve family-specific runtime copy with changed unified terms. No database or expanded template persistence.
$GLOBALS['options']['bl_content_support'] = ['terms' => ['warranty' => ['value' => '24'], 'lead' => ['value' => '10–40']]];
$check(bl_family_copy('loaders', 'product.copy.warranty_scope', ['model' => 'Test <model>']) === 'Test &lt;model&gt;: 24 months', 'model escaping or unified warranty variable failed');
$check(bl_family_copy('dumpers', 'product.copy.warranty_scope', ['model' => 'Dumper']) === 'Dumper: 24 months', 'dumper variable failed');
$check(bl_family_copy('loaders', 'product.copy.faq_intro') === '', 'explicit empty rendered fallback');
$check(bl_catalog_filters('skid-steer-loaders')['gear']['options']['tracked'] === 'Edited tracks', 'edited filters not consumed');
ob_start(); require __DIR__ . '/../templates/partials/making-story.php'; $makingHtml = ob_get_clean();
$check(str_contains($makingHtml, 'src="https://media.example.test/custom.jpg"') && !str_contains($makingHtml, 'https://media.example.test/custom.jpg 900w'), 'custom manufacturing image got fabricated responsive variants');
$check(!str_contains($makingHtml, 'src=""'), 'cleared manufacturing photo emitted an empty image URL');
$check(strpos($makingHtml, '>Ready to ship</a>') < strpos($makingHtml, '>Cut and weld</a>'), 'manufacturing reorder did not render');
$GLOBALS['posts'] = [];
$product = bl_read_data('products')[0]; $product['excerpt'] = bl_product_excerpt($product);
ob_start(); (static function ($route) { require __DIR__ . '/../templates/pages/product.php'; })(['record' => $product]); $html = ob_get_clean();
$check(str_contains($html, $product['model'] . ': 24 months') && str_contains($html, 'Ships in 10–40 working days from order'), 'detail did not consume edited copy/terms');
// A row saved before the rules is left alone while untouched, and checked again once it is edited.
$check(bl_specs_error(['Rated load' => 'about 1 ton'], ['Rated load' => 'about 1 ton']) === '', 'an untouched legacy spec blocked saving');
$check(bl_specs_error(['Rated load' => 'about 2 ton'], ['Rated load' => 'about 1 ton']) !== '', 'an edited spec skipped validation');
// A number sent over REST for a free-form spec is accepted, as it was before the unit rules.
$check(bl_specs_error(['Number of cylinders' => 4]) === '', 'a numeric free-form spec was refused');
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "backend P4 fields regression ok\n";
