<?php
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-support.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';
require __DIR__ . '/../inc/wp-tracking.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$capture = static function (callable $render): string { ob_start(); $render(); return ob_get_clean(); };
$GLOBALS['posts'] = [1 => new WP_Post(1, 'bl_page_content', 'global'), 2 => new WP_Post(2, 'bl_page_content', 'about')];

// P0 schema round trips remain lossless, including previously unseen legacy fields.
foreach (['global', 'about', 'contact', 'privacy', 'news', '404', 'loaders', 'dumpers'] as $page) {
    $defaults = bl_content_defaults($page);
    $prepared = bl_content_prepare($page, ['legacy_extension' => ['retained' => true]], $defaults, true);
    $check(bl_content_resolve($page, $prepared) === $defaults, "$page defaults changed on form round trip");
    $check($prepared['legacy_extension']['retained'] === true, "$page lost historical fields");
    foreach (bl_content_schema($page) as $section) foreach ($section['fields'] as $field) {
        $check(bl_content_get($defaults, $field['key']) !== null, "$page schema path missing: {$field['key']}");
    }
}
$check(bl_content_defaults('about')['group']['facts'][1]['value'] === '37,000', 'about factory-area default changed');
$check(bl_content_defaults('contact')['mail']['body'] === "Preferred visit dates:\nNumber of visitors:\nEquipment of interest:\n", 'visit draft whitespace changed');
$check(bl_content_defaults('global')['footer']['support_label'] === 'Buying &amp; support', 'footer entity spelling changed');

// Exercise the actual REST proxy write, then render several consumers of one changed email/number.
$request = new WP_REST_Request(['id' => 1, 'meta' => ['_bl_content' => ['company' => ['email' => 'sales@example.test', 'phone' => '+44 123 456', 'sales_whatsapp' => '44123456', 'name' => 'Example Machinery', 'domain' => 'machines.example.test'], 'brand' => ['logo' => 'https://cdn.example.test/logo.png']]]]);
$result = run_filter('rest_pre_insert_bl_page_content', (object) [], $request);
$check(!is_wp_error($result), 'global REST write failed');
$check(bl_company('email') === 'sales@example.test', 'shared company read ignored REST save');
$footer = $capture(static fn() => require __DIR__ . '/../templates/footer.php');
$side = $capture(static fn() => require __DIR__ . '/../templates/partials/side-contact.php');
$check(str_contains($footer, 'mailto:sales@example.test') && str_contains($side, 'mailto:sales@example.test'), 'email did not propagate to footer and floating contact');
$check(str_contains($footer, 'tel:+44123456') && str_contains($side, 'https://wa.me/44123456'), 'phone/WhatsApp did not propagate');
$check(str_contains($footer, 'Example Machinery') && str_contains($footer, 'machines.example.test'), 'company/domain did not propagate');
$check(bl_brand_logo() === 'https://cdn.example.test/logo.png', 'brand logo not shared');
$check(bl_company('sales_whatsapp_label') === '+44123456', 'sales label still displays old phone');
$route = ['type' => 'contact', 'path' => '/contact/', 'status' => 200, 'title' => 'Contact', 'description' => 'Contact'];
$graph = bl_structured_data($route)['@graph'][0];
$check($graph['email'] === 'sales@example.test' && $graph['telephone'] === '+44123456' && $graph['name'] === 'Example Machinery', 'SEO company data not shared');
$check(str_contains(bl_privacy_body('intro'), 'sales@example.test') && str_contains(bl_privacy_body('intro'), 'Example Machinery'), 'privacy placeholders not shared');
$check(str_contains(bl_privacy_body('submitted'), 'ipinfo.io') && str_contains(bl_privacy_body('draft'), 'not stored in a website database'), 'privacy modes not distinct');
$check(!isset(BL_CONTENT_PAGES['tracking']) && bl_page_content_key(3) === '', 'tracking exposed as REST content item');
$globalRead = run_filter('get_post_metadata', null, 1, '_bl_content', true)[0];
$check(!isset($globalRead['tracking'], $globalRead['head']), 'raw tracking exposed through global REST read');

