<?php
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-support.php';
require __DIR__ . '/../inc/wp-product-page.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';
require __DIR__ . '/../inc/wp-tracking.php';
require __DIR__ . '/../inc/wp-admin-ui.php';

function add_meta_box($id, $title, $callback, $screen, $context, $priority) { $GLOBALS['boxes'][$screen][$id] = compact('title', 'callback', 'context', 'priority'); }
function remove_meta_box($id, $screen, $context) { unset($GLOBALS['boxes'][$screen][$id]); }
function add_menu_page(...$args) { $GLOBALS['menu_slug'] = $args[3]; }
function add_submenu_page($parent, $title, $menu, $cap, $slug, $callback) { $GLOBALS['submenu'][$parent][] = [$menu, $cap, $slug]; $GLOBALS['screens'][$slug] = $callback; }
function get_the_post_thumbnail_url($post, $size) { return $GLOBALS['thumbnail_url'] ?? ''; }
function get_post_thumbnail_id($post) { return $GLOBALS['thumbnail_url'] ?? '' ? 7 : 0; }
function selected($a, $b, $echo = true) { return $a === $b ? ' selected' : ''; }
function bl_wp_image($path) { return str_starts_with($path, 'images/') ? bl_asset($path) : $path; }

function get_current_screen() { return null; }
function remove_menu_page($slug) {}
function wp_count_posts($type) { return (object) ['publish' => 0]; }
function get_template_directory() { return dirname(__DIR__); }
function wp_enqueue_media() {}
function wp_enqueue_style(...$args) { $GLOBALS['styles'][$args[0]] = $args; }
function wp_enqueue_script(...$args) { $GLOBALS['scripts'][$args[0]] = $args; }

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$capture = static function (callable $render): string { ob_start(); $render(); return ob_get_clean(); };
$GLOBALS['posts'] = [1 => new WP_Post(1, 'bl_product', 'machine', 'Machine'), 2 => new WP_Post(2, 'bl_news', 'story'), 3 => new WP_Post(3, 'bl_page_content', 'support')];

// Exact schema paths survive presentation changes and a complete form round trip.
foreach (array_keys(BL_CONTENT_PAGES) as $page) {
    $page = (string) $page;
    $paths = static function (array $sections): array { $paths = []; foreach ($sections as $section) foreach ($section['fields'] as $field) $paths[] = $field['key']; sort($paths); return $paths; };
    $check($paths(bl_content_schema($page)) === $paths(bl_content_schema_fields($page)), "$page changed schema keys");
    $defaults = bl_content_defaults($page);
    $check(bl_content_resolve($page, bl_content_prepare($page, [], $defaults, true)) === $defaults, "$page round trip changed defaults");
}
$home = bl_content_schema('home');
$check(array_column(array_slice($home, 0, 6), 'title') === ['首屏', '首屏 · 迷你翻斗车', '01 产品系列', '滑移装载机卡片', '迷你翻斗车卡片', '产品系列下方'], 'home visual order incorrect');
$app = bl_content_schema('applications');
$check(str_starts_with($app[1]['fields'][0]['key'], 'jobs.') && $app[count($app)-2]['fields'][0]['key'] === 'send.eyebrow', 'application order incorrect');
run_hook('add_meta_boxes_bl_product');
$boxes = $GLOBALS['boxes']['bl_product'];
$check($boxes['bl-product-page']['title'] === '概述' && $boxes['bl-product-page']['context'] === 'bl_main', 'overview not before body');
foreach (['photos', 'attachments', 'faq', 'specs', 'video'] as $kind) $check($boxes['bl-product-' . $kind]['context'] === 'normal', "$kind should follow body");
$check(!isset($boxes['postimagediv']) && isset($boxes['bl-effective-image']), 'duplicate featured-image box remains');
$overview = $capture(static fn() => ($boxes['bl-product-page']['callback'])($GLOBALS['posts'][1], $boxes['bl-product-page']));
$check(str_contains($overview, 'bl_page_nonce') && !str_contains($overview, 'bl_page[faq]'), 'overview callback not compatible with WP meta-box arguments');

