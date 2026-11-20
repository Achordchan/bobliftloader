<?php
declare(strict_types=1);
// php tests/test_organization_entity.php [empty] — the company is one Organization on the crane site and here: the same @id, brand name, official website (the group site), square logo, credit code and profile list. With "empty", 全站设置 → 起重机站推荐 has no address and the crane line's profiles leave the list with the crane website.
require __DIR__ . '/fixtures/wp-memory.php';
$empty = ($argv[1] ?? '') === 'empty';
if ($empty) $GLOBALS['options']['bl_content_global'] = ['sister' => ['url' => '']];
require __DIR__ . '/../inc/bootstrap.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$graph = bl_structured_data(['type' => 'home', 'path' => '/', 'title' => 'Home', 'description' => 'Home', 'record' => null, 'status' => 200]);
$organization = array_values(array_filter($graph['@graph'], static fn($item) => ($item['@type'] ?? '') === 'Organization'))[0] ?? [];

$check(($organization['@id'] ?? '') === 'https://www.boblift.com/#organization' && ($organization['url'] ?? '') === 'https://www.boblift.com/', 'the organisation is not the group-site entity the crane site uses');
$check(($organization['name'] ?? '') === 'BOB-LIFT' && in_array('BOBLIFT', (array) ($organization['alternateName'] ?? []), true), 'brand name: ' . json_encode([$organization['name'] ?? null, $organization['alternateName'] ?? null]));
$logo = $organization['logo'] ?? [];
$check(($logo['width'] ?? 0) >= 112 && ($logo['height'] ?? 0) >= 112 && str_ends_with(strtok((string) ($logo['url'] ?? ''), '?'), 'images/logo-square-600.png') && is_file(__DIR__ . '/../assets/images/logo-square-600.png'), 'the logo is not the square one Google can use: ' . json_encode($logo));
$size = getimagesize(__DIR__ . '/../assets/images/logo-square-600.png');
$check(is_array($size) && $size[0] === 600 && $size[1] === 600, 'the square logo file is not 600 x 600');
$check(($organization['identifier']['value'] ?? '') === '91320300078204097G', 'the unified social credit code is missing');
$sameAs = (array) ($organization['sameAs'] ?? []);
$check(!in_array('https://www.boblift.com/', $sameAs, true), 'sameAs repeats the organisation url');
$check(in_array('https://bobliftloader.com/', $sameAs, true) && in_array('https://www.youtube.com/@bobliftloader', $sameAs, true), 'sameAs lost this site or its own profiles');
$crane = ['https://www.bobliftcrane.com/', 'https://www.youtube.com/@bobliftcrane', 'https://www.facebook.com/bobliftconstruction', 'https://boblift.en.made-in-china.com/'];
if ($empty) {
    $check(!array_intersect($crane, $sameAs), 'the crane line stays in sameAs with no crane website address: ' . implode(', ', array_intersect($crane, $sameAs)));
    $check(!str_contains((string) ($organization['description'] ?? ''), 'crane'), 'the description still names cranes with no crane website address');
} else {
    $check(count(array_intersect($crane, $sameAs)) === count($crane), 'sameAs misses the crane line: ' . implode(', ', array_diff($crane, $sameAs)));
}
$check(count($sameAs) === count(array_unique($sameAs)), 'sameAs lists a profile twice');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "organization entity ok" . ($empty ? ' (no crane website)' : '') . "\n";