// Empty hooks emit no markup; authorized raw writes retain scripts, noscript, quotes and line endings.
foreach (['wp_head', 'wp_body_open', 'wp_footer'] as $hook) $check($capture(static fn() => run_hook($hook)) === '', "empty $hook emitted markup");
$snippets = ['head' => "<script>window.track = '<&>';\r\n</script>", 'body_open' => '<noscript><iframe src="https://example.test/tag"></iframe></noscript>', 'footer' => '<script async src="https://example.test/analytics.js"></script>'];
$expected = bl_content_revision([]);
$result = bl_content_locked('tracking', static fn($previous) => bl_content_prepare('tracking', $previous, $snippets + ['grouped' => '1'], true), $expected);
$check($result === true, 'authorized raw save failed');
$check(array_intersect_key(get_option('bl_content_tracking'), $snippets) === $snippets, 'tracking code was filtered');
foreach (['head' => 'wp_head', 'body_open' => 'wp_body_open', 'footer' => 'wp_footer'] as $position => $hook) {
    $check($capture(static fn() => run_hook($hook)) === bl_tracking_markup('analytics', $snippets[$position]), "$position snippet output mismatch");
}
$check(isset($GLOBALS['hooks']['wp_head'][1], $GLOBALS['hooks']['wp_body_open'][1], $GLOBALS['hooks']['wp_footer'][99]), 'tracking hook priorities changed');
$before = get_option('bl_content_tracking');
foreach (['unfiltered_html', 'manage_options'] as $cap) {
    $GLOBALS['caps'] = [$cap => false];
    $check($capture('bl_tracking_screen') === '', "$cap denied user can see raw settings");
    $_POST = ['bl_page' => 'tracking', 'bl_content' => ['head' => 'changed'], 'bl_content_complete' => '1', 'bl_content_revision' => bl_content_revision($before)];
    $denied = false;
    try { bl_content_handle_submission('save'); } catch (RuntimeException $e) { $denied = str_contains($e->getMessage(), '权限'); }
    $check($denied && get_option('bl_content_tracking') === $before, "$cap denied user saved raw code");
}
$GLOBALS['caps'] = [];
$_POST = [];
$check(is_wp_error(bl_content_locked('tracking', static fn($previous) => [], $expected)), 'tracking stale version did not conflict');

// Registrations are exercised without a database; the proxy-creation hook at priority 20 is intentionally excluded.
foreach ($GLOBALS['hooks']['init'][10] ?? [] as $callback) $callback();
run_hook('rest_api_init');
$check($GLOBALS['settings']['bl_catalogue_url']['show_in_rest'] === true, 'legacy settings API field missing');
$endpoint = $GLOBALS['rest_routes']['boblift/v1/catalogue'];
$GLOBALS['caps']['edit_pages'] = false;
$check(!$endpoint[0]['permission_callback']() && !$endpoint[1]['permission_callback'](), 'catalogue permits non-editor');
$GLOBALS['caps'] = ['manage_options' => false, 'edit_pages' => true];
$check($endpoint[0]['permission_callback']() && $endpoint[1]['permission_callback'](), 'catalogue unnecessarily requires administrator');
$GLOBALS['options']['bl_catalogue_size'] = 4096;
$updated = bl_catalogue_rest_save(new WP_REST_Request(['bl_catalogue_url' => 'https://example.test/catalogue.pdf']));
$check($updated['bl_catalogue_url'] === 'https://example.test/catalogue.pdf' && $updated['bl_catalogue_size'] === 0, 'catalogue read/write contract mismatch');
$check(is_wp_error(bl_catalogue_rest_save(new WP_REST_Request(['bl_catalogue_size' => 9]))), 'catalogue size accepted writes');
$check($endpoint['schema']()['properties']['bl_catalogue_size']['readonly'] === true, 'catalogue size schema is writable');
$meta = $GLOBALS['registered_meta']['user']['bl_title'];
$GLOBALS['caps'] = ['edit_user' => false];
$check(!$meta['auth_callback'](false, 'bl_title', 42), 'author title allowed unauthorized user edit');
$check(end($GLOBALS['cap_checks']) === ['edit_user', 42], 'author title permission uses wrong target user');
$check($meta['show_in_rest'] === true && $meta['sanitize_callback']('<b>Director</b>') === 'Director', 'author title registration incomplete');
$bio = $GLOBALS['registered_meta']['user']['bl_bio'];
$check(!$bio['auth_callback'](false, 'bl_bio', 42) && $bio['show_in_rest'] === true, 'author bio allowed unauthorized user edit or is not in REST');
$check($bio['sanitize_callback']("<b>Two</b> lines\nhere") === "Two lines\nhere", 'author bio keeps markup or loses its line break');
$caseImage = $GLOBALS['post_meta_schema']['bl_application']['_bl_record']['show_in_rest']['schema']['properties']['image'];
$check($caseImage['deprecated'] === true && str_contains($caseImage['description'], '特色图片'), 'case image deprecation missing');
$check(in_array('image', BL_REST_FIELDS['bl_application'], true), 'historical image field was removed');