// Overflow is preserved by both the REST cleaner and actual classic-save hooks.
$faq = array_map(static fn($n) => ['q' => 'Question ' . $n, 'a' => 'Answer ' . $n, 'link_label' => '', 'link_url' => ''], range(1, 7));
$photos = array_map(static fn($n) => ['src' => 'images/' . $n . '.jpg', 'group' => $n < 3 ? 'drawing' : 'detail', 'alt' => '', 'caption' => 'Photo ' . $n], range(1, 26));
$specs = array_combine(array_map(static fn($n) => 'Spec ' . $n, range(1, 42)), array_fill(0, 42, '10 mm'));
$check(count(bl_rest_field('bl_product', 'faq', $faq)) === 7, 'REST truncated FAQ');
$check(count(bl_rest_field('bl_product', 'photos', $photos)) === 26, 'REST truncated photos');
$check(count(bl_rest_field('bl_product', 'specs', $specs)) === 42, 'REST truncated specs');
$parallel = static function (array $rows): array { $out = []; foreach ($rows as $row) foreach ($row as $key => $value) $out[$key][] = $value; return $out; };
$_POST = ['bl_product_complete' => '1', 'bl_record_nonce' => 'bl_save_record', 'bl_page_nonce' => 'bl_save_product_page', 'bl_record' => ['category' => 'mini-dumpers', 'specs' => ['key' => array_keys($specs), 'value' => array_values($specs)]], 'bl_page' => ['faq' => $parallel($faq), 'photos' => $parallel($photos), 'attachments' => ['Pallet forks', 'Unknown attachment']]];
run_hook('save_post_bl_product', 1); run_hook('save_post', 1);
$record = get_post_meta(1, '_bl_record', true);
$check(count($record['faq']) === 7 && count($record['specs']) === 42 && count($record['photos']) === 26, 'classic save lost overflow');
$check($record['attachments'] === ['Pallet forks', 'Unknown attachment'], 'hidden dumper attachments lost');
$check(count(bl_product_faq($record)) === BL_CONTENT_LIMITS['faq'], 'FAQ display limit diverged');
$check(count(bl_product_photos($record + ['model' => 'Test'], ['drawing'])) === BL_CONTENT_LIMITS['drawing'], 'drawing display limit diverged');
$check(array_sum(array_map('count', bl_spec_groups($record['specs']))) === BL_CONTENT_LIMITS['specs'], 'spec display limit diverged');
$factory = array_fill(0, 5, ['image' => 'https://example.test/factory.jpg', 'alt' => '', 'caption' => 'Factory']);
$saved = bl_content_prepare('loaders', [], ['product' => ['factory' => ['images' => $factory], 'faq' => $faq]]);
$check(count(bl_content_resolve('loaders', $saved)['product']['factory']['images']) === 5 && count(bl_content_resolve('loaders', $saved)['product']['faq']) === 7, 'content save lost overflow');
foreach (bl_content_schema('loaders') as $section) foreach ($section['fields'] as $field) {
    if ($field['key'] === 'product.factory.images') $check($field['max'] === BL_CONTENT_LIMITS['factory'], 'factory editor limit diverged');
    if ($field['key'] === 'product.faq') $check($field['max'] === BL_CONTENT_LIMITS['faq'], 'shared FAQ editor limit diverged');
}
$check(str_contains(bl_product_page_row('faq', $faq[0]), 'data-bl-fold') && str_contains(bl_product_page_row('photos', $photos[0]), 'hidden'), 'rows not folded');

