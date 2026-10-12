<?php
declare(strict_types=1);
require_once __DIR__ . '/content-product-copy.php';
require_once __DIR__ . '/content-catalog.php';
require_once __DIR__ . '/content-loader-story.php';

/** Add fields to the existing family options; field paths and frontend machine identifiers stay independent. */
function bl_family_content_definition(bool $loader): array
{
    $defaults = []; $sections = []; $productFields = [];
    foreach (['catalog' => bl_catalog_copy_fields($loader), 'product.copy' => bl_product_copy_fields($loader)] as $prefix => $definitions) {
        foreach ($definitions as $key => $definition) {
            [$label, $value, $group, $type] = $definition;
            $path = $prefix . '.' . $key;
            $parts = explode('.', $path); $ref = &$defaults;
            foreach ($parts as $part) $ref = &$ref[$part];
            $ref = $value; unset($ref);
            $field = ['key' => $path, 'label' => $label, 'type' => $type === 'text' && strlen($value) > 100 ? 'textarea' : $type];
            if (str_contains($value, '{')) $field['help'] = '保留花括号变量：{model} 型号、{name} 产品名称、{warranty_months} 质保月数、{lead_time} 交期。其他变量按默认文案保留，由当前产品数据计算。';
            if (!empty($definition[4])) $field['help'] = $definition[4];
            if ($type === 'html') $field['help'] = '保留 <br> 换行及现有强调文字标签；只修改标签之间的文字。';
            if ($prefix === 'catalog') $sections[$group]['fields'][] = $field;
            else $productFields[] = $field + ['group' => $group];
        }
    }
    $gear = ['label' => 'Running gear', 'options' => ['tracked' => 'Tracked', 'wheeled' => 'Wheeled']];
    $defaults['catalog_filters'] = $loader ? [
        'gear' => $gear,
        'operator' => ['label' => 'Operator', 'options' => ['stand-on' => 'Stand-on', 'seated' => 'Seated']],
        'load' => ['label' => 'Rated load', 'options' => ['light' => 'Up to 250 kg', 'mid' => '251–400 kg', 'heavy' => 'Over 400 kg']],
    ] : [
        'load' => ['label' => 'Rated load', 'options' => ['light' => 'Up to 1 t', 'mid' => '1–2 t', 'heavy' => 'Over 2 t']],
        'gear' => $gear,
    ];
    $filterFields = [];
    foreach ($defaults['catalog_filters'] as $key => $filter) {
        $label = ['gear' => '行走机构', 'operator' => '驾驶位置', 'load' => '额定载荷'][$key];
        $filterFields[] = ['key' => 'catalog_filters.' . $key . '.label', 'label' => $label . '分组名称', 'type' => 'text'];
        foreach ($filter['options'] as $value => $text) $filterFields[] = ['key' => 'catalog_filters.' . $key . '.options.' . $value, 'label' => $label . '选项：' . $text, 'type' => 'text'];
    }
    $sections['筛选与空状态']['fields'] = [...$filterFields, ...$sections['筛选与空状态']['fields']];
    foreach ($sections as $title => &$section) $section['title'] = $title;
    unset($section);
    $sections = array_values($sections);
    if ($loader) {
        $story = bl_loader_story_definition();
        $defaults = array_replace_recursive($defaults, $story['defaults']);
        $sections = [...$sections, ...$story['sections']];
    }
    return ['defaults' => $defaults, 'sections' => $sections, 'product_fields' => $productFields];
}

/** Variables are expanded at read time only; stored templates retain references to the unified purchase terms. */
function bl_family_copy_variables(array $variables): array
{
    $result = bl_business_variables(bl_editable('support')['terms']);
    foreach ($variables as $key => $value) $result['{' . $key . '}'] = (string) $value;
    return $result;
}

function bl_family_copy_value(string $page, string $path, array $variables = []): string
{
    return strtr(bl_content_value($page, $path), bl_family_copy_variables($variables));
}

/** Defaults keep literal entity spelling; authored HTML passes WordPress filtering and variables are always escaped. */
function bl_family_copy(string $page, string $path, array $variables = []): string
{
    $htmlFields = str_starts_with($path, 'product.copy.') ? bl_product_copy_fields($page === 'loaders') : bl_catalog_copy_fields($page === 'loaders');
    $key = substr($path, strrpos($path, '.') + 1);
    $html = ($htmlFields[$key][3] ?? '') === 'html';
    $text = $html ? bl_content_html($page, $path) : bl_content_text($page, $path);
    return strtr($text, array_map('bl_escape', bl_family_copy_variables($variables)));
}
