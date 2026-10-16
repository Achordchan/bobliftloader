<?php
declare(strict_types=1);
// php tests/test_site_name.php — Google's site name for this website is BOB-LIFT Loader: the WebSite node, og:site_name and the home page title agree.
require __DIR__ . '/../inc/bootstrap.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$render = static function (array $route): string { ob_start(); bl_render_seo($route); return ob_get_clean(); };

$home = ['type' => 'home', 'path' => '/', 'status' => 200, 'title' => 'Mini Skid Steer Loader &amp; Mini Dumper Factory', 'description' => 'Home', 'record' => null];
$website = array_values(array_filter(bl_structured_data($home)['@graph'], static fn($node) => $node['@type'] === 'WebSite'))[0] ?? [];
$check(($website['name'] ?? '') === 'BOB-LIFT Loader' && ($website['alternateName'] ?? '') === 'BOB-LIFT', 'WebSite name/alternateName: ' . json_encode($website));
$head = $render($home);
$check(str_contains($head, '| BOB-LIFT Loader</title>'), 'home title does not end with the site name');
$check(str_contains($head, '<meta property="og:site_name" content="BOB-LIFT Loader">'), 'og:site_name changed');
$about = $render(['type' => 'about', 'path' => '/about/', 'status' => 200, 'title' => 'About', 'description' => 'About', 'record' => null]);
$check(str_contains($about, '<title>About | BOB-LIFT</title>'), 'other pages lost the short brand suffix');

// Google needs a favicon whose size is a multiple of 48 px; the .ico is declared 48x48, so it must hold a 48 px image.
$ico = file_get_contents(__DIR__ . '/../assets/images/icons/favicon.ico');
$sizes = [];
for ($i = 0, $n = unpack('v', $ico, 4)[1]; $i < $n; $i++) $sizes[] = (ord($ico[6 + 16 * $i]) ?: 256);
$check(in_array(48, $sizes, true), 'favicon.ico has no 48 px image: ' . implode(',', $sizes));

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "site name ok\n";