// The selector preserves unknown values and original CSV/array contracts.
$html = bl_content_selector('attachments', 'bl_page[attachments]', ['Unknown attachment', 'Pallet forks']);
$check(str_contains($html, '未找到：Unknown attachment') && str_contains($html, 'name="bl_page[attachments][]"'), 'unknown array choice not retained');
$html = bl_content_selector('models', 'bl_content[jobs][test][models]', 'Typo, FC10');
$check(str_contains($html, 'value="Typo, FC10"') && str_contains($html, '未找到：Typo') && str_contains($html, 'data-bl-choice="up"'), 'CSV contract or sorting missing');
$slug = bl_application_slug($GLOBALS['bl_application_base'][0]);
$GLOBALS['options']['bl_content_applications'] = ['jobs' => [$slug => ['title' => '已改名的中文场景']]];
$check(bl_case_hubs()[$slug] === '已改名的中文场景', 'case choices ignore current Chinese title');

// Image priority matches the frontend; backups are only visible in a disclosure.
$GLOBALS['meta'][2]['_bl_record'] = ['image' => 'images/backup.jpg'];
$GLOBALS['thumbnail_url'] = 'https://example.test/featured.jpg';
$html = $capture(static fn() => bl_effective_image_box($GLOBALS['posts'][2]));
$check(substr_count($html, '<img ') === 1 && str_contains($html, '来源：特色图片'), 'effective image duplicates preview or has wrong source');
$GLOBALS['thumbnail_url'] = '';
$check(str_contains($capture(static fn() => bl_effective_image_box($GLOBALS['posts'][2])), '来源：备用图片'), 'backup image priority wrong');
$GLOBALS['meta'][2]['_bl_record'] = [];
$check(str_contains($capture(static fn() => bl_effective_image_box($GLOBALS['posts'][2])), '来源：主题默认图片'), 'news default source wrong');
$check(str_contains($capture(static fn() => bl_news_image_box($GLOBALS['posts'][2])), '<details><summary>备用图片</summary>'), 'news backup not collapsed');
$html = bl_content_row_field(['type' => 'image', 'label' => '照片'], 'photo', 'photo-id', '');
$check(str_contains($html, 'for="photo-id-pick"') && str_contains($html, 'id="photo-id-pick"') && str_contains($html, '移除图片'), 'image label or removal incorrect');
$check(str_contains(bl_content_input('image', 'photo', 'photo-id', '', 'images/default.jpg'), '恢复默认图片'), 'default-image removal label incorrect');

// Shared terms change all variable consumers, while exact legacy copy migrates and authored text stays intact.
$legacy = ['after' => ['warranty_text' => 'Every BOB-LIFT loader and dumper carries a 12-month warranty that covers the whole machine.', 'intro' => 'Our custom 12-month message.']];
$migrated = bl_content_resolve('support', $legacy);
$check(str_contains($migrated['after']['warranty_text'], '{warranty_months}') && $migrated['after']['intro'] === $legacy['after']['intro'], 'legacy variable migration rewrote custom text');
$request = new WP_REST_Request(['id' => 3, 'meta' => ['_bl_content' => ['terms' => ['warranty' => ['value' => '24'], 'lead' => ['value' => '10–40'], 'moq' => ['value' => '2']]]]]);
$check(!is_wp_error(run_filter('rest_pre_insert_bl_page_content', (object) [], $request)), 'business terms REST save failed');
$content = bl_business_render('support', bl_content_resolve('support', get_option('bl_content_support')));
$check(str_contains($content['after']['warranty_text'], '24-month') && str_contains($content['after']['title'], '24 MONTHS'), 'warranty did not propagate');
$check(str_contains($content['buying']['steps'][2]['text'], 'Two machines are') && str_contains($content['buying']['steps'][3]['text'], '10 to 40'), 'MOQ or lead time did not propagate');
$check(str_contains(bl_business_render('home', bl_content_defaults('home'))['approach']['steps'][2]['text'], '24-month'), 'home warranty did not propagate');
$check(str_contains(bl_content_resolve('support', get_option('bl_content_support'))['after']['warranty_text'], '{warranty_months}'), 'expanded text persisted');

