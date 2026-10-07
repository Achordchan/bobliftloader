<?php
/** Defaults retain the original frontend strings; sections feed the shared content editor. */
return [
    'defaults' => [
        'hero' => [
            'title' => 'News',
            'intro' => 'Product news, customer stories and industry news from the BOB-LIFT Loader team.',
            'empty_title' => 'No stories yet.',
            'empty_text' => 'News from the BOB-LIFT Loader team will appear here.',
        ],
        'seo' => ['title' => 'BOB-LIFT News & Company Updates', 'description' => 'Read company and exhibition updates from BOB-LIFT, with links to original announcements on the main BOB-LIFT website.', 'image' => ''],
    ],
    'sections' => [
        [
            'title' => '页面文案',
            'fields' => [
                ['key' => 'hero.title', 'label' => '页面标题（选了栏目时显示栏目名）', 'type' => 'text'],
                ['key' => 'hero.intro', 'label' => '标题旁说明', 'type' => 'textarea'],
                ['key' => 'hero.empty_title', 'label' => '没有新闻时的标题', 'type' => 'text'],
                ['key' => 'hero.empty_text', 'label' => '没有新闻时的说明', 'type' => 'text'],
            ],
        ],
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
