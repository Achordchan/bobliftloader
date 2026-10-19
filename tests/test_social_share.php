<?php
declare(strict_types=1);
// php tests/test_social_share.php — news reaches the social pages only when the article opts in.
define('ABSPATH', __DIR__ . '/');
require __DIR__ . '/../inc/wordpress.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

$check(bl_social_share_allowed(true, 'bl_news', '') === false, 'news without opt-in is shared');
$check(bl_social_share_allowed(true, 'bl_news', false) === false, 'news opted out is shared');
$check(bl_social_share_allowed(true, 'bl_news', '1') === true, 'opted-in news is not shared');
$check(bl_social_share_allowed(false, 'bl_news', '1') === false, 'opt-in overrides a Jetpack refusal');
$check(bl_social_share_allowed(true, 'post', '') === true, 'other types lost Jetpack default');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "ok\n";
