<?php
declare(strict_types=1);
// php tests/test_social_links.php — a social address that is not a web address hides that network. A separate file because the company settings are read once per request.
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
$GLOBALS['posts'] = [1 => new WP_Post(1, 'bl_page_content', 'global')];
$route = ['type' => 'contact', 'path' => '/contact/', 'status' => 200, 'title' => 'Contact', 'description' => 'Contact'];
$sameAs = static fn() => bl_structured_data($route)['@graph'][0]['sameAs'];

// A network whose address is not a web address hides everywhere and the other keeps showing.
$save = static fn(array $company) => run_filter('rest_pre_insert_bl_page_content', (object) [], new WP_REST_Request(['id' => 1, 'meta' => ['_bl_content' => ['company' => $company]]]));
$check(!is_wp_error($save(['youtube_url' => 'javascript:alert(1)', 'facebook_url' => 'https://www.facebook.com/example'])), 'saving the social addresses failed');
$check(array_keys(bl_social_links()) === ['facebook'], 'a non-web address became a social link: ' . implode(',', array_keys(bl_social_links())));
$footer = $capture(static fn() => require __DIR__ . '/../templates/footer.php');
$check(!str_contains($footer, 'youtube') && !str_contains($footer, 'javascript:') && str_contains($footer, 'https://www.facebook.com/example'), 'footer does not follow the settings');
$check(count($sameAs()) === 3 && in_array('https://www.facebook.com/example', $sameAs(), true), 'sameAs does not follow the settings');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "social link settings ok\n";
