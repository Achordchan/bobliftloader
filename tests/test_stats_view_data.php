<?php
declare(strict_types=1);
// php tests/test_stats_view_data.php — Jetpack Stats names each theme list page by its path; other views are left alone.
define('ABSPATH', __DIR__ . '/');
require __DIR__ . '/../inc/wordpress.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

$list = ['v' => 'ext', 'blog' => '257707557', 'post' => '0', 'arch_results' => '10'];
$named = bl_stats_view_data($list, true, '/products/mini-dumpers/?utm_source=blaze');
$check(($named['arch_other'] ?? '') === '/products/mini-dumpers/', 'category page not named by its path');
$check(!isset($named['arch_results']), 'arch_results kept next to arch_other');
$check($named['blog'] === '257707557' && $named['post'] === '0', 'other fields changed');
$check((bl_stats_view_data($list, true, '/news/page/2/')['arch_other'] ?? '') === '/news/page/2/', 'news page 2 not named');

$single = ['v' => 'ext', 'post' => '699'];
$check(bl_stats_view_data($single, true, '/news/x/') === $single, 'a post view changed');
$check(bl_stats_view_data($list, false, '/missing/') === $list, 'a view outside the list pages changed');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "ok\n";
