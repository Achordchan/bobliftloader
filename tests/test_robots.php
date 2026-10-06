<?php
declare(strict_types=1);
// php tests/test_robots.php — AI crawlers are let back in; everything else in robots.txt stays.
require __DIR__ . '/../inc/seo.php';

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
$failures = [];
foreach (['GPTBot', 'ClaudeBot', 'Google-Extended', 'CCBot', 'AI crawlers'] as $gone) if (stripos($out, $gone) !== false) $failures[] = "$gone still present";
foreach (['User-agent: *', 'Disallow: /wp-admin/', 'Allow: /wp-admin/admin-ajax.php', 'Sitemap: https://bobliftloader.com/wp-sitemap.xml'] as $kept) if (!str_contains($out, $kept)) $failures[] = "$kept lost";
if (bl_allow_ai_crawlers($out) !== $out) $failures[] = 'not idempotent';
if (str_contains(bl_allow_ai_crawlers("User-agent: *\nDisallow: /private/\n"), 'Disallow: /private/') === false) $failures[] = 'generic rule dropped';
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "robots ok\n";
