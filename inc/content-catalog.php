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
        // The range film opens from a second button beside the catalogue download; with no address saved the button is not shown.
        'hero_film' => ['宣传片按钮', 'Watch the range film', '栏目首屏', 'text'],
        'film_video' => ['系列宣传片：视频地址', '', '栏目首屏', 'url', '可选。点击首屏「观看宣传片」按钮后弹窗播放的横版 16:9 视频（建议 1920×1080、约 2.5 Mbps、单文件 20 MB 以内的 MP4；超过 20 MB 会被上传网关拒绝）；留空则不显示按钮。'],
        'film_poster' => ['系列宣传片：封面图', '', '栏目首屏', 'url', '弹窗里开始播放前显示的图片地址，建议用视频的首帧。'],
        'film_caption' => ['系列宣传片：说明文字', '', '栏目首屏', 'text'],
        'card_label' => ['系列名称', $loader ? 'Mini skid steer loaders' : 'Mini dumpers', '系列卡片', 'text'],
        'card_description' => ['系列说明', $loader ? 'Rated {load_range} · from {min_width} mm wide' : 'Rated {load_range} · tracked and wheeled', '系列卡片', 'text'],
        'card_models' => ['型号数量单位', 'models', '系列卡片', 'text'],
        'all' => ['全部选项', 'All', '筛选与空状态', 'text'],
        'clear' => ['清空全部', 'Clear all', '筛选与空状态', 'text'],
        'apply' => ['应用筛选', 'Apply filters', '筛选与空状态', 'text'],
        'empty_title' => ['空状态标题', 'No matching equipment.', '筛选与空状态', 'text'],
        'empty_intro' => ['空状态说明', 'Try another model name, or remove a filter.', '筛选与空状态', 'text'],
        'empty_clear' => ['空状态清空按钮', 'Clear filters', '筛选与空状态', 'text'],
        'guides_label' => ['相关文章眉题', 'GUIDES AND NEWS', '相关文章', 'text'],
        'guides_title' => ['相关文章标题', 'READ BEFORE YOU BUY.', '相关文章', 'text'],
        'guides_link' => ['新闻列表链接', 'All news and guides', '相关文章', 'text'],
    ];
}