// News visibility is not a destructive save operation.
$_POST = ['bl_record_nonce' => 'bl_save_record', 'bl_record' => ['line' => 'group', 'category' => 'product-news', 'event_start' => '', 'event_name' => 'Next event', 'event_venue' => 'Saved venue']];
run_hook('save_post', 2);
$news = get_post_meta(2, '_bl_record', true);
$check($news['category'] === 'product-news' && $news['event']['name'] === 'Next event', 'conditional news fields discarded on save');
$check(bl_rest_field('bl_news', 'event', ['start' => '', 'name' => 'Draft event'])['name'] === 'Draft event', 'REST discarded folded event fields');
$check(str_contains($capture(static fn() => bl_news_section_box($GLOBALS['posts'][2])), 'value="product-news" selected'), 'group editor reset the hidden stored category');
$check(str_contains($capture(static fn() => bl_news_event_box($GLOBALS['posts'][2])), '<details data-bl-event>'), 'non-event disclosure unexpectedly open');

// Parent slug and callbacks survive menu reordering; raw scripts remain separately permission-gated.
$GLOBALS['pagenow'] = 'admin.php';
add_action('admin_menu', static fn() => add_submenu_page('bl-content-home', '询盘邮件', '询盘邮件', 'edit_pages', 'bl-inquiry-mail', 'mail'), 21);
run_hook('admin_menu');
$expectedOrder = ['bl-content-global', 'bl-content-home', 'bl-content-loaders', 'bl-content-dumpers', 'bl-content-applications', 'bl-content-support', 'bl-content-about', 'bl-content-news', 'bl-content-contact', 'bl-content-thanks', 'bl-content-privacy', 'bl-content-404', 'bl-downloads', 'bl-inquiry-mail', 'bl-content-tracking'];
$check(BL_CONTENT_MENU_ORDER === $expectedOrder, 'content menu order omits or misplaces a screen');
$check($GLOBALS['menu_slug'] === 'bl-content-home' && array_column($GLOBALS['submenu']['bl-content-home'], 2) === $expectedOrder, 'menu order or parent slug changed');
$dashboard = $capture('bl_dashboard_widget');
preg_match_all('/admin\.php\?page=([^"]+)/', $dashboard, $links);
$check($links[1] === $expectedOrder, 'dashboard content links do not follow menu order');
foreach (['bl-content-home', 'bl-content-thanks', 'bl-content-tracking', 'bl-downloads', 'bl-inquiry-mail'] as $screen) {
    $GLOBALS['scripts'] = $GLOBALS['styles'] = [];
    run_hook('admin_enqueue_scripts', 'website_page_' . $screen);
    $check(isset($GLOBALS['scripts']['bl-content-editor']), "$screen lacks shared unsaved-change handler");
    if (in_array($screen, ['bl-downloads', 'bl-inquiry-mail'], true)) $check(!isset($GLOBALS['styles']['bl-content-editor']), "$screen unexpectedly loads content layout styles");
}
// 首页 is served by the top-level menu page; every other screen keeps its own callback.
foreach (BL_CONTENT_PAGES as $page => $_) if ($page !== 'home') $check(is_callable($GLOBALS['screens']['bl-content-' . $page]), "$page original URL has no callback");
$html = $capture('bl_tracking_screen');
$check(str_contains($html, 'class="bl-ce-form"') && !str_contains($html, 'data-bl-unsaved') && str_contains($html, 'bl_content_revision') && str_contains($html, 'bl_content_complete'), 'tracking missed unified save or dirty controls');
$_POST = ['bl_page' => 'tracking', 'bl_content_complete' => '1', 'bl_content_revision' => bl_content_revision([]), 'bl_content' => ['head' => '<script>track()</script>']];
try { bl_content_handle_submission('save'); } catch (TestRedirect $redirect) { $check(str_contains($redirect->getMessage(), 'bl-content-tracking'), 'tracking save redirected to global'); }
$GLOBALS['caps'] = ['unfiltered_html' => false];
$check($capture('bl_tracking_screen') === '', 'tracking permission regressed');

// WordPress runs every callback hooked to the top-level slug, so the 首页 submenu must not add a second screen.
$check(($GLOBALS['screens']['bl-content-home'] ?? null) === '', 'home screen registered twice');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "backend P2 regression ok\n";
