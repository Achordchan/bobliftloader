<?php
/**
 * /dealers/: for companies that would sell or rent the range. Only terms the factory has confirmed are written here (one
 * machine to order, T/T, the trade terms, own-logo and colour rules, warranty and parts); no prices, discounts,
 * exclusive territories or overseas branches. {tokens} are filled from the site's own data when the page is drawn:
 * {warranty} (12-month), {lead} (8–30 working days), {loader_count}, {loader_load}, {dumper_count}, {dumper_load}.
 */
return [
    'defaults' => [
        'seo' => [
            'title' => 'Become a Dealer: Mini Skid Steers & Mini Dumpers',
            'description' => 'Sell our mini skid steer loaders and mini dumpers. Buy direct from the Xuzhou factory from one machine, add your own logo, and get warranty and parts support.',
            'image' => '',
        ],
        'hero' => [
            'eyebrow' => 'DEALERS & DISTRIBUTORS',
            'title' => "SELL THE RANGE.\n*BUY FROM*\n*THE FACTORY.*",
            'text' => 'BOB-LIFT builds mini skid steer loaders and mini dumpers in Xuzhou, China. Equipment dealers, rental companies and importers buy from us directly: one machine is enough to start, your own logo is welcome, and the team that builds the machines supports them after delivery.',
            'button' => 'Apply to become a dealer',
            'link' => 'How dealers buy',
            'image' => '',
            'alt' => 'Finished mini skid steer loaders in several colours at the BOB-LIFT factory in Xuzhou',
            'caption' => 'Finished loaders in several colours at our Xuzhou factory',
            'breadcrumb' => 'Dealers',
        ],
        'steps' => [
            'eyebrow' => '01 / HOW DEALERS BUY',
            'title' => "FOUR STEPS\n*TO YOUR FIRST ORDER.*",
            'intro' => 'You deal with the company that builds the machines, from the first message to the container door.',
            'items' => [
                ['title' => 'Tell us about your market', 'text' => 'Where you sell, who buys from you and which machines they ask for. Send your company website if you have one.'],
                ['title' => 'Choose your first order', 'text' => 'We suggest models for your market, confirm the engine, running gear, colour and attachments for each, then quote on the trade terms you need: FOB, CIF, EXW, DDP, FCA or DAP.'],
                ['title' => 'Place the order', 'text' => 'One machine is enough to order. Payment is by T/T bank transfer.'],
                ['title' => 'Built and shipped', 'text' => 'Loaders are built in {lead}; most mini dumpers are ready in about 10 working days. We ship by container and confirm the loading plan with your order.'],
            ],
            'link' => 'Machines per container',
        ],
        'range' => [
            'eyebrow' => '02 / THE RANGE',
            'title' => "TWO FAMILIES.\n*ONE SUPPLIER.*",
            'intro' => 'Pick the models your customers ask for. Every machine is built to order, so the first order can be as small as one.',
            'loaders_title' => 'Mini skid steer loaders',
            'loaders_text' => 'Rated {loader_load}, on tracks or tyres, stand-on or seated. Several models offer more than one engine brand.',
            'dumpers_title' => 'Mini dumpers',
            'dumpers_text' => 'Rated {dumper_load}: tracked dumpers and a tracked mini mixer for narrow paths and soft ground, wheeled dumpers for site haulage.',
            'attachments_title' => 'Attachments',
            'attachments_text' => 'Pallet forks, augers, sweepers, breakers and more, quoted with the loader. The quick-attach plate differs between models, so we match attachments to each one.',
        ],
        'brand' => [
            'eyebrow' => '03 / YOUR BRAND',
            'title' => "YOUR LOGO.\n*OUR FACTORY.*",
            'intro' => 'Sell the machines under your own name. These are the rules for every private-label order.',
            'image' => '',
            'alt' => 'Loader frames painted blue for a customer order, waiting for assembly at the BOB-LIFT factory in Xuzhou',
            'items' => [
                ['title' => 'Your logo from one machine', 'text' => 'Any model can carry your logo, even on a single machine, and a logo alone does not change the lead time. Send it as a vector file.'],
                ['title' => 'Nameplate, manual and packaging', 'text' => 'The nameplate, the electronic manual and the packaging can carry your brand as well.'],
            ],
            'colour_title' => 'Your colour',
            'colour_note' => 'Send us the colour code when you ask for a quote.',
            'colours' => [
                ['range' => 'Wheeled mini dumpers', 'rule' => 'Your colour from a single machine, about three days added to the build.'],
                ['range' => 'BL-T500-HD and BL-T200-HJ', 'rule' => 'Yellow, red or blue as standard. Another colour costs nothing extra on any quantity and takes a little longer to build.'],
                ['range' => 'Mini skid steer loaders', 'rule' => 'Your colour on orders of four or more, about three days added. On one to three machines the standard colour is red; another colour is possible at extra cost.'],
            ],
        ],
        'after' => [
            'eyebrow' => '04 / AFTER THE SALE',
            'title' => "WE STAND\n*BEHIND IT.*",
            'intro' => 'Your customers come to you. You come to the team that built the machine.',
            'warranty_eyebrow' => 'WARRANTY',
            'warranty_title' => 'The whole machine.',
            'warranty_text' => 'Every machine carries our {warranty} whole-machine warranty.',
            'checks_title' => 'Checked and certified.',
            'checks_text' => 'Every machine is run and checked at the factory before it ships, and every model in the range is CE certified.',
            'service_eyebrow' => 'IF SOMETHING GOES WRONG',
            'service' => [
                ['title' => 'Send us the details', 'text' => 'The model, the serial number and a photo or short video of the problem.'],
                ['title' => 'Remote guidance', 'text' => 'Our team talks you through the diagnosis and the fix over WhatsApp, video or email.'],
                ['title' => 'Parts on the way', 'text' => 'We ship the replacement parts to you.'],
            ],
            'parts_eyebrow' => 'SPARE PARTS',
            'parts_title' => 'Original wear parts.',
            'parts_text' => 'We keep a list of original wear parts you can order at any time, quoting the model and serial number. Wheeled mini dumpers ship with original filters.',
            'parts_image' => '',
            'parts_alt' => 'Hydraulic hose fittings at a loader’s lift arm pivot',
        ],
        'faq' => [
            'eyebrow' => '05 / QUESTIONS',
            'title' => "DEALERS\n*ASK US.*",
            'intro' => 'Not answered here? Write to us with your question.',
            'items' => [
                ['q' => 'What is the minimum order?', 'a' => 'One machine. Payment is by T/T bank transfer, and we quote FOB, CIF, EXW, DDP, FCA or DAP.'],
                ['q' => 'Can we sell the machines under our own brand?', 'a' => 'Yes. Any model can carry your logo from a single machine, and the nameplate, the electronic manual and the packaging can carry your brand as well. A logo alone does not change the lead time.'],
                ['q' => 'How long does an order take?', 'a' => 'Mini skid steer loaders are typically built in {lead}. Most mini dumpers are ready in about 10 working days; the FJZ30 takes about 30. Quantity and configuration decide the date, which we confirm with your order.'],
                ['q' => 'How do you support machines after we sell them?', 'a' => 'Every machine carries a {warranty} whole-machine warranty. Send us the model, the serial number and a photo or video of the problem; our team guides the fix remotely and ships the parts.'],
                ['q' => 'Can we visit the factory?', 'a' => 'Yes. Tell us your preferred dates and the machines you want to see, and confirm the appointment with our team before you travel.'],
            ],
        ],
        'apply' => [
            'eyebrow' => '06 / APPLY',
            'title' => "TELL US ABOUT\n*YOUR BUSINESS.*",
            'text' => 'Where you sell, what you sell today and which machines interest you. We reply by email with the models, configuration and terms that suit your market.',
            'points' => "Company name and website\nThe countries or regions you sell in\nThe machines your customers ask for",
            'points_title' => 'WHAT HELPS US REPLY',
        ],
    ],
    'sections' => [
        [
            'title' => '首屏',
            'fields' => [
                ['key' => 'hero.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'hero.title', 'label' => '标题', 'type' => 'textarea', 'help' => '每行一行；整行用 *星号* 括起来显示为蓝色。'],
                ['key' => 'hero.text', 'label' => '介绍', 'type' => 'textarea', 'help' => '只写已确认的政策：不写价格、返点、独家代理、海外分公司或仓库。'],
                ['key' => 'hero.button', 'label' => '申请按钮', 'type' => 'text'],
                ['key' => 'hero.link', 'label' => '下方链接文字', 'type' => 'text'],
                ['key' => 'hero.image', 'label' => '首屏图片（留空用主题自带工厂照）', 'type' => 'image', 'preview' => 'images/about/ready-1600.webp'],
                ['key' => 'hero.alt', 'label' => '图片替代文字', 'type' => 'text'],
                ['key' => 'hero.caption', 'label' => '图片说明（留空不显示）', 'type' => 'text'],
                ['key' => 'hero.breadcrumb', 'label' => '面包屑名称', 'type' => 'text'],
            ],
        ],
        [
            'title' => '经销商采购流程',
            'fields' => [
                ['key' => 'steps.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'steps.title', 'label' => '标题', 'type' => 'textarea', 'help' => '换行即换行，*星号* 内文字显示为蓝色。'],
                ['key' => 'steps.intro', 'label' => '说明', 'type' => 'textarea'],
                ['key' => 'steps.items', 'label' => '步骤', 'type' => 'list', 'add' => '添加步骤', 'summary' => ['title'], 'fields' => [
                    'title' => ['label' => '标题', 'type' => 'text'],
                    'text' => ['label' => '说明', 'type' => 'textarea', 'help' => '{lead} 自动换成采购条款里的交期。'],
                ]],
                ['key' => 'steps.link', 'label' => '最后一步下的装柜链接文字', 'type' => 'text'],
            ],
        ],
        [
            'title' => '产品范围',
            'fields' => [
                ['key' => 'range.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'range.title', 'label' => '标题', 'type' => 'textarea'],
                ['key' => 'range.intro', 'label' => '说明', 'type' => 'textarea'],
                ['key' => 'range.loaders_title', 'label' => '滑移装载机标题', 'type' => 'text'],
                ['key' => 'range.loaders_text', 'label' => '滑移装载机说明', 'type' => 'textarea', 'help' => '{loader_load} 自动换成已发布型号的额定载荷范围。'],
                ['key' => 'range.dumpers_title', 'label' => '迷你翻斗车标题', 'type' => 'text'],
                ['key' => 'range.dumpers_text', 'label' => '迷你翻斗车说明', 'type' => 'textarea', 'help' => '{dumper_load} 自动换成已发布型号的额定载荷范围。'],
                ['key' => 'range.attachments_title', 'label' => '属具标题', 'type' => 'text'],
                ['key' => 'range.attachments_text', 'label' => '属具说明', 'type' => 'textarea'],
            ],
        ],
        [
            'title' => '贴牌与颜色',
            'fields' => [
                ['key' => 'brand.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'brand.title', 'label' => '标题', 'type' => 'textarea'],
                ['key' => 'brand.intro', 'label' => '说明', 'type' => 'textarea'],
                ['key' => 'brand.image', 'label' => '图片（留空用主题自带喷漆照）', 'type' => 'image', 'preview' => 'images/about/paint-900.webp'],
                ['key' => 'brand.alt', 'label' => '图片替代文字', 'type' => 'text'],
                ['key' => 'brand.items', 'label' => '贴牌要点', 'type' => 'list', 'add' => '添加要点', 'summary' => ['title'], 'fields' => [
                    'title' => ['label' => '标题', 'type' => 'text'],
                    'text' => ['label' => '说明', 'type' => 'textarea'],
                ]],
                ['key' => 'brand.colour_title', 'label' => '颜色小标题', 'type' => 'text'],
                ['key' => 'brand.colours', 'label' => '各产品线颜色规则', 'type' => 'list', 'add' => '添加产品线', 'summary' => ['range'], 'fields' => [
                    'range' => ['label' => '产品线', 'type' => 'text'],
                    'rule' => ['label' => '规则', 'type' => 'textarea', 'help' => '与采购与售后页颜色问答保持一致。'],
                ]],
                ['key' => 'brand.colour_note', 'label' => '颜色说明末句', 'type' => 'text'],
            ],
        ],
        [
            'title' => '质保与配件',
            'fields' => [
                ['key' => 'after.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'after.title', 'label' => '标题', 'type' => 'textarea'],
                ['key' => 'after.intro', 'label' => '说明', 'type' => 'textarea'],
                ['key' => 'after.warranty_eyebrow', 'label' => '质保小标题', 'type' => 'text'],
                ['key' => 'after.warranty_title', 'label' => '质保标题', 'type' => 'text'],
                ['key' => 'after.warranty_text', 'label' => '质保说明', 'type' => 'textarea', 'help' => '{warranty} 自动换成采购条款里的质保期，如 12-month。'],
                ['key' => 'after.checks_title', 'label' => '出厂检查标题', 'type' => 'text'],
                ['key' => 'after.checks_text', 'label' => '出厂检查说明', 'type' => 'textarea'],
                ['key' => 'after.service_eyebrow', 'label' => '售后流程小标题', 'type' => 'text'],
                ['key' => 'after.service', 'label' => '售后流程', 'type' => 'list', 'add' => '添加步骤', 'summary' => ['title'], 'fields' => [
                    'title' => ['label' => '标题', 'type' => 'text'],
                    'text' => ['label' => '说明', 'type' => 'textarea'],
                ]],
                ['key' => 'after.parts_eyebrow', 'label' => '配件小标题', 'type' => 'text'],
                ['key' => 'after.parts_title', 'label' => '配件标题', 'type' => 'text'],
                ['key' => 'after.parts_text', 'label' => '配件说明', 'type' => 'textarea'],
                ['key' => 'after.parts_image', 'label' => '配件图片（留空用主题自带液压照）', 'type' => 'image', 'preview' => 'images/about/hydraulics-900.webp'],
                ['key' => 'after.parts_alt', 'label' => '配件图片替代文字', 'type' => 'text'],
            ],
        ],
        [
            'title' => '常见问题',
            'fields' => [
                ['key' => 'faq.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'faq.title', 'label' => '标题', 'type' => 'textarea'],
                ['key' => 'faq.intro', 'label' => '说明', 'type' => 'textarea'],
                ['key' => 'faq.items', 'label' => '问答', 'type' => 'list', 'add' => '添加问答', 'summary' => ['q'], 'fields' => [
                    'q' => ['label' => '问题', 'type' => 'text'],
                    'a' => ['label' => '回答', 'type' => 'textarea', 'help' => '可用 {lead}、{warranty}。'],
                ]],
            ],
        ],
        [
            'title' => '申请表',
            'fields' => [
                ['key' => 'apply.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'apply.title', 'label' => '标题', 'type' => 'textarea'],
                ['key' => 'apply.text', 'label' => '说明', 'type' => 'textarea'],
                ['key' => 'apply.points_title', 'label' => '提示小标题', 'type' => 'text'],
                ['key' => 'apply.points', 'label' => '提示（每行一条）', 'type' => 'textarea'],
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
