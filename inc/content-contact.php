<?php
/** Defaults retain the original frontend strings; sections feed the shared content editor. */
return [
    'defaults' => [
        'hero' => [
            'eyebrow' => 'A DIRECT LINE TO BOB-LIFT',
            'line1' => 'LET’S BUILD',
            'line2' => 'WHAT’S NEXT.',
            'text' => 'Equipment questions. Project requirements. Business partnerships. Start a conversation with our team.',
            'image_alt' => 'Finished skid steer loaders at the BOB-LIFT factory in Xuzhou',
            'breadcrumb' => 'Contact',
        ],
        'channels' => ['whatsapp' => 'WhatsApp · photos and quick questions', 'email' => 'Email · specifications and quotations', 'youtube' => 'Videos · our machines at work', 'facebook' => 'Company news and updates'],
        'form' => ['prepare_title' => 'WHAT HELPS US REPLY WELL', 'eyebrow' => 'TELL US ABOUT YOUR PROJECT', 'line1' => 'THE RIGHT CONVERSATION', 'line2' => 'STARTS WITH YOU.'],
        'visit' => [
            'eyebrow' => 'MEET US IN XUZHOU',
            'line1' => 'COME SEE',
            'line2' => 'WHERE IT STARTS.',
            'caption' => 'BOB-LIFT · XUZHOU, CHINA',
            'caption_detail' => 'Inside our skid steer loader workshop',
            'plan_title' => 'PLAN YOUR VISIT',
            'plan_line1' => 'A conversation.',
            'plan_line2' => 'A closer look.',
            'text' => 'Contact our team with your preferred dates and the equipment you would like to discuss. Please confirm your appointment and arrival details before travelling.',
            'address_label' => 'COMPANY &amp; ADDRESS',
            'registered_label' => 'Registered address',
            'factory_label' => 'Main factory',
            'office_label' => 'Office',
            'hours_label' => 'OFFICE HOURS',
            'image_small' => '',
            'image' => '',
            'button' => 'Arrange a visit ',
            'map_label' => 'View address on map ',
        ],
        'business' => ['title' => 'BUSINESS COOPERATION', 'text' => 'For distribution and business partnerships'],
        'mail' => ['subject' => 'Factory visit enquiry', 'body' => 'Preferred visit dates:
Number of visitors:
Equipment of interest:
'],
        'seo' => ['title' => 'Contact BOB-LIFT Loader', 'description' => 'Contact BOB-LIFT for skid steer loaders, mini dumpers, specifications and quotations. Reach our Xuzhou team by email, phone or WhatsApp.', 'image' => ''],
    ],
    'sections' => [
        [
            'title' => '首屏',
            'fields' => [
                ['key' => 'hero.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'hero.line1', 'label' => '标题第一行', 'type' => 'text'],
                ['key' => 'hero.line2', 'label' => '标题第二行', 'type' => 'text'],
                ['key' => 'hero.text', 'label' => '联系说明', 'type' => 'textarea'],
                ['key' => 'hero.image_alt', 'label' => '图片替代文字 1', 'type' => 'text'],
                ['key' => 'hero.breadcrumb', 'label' => '面包屑名称', 'type' => 'text'],
            ],
        ],
        [
            'title' => '联系渠道',
            'fields' => [
                ['key' => 'channels.whatsapp', 'label' => 'WhatsApp 说明', 'type' => 'text'],
                ['key' => 'channels.email', 'label' => '邮件说明', 'type' => 'text'],
                ['key' => 'channels.youtube', 'label' => 'YouTube 说明', 'type' => 'text'],
                ['key' => 'channels.facebook', 'label' => 'Facebook 说明', 'type' => 'text'],
            ],
        ],
        [
            'title' => '询盘区',
            'fields' => [
                ['key' => 'form.prepare_title', 'label' => '询价准备标题', 'type' => 'text'],
                ['key' => 'form.eyebrow', 'label' => '表单小标题', 'type' => 'text'],
                ['key' => 'form.line1', 'label' => '表单标题第一行', 'type' => 'text'],
                ['key' => 'form.line2', 'label' => '表单标题第二行', 'type' => 'text'],
            ],
        ],
        [
            'title' => '工厂拜访',
            'fields' => [
                ['key' => 'visit.eyebrow', 'label' => '拜访小标题', 'type' => 'text'],
                ['key' => 'visit.line1', 'label' => '拜访标题第一行', 'type' => 'text'],
                ['key' => 'visit.line2', 'label' => '拜访标题第二行', 'type' => 'text'],
                ['key' => 'visit.caption', 'label' => '照片主说明', 'type' => 'text'],
                ['key' => 'visit.caption_detail', 'label' => '照片附加说明', 'type' => 'text'],
                ['key' => 'visit.plan_title', 'label' => '行程小标题', 'type' => 'text'],
                ['key' => 'visit.plan_line1', 'label' => '行程标题第一行', 'type' => 'text'],
                ['key' => 'visit.plan_line2', 'label' => '行程标题第二行', 'type' => 'text'],
                ['key' => 'visit.text', 'label' => '预约说明', 'type' => 'textarea'],
                ['key' => 'visit.address_label', 'label' => '地址标题', 'type' => 'text'],
                ['key' => 'visit.registered_label', 'label' => '注册地址名称', 'type' => 'text'],
                ['key' => 'visit.factory_label', 'label' => '主厂区地址名称', 'type' => 'text'],
                ['key' => 'visit.office_label', 'label' => '办公区地址名称', 'type' => 'text'],
                ['key' => 'visit.hours_label', 'label' => '营业时间标题', 'type' => 'text'],
                ['key' => 'visit.image_small', 'label' => '拜访图片（1200 像素）', 'type' => 'image', 'preview' => 'images/about/hall-1200.webp'],
                ['key' => 'visit.image', 'label' => '拜访图片（2560 像素）', 'type' => 'image', 'preview' => 'images/about/hall-2560.webp'],
                ['key' => 'visit.button', 'label' => '预约按钮文字', 'type' => 'verbatim'],
                ['key' => 'visit.map_label', 'label' => '地图按钮文字', 'type' => 'verbatim'],
            ],
        ],
        [
            'title' => '预约邮件',
            'fields' => [
                ['key' => 'mail.subject', 'label' => '预约邮件主题', 'type' => 'verbatim'],
                ['key' => 'mail.body', 'label' => '预约邮件正文', 'type' => 'verbatim'],
            ],
        ],
        [
            'title' => '商务合作',
            'fields' => [
                ['key' => 'business.title', 'label' => '商务合作标题', 'type' => 'text'],
                ['key' => 'business.text', 'label' => '商务合作说明', 'type' => 'text'],
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
