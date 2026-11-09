<?php
declare(strict_types=1);
// php tests/test_event_notices.php — the event bar and the home event card show only with words in them and inside their dates.
require __DIR__ . '/../inc/bootstrap.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

$check(bl_notice_live('', '', '2026-11-28'), 'no dates: always live');
$check(bl_notice_live('', '2026-11-27', '2026-11-27'), 'last day is still live');
$check(!bl_notice_live('', '2026-11-27', '2026-11-28'), 'the day after the last day is not live');
$check(!bl_notice_live('2026-11-01', '', '2026-10-31'), 'before the first day is not live');
$check(bl_notice_live('2026-11-01', '2026-11-27', '2026-11-01'), 'first day is live');
$check(!bl_notice_live('', '27/11/2026', '2026-10-01'), 'a date not written Y-m-d hides the notice');
$check(!bl_notice_live('', '2026-02-30', '2026-01-01'), 'an impossible date hides the notice');

$render = static function (string $file, array $vars = []): string { extract($vars); ob_start(); require __DIR__ . '/../templates/partials/' . $file; return (string) ob_get_clean(); };
// The theme's defaults hold no notice, so neither shows until one is written.
$check(trim($render('event-bar.php')) === '', 'event bar shows with empty defaults');
$check(trim($render('event-card.php', ['content' => bl_editable('home')])) === '', 'event card shows with empty defaults');

$card = ['eyebrow' => 'Trade show', 'title' => 'Meet us at bauma CHINA 2026', 'text' => 'Stand H.03.', 'facts' => [['label' => 'Stand', 'value' => 'Outdoor H.03'], ['label' => 'Empty', 'value' => ''], ['label' => 'A', 'value' => '1'], ['label' => 'B', 'value' => '2'], ['label' => 'C', 'value' => '3']], 'button' => 'Plan your visit', 'url' => '/news/bauma-china-2026/', 'image' => '', 'alt' => '', 'starts' => '', 'ends' => ''];
$html = $render('event-card.php', ['content' => ['event' => $card]]);
$check(str_contains($html, 'Meet us at bauma CHINA 2026') && str_contains($html, 'bl-event-card-plain'), 'card without image renders as text only');
$check(substr_count($html, '<dt>') === 3 && !str_contains($html, '>Empty<'), 'card shows at most 3 facts and skips empty ones');
$check(str_contains($html, 'href="' . bl_url('/news/bauma-china-2026/') . '"'), 'card button links to the site path');
$check(trim($render('event-card.php', ['content' => ['event' => ['ends' => '2000-01-01'] + $card]])) === '', 'card past its last day still shows');

if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
echo "event notices: ok\n";
