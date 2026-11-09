<?php
declare(strict_types=1);
// php tests/test_theme_includes.php — WordPress loads the theme through functions.php and answers /sitemap.xml, /llms.txt and
// robots.txt from its hooks before any template (and so before inc/bootstrap.php) runs: every file bootstrap.php loads must be
// loaded by functions.php too, or a function those early answers reach is undefined there (a fatal error on the live site only).
$bootstrap = (string) file_get_contents(__DIR__ . '/../inc/bootstrap.php');
$functions = (string) file_get_contents(__DIR__ . '/../functions.php');
preg_match_all("~require_once __DIR__ \. '/([a-z0-9-]+\.php)'~", $bootstrap, $m);
$missing = array_values(array_filter($m[1], static fn(string $file): bool => !str_contains($functions, "require_once __DIR__ . '/inc/" . $file . "'")));
if (!$m[1] || $missing) { fwrite(STDERR, 'functions.php does not load: ' . implode(', ', $missing ?: ['(no files found in bootstrap.php)']) . "\n"); exit(1); }
echo "theme includes: ok\n";
