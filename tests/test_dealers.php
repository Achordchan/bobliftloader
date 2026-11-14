<?php
/** php tests/test_dealers.php — the product hero's figures and the dealer page's terms come from the site's own data. */
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
$fail = static function (string $what): void { fwrite(STDERR, $what . "\n"); exit(1); };

// Hero figures: up to six, in the family's order, metric with imperial below; a missing or wordy value leaves no tile.
$loader = ['category' => 'skid-steer-loaders', 'specs' => ['Rated load' => '300 kg', 'Machine weight' => '730 kg', 'Engine power' => '16.8 kW', 'Bucket capacity' => '0.15 m3', 'Hydraulic pressure' => '17 MPa', 'Max travel speed' => '6 km/h', 'Machine width' => 'See drawing']];
$figures = bl_product_hero_figures($loader);
if (array_column($figures, 'label') !== ['Rated load', 'Machine weight', 'Engine power', 'Bucket capacity', 'Hydraulic pressure', 'Travel speed']) $fail('loader figures ' . json_encode(array_column($figures, 'label')));
$bucket = $figures[3];
if ($bucket['value'] !== '0.15 m3' || $bucket['imperial'] !== '5.3 ft³') $fail('ASCII cubic metres ' . json_encode($bucket, JSON_UNESCAPED_UNICODE));
if ($figures[0]['imperial'] !== '661 lb' || $figures[2]['imperial'] !== '22.5 hp' || $figures[4]['imperial'] !== '2,466 psi') $fail('imperial conversions');
$dumper = ['category' => 'mini-dumpers', 'specs' => ['Rated load' => '500 kg', 'Standard dimensions' => '1,950 × 880 × 1,200 mm', 'Hopper capacity' => '0.2 m³']];
if (array_column(bl_product_hero_figures($dumper), 'value') !== ['500 kg', '0.2 m³', '880 mm']) $fail('dumper figures or width from dimensions');
if (bl_product_hero_figures(['category' => 'mini-dumpers', 'specs' => []]) !== []) $fail('figures without specs');
$legacy = bl_product_hero_figures(['category' => 'mini-dumpers', 'specs' => ['Rated load' => '1000 kg', 'Bucket capacity' => '0.5 m³']]);
if (array_column($legacy, 'label') !== ['Rated load', 'Hopper capacity'] || $legacy[1]['imperial'] !== '0.7 yd³') $fail('legacy dumper bucket capacity ' . json_encode($legacy, JSON_UNESCAPED_UNICODE));

// The dealer page states the minimum order from the Support terms, never a fixed "one machine".
$tokens = bl_dealer_tokens();
foreach (bl_business_variables(bl_editable('support')['terms']) as $token => $value) if (($tokens[$token] ?? null) !== $value) $fail('dealer token ' . $token);
$copy = serialize(bl_content_defaults('dealers'));
if (preg_match('/\bone machine\b|\bsingle machine\b/i', $copy)) $fail('dealer copy fixes the minimum order');
$minimum = array_values(array_filter(bl_dealer_faq(), static fn(array $item): bool => $item['q'] === 'What is the minimum order?'))[0]['a'] ?? '';
if (!str_starts_with($minimum, $tokens['{minimum_order_sentence}'] . ' ' . $tokens['{minimum_order_noun}'] . '.') || str_contains($minimum, '{')) $fail('minimum order answer: ' . $minimum);
echo "dealers: ok\n";
