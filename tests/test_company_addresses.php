<?php
declare(strict_types=1);
// php tests/test_company_addresses.php — the registered address, the main factory and the office are three addresses of one company.
require __DIR__ . '/../inc/bootstrap.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

$check(bl_company_full_address('street') === 'No. 79, Zone B, Jingluotuo Industrial Park, Yunlong District, Xuzhou, Jiangsu, China', 'registered address: ' . bl_company_full_address('street'));
$check(bl_company_full_address('factory_street') === 'S324 Provincial Road, Zhangji Town, Tongshan District, Xuzhou, Jiangsu, China', 'main factory address: ' . bl_company_full_address('factory_street'));
$check(bl_company_full_address('office_street') === '13F, Building A, Yongjia New Town Center, Yunlong District, Xuzhou, Jiangsu, China', 'office address: ' . bl_company_full_address('office_street'));
// The contact page puts the street on the first line and the district onward on the second.
$check(bl_company_address_part(0, 'factory_street') === 'S324 Provincial Road, Zhangji Town,' && bl_company_address_part(1, 'factory_street') === 'Tongshan District, Xuzhou, Jiangsu, China', 'factory address lines');
$check(bl_company_address_part(0) === 'No. 79, Zone B, Jingluotuo Industrial Park,' && bl_company_address_part(1) === 'Yunlong District, Xuzhou, Jiangsu, China', 'registered address lines changed');

$llms = bl_llms_txt();
foreach (['- Registered address: No. 79, Zone B, Jingluotuo Industrial Park', '- Main factory: S324 Provincial Road, Zhangji Town, Tongshan District, Xuzhou, Jiangsu, China', '- Office: 13F, Building A, Yongjia New Town Center'] as $line) $check(str_contains($llms, $line), 'llms.txt misses ' . $line);

foreach (['- YouTube: https://www.youtube.com/@bobliftloader', '- Facebook: https://facebook.com/122103113139506578'] as $line) $check(str_contains($llms, $line), 'llms.txt misses ' . $line);

$graph = bl_structured_data(['type' => 'contact', 'path' => '/contact/', 'title' => 'Contact', 'description' => 'Contact', 'record' => null, 'status' => 200]);
$organization = array_values(array_filter($graph['@graph'] ?? $graph, static fn($item) => ($item['@type'] ?? '') === 'Organization'))[0] ?? [];
$check(($organization['address']['streetAddress'] ?? '') === 'No. 79, Zone B, Jingluotuo Industrial Park, Yunlong District', 'the organisation address is no longer the registered address');
$places = array_column($organization['location'] ?? [], 'address', 'name');
$check(($places['Main factory']['streetAddress'] ?? '') === 'S324 Provincial Road, Zhangji Town, Tongshan District' && ($places['Office']['streetAddress'] ?? '') === '13F, Building A, Yongjia New Town Center, Yunlong District', 'factory and office are not listed as places');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "company addresses ok\n";
