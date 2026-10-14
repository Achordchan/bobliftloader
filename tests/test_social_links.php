<?php
declare(strict_types=1);
// php tests/test_social_links.php — the YouTube and Facebook addresses are company settings; every place that shows them reads the same two (defaults).
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

// Defaults: both profiles show, in the footer with a new-tab, noopener link, and and in the structured data (llms.txt is checked in test_company_addresses.php).
$links = bl_social_links();
$check(array_keys($links) === ['youtube', 'facebook'], 'default social networks: ' . implode(',', array_keys($links)));
$check($links['youtube']['url'] === 'https://www.youtube.com/@bobliftloader' && $links['facebook']['url'] === 'https://www.facebook.com/@bobliftloader', 'default social addresses changed');
$footer = $capture(static fn() => require __DIR__ . '/../templates/footer.php');
$check(str_contains($footer, 'href="https://www.youtube.com/@bobliftloader" target="_blank" rel="noopener noreferrer"') && str_contains($footer, 'href="https://www.facebook.com/@bobliftloader" target="_blank" rel="noopener noreferrer"'), 'footer social links missing or not opening safely in a new tab');
$check(str_contains($footer, 'aria-label="BOB-LIFT Loader on YouTube (opens in a new tab)"') && str_contains($footer, '>Follow us<'), 'footer social links have no accessible name');
$check(count(array_intersect($sameAs(), ['https://www.youtube.com/@bobliftloader', 'https://www.facebook.com/@bobliftloader'])) === 2 && in_array('https://www.boblift.com/', $sameAs(), true), 'sameAs lost a profile or the sister sites');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "social links ok\n";
