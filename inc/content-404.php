<?php
/** Defaults retain the original frontend strings; sections feed the shared content editor. */
return [
    'defaults' => [
        'content' => [
            'eyebrow' => '404 / OFF THE BEATEN TRACK',
            'line1' => 'LET’S GET YOU',
            'line2' => 'BACK TO WORK.',
            'text' => 'We couldn’t find that page. Explore the equipment range or return to the homepage.',
            'equipment_url' => '/products/skid-steer-loaders/',
            'home_url' => '/',
            'equipment_label' => 'Explore equipment ',
            'home_label' => 'Back to home ',
        ],
    ],
    'sections' => [
        [
            'title' => '页面文案',
            'fields' => [
                ['key' => 'content.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'content.line1', 'label' => '标题第一行', 'type' => 'text'],
                ['key' => 'content.line2', 'label' => '标题第二行', 'type' => 'text'],
                ['key' => 'content.text', 'label' => '页面说明', 'type' => 'text'],
                ['key' => 'content.equipment_url', 'label' => '产品链接', 'type' => 'url'],
                ['key' => 'content.home_url', 'label' => '首页链接', 'type' => 'url'],
                ['key' => 'content.equipment_label', 'label' => '产品按钮文字', 'type' => 'verbatim'],
                ['key' => 'content.home_label', 'label' => '首页按钮文字', 'type' => 'verbatim'],
            ],
        ],
    ],
];
