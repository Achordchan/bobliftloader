<?php
declare(strict_types=1);
// php tests/test_spec_columns.php — spec groups share out into columns that end at about the same height.
require __DIR__ . '/../inc/bootstrap.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$rows = static fn(int $count, string $prefix) => array_combine(array_map(static fn($i) => "$prefix $i", range(1, $count)), array_fill(0, $count, '1 kg'));

// A dumper (6, 4, 4 and 2 rows): two columns of the same height instead of four ragged ones.
$dumper = ['Performance' => $rows(6, 'p'), 'Engine' => $rows(4, 'e'), 'Dimensions' => $rows(4, 'd'), 'Build' => $rows(2, 'b')];
$check(bl_spec_columns($dumper) === [['Performance', 'Build'], ['Engine', 'Dimensions']], 'dumper groups not paired into two even columns');
// Groups that already match keep a column each.
$even = ['Performance' => $rows(5, 'p'), 'Engine' => $rows(5, 'e'), 'Dimensions' => $rows(5, 'd'), 'Build' => $rows(5, 'b')];
$check(bl_spec_columns($even) === [['Performance'], ['Engine'], ['Dimensions'], ['Build']], 'even groups were stacked');
// Beside a drawing there are at most two columns; one group stays one column.
$check(count(bl_spec_columns($even, 2)) === 2, 'more than two columns beside a drawing');
$check(bl_spec_columns(['Details' => $rows(3, 'x')]) === [['Details']], 'a single group is not one column');
// Every group appears once, the first group leads the first column.
$columns = bl_spec_columns(['A' => $rows(7, 'a'), 'B' => $rows(6, 'b'), 'C' => $rows(6, 'c'), 'D' => $rows(2, 'd'), 'E' => $rows(1, 'e')]);
$flat = array_merge(...$columns);
sort($flat);
$check($flat === ['A', 'B', 'C', 'D', 'E'] && $columns[0][0] === 'A', 'groups lost, repeated or out of order');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "OK\n";
