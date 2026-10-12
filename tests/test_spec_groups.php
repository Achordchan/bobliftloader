<?php
declare(strict_types=1);
// php tests/test_spec_groups.php — the rows the technical sheets add sit in their own groups, and a dumper's summary calls its capacity a hopper.
require __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/product-specs.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

$specs = [
    'Rated load' => '400 kg', 'Lifting capacity' => '450 kg', 'Breakout force' => '4.5 kN', 'Hydraulic pressure' => '18 MPa', 'Hydraulic pumps' => 'Two piston pumps and one gear pump',
    'Engine power' => '18.6 kW', 'Fuel' => 'Diesel', 'Fuel tank' => '20 L',
    'Working height' => '2,140 mm', 'Max dumping height' => '1,700 mm', 'Height with arms raised' => '2,920 mm', 'Overall height' => '1,550 mm', 'Standard dimensions' => '1,570 × 880 × 1,300 mm', 'Dimensions with footplate' => '2,050 mm', 'Ground clearance' => '240 mm',
    'Controls' => 'Joystick operation', 'Travel control' => 'Spring-return foot pedal',
];
$groups = bl_spec_groups($specs);
$check(!isset($groups['Details']), 'a row from the technical sheet fell under Details: ' . implode(', ', array_keys($groups['Details'] ?? [])));
$check(isset($groups['Performance']['Breakout force'], $groups['Performance']['Hydraulic pumps']), 'breakout force or hydraulic pumps not under Performance');
$check(isset($groups['Engine']['Fuel tank']), 'fuel tank not under Engine');
foreach (['Max dumping height', 'Height with arms raised', 'Dimensions with footplate', 'Ground clearance'] as $label) $check(isset($groups['Dimensions'][$label]), "$label not under Dimensions");
$check(isset($groups['Build']['Travel control']), 'travel control not under Build');
// Reading order inside a group follows the sheet: the heights, then the lengths, the footprint, the clearance.
$check(array_keys($groups['Dimensions']) === ['Working height', 'Max dumping height', 'Height with arms raised', 'Overall height', 'Standard dimensions', 'Dimensions with footplate', 'Ground clearance'], 'dimension rows out of reading order');
$check(array_keys($groups['Engine']) === ['Engine power', 'Fuel', 'Fuel tank'], 'fuel tank does not follow the fuel');

// A dumper's hopper capacity is a performance figure, like the loader's bucket capacity.
$hopper = bl_spec_groups(['Rated load' => '3,000 kg', 'Hopper capacity' => '1.5 m³', 'Engine power' => '37 kW']);
$check(isset($hopper['Performance']['Hopper capacity']) && !isset($hopper['Details']), 'hopper capacity not under Performance');

// Every label that has a group also has an edit-screen name in Chinese.
$choices = bl_spec_choices();
foreach (['Hopper capacity', 'Breakout force', 'Hydraulic pumps', 'Fuel tank', 'Max dumping height', 'Height with arms raised', 'Dimensions with footplate', 'Ground clearance', 'Travel control'] as $label) $check(!empty($choices[$label]), "$label has no edit-screen name");

// A dumper's capacity figure is its hopper, else the older bucket name; an empty value does not hide the other one.
$check(bl_dumper_capacity(['Hopper capacity' => '1.5 m³', 'Bucket capacity' => '0.3 m³']) === '1.5 m³', 'hopper not preferred over a front bucket');
$check(bl_dumper_capacity(['Hopper capacity' => '', 'Bucket capacity' => '0.8 m³']) === '0.8 m³', 'an empty hopper hid the legacy bucket figure');
$check(bl_dumper_capacity(['Bucket capacity' => ' 0.8 m³ ']) === '0.8 m³' && bl_dumper_capacity(['Drum capacity' => '200 L']) === '', 'legacy name or no capacity');

// Comparison rows: legacy "Bucket capacity" on a dumper reads as its hopper; a record that lists both keeps its front bucket apart.
$legacy = bl_normalize_dumper_capacity(['Rated load' => '1,000 kg', 'Bucket capacity' => '0.8 m³', 'Engine power' => '10 kW']);
$check(array_keys($legacy) === ['Rated load', 'Hopper capacity', 'Engine power'] && $legacy['Hopper capacity'] === '0.8 m³', 'legacy bucket capacity not moved to Hopper capacity in place');
$emptyHopper = bl_normalize_dumper_capacity(['Hopper capacity' => '', 'Bucket capacity' => '0.8 m³']);
$check($emptyHopper === ['Hopper capacity' => '0.8 m³'], 'an empty hopper row blocked the legacy figure');
$self = ['Hopper capacity' => '1.5 m³', 'Bucket capacity' => '0.3 m³'];
$check(bl_normalize_dumper_capacity($self) === $self && bl_normalize_dumper_capacity(['Hopper capacity' => '1 m³']) === ['Hopper capacity' => '1 m³'], 'a record with both capacities was changed');
$models = [['specs' => $legacy], ['specs' => ['Hopper capacity' => '1.2 m³']], ['specs' => $self]];
$check(bl_spec_synonyms('Hopper capacity', $models) === ['Hopper capacity'] && bl_spec_value($models[2], 'Bucket capacity', $models) === '0.3 m³' && bl_spec_value($models[0], 'Bucket capacity', $models) === null, 'front bucket mixed into the hopper row: ' . json_encode(bl_spec_synonyms('Hopper capacity', $models)));

// A generated summary: a dumper's capacity is its hopper, a loader's its bucket.
$dumper = ['name' => 'FY30 3 Ton Mini Dumper', 'model' => 'FY30', 'category' => 'mini-dumpers', 'specs' => ['Rated load' => '3,000 kg', 'Bucket capacity' => '1.5 m³', 'Engine power' => '37 kW']];
$loader = ['name' => 'BL-T750-H', 'model' => 'BL-T750-H', 'category' => 'skid-steer-loaders', 'specs' => ['Rated load' => '410 kg', 'Bucket capacity' => '0.15 m³', 'Engine power' => '18.6 kW']];
$check(bl_spec_phrase($dumper) === 'a 3,000 kg rated load, a 1.5 m³ hopper and a 37 kW engine', 'dumper summary: ' . bl_spec_phrase($dumper));
$both = $dumper; $both['specs']['Hopper capacity'] = '1.5 m³'; $both['specs']['Bucket capacity'] = '0.3 m³';
$check(str_contains(bl_spec_phrase($both), 'a 0.3 m³ bucket') && str_contains(bl_spec_phrase($both), 'a 1.5 m³ hopper'), 'a record with both capacities lost its front bucket: ' . bl_spec_phrase($both));
$check(bl_spec_phrase($loader) === 'a 410 kg rated load, a 0.15 m³ bucket and an 18.6 kW engine', 'loader summary: ' . bl_spec_phrase($loader));
// The summary stored with the old wording is still recognised as generated, so it is rebuilt rather than shown.
$check(bl_is_generated_product_excerpt('FY30 3 Ton Mini Dumper with a 3,000 kg rated load, a 1.5 m³ bucket and a 37 kW engine. Explore published specifications and discuss your application with BOB-LIFT.', ['FY30 3 Ton Mini Dumper']), 'old generated wording no longer recognised');
$check(bl_is_generated_product_excerpt(bl_product_excerpt($dumper), ['FY30 3 Ton Mini Dumper']), 'new generated wording not recognised');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "spec groups ok\n";
