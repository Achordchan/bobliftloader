<?php
declare(strict_types=1);

/** English keys consumed by helpers, range charts, specification groups and both comparison tables. */
function bl_spec_choices(): array
{
    return [
        'Rated load' => '额定载荷',
        'Lifting capacity' => '举升能力',
        'Breakout force' => '最大掘起力',
        'Bucket capacity' => '铲斗容量',
        'Hopper capacity' => '料斗容量',
        'Drum capacity' => '搅拌筒容量',
        'Travel speed' => '行驶速度',
        'Max travel speed' => '最高行驶速度',
        'Hydraulic pressure' => '液压压力',
        'Hydraulic pumps' => '液压泵',
        'Gradeability' => '爬坡能力',
        'Tipping' => '卸料方式',
        'Tipping angle' => '卸料角度',
        'Machine weight' => '整机重量',
        'Engine options' => '发动机选项',
        'Engine model' => '发动机型号',
        'Engine power' => '发动机功率',
        'Engine type' => '发动机类型',
        'Fuel' => '燃料',
        'Fuel tank' => '油箱容积',
        'Starting' => '启动方式',
        'Emission standard' => '排放标准',
        'Operating voltage' => '工作电压',
        'Working height' => '作业高度',
        'Max dumping height' => '最大卸载高度',
        'Height with arms raised' => '大臂升起高度',
        'Length with bucket' => '带斗长度',
        'Length without bucket' => '不带斗长度',
        'Machine width' => '整机宽度',
        'Bucket width' => '铲斗宽度',
        'Overall height' => '整机高度',
        'Standard dimensions' => '标准尺寸',
        'Length with footplate' => '含踏板长度',
        'Dimensions with roof / cab' => '带顶棚或驾驶室尺寸',
        'Hopper size' => '料斗尺寸',
        'Drum length' => '搅拌筒长度',
        'Drum opening diameter' => '搅拌筒开口直径',
        'Loading bucket length' => '上料斗长度',
        'Loading bucket opening width' => '上料斗开口宽度',
        'Wheelbase' => '轴距',
        'Minimum ground clearance' => '最小离地间隙',
        'Ground clearance' => '离地间隙',
        'Running gear' => '行走机构',
        'Drive' => '驱动方式',
        'Brakes' => '制动方式',
        'Operator station' => '驾驶位置',
        'Controls' => '操纵方式',
        'Travel control' => '行走操纵',
        'Body material' => '车身材料',
        'Bucket material' => '铲斗材料',
    ];
}

/**
 * Specs as sent over REST: the stored object (name => value), or an ordered list of {"key", "value"} rows for clients that
 * reorder object keys (the WordPress.com connector sorts them alphabetically). A list becomes the same object in the
 * list's order, so the table keeps the order it was written in; rows with an empty value are dropped, as on the edit
 * screen. Returns [specs, error]; a malformed row or a name given twice is an error and nothing is converted.
 */
function bl_specs_normalize($value): array
{
    if (!is_array($value)) return [null, '规格参数必须为英文参数名到显示值的对象，或按顺序排列的 {"key","value"} 列表。'];
    if (!$value || !array_is_list($value)) return [$value, ''];
    $specs = [];
    foreach ($value as $i => $row) {
        if (!is_array($row) || !array_key_exists('key', $row) || !is_string($row['key']) || !array_key_exists('value', $row) || (!is_scalar($row['value']) && $row['value'] !== null)) {
            return [null, '规格列表第 ' . ($i + 1) . ' 项格式不对，每项须为 {"key": 英文参数名, "value": 显示值}。'];
        }
        $key = trim($row['key']);
        $item = $row['value'] === null ? '' : trim((string) $row['value']);
        if ($key === '' && $item === '') continue;
        if ($key !== '' && array_key_exists($key, $specs)) return [null, '参数“' . $key . '”重复，请合并或修改参数名。'];
        if ($item === '' && $key !== '') continue;
        $specs[$key] = $item;
    }
    return [$specs, ''];
}

/** Value formats the frontend reads (filters, charts, comparisons): pattern and an example editors can copy. */
function bl_spec_rules(): array
{
    $integer = '(?:[1-9][0-9]*|[1-9][0-9]{0,2}(?:,[0-9]{3})+)';
    $decimal = '(?:0|[1-9][0-9]*)(?:\.[0-9]+)?';
    return [
        'Rated load' => [$integer . '\s*kg', '1,000 kg（整数，单位 kg）'],
        'Bucket capacity' => [$decimal . '\s*m³', '0.5 m³（数值，单位 m³）'],
        'Engine power' => [$decimal . '\s*kW', '12.1 kW（数值，单位 kW）'],
        'Machine width' => [$integer . '\s*mm', '960 mm（整数，单位 mm）'],
        'Bucket width' => [$integer . '\s*mm', '1,050 mm（整数，单位 mm）'],
        'Overall height' => [$integer . '\s*mm', '1,550 mm（整数，单位 mm）'],
        'Standard dimensions' => [$integer . '\s*×\s*' . $integer . '\s*×\s*' . $integer . '\s*mm', '2050 × 880 × 1300 mm（长 × 宽 × 高，使用乘号 ×）'],
        'Running gear' => ['(?=.*(?:track|tyre|tire|wheel))[^<>]+', 'Rubber tracks / Rubber tyres / Wheeled（需含 track、tyre、tire 或 wheel）'],
        'Operator station' => ['(?=.*(?:stand|seat))[^<>]+', 'Stand-on platform / Seat（需含 stand 或 seat）'],
    ];
}

/**
 * Validate the units the existing readers understand; never reinterpret or rewrite a published value. A row left exactly as
 * it is stored in $stored is not checked, so a product saved before these rules can still be saved without touching it.
 */
function bl_specs_error(array $specs, array $stored = []): string
{
    $rules = bl_spec_rules();
    $seen = [];
    foreach ($specs as $key => $value) {
        $key = function_exists('sanitize_text_field') ? sanitize_text_field((string) $key) : trim(strip_tags((string) $key));
        if ($key === '' && $value !== '') return '有参数值但未填写参数名。';
        if (isset($seen[$key])) return '参数“' . $key . '”重复，请合并或修改参数名。';
        $seen[$key] = true;
        // Numbers sent over REST for a free-form spec are kept as text, as before; only arrays and the like are refused.
        if (!is_scalar($value)) return '规格参数值必须是文字或数值与单位组成的字符串。';
        $value = (string) $value;
        if ($value === '') continue;
        if (array_key_exists($key, $stored) && $stored[$key] === $value) continue;
        foreach (bl_spec_choices() as $known => $_label) {
            if ($key !== $known && strtolower(trim((string) $key)) === strtolower($known)) return '参数名“' . $key . '”需要使用常用参数中的“' . $known . '”，英文大小写必须一致。';
        }
        if (!isset($rules[$key])) continue;
        [$pattern, $example] = $rules[$key];
        if (!preg_match('~^' . $pattern . '$~' . ($key === 'Bucket capacity' ? 'uD' : 'iuD'), $value)
            || ($key === 'Rated load' && (int) str_replace(',', '', $value) > 1000000)
            || (in_array($key, ['Bucket capacity', 'Engine power'], true) && (float) $value <= 0)) {
            return (bl_spec_choices()[$key] ?? $key) . '（' . $key . '）无法识别，请填写 ' . $example . '。本次修改未保存，输入已保留。';
        }
    }
    return '';
}
