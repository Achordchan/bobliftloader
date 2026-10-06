<?php
/** Defaults retain the original frontend strings; sections feed the shared content editor. */
return [
    'defaults' => [
        'seo' => ['title' => 'Skid Steer Loaders', 'description' => 'Compare BOB-LIFT mini skid steer loaders rated from 200 to 500 kg: stand-on, tracked and wheeled seated models with published specifications.', 'image' => ''],
    ],
    'sections' => [
        [
            'title' => '搜索结果显示',
            'fields' => [
                ['key' => 'seo.title', 'label' => '搜索标题', 'type' => 'text'],
                ['key' => 'seo.description', 'label' => '搜索描述', 'type' => 'textarea'],
                ['key' => 'seo.image', 'label' => '分享图片', 'type' => 'image', 'preview' => 'images/share-default.jpg'],
            ],
        ],
    ],
];
