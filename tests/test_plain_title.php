<?php
/**
 * Titles come out of WordPress as HTML, so 4x4 arrives as 4&#215;4; the theme escapes titles itself and must start
 * from plain text, or the page shows "4&#215;4" in the heading, the browser tab and search results.
 * Run: php tests/test_plain_title.php
 */
declare(strict_types=1);
require __DIR__ . '/../inc/helpers.php';
$failures = 0;
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) { $failures++; fwrite(STDERR, "FAIL: $message\n"); } };
$check(bl_plain_title('4&#215;4 Dumper vs 4&#215;2') === "4\u{00D7}4 Dumper vs 4\u{00D7}2", 'numeric entity not decoded');
$check(bl_plain_title('Who&#8217;s &amp; why') === "Who\u{2019}s & why", 'quote and ampersand not decoded');
$check(bl_plain_title('<em>Plain</em> title') === 'Plain title', 'tags kept');
$check(htmlspecialchars(bl_plain_title('4&#215;4'), ENT_QUOTES, 'UTF-8') === "4\u{00D7}4", 'escaped again after decoding');
if ($failures) exit(1);
echo "plain title: ok\n";
