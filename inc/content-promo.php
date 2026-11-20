<?php
/**
 * /year-end-specials/ and the home page's offer banner: the year-end offers the company runs from 20 November to
 * 31 December 2026 (the order coupons and the deposit offer). Only the offers' size and rules are written here: never a
 * price, a price list or the year-end special-price models, which the sales team quotes per order (company policy).
 * After dates.ends the banner is gone, the page says the offers have ended and is kept out of search and the sitemap.
 */
return [
    'defaults' => [
        'seo' => [
            'title' => 'Year-End Offers: Up to US$2,500 Off Loaders & Dumpers',
            'description' => 'Until 31 December 2026: up to US$2,500 off orders of mini skid steer loaders and mini dumpers, or pay a small deposit now and it counts double when you order.',
            'image' => '',
        ],
        'dates' => [
            'starts' => '2026-11-20',
            'ends' => '2026-12-31',
        ],
        'banner' => [
            'eyebrow' => 'YEAR-END OFFERS · UNTIL 31 DECEMBER 2026',
            'title' => 'Up to US$2,500 off your order.',
            'text' => 'Or pay a small deposit now and it counts double when you order.',
            'button' => 'See the offers',
        ],
        'hero' => [
            'eyebrow' => 'YEAR-END OFFERS · 20 NOV – 31 DEC 2026',
            'title' => "ORDER BEFORE\n*THE YEAR ENDS.*",
            'text' => 'Two offers run until 31 December 2026 on our mini skid steer loaders and mini dumpers. Take a coupon off a confirmed order, or hold a later order with a small deposit that counts double when you place it. Each order takes one offer.',
            'button' => 'Claim an offer',
            'link' => 'Read the terms',
            'breadcrumb' => 'Year-end offers',
            'image' => '',
            'alt' => 'BOB-LIFT mini skid steer loader at work on a site',
            'ended' => 'These offers ended on 31 December 2026. Ask our team about your order: we reply with a quotation and the current terms.',
            'ended_button' => 'Get a quote',
        ],
        'coupons' => [
            'eyebrow' => '01 / ORDER COUPONS',
            'title' => "THE BIGGER THE ORDER,\n*THE MORE YOU SAVE.*",
            'intro' => 'The coupon comes off the order total in your contract. Every mini skid steer loader and mini dumper on this website qualifies, attachments included in the same order.',
            'items' => [
                ['save' => '500', 'over' => '10,000'],
                ['save' => '1,500', 'over' => '30,000'],
                ['save' => '2,500', 'over' => '50,000'],
            ],
        ],
        'deposit' => [
            'eyebrow' => '02 / DEPOSIT OFFER',
            'title' => "PAY A DEPOSIT NOW.\n*IT COUNTS DOUBLE.*",
            'intro' => 'For buyers with a real order who can only pay in the coming weeks: pay a small deposit by 31 December 2026, and it counts double against the order when you place it.',
            'items' => [
                ['order' => 'US$5,000 – 15,000', 'pay' => '500', 'worth' => '1,000'],
                ['order' => 'US$15,001 – 30,000', 'pay' => '700', 'worth' => '1,400'],
                ['order' => 'Above US$30,000', 'pay' => '1,000', 'worth' => '2,000'],
            ],
            'points' => [
                ['title' => 'Paid to our company account', 'text' => 'Pay the deposit by bank transfer to the company account we give you. We then send an official receipt by email; that receipt is your proof of the doubled deposit.'],
                ['title' => 'Taken off the contract payment', 'text' => 'The doubled amount is deducted from the payment in your contract.'],
                ['title' => 'Refunded if you do not order', 'text' => 'If you decide not to place the order, we refund the deposit in full.'],
                ['title' => 'A smaller order than planned', 'text' => 'If the final order is small, we agree with you how much of the doubled deposit applies, or carry it over as prepayment for orders in several batches.'],
            ],
        ],
        'terms' => [
            'eyebrow' => '03 / TERMS',
            'title' => "THE RULES,\n*IN PLAIN WORDS.*",
            'items' => "The offers run from 20 November to 31 December 2026, China time. A deposit must be paid by 31 December 2026.\nEach order takes one offer: a coupon cannot be combined with the deposit offer, or used on models already at a year-end special price.\nThe offers do not combine with other major company promotions. The signed contract sets the final terms.\nAll amounts are in US dollars. We quote every order individually: ask our team for the price of the machines you need.",
        ],
        'claim' => [
            'eyebrow' => '04 / CLAIM AN OFFER',
            'title' => "TELL US WHAT\n*YOU NEED.*",
            'text' => 'Send the models and quantities you have in mind. Our sales team replies with a quotation and tells you which offer saves you more on that order.',
            'message' => '',
            'whatsapp' => 'Ask on WhatsApp',
        ],
    ],
    'sections' => [
        [
            'title' => '活动日期',
            'fields' => [
                ['key' => 'dates.starts', 'label' => '开始日期', 'type' => 'text', 'help' => '格式 2026-11-20（中国时间），当天起显示首页横幅。'],
                ['key' => 'dates.ends', 'label' => '结束日期', 'type' => 'text', 'help' => '格式 2026-12-31，当天仍显示；次日起首页横幅自动消失，活动页改为「活动已结束」并不再被搜索收录。'],
            ],
        ],
        [
            'title' => '首页横幅',
            'fields' => [
                ['key' => 'banner.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'banner.title', 'label' => '标题', 'type' => 'text'],
                ['key' => 'banner.text', 'label' => '说明', 'type' => 'text'],
                ['key' => 'banner.button', 'label' => '按钮文字', 'type' => 'text'],
            ],
        ],
        [
            'title' => '活动页首屏',
            'fields' => [
                ['key' => 'hero.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'hero.title', 'label' => '标题', 'type' => 'textarea', 'help' => '每行一行；整行用 *星号* 括起来显示为蓝色。'],
                ['key' => 'hero.text', 'label' => '介绍', 'type' => 'textarea', 'help' => '只写优惠力度和规则，不写任何价格或特价型号（公司政策）。'],
                ['key' => 'hero.button', 'label' => '按钮文字', 'type' => 'text'],
                ['key' => 'hero.link', 'label' => '条款链接文字', 'type' => 'text'],
                ['key' => 'hero.breadcrumb', 'label' => '面包屑名称', 'type' => 'text'],
                ['key' => 'hero.image', 'label' => '首屏图片（留空用主题自带装载机照）', 'type' => 'image', 'preview' => 'images/hero-real-loader.webp'],
                ['key' => 'hero.alt', 'label' => '首屏图片替代文字', 'type' => 'text'],
                ['key' => 'hero.ended', 'label' => '活动结束后显示的说明', 'type' => 'textarea'],
                ['key' => 'hero.ended_button', 'label' => '活动结束后的按钮文字', 'type' => 'text'],
            ],
        ],
        [
            'title' => '满减券',
            'fields' => [
                ['key' => 'coupons.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'coupons.title', 'label' => '标题', 'type' => 'textarea'],
                ['key' => 'coupons.intro', 'label' => '说明（含适用范围）', 'type' => 'textarea'],
                ['key' => 'coupons.items', 'label' => '档位', 'type' => 'list', 'add' => '添加档位', 'summary' => ['save'], 'fields' => [
                    'save' => ['label' => '立减（美元，只写数字如 1,500）', 'type' => 'text'],
                    'over' => ['label' => '订单满（美元，只写数字如 30,000）', 'type' => 'text'],
                ]],
            ],
        ],
        [
            'title' => '定金膨胀',
            'fields' => [
                ['key' => 'deposit.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'deposit.title', 'label' => '标题', 'type' => 'textarea'],
                ['key' => 'deposit.intro', 'label' => '说明', 'type' => 'textarea'],
                ['key' => 'deposit.items', 'label' => '档位', 'type' => 'list', 'add' => '添加档位', 'summary' => ['order'], 'fields' => [
                    'order' => ['label' => '订单金额', 'type' => 'text'],
                    'pay' => ['label' => '支付定金（美元，只写数字）', 'type' => 'text'],
                    'worth' => ['label' => '膨胀为（美元，只写数字）', 'type' => 'text'],
                ]],
                ['key' => 'deposit.points', 'label' => '规则要点', 'type' => 'list', 'add' => '添加要点', 'summary' => ['title'], 'fields' => [
                    'title' => ['label' => '标题', 'type' => 'text'],
                    'text' => ['label' => '说明', 'type' => 'textarea'],
                ]],
            ],
        ],
        [
            'title' => '活动条款',
            'fields' => [
                ['key' => 'terms.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'terms.title', 'label' => '标题', 'type' => 'textarea'],
                ['key' => 'terms.items', 'label' => '条款', 'type' => 'textarea', 'help' => '每行一条。'],
            ],
        ],
        [
            'title' => '询盘表单',
            'fields' => [
                ['key' => 'claim.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'claim.title', 'label' => '标题', 'type' => 'textarea'],
                ['key' => 'claim.text', 'label' => '说明', 'type' => 'textarea'],
                ['key' => 'claim.message', 'label' => '留言框预填（可留空）', 'type' => 'textarea'],
                ['key' => 'claim.whatsapp', 'label' => 'WhatsApp 按钮文字', 'type' => 'text'],
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
