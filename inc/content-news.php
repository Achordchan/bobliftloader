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
        // A photo per author for the author card on each story; an author without one keeps their Gravatar.
        'authors' => ['items' => []],
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
            'title' => '文章作者头像',
            'note' => '显示在每篇文章右侧作者卡里。没有在这里设置头像的作者，继续使用其 WordPress.com 账号的 Gravatar。',
            'fields' => [
                ['key' => 'authors.items', 'label' => '作者', 'type' => 'list', 'add' => '添加作者', 'summary' => ['login'], 'fields' => [
                    'login' => ['label' => 'WordPress 用户名', 'type' => 'text', 'help' => '登录用的用户名（不是显示名），例如 achordchan。'],
                    'image' => ['label' => '头像', 'type' => 'image', 'help' => '真人正方形照片，至少 128×128 像素，前台按 64 像素圆形显示。'],
                ]],
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
