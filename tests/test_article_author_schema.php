<?php
declare(strict_types=1);
// php tests/test_article_author_schema.php — a story's Article node names the person in its byline; imported stories keep the organisation.
require __DIR__ . '/../inc/seo.php';
if (!function_exists('bl_public_base')) { function bl_public_base(): string { return 'https://bobliftloader.com'; } }

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$org = 'https://www.boblift.com/#organization';

$person = bl_article_author(['author' => ['name' => 'Achord Chan', 'title' => 'Product Operations Director, BOB-LIFT', 'avatar' => '/wp-content/uploads/2026/10/achord.webp', 'bio' => '']], $org);
$check(($person['@type'] ?? '') === 'Person' && ($person['name'] ?? '') === 'Achord Chan', 'the byline author is not a Person');
$check(($person['jobTitle'] ?? '') === 'Product Operations Director, BOB-LIFT', 'job title lost');
$check(($person['image'] ?? '') === 'https://bobliftloader.com/wp-content/uploads/2026/10/achord.webp', 'avatar not made absolute');
$check(($person['worksFor'] ?? []) === ['@id' => $org], 'the person is not tied to the organisation');

$bare = bl_article_author(['author' => ['name' => 'Editor', 'title' => '', 'avatar' => '']], $org);
$check(!isset($bare['jobTitle']) && !isset($bare['image']), 'empty title or avatar was written out');
$check(bl_article_author([], $org) === ['@id' => $org], 'a story without an author did not fall back to the organisation');
$check(bl_article_author(['author' => ['name' => '']], $org) === ['@id' => $org], 'an empty author name was marked up');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "ok\n";
