<?php
/** Review regressions exercise the real save handlers against isolated in-memory WordPress boundaries. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';
$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$GLOBALS['posts'] = [1 => new WP_Post(1, 'bl_page_content', 'global')];
$save = static function (array $input) {
    return run_filter('rest_pre_insert_bl_page_content', (object) [], new WP_REST_Request(['id' => 1, 'meta' => ['_bl_content' => $input]]));
};
$save(['company' => ['phone' => '+1 234']]);
$check(empty(get_option('bl_content_global')['_bl_email_explicit']), 'unrelated REST edit selected email');
foreach (['sales@example.test', 'info@boblift.com'] as $email) {
    $check(!is_wp_error($save(['company' => ['email' => $email]])), 'valid REST email rejected');
    $check(get_option('bl_content_global')['_bl_email_explicit'] === true && bl_content_resolve('global', get_option('bl_content_global'))['company']['email'] === $email, 'explicit selection lost');
}
$check(!isset(get_option('bl_content_global')['company']['email']), 'default email no longer difference-based');
$save(['company' => ['phone' => '+44 567']]);
$check(get_option('bl_content_global')['_bl_email_explicit'] === true, 'partial write cleared selection');
foreach (['', 'invalid', '<b>sales@example.test</b>', "a@example.test\r\nBcc: x@example.test", null, []] as $invalid) {
    $before = get_option('bl_content_global');
    $input = ['company' => ['email' => $invalid, 'phone' => 'must not save']];
    $result = $save($input);
    $check(is_wp_error($result) && str_contains($result->get_error_message(), '销售邮箱'), 'REST invalid email lacks Chinese error');
    $check(get_option('bl_content_global') === $before, 'invalid REST write changed saved content');
    $_POST = ['bl_page' => 'global', 'bl_content_complete' => '1', 'bl_content_revision' => bl_content_revision($before, 'global'), 'bl_content' => $input];
    try { bl_content_handle_submission('save'); $check(false, 'invalid form accepted'); } catch (TestRedirect $redirect) {
        parse_str(parse_url($redirect->getMessage(), PHP_URL_QUERY), $_GET);
        $recovery = bl_content_recovery('global');
        $check(($recovery['input'] ?? null) === $input && str_contains($recovery['message'] ?? '', '销售邮箱'), 'form recovery lost input/error');
    }
    $check(get_option('bl_content_global') === $before, 'invalid form changed saved content');
}
$check(is_wp_error(bl_content_prepare('global', [], [], true)), 'complete form accepted missing required email');
$prepared = bl_content_prepare('global', [], bl_content_defaults('global'), true);
// A form saved with the address untouched leaves an older form recipient in charge.
$check(empty($prepared['_bl_email_explicit']), 'untouched form address took over the recipient');
$prepared = bl_content_prepare('global', ['company' => ['email' => 'sales@example.test']], bl_content_defaults('global'), true);
$check($prepared['_bl_email_explicit'] === true, 'form selecting default did not record intent');
$GLOBALS['options']['bl_content_global'] = ['company' => ['phone' => '+1 999', 'sales_whatsapp' => '44123456']];
function bl_wp_application_cases() { return []; }
$route = ['record' => bl_read_data('products')[0]];
$route['record']['excerpt'] = bl_product_excerpt($route['record']);
ob_start(); (static function (array $route) { require __DIR__ . '/../templates/pages/product.php'; })($route); $html = ob_get_clean();
$check(substr_count($html, '>WhatsApp +44123456</a>') === 1 && substr_count($html, '>+44123456</a>') === 1, 'product WhatsApp labels disagree with destination');
$check(substr_count($html, 'https://wa.me/44123456?text=') === 3, 'product WhatsApp URLs disagree');
$result = bl_content_prepare('global', [], ['company' => ['sales_whatsapp' => '']]);
$check(is_wp_error($result) && str_contains($result->get_error_message(), 'WhatsApp'), 'empty sales WhatsApp was accepted');
$result = bl_content_prepare('global', [], ['company' => ['email' => 'info@boblift.com']]);
$check(!is_wp_error($result) && $result['_bl_email_explicit'] === true, 'REST choosing the default email was not recorded');
$result = bl_content_prepare('global', [], ['company' => ['email' => 'sales%ab@example.com']]);
$check(!is_wp_error($result) && $result['company']['email'] === 'sales%ab@example.com', 'a valid percent address was rewritten');
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "backend P1 review save/render regressions ok\n";