// Render real editor fields to catch invalid schema paths, missing controls and capability-gated UI.
$GLOBALS['caps'] = [];
foreach (['global', 'about', 'contact', 'privacy', 'news', '404'] as $page) {
    $screen = $capture(static fn() => bl_content_screen($page));
    $check(str_contains($screen, 'bl_content_complete'), "$page form lacks completeness marker");
    if ($page === 'global') {
        $check(!str_contains($screen, 'name="bl_content[body_open]"'), 'global screen still embeds tracking form');
        $screen = $capture('bl_tracking_screen');
        $check(str_contains($screen, 'name="bl_content[body_open]"'), 'independent tracking screen missing form');
        $check(!str_contains($screen, $snippets['head']) && str_contains($screen, esc_textarea($snippets['head'])), 'raw scripts executed instead of escaped in editor');
    }
}
$GLOBALS['caps'] = ['unfiltered_html' => false];
$check(!str_contains($capture(static fn() => bl_content_screen('global')), 'name="bl_content[body_open]"'), 'tracking controls leaked to editor');
$GLOBALS['caps'] = [];
$GLOBALS['inquiry_form_id'] = 1;
$check(str_contains($capture(static fn() => bl_content_screen('privacy')), '当前模式：站内提交'), 'privacy submitted mode label missing');
$GLOBALS['inquiry_form_id'] = 0;
$check(str_contains($capture(static fn() => bl_content_screen('privacy')), '当前模式：邮件草稿'), 'privacy draft mode label missing');
$check(bl_content_html('privacy', 'hero.title') === 'PRIVACY &<br>ENQUIRIES.', 'WordPress default privacy heading entities changed');
$GLOBALS['options']['site_icon'] = 12;
$check(bl_brand_icon('images/icons/icon-32.png', 32) === 'https://example.test/native-icon.png', 'native site icon ignored');
unset($GLOBALS['options']['site_icon']);
$check(bl_brand_icon('images/icons/icon-32.png', 32) === bl_asset('images/icons/icon-32.png'), 'theme icon fallback changed');
$GLOBALS['posts'][3] = new WP_Post(3, 'bl_page_content', 'tracking');
$check(bl_page_content_key(3) === '', 'tracking proxy identity accepted');
$trackingRequest = new WP_REST_Request(['id' => 3, 'meta' => ['_bl_content' => ['head' => 'REST injection']]]);
$check(is_wp_error(run_filter('rest_pre_insert_bl_page_content', (object) [], $trackingRequest)), 'REST accepted tracking content');
$GLOBALS['options']['bl_content_news'] = ['seo' => ['image' => 'https://cdn.example.test/news.jpg']];
$check(bl_share_image(['type' => 'news']) === 'https://cdn.example.test/news.jpg', 'news share image override ignored');
$check(bl_seo_content_page(['type' => 'products', 'category' => 'mini-dumpers']) === 'dumpers' && bl_seo_content_page(['type' => 'products', 'category' => 'skid-steer-loaders']) === 'loaders', 'product SEO mapping incorrect');

$registration = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/fixtures/register-backend-p1-types.php'), $registration, $registrationStatus);
$check($registrationStatus === 0 && in_array('author', json_decode(implode("\n", $registration), true) ?? [], true), 'news CPT lacks author support');

$mailProbe = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/fixtures/check-backend-p1-mail.php'), $mailProbe, $mailStatus);
$check($mailStatus === 0, 'CF7 primary recipient or legacy compatibility failed');

$akismetProbe = [];
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/fixtures/check-inquiry-akismet.php'), $akismetProbe, $akismetStatus);
$check($akismetStatus === 0, 'enquiry form fields lack their Akismet options');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "backend P1 regression ok\n";
