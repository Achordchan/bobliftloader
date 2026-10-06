<?php
/** Header words, the site switcher and their screen; the menu's order and addresses stay in templates/header.php. */
return [
    'defaults' => [
        'header' => [
            // Fixed items in a fixed order (the products panel and its styles hang off the first); only the words change.
            'labels' => ['equipment' => 'Equipment', 'applications' => 'Applications', 'support' => 'Support', 'about' => 'Our company', 'news' => 'News'],
            'products' => ['loader' => 'Mini skid steer loaders', 'dumper' => 'Mini dumpers'],
            'contact_label' => 'Let’s talk', 'contact_url' => '/contact/',
        ],
        'switcher' => [
            'label' => 'LOADER', 'title' => 'BOB-LIFT websites', 'current' => 'You are here',
            'sites' => [
                ['url' => 'https://www.boblift.com/', 'domain' => 'boblift.com', 'name' => 'BOB-LIFT', 'text' => 'The company, its factory and the full range', 'mark' => 'B'],
                ['url' => 'https://www.bobliftcrane.com/', 'domain' => 'bobliftcrane.com', 'name' => 'Cranes', 'text' => 'Truck-mounted, marine and pickup cranes', 'mark' => 'C'],
                ['url' => '', 'domain' => 'bobliftloader.com', 'name' => 'Loader', 'text' => 'Mini skid steer loaders and mini dumpers', 'mark' => 'L'],
            ],
        ],
    ],
    'sections' => [
        ['title' => '页眉菜单', 'fields' => [
            ['key' => 'header.labels.equipment', 'label' => '产品（带下拉面板）', 'type' => 'text', 'help' => '菜单顺序和链接固定，这里只改显示的文字。'],
            ['key' => 'header.products.loader', 'label' => '下拉面板 · 滑移装载机', 'type' => 'text'],
            ['key' => 'header.products.dumper', 'label' => '下拉面板 · 迷你翻斗车', 'type' => 'text'],
            ['key' => 'header.labels.applications', 'label' => '应用场景', 'type' => 'text'],
            ['key' => 'header.labels.support', 'label' => '采购与售后', 'type' => 'text'],
            ['key' => 'header.labels.about', 'label' => '关于我们', 'type' => 'text'],
            ['key' => 'header.labels.news', 'label' => '新闻', 'type' => 'text'],
            ['key' => 'header.contact_label', 'label' => '联系按钮名称', 'type' => 'text'],
            ['key' => 'header.contact_url', 'label' => '联系按钮地址', 'type' => 'url'],
        ]],
        ['title' => '站点切换器', 'fields' => [
            ['key' => 'switcher.label', 'label' => '按钮名称', 'type' => 'text'],
            ['key' => 'switcher.title', 'label' => '面板标题', 'type' => 'text'],
            ['key' => 'switcher.current', 'label' => '当前站点提示', 'type' => 'text'],
            ['key' => 'switcher.sites', 'label' => '站点列表', 'type' => 'list', 'add' => '添加站点', 'summary' => ['name', 'domain'], 'fields' => [
                'name' => ['label' => '名称', 'type' => 'text'], 'text' => ['label' => '介绍', 'type' => 'textarea'],
                'url' => ['label' => '地址', 'type' => 'url', 'help' => '留空表示当前站点。'],
                'domain' => ['label' => '显示域名', 'type' => 'text', 'help' => '地址留空表示当前站点，域名统一读取公司信息中的本站域名。'], 'mark' => ['label' => '简称', 'type' => 'text'],
            ]],
        ]],
    ],
];
