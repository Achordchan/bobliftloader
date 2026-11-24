<?php
declare(strict_types=1);
// php tests/test_indexnow.php — which sitemap pages IndexNow sends, and the Spanish section of llms.txt.
require __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/indexnow.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

$check(bl_indexnow_valid_key(str_repeat('a1', 16)), 'a 32-character hex key is refused');
foreach ([str_repeat('a', 31), str_repeat('A', 32), str_repeat('a', 32) . "\n", 'g' . str_repeat('a', 31), null, 123] as $bad) $check(!bl_indexnow_valid_key($bad), 'a broken key is accepted: ' . var_export($bad, true));

$check(bl_indexnow_host('https://BobliftLoader.com./') === 'bobliftloader.com', 'host not normalised');
$check(bl_indexnow_host('https://[::1]/') === '::1', 'IPv6 brackets kept');
$check(bl_indexnow_english_path('/es/news/fob-vs-cif/') === '/news/fob-vs-cif/', 'language folder kept');
$check(bl_indexnow_english_path('/escalators/') === '/escalators/', 'a path starting with es is cut');

// Each story, product and case carries its own post's time in every language; every other page the newest of all.
$modified = [
    'bl_news' => ['fob-vs-cif' => '2026-11-21 10:00:00', 'tt-payment' => '2026-11-22 01:00:00'],
    'bl_product' => ['fy30' => '2026-11-01 00:00:00'],
    'bl_application' => [],
    'bl_page_content' => ['home' => '2026-11-23 08:00:00'],
];
$paths = ['/', '/news/', '/news/fob-vs-cif/', '/es/news/fob-vs-cif/', '/product/fy30/', '/es/product/fy30/', '/news/unknown/'];
$versions = bl_indexnow_versions($paths, $modified);
$check($versions['/news/fob-vs-cif/'] === '2026-11-21 10:00:00' && $versions['/es/news/fob-vs-cif/'] === '2026-11-21 10:00:00', 'story version wrong');
$check($versions['/product/fy30/'] === '2026-11-01 00:00:00' && $versions['/es/product/fy30/'] === '2026-11-01 00:00:00', 'product version wrong');
$check($versions['/'] === $versions['/news/'] && strlen($versions['/']) === 32, 'shared pages do not share the site fingerprint');
$check($versions['/news/unknown/'] === $versions['/'], 'a story with no post does not fall back to the site fingerprint');

// The order of the records does not change the fingerprint; removing an older one, editing one or adding one does.
$check(bl_indexnow_versions(['/'], array_reverse($modified, true))['/'] === $versions['/'], 'record order changes the fingerprint');
$removed = $modified;
unset($removed['bl_product']['fy30']);
$check(bl_indexnow_versions(['/news/'], $removed)['/news/'] !== $versions['/news/'], 'removing an older record leaves shared pages unchanged');
$edited = $modified;
$edited['bl_news']['fob-vs-cif'] = '2026-11-21 11:00:00';
$check(bl_indexnow_versions(['/'], $edited)['/'] !== $versions['/'], 'editing a record leaves shared pages unchanged');

// Saved translation corrections change the pages in that language only.
$withStrings = bl_indexnow_versions($paths, $modified, ['es' => 'abc']);
$check($withStrings['/es/news/fob-vs-cif/'] === '2026-11-21 10:00:00|abc' && $withStrings['/news/fob-vs-cif/'] === '2026-11-21 10:00:00', 'translation corrections not in the Spanish version only');
$check(bl_indexnow_versions(['/es/'], $modified, ['es' => 'abd'])['/es/'] !== bl_indexnow_versions(['/es/'], $modified, ['es' => 'abc'])['/es/'], 'a changed correction leaves the Spanish page unchanged');
$check(bl_indexnow_language('/es/news/x/') === 'es' && bl_indexnow_language('/escalators/') === 'en', 'language folder misread');

// New, changed and removed pages are sent; unchanged ones are not.
$sent = ['/' => 'a', '/news/' => 'b', '/old/' => 'c'];
$now = ['/' => 'a', '/news/' => 'B', '/es/' => 'd'];
$changed = bl_indexnow_changed($sent, $now);
sort($changed);
$check($changed === ['/es/', '/news/', '/old/'], 'changed pages: ' . implode(',', $changed));
$check(bl_indexnow_changed($now, $now) === [], 'unchanged pages are sent');

// One body per 10,000 URLs, with the host, key and key file.
$many = array_map(static fn(int $i): string => '/news/p' . $i . '/', range(1, BL_INDEXNOW_BATCH + 1));
$payloads = bl_indexnow_payloads($many, 'https://bobliftloader.com', str_repeat('ab', 16));
$check(count($payloads) === 2 && count($payloads[0]['body']['urlList']) === BL_INDEXNOW_BATCH && count($payloads[1]['paths']) === 1, 'batches not split at 10,000');
$body = $payloads[0]['body'];
$check($body['host'] === 'bobliftloader.com' && $body['keyLocation'] === 'https://bobliftloader.com/' . str_repeat('ab', 16) . '.txt', 'host or key location wrong');
$check($body['urlList'][0] === 'https://bobliftloader.com/news/p1/', 'URL not absolute');

// Outside WordPress nothing is submitted.
$check(!bl_indexnow_enabled(), 'IndexNow is on outside WordPress');

// llms.txt lists the Spanish pages at their /es/ address, ranges first, under Spanish titles.
$llms = bl_llms_txt();
$spanish = substr($llms, (int) strpos($llms, '## En español'));
$check(str_contains($llms, '## En español'), 'llms.txt has no Spanish section');
$check(str_contains($spanish, '(https://bobliftloader.com/es/products/mini-dumpers/)'), 'Spanish mini dumper range missing');
$check(str_contains($spanish, 'minicargadora') && str_contains($spanish, '/es/product/'), 'Spanish product titles or addresses missing');
$check(strpos($spanish, '/es/products/') < strpos($spanish, '/es/product/'), 'ranges are not listed first');
$check(!preg_match('~\]\(https://bobliftloader\.com/(product|news)/~', substr($spanish, 0, (int) (strpos($spanish, '## Company') ?: strlen($spanish)))), 'an English address in the Spanish section');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "indexnow ok\n";
