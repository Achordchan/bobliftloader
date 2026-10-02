<?php
/**
 * Model names in the dumper jobs resolve whichever way a model was renamed. Run: php tests/test_dumper_models.php
 */
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';

$failures = 0;
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) { $failures++; fwrite(STDERR, "FAIL: $message\n"); } };
$dumper = static fn(string $model): array => ['model' => $model, 'category' => 'mini-dumpers', 'slug' => strtolower($model)];

// Records renamed to the brochure names; the editor still writes a former name.
$index = bl_dumper_model_index([$dumper('FY30'), $dumper('FJZ30'), ['model' => 'BL-T360-H', 'category' => 'skid-steer-loaders', 'slug' => 'loader']]);
$check(($index['fy-30']['model'] ?? '') === 'FY30', 'former name FY-30 does not reach the renamed FY30');
$check(($index['fy30']['model'] ?? '') === 'FY30', 'current name FY30 does not reach FY30');
$check(($index['fjz-30']['model'] ?? '') === 'FJZ30', 'former name FJZ-30 does not reach FJZ30');
$check(!isset($index['bl-t360-h']), 'a loader is offered as a dumper');
$check(!isset($index['fy-40']), 'a model that is not published resolves');

// Records still on the former names; the editor writes the current name.
$index = bl_dumper_model_index([$dumper('FY-30'), $dumper('500 Tracked')]);
$check(($index['fy30']['model'] ?? '') === 'FY-30', 'current name FY30 does not reach a record still named FY-30');
$check(($index['bl-t500-hd']['model'] ?? '') === '500 Tracked', 'BL-T500-HD does not reach a record still named 500 Tracked');

// A model's own name is never taken over by another model's former name.
$index = bl_dumper_model_index([$dumper('FY-20'), $dumper('FY20-4')]);
$check(($index['fy-20']['model'] ?? '') === 'FY-20', 'an exact model name lost to an alias');

// A job's headline figure covers only the named machines of its own family; another family's names fall back to the whole family.
$landscaping = ['product' => 'loader', 'focus' => 'width'];
$check(bl_application_focus($landscaping + ['models' => ['FC10']]) === bl_application_focus($landscaping), 'a dumper named on a loader job changes its headline');
$check(bl_application_focus(['product' => 'dumper', 'focus' => 'width', 'models' => ['BL-T500-HD', 'FC10']])['value'] === '880', 'the named tracked dumper is not the narrowest');

if ($failures) exit(1);
echo "OK\n";
