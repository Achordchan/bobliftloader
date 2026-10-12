<?php
/**
 * A pin can carry an end date: a fair's story stays on top through its last day (China time) and then drops back into
 * date order, while a standing pin stays; while both are pinned, the one with the end date comes first.
 * Run: php tests/test_news_pin_until.php
 */
declare(strict_types=1);
require __DIR__ . '/../inc/helpers.php';
$failures = 0;
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) { $failures++; fwrite(STDERR, "FAIL: $message\n"); } };
$story = static fn(string $slug, array $extra = []) => $extra + ['slug' => $slug, 'pinned' => false, 'pinned_until' => ''];
$articles = [
    $story('standing', ['pinned' => true]),
    $story('newest'),
    $story('fair', ['pinned' => true, 'pinned_until' => '2026-10-19']),
    $story('older'),
    $story('bad-date', ['pinned' => true, 'pinned_until' => 'soon']),
];
$order = static fn(string $today) => array_column(bl_news_order($articles, $today), 'slug');
$check($order('2026-10-12') === ['fair', 'standing', 'bad-date', 'newest', 'older'], 'before the end date: ' . implode(',', $order('2026-10-12')));
$check($order('2026-10-19') === ['fair', 'standing', 'bad-date', 'newest', 'older'], 'on the last day the pin still holds');
$check($order('2026-10-20') === ['standing', 'bad-date', 'newest', 'fair', 'older'], 'after the end date: ' . implode(',', $order('2026-10-20')));
$check(!bl_news_pinned($story('x', ['pinned_until' => '2026-12-31']), '2026-10-12'), 'an end date alone pins nothing');
$check(bl_news_pinned($story('x', ['pinned' => true]), '2030-01-01'), 'a pin without an end date lapsed');
if ($failures) exit(1);
echo "news pin until: ok\n";
