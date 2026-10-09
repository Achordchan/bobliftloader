<?php
declare(strict_types=1);

/** Field definitions keep the former template copy and its editor label together. */
function bl_catalog_copy_fields(bool $loader): array
{
    return [
        'hero_eyebrow' => ['眉题', 'THE BOB-LIFT EQUIPMENT RANGE', '栏目首屏', 'text'],
        'hero_title' => ['标题', $loader ? 'COMPACT SIZE.<br>MORE CAPABILITY.' : 'KEEP MATERIALS<br>MOVING.', '栏目首屏', 'html'],
        'hero_intro' => ['介绍', $loader ? 'Start with the work ahead. Explore our compact equipment, then get into the details.' : 'Published capacities. Real models. Find the mini dumper that fits your material transport requirements.', '栏目首屏', 'textarea'],
        'hero_download' => ['下载画册按钮', $loader ? 'Download the loader catalogue' : 'Download the dumper catalogue', '栏目首屏', 'text'],
        'card_label' => ['系列名称', $loader ? 'Mini skid steer loaders' : 'Mini dumpers', '系列卡片', 'text'],
        'card_description' => ['系列说明', $loader ? 'Rated {load_range} · from {min_width} mm wide' : 'Rated {load_range} · tracked and wheeled', '系列卡片', 'text'],
        'card_models' => ['型号数量单位', 'models', '系列卡片', 'text'],
        'all' => ['全部选项', 'All', '筛选与空状态', 'text'],
        'clear' => ['清空全部', 'Clear all', '筛选与空状态', 'text'],
        'apply' => ['应用筛选', 'Apply filters', '筛选与空状态', 'text'],
        'empty_title' => ['空状态标题', 'No matching equipment.', '筛选与空状态', 'text'],
        'empty_intro' => ['空状态说明', 'Try another model name, or remove a filter.', '筛选与空状态', 'text'],
        'empty_clear' => ['空状态清空按钮', 'Clear filters', '筛选与空状态', 'text'],
    ];
}
