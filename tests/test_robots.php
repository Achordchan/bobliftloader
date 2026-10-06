<?php
declare(strict_types=1);
// php tests/test_robots.php — AI crawlers are let back in; everything else in robots.txt stays.
require __DIR__ . '/../inc/seo.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

// The platform's file: separate one-agent groups behind a comment.
$platform = <<<TXT
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

# AI crawlers blocked via Jetpack SEO.
User-agent: GPTBot
Disallow: /

User-agent: ClaudeBot
Disallow: /

User-agent: Google-Extended
Disallow: /

User-agent: CCBot
Disallow: /

Sitemap: https://bobliftloader.com/wp-sitemap.xml
TXT;
$out = bl_allow_ai_crawlers($platform);
foreach (['GPTBot', 'ClaudeBot', 'Google-Extended', 'CCBot', 'AI crawlers'] as $gone) $check(stripos($out, $gone) === false, "$gone still present");
foreach (['User-agent: *', 'Disallow: /wp-admin/', 'Allow: /wp-admin/admin-ajax.php', 'Sitemap: https://bobliftloader.com/wp-sitemap.xml'] as $kept) $check(str_contains($out, $kept), "$kept lost");
$check(substr_count($out, 'Disallow: /') === 1 && !str_contains($out, "Disallow: /\n"), 'a blocking rule is left behind');
$check(bl_allow_ai_crawlers($out) === $out, 'not idempotent');

// One group shared by several agents: the other agent keeps the rule.
$shared = bl_allow_ai_crawlers("User-agent: OtherBot\nUser-agent: GPTBot\nDisallow: /\n\nUser-agent: *\nDisallow: /wp-admin/\n");
$check(str_contains($shared, "User-agent: OtherBot\nDisallow: /"), 'shared group lost OtherBot or its rule');
$check(!str_contains($shared, 'GPTBot'), 'GPTBot left in the shared group');
$check(str_contains($shared, "User-agent: *\nDisallow: /wp-admin/"), 'wildcard group changed');

// A comment between the agent and its rules does not strand the rule on the group above.
$commented = bl_allow_ai_crawlers("User-agent: *\nDisallow: /wp-admin/\n\nUser-agent: ClaudeBot\n# keep out\nDisallow: /\n\nSitemap: https://example.com/sitemap.xml\n");
$check(substr_count($commented, 'Disallow:') === 1 && str_contains($commented, 'Disallow: /wp-admin/'), 'an orphaned rule stayed behind');
$check(!str_contains($commented, 'keep out') && !str_contains($commented, 'ClaudeBot'), 'the removed group left text behind');
$check(str_contains($commented, 'Sitemap: https://example.com/sitemap.xml'), 'Sitemap lost');

// The platform also lists these two data scrapers (sentibot in two spellings); they go with the rest.
$scrapers = bl_allow_ai_crawlers("User-agent: *\nDisallow: /wp-admin/\n\nUser-agent: omgili\nDisallow: /\n\nUser-agent: SentiBot\nDisallow: /\n\nUser-agent: sentibot\nDisallow: /\n");
$check(stripos($scrapers, 'omgili') === false && stripos($scrapers, 'sentibot') === false, 'omgili or SentiBot still present');
$check($scrapers === "User-agent: *\nDisallow: /wp-admin/\n", 'the wildcard group changed');

// Nothing named: unchanged apart from layout.
$plain = "User-agent: *\nDisallow: /private/\n";
$check(bl_allow_ai_crawlers($plain) === $plain, 'a plain file changed');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "robots ok\n";
