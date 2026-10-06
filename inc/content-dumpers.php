<?php
/** Defaults retain the original frontend strings; sections feed the shared content editor. */
return [
    'defaults' => [
        'seo' => [
            'title' => 'Mini Dumpers | 0.5–5 Ton Range',
            'description' => 'Browse BOB-LIFT mini dumpers rated from 0.5 to 5 tonnes, including tracked and mixer models. View model specifications, bucket capacities and enquiry options.',
            'image' => '',
        ],
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
