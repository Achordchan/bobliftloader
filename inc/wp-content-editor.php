<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * "网站内容" screens for the home, Support, Applications, skid steer loader and mini dumper pages. Each screen is built from the field list below; saving keeps only
 * what differs from the defaults in inc/editable.php, so text an editor never touched follows later theme updates.
 */

const BL_CONTENT_PAGES = ['home' => ['title' => '首页内容', 'menu' => '首页', 'path' => '/'], 'support' => ['title' => '采购与售后（Support）页内容', 'menu' => '采购与售后 Support', 'path' => '/support/'], 'applications' => ['title' => '应用场景（Applications）页内容', 'menu' => '应用场景 Applications', 'path' => '/applications/'], 'loaders' => ['title' => '滑移装载机栏目内容', 'menu' => '滑移装载机', 'path' => '/products/skid-steer-loaders/'], 'dumpers' => ['title' => '迷你翻斗车栏目内容', 'menu' => '迷你翻斗车', 'path' => '/products/mini-dumpers/']];

/**
 * Field list per page. "group" only groups fields on the screen; "preview" is the theme's own image shown while a slot is
 * empty; a list's "summary" names the row fields shown on its folded row.
 */
function bl_content_schema(string $page): array
{
    $heading = '换行即分行；用 *星号* 包住的文字显示为强调色。';
    $rich = '换行即分行；可以用 &lt;a href="…"&gt;链接&lt;/a&gt;、&lt;strong&gt;加粗&lt;/strong&gt;。';
    $image = '留空使用主题自带的照片。建议上传宽度 1600px 以上的横图。';
    $alt = '一句话描述图片内容，给看不到图片的访客和搜索引擎。';
    $optional = '可留空。';
    $share = '在微信、WhatsApp、LinkedIn 等处转发这个页面时显示的图。留空使用主题自带的默认图。建议 1200×630 像素的横图。';
    $seo = static fn(string $extra): array => ['title' => '搜索结果显示', 'note' => '浏览器标签和 Google 搜索结果里的标题与摘要；标题后面会自动加“ | BOB-LIFT”。', 'fields' => [
        ['key' => 'seo.title', 'label' => '页面标题', 'type' => 'text', 'help' => '建议 60 个字符以内。'],
        ['key' => 'seo.description', 'label' => '页面描述', 'type' => 'textarea', 'help' => '建议 150 个字符左右。' . $extra],
        ['key' => 'seo.image', 'label' => '分享图片', 'type' => 'image', 'preview' => 'images/share-default.jpg', 'help' => $share],
    ]];
    // The block every product page of the family shares (inc/product-page.php); a product's own fields come first.
    $productPage = static fn(string $family): array => ['title' => '产品页通用模块', 'note' => '本系列每个产品详情页都会显示的内容：' . ($family === 'loader' ? '默认展示的属具、' : '') . '工厂与出厂检查、常见问题。单个产品在“设备产品 → 编辑 → 产品页模块”里填的内容优先显示。', 'fields' => array_merge($family === 'loader' ? [
        ['key' => 'product.attachments', 'label' => '默认展示的属具', 'type' => 'text', 'help' => '产品页“Works with”一节显示的属具名称，英文逗号分隔，名称要和上面属具列表里一致；产品自己没勾选时用这里。留空则不显示这一节。'],
    ] : [], [
        ['key' => 'product.factory.title', 'group' => '工厂与出厂检查', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
        ['key' => 'product.factory.text', 'group' => '工厂与出厂检查', 'label' => '说明', 'type' => 'textarea'],
        ['key' => 'product.factory.link', 'group' => '工厂与出厂检查', 'label' => '链接文字', 'type' => 'text', 'help' => $family === 'loader' ? '链接到栏目页“How they are made”。留空不显示。' : '链接到关于我们。留空不显示。'],
        ['key' => 'product.factory.images', 'group' => '工厂与出厂检查', 'label' => '照片', 'type' => 'list', 'add' => '添加照片', 'summary' => ['caption'], 'fields' => [
            'image' => ['label' => '图片', 'type' => 'image', 'help' => '真实的工厂照片，3:2 横图。最多显示 3 张；全部删掉则只显示文字和检查项。'],
            'alt' => ['label' => '替代文字', 'type' => 'text', 'help' => $alt],
            'caption' => ['label' => '图下说明', 'type' => 'text'],
        ]],
        ['key' => 'product.factory.checks', 'group' => '工厂与出厂检查', 'label' => '出厂检查', 'type' => 'list', 'add' => '添加检查项', 'summary' => ['title'], 'fields' => [
            'title' => ['label' => '名称', 'type' => 'text'],
            'text' => ['label' => '说明', 'type' => 'textarea'],
        ]],
        ['key' => 'product.faq', 'label' => '常见问题', 'type' => 'list', 'add' => '添加问题', 'summary' => ['q'], 'fields' => [
            'q' => ['label' => '问题', 'type' => 'text'],
            'a' => ['label' => '回答', 'type' => 'textarea'],
            'link_label' => ['label' => '链接文字', 'type' => 'text', 'help' => $optional],
            'link_url' => ['label' => '链接地址', 'type' => 'url', 'help' => '可留空；文字和地址都填了才显示链接。站内页面写 /support/…。'],
        ]],
    ])];
    if ($page === 'applications') {
        $names = ['landscaping' => '景观绿化', 'site-preparation' => '场地准备', 'material-transport' => '物料运输', 'gardens-and-homesteads' => '园林家用', 'farms-and-orchards' => '农场果园', 'building-sites' => '工地'];
        $sections = [
            ['title' => '列表页首屏', 'note' => '“应用”列表页最上面的大标题和右侧说明；下面三张场景卡的图片和文字在各应用分区里改。', 'fields' => [
                ['key' => 'hero.line1', 'label' => '大标题第一行', 'type' => 'text', 'help' => '普通实心字。'],
                ['key' => 'hero.line2', 'label' => '大标题第二行', 'type' => 'text', 'help' => '描边字。'],
                ['key' => 'hero.line3', 'label' => '大标题第三行', 'type' => 'text', 'help' => '蓝色字。'],
                ['key' => 'hero.text', 'label' => '右侧说明', 'type' => 'textarea'],
                ['key' => 'hero.link', 'label' => '向下链接文字', 'type' => 'text'],
            ]],
            ['title' => '询价前请告诉我们', 'note' => '列表页下方的“四个问题”，帮访客先准备好资料再询价。', 'fields' => [
                ['key' => 'send.eyebrow', 'label' => '小标题', 'type' => 'text'],
                ['key' => 'send.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
                ['key' => 'send.text', 'label' => '标题下说明', 'type' => 'textarea'],
                ['key' => 'send.items', 'label' => '四个问题', 'type' => 'list', 'add' => '添加问题', 'summary' => ['title'], 'fields' => [
                    'title' => ['label' => '问题', 'type' => 'text'],
                    'text' => ['label' => '说明', 'type' => 'rich', 'help' => $rich],
                ]],
            ]],
        ];
        foreach ($GLOBALS['bl_application_base'] as $app) {
            $slug = bl_application_slug($app);
            $sections[] = ['title' => ($names[$slug] ?? $app['title']) . ' ' . $app['title'], 'note' => '这个应用的详情页（' . '/applications/' . $slug . '/）。网址固定，改名称不会改网址；首页应用场景卡片、联系表单的下拉选项、站内搜索都读这里，改一处全站同步。', 'fields' => [
                ['key' => "jobs.$slug.title", 'label' => '名称', 'type' => 'text', 'help' => '页面大标题、面包屑、卡片和联系表单选项都用它。留空使用主题自带名称。'],
                ['key' => "jobs.$slug.subtitle", 'label' => '副标题', 'type' => 'text', 'help' => '大标题下方的一句英文口号。'],
                ['key' => "jobs.$slug.description", 'label' => '介绍', 'type' => 'textarea', 'help' => '详情页首屏右侧和首页卡片上的说明。'],
                ['key' => "jobs.$slug.image", 'group' => '场景图', 'label' => '图片', 'type' => 'image', 'preview' => substr($app['image'], strlen('/assets/')), 'help' => '留空使用主题自带的场景图（3:2 横图）。列表页三联图、首页卡片、详情页大图共用这一张。'],
                ['key' => "jobs.$slug.alt", 'group' => '场景图', 'label' => '替代文字', 'type' => 'text', 'help' => $alt],
                ['key' => "jobs.$slug.models", 'label' => '推荐机型', 'type' => 'text', 'help' => '详情页“机器”一节显示的型号，用英文逗号分隔，按显示顺序写（如 FC10, FY15）。留空则按这个应用的关键参数自动选同类前三款。'],
                ['key' => "jobs.$slug.checks", 'group' => '询价前要确认的事', 'label' => '事项', 'type' => 'list', 'add' => '添加事项', 'summary' => ['title'], 'fields' => [
                    'title' => ['label' => '标题', 'type' => 'text'],
                    'text' => ['label' => '说明', 'type' => 'textarea'],
                    'measure' => ['label' => '访客需要准备', 'type' => 'text', 'help' => '“You’ll need”后面那句。'],
                    'link_label' => ['label' => '链接文字', 'type' => 'text', 'help' => $optional],
                    'link_url' => ['label' => '链接地址', 'type' => 'url', 'help' => '可留空；文字和地址都填了才显示链接。站内页面写 /products/…。'],
                ]],
            ]];
        }
        $sections[] = $seo('');
        return $sections;
    }
    if ($page === 'loaders') return [
        ['title' => '适用场景', 'note' => '滑移装载机栏目页属具上方的“用在哪里”，常见问题“什么是滑移装载机”链接到这里（#loader-jobs）。全部删掉则不显示这一块。', 'fields' => [
            ['key' => 'jobs.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'jobs.items', 'label' => '场景', 'type' => 'list', 'add' => '添加场景', 'summary' => ['title'], 'fields' => [
                'title' => ['label' => '场景名', 'type' => 'text'],
                'text' => ['label' => '说明', 'type' => 'textarea'],
                'tools' => ['label' => '常用属具', 'type' => 'text', 'help' => '用英文逗号分隔，按页面上显示的顺序写；最好和下面属具列表里的名称一致。'],
            ]],
        ]],
        ['title' => '属具', 'note' => '栏目页的属具一节（#attachments）。标题里的属具数量按下面的条目自动计算。', 'fields' => [
            ['key' => 'attachments.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'attachments.text', 'label' => '标题旁说明', 'type' => 'textarea'],
            ['key' => 'attachments.link', 'label' => '询价链接文字', 'type' => 'text', 'help' => '链接去联系页（已选好滑移装载机）。留空不显示。'],
            ['key' => 'attachments.items', 'label' => '属具', 'type' => 'list', 'add' => '添加属具', 'summary' => ['name'], 'meta' => 'group', 'fields' => [
                'group' => ['label' => '分组', 'type' => 'text', 'help' => '分组名相同的属具归在同一组，分组按第一次出现的顺序排列。'],
                'name' => ['label' => '名称', 'type' => 'text'],
                'use' => ['label' => '用途', 'type' => 'text'],
                'image' => ['label' => '图片', 'type' => 'image', 'help' => '4:3 横图。留空则不显示图片。'],
            ]],
            ['key' => 'attachments.note', 'label' => '底部备注', 'type' => 'textarea', 'help' => $optional],
        ]],        $productPage('loader'),
    ];
    if ($page === 'dumpers') return [
        ['title' => '栏目介绍与配置', 'note' => '翻斗车栏目页产品列表下方的介绍（#about-the-range）：标题、介绍和下面一排配置说明。中间的载荷图由产品数据自动生成。', 'fields' => [
            ['key' => 'about.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'about.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'about.intro', 'label' => '标题旁介绍', 'type' => 'textarea'],
            ['key' => 'about.options', 'label' => '配置说明', 'type' => 'list', 'add' => '添加一项', 'summary' => ['big', 'title'], 'fields' => [
                'big' => ['label' => '大字', 'type' => 'text', 'help' => '如 WHEELS / TRACKS；斜杠显示为蓝色。'],
                'title' => ['label' => '名称', 'type' => 'text'],
                'text' => ['label' => '说明', 'type' => 'textarea'],
            ]],
        ]],
        ['title' => '适用场景', 'note' => '栏目页的“用在哪里”（#dumper-jobs），在介绍和参数对比之间。全部删掉则不显示这一块。', 'fields' => [
            ['key' => 'jobs.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'jobs.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'jobs.text', 'label' => '标题旁说明', 'type' => 'textarea'],
            ['key' => 'jobs.items', 'label' => '场景', 'type' => 'list', 'add' => '添加场景', 'summary' => ['title'], 'fields' => [
                'title' => ['label' => '场景名', 'type' => 'text'],
                'text' => ['label' => '说明', 'type' => 'textarea'],
                'models' => ['label' => '适合型号', 'type' => 'text', 'help' => '写产品的型号（如 FY30），英文逗号分隔；会链接到对应产品页。写错或已下架的型号不显示。'],
            ]],
        ]],
        ['title' => '参数对比', 'fields' => [
            ['key' => 'compare.note', 'label' => '对比表说明', 'type' => 'textarea', 'help' => '对比表标题旁的说明，写参数出处。'],
        ]],        $productPage('dumper'),
    ];
    if ($page === 'support') return [
        ['title' => '首屏', 'fields' => [
            ['key' => 'hero.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'hero.title', 'label' => '大标题', 'type' => 'heading', 'help' => '每行一段；整行用 *星号* 包住显示为蓝色。'],
            ['key' => 'hero.text', 'label' => '右侧介绍', 'type' => 'textarea'],
            ['key' => 'hero.link', 'label' => '向下链接文字', 'type' => 'text'],
            ['key' => 'hero.image', 'group' => '大图', 'label' => '图片', 'type' => 'image', 'preview' => 'images/about/people-1600.webp', 'help' => $image],
            ['key' => 'hero.alt', 'group' => '大图', 'label' => '替代文字', 'type' => 'text', 'help' => $alt],
            ['key' => 'hero.caption', 'group' => '大图', 'label' => '图片说明', 'type' => 'text'],
        ]],
        ['title' => '采购条款', 'note' => '显示在 Support 首屏下方，产品页的“质保 / 交期 / 起订量”也读这里，改一处全站同步。', 'fields' => [
            ['key' => 'terms', 'type' => 'terms', 'rows' => ['moq' => '起订量', 'payment' => '付款方式', 'lead' => '交期', 'warranty' => '质保']],
        ]],
        ['title' => '01 采购流程', 'fields' => [
            ['key' => 'buying.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'buying.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading . ' 步骤数量变了记得改标题里的数字。'],
            ['key' => 'buying.intro', 'label' => '标题旁说明', 'type' => 'textarea'],
            ['key' => 'buying.steps', 'label' => '步骤', 'type' => 'list', 'add' => '添加步骤', 'summary' => ['title'], 'fields' => [
                'title' => ['label' => '步骤名', 'type' => 'text'],
                'text' => ['label' => '说明', 'type' => 'textarea'],
                'link_label' => ['label' => '链接文字', 'type' => 'text', 'help' => $optional],
                'link_url' => ['label' => '链接地址', 'type' => 'url', 'help' => '可留空。站内页面写 /contact/，本页位置写 #containers。'],
            ]],
        ]],
        ['title' => '装柜数量', 'fields' => [
            ['key' => 'containers.title', 'label' => '标题', 'type' => 'text'],
            ['key' => 'containers.text', 'label' => '说明', 'type' => 'textarea'],
            ['key' => 'containers.loading', 'label' => '每柜台数', 'type' => 'loading', 'help' => '约数。两格都留空，该型号不出现在表里。'],
            ['key' => 'containers.note', 'label' => '表格下方备注', 'type' => 'textarea'],
        ]],
        ['title' => '02 售后', 'fields' => [
            ['key' => 'after.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'after.title', 'label' => '标题', 'type' => 'heading', 'help' => '换行即分行；*星号* 里的文字换一种颜色显示。'],
            ['key' => 'after.intro', 'label' => '标题旁说明', 'type' => 'textarea'],
            ['key' => 'after.warranty_eyebrow', 'group' => '质保', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'after.warranty_title', 'group' => '质保', 'label' => '标题', 'type' => 'text'],
            ['key' => 'after.warranty_text', 'group' => '质保', 'label' => '说明', 'type' => 'textarea'],
            ['key' => 'after.service_eyebrow', 'group' => '报修流程', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'after.service_steps', 'group' => '报修流程', 'label' => '步骤', 'type' => 'list', 'add' => '添加一步', 'summary' => ['title'], 'fields' => [
                'title' => ['label' => '步骤名', 'type' => 'text'],
                'text' => ['label' => '说明', 'type' => 'textarea'],
            ]],
            ['key' => 'after.parts_image', 'group' => '配件', 'label' => '图片', 'type' => 'image', 'preview' => 'images/about/hydraulics-900.webp', 'help' => '留空使用主题自带的照片（3:2 横图显示）。'],
            ['key' => 'after.parts_alt', 'group' => '配件', 'label' => '替代文字', 'type' => 'text', 'help' => $alt],
            ['key' => 'after.parts_eyebrow', 'group' => '配件', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'after.parts_title', 'group' => '配件', 'label' => '标题', 'type' => 'text'],
            ['key' => 'after.parts_text', 'group' => '配件', 'label' => '说明', 'type' => 'textarea'],
            ['key' => 'after.button', 'group' => '报修按钮与 WhatsApp', 'label' => '报修按钮文字', 'type' => 'text'],
            ['key' => 'after.whatsapp_label', 'group' => '报修按钮与 WhatsApp', 'label' => 'WhatsApp 链接文字', 'type' => 'text'],
            ['key' => 'after.whatsapp_number', 'group' => '报修按钮与 WhatsApp', 'label' => 'WhatsApp 号码', 'type' => 'digits', 'help' => '含国家码，只填数字。留空则不显示 WhatsApp 链接。'],
            ['key' => 'after.whatsapp_message', 'group' => '报修按钮与 WhatsApp', 'label' => 'WhatsApp 预填消息', 'type' => 'text', 'help' => '访客打开 WhatsApp 时输入框里预先填好的文字。'],
        ]],
        ['title' => '03 资料下载', 'note' => '画册 PDF 本身在<a href="' . esc_url(admin_url('admin.php?page=bl-downloads')) . '">“网站内容 → 资料下载”</a>更换；视频条目由产品视频自动生成。', 'fields' => [
            ['key' => 'downloads.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'downloads.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'downloads.intro', 'label' => '标题旁说明', 'type' => 'textarea'],
            ['key' => 'downloads.catalogue_title', 'group' => '画册', 'label' => '名称', 'type' => 'text'],
            ['key' => 'downloads.catalogue_text', 'group' => '画册', 'label' => '说明', 'type' => 'text'],
            ['key' => 'downloads.sheets_title', 'group' => '规格表', 'label' => '名称', 'type' => 'text'],
            ['key' => 'downloads.sheets_text', 'group' => '规格表', 'label' => '说明', 'type' => 'text'],
            ['key' => 'downloads.videos_title', 'group' => '视频', 'label' => '名称', 'type' => 'text'],
        ]],
        ['title' => '04 常见问题', 'note' => '问答也会作为 FAQ 结构化数据提供给搜索引擎。', 'fields' => [
            ['key' => 'faq.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'faq.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'faq.intro', 'label' => '左侧说明', 'type' => 'textarea'],
            ['key' => 'faq.link', 'label' => '提问链接文字', 'type' => 'text'],
            ['key' => 'faq.items', 'label' => '问答', 'type' => 'list', 'add' => '添加问答', 'summary' => ['q'], 'meta' => 'group', 'fields' => [
                'group' => ['label' => '分组', 'type' => 'text', 'help' => '相邻且分组相同的问题归在同一个小标题下。'],
                'q' => ['label' => '问题', 'type' => 'text'],
                'a' => ['label' => '回答', 'type' => 'textarea'],
                'link_label' => ['label' => '链接文字', 'type' => 'text', 'help' => $optional],
                'link_url' => ['label' => '链接地址', 'type' => 'url', 'help' => $optional],
            ]],
        ]],
        $seo('采购条款改了，记得同步这里的描述。'),
    ];

    $family = static fn(string $key, string $name, string $preview): array => ['title' => $name . '卡片', 'note' => '型号数量、载荷和斗容由产品数据自动计算。', 'fields' => [
        ['key' => "families.$key.label", 'label' => '名称', 'type' => 'text'],
        ['key' => "families.$key.title", 'label' => '标题', 'type' => 'heading', 'help' => '换行即分行。'],
        ['key' => "families.$key.description", 'label' => '介绍', 'type' => 'textarea'],
        ['key' => "families.$key.image", 'group' => '产品图', 'label' => '图片', 'type' => 'image', 'preview' => $preview, 'help' => '同一张图也用在顶部菜单和产品总览页。留空使用主题自带的棚拍图；建议透明或浅色背景，1374×1145 左右。'],
        ['key' => "families.$key.alt", 'group' => '产品图', 'label' => '替代文字', 'type' => 'text', 'help' => $alt],
    ]];
    return [
        ['title' => '首屏', 'fields' => [
            ['key' => 'hero.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'hero.title', 'label' => '大标题', 'type' => 'heading', 'help' => '每行一段，最后的句号显示为蓝色。'],
            ['key' => 'hero.button', 'label' => '按钮文字', 'type' => 'text', 'help' => '按钮进入产品页。'],
            ['key' => 'hero.link', 'label' => '链接文字', 'type' => 'text', 'help' => '链接去联系页。'],
            ['key' => 'hero.image', 'group' => '大图', 'label' => '宽屏', 'type' => 'image', 'preview' => 'images/hero-banner-2k.webp', 'help' => '留空使用主题自带的首屏图。建议 2560×1067 左右的横图。'],
            ['key' => 'hero.image_mobile', 'group' => '大图', 'label' => '手机', 'type' => 'image', 'preview' => 'images/hero-real-loader.webp', 'help' => '留空时手机也用上面的宽屏图；两张都空则用主题自带的图。'],
            ['key' => 'hero.alt', 'group' => '大图', 'label' => '替代文字', 'type' => 'text', 'help' => $alt],
        ]],
        ['title' => '首屏 · 迷你翻斗车', 'note' => '首屏第二张，访客可在首屏右上角切换，也会每隔几秒自动轮换（鼠标停在首屏上或点了切换按钮就停）。按钮进入迷你翻斗车栏目页。', 'fields' => [
            ['key' => 'hero_dumper.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'hero_dumper.title', 'label' => '大标题', 'type' => 'heading', 'help' => '每行一段，最后的句号显示为蓝色。'],
            ['key' => 'hero_dumper.button', 'label' => '按钮文字', 'type' => 'text', 'help' => '按钮进入迷你翻斗车栏目页。'],
            ['key' => 'hero_dumper.link', 'label' => '链接文字', 'type' => 'text', 'help' => '链接去联系页。'],
            ['key' => 'hero_dumper.image', 'group' => '大图', 'label' => '宽屏', 'type' => 'image', 'preview' => 'images/hero-dumper-2k.webp', 'help' => '留空使用主题自带的翻斗车首屏图。建议 2560×1067 左右的横图，车放在右侧三分之一。'],
            ['key' => 'hero_dumper.image_mobile', 'group' => '大图', 'label' => '手机', 'type' => 'image', 'preview' => 'images/hero-dumper-mobile.webp', 'help' => '留空时手机也用上面的宽屏图；两张都空则用主题自带的图。'],
            ['key' => 'hero_dumper.alt', 'group' => '大图', 'label' => '替代文字', 'type' => 'text', 'help' => $alt],
        ]],
        $family('loader', '滑移装载机', 'images/range-loader-studio.webp'),
        $family('dumper', '迷你翻斗车', 'images/range-dumper-studio.webp'),
        ['title' => '01 产品系列', 'fields' => [
            ['key' => 'range.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'range.title', 'label' => '标题', 'type' => 'heading', 'help' => '最后的句号显示为蓝色。'],
            ['key' => 'range.description', 'label' => '标题旁说明', 'type' => 'rich', 'help' => '{models} 会替换成当前的型号数量。' . $rich],
            ['key' => 'range.assist', 'group' => '卡片下方', 'label' => '一句话', 'type' => 'text'],
            ['key' => 'range.assist_link', 'group' => '卡片下方', 'label' => '链接文字', 'type' => 'text'],
        ]],
        ['title' => '02 应用场景', 'note' => '这里只改这一块的标题和说明；各场景卡片的图片和文字在<a href="' . esc_url(admin_url('admin.php?page=bl-content-applications')) . '">应用场景</a>里改。', 'fields' => [
            ['key' => 'applications.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'applications.title', 'label' => '标题', 'type' => 'heading', 'help' => '换行即分行；*星号* 里的文字显示为描边字。'],
            ['key' => 'applications.description', 'label' => '标题旁说明', 'type' => 'rich', 'help' => $rich],
        ]],
        ['title' => '03 团队与服务', 'fields' => [
            ['key' => 'approach.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'approach.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'approach.text', 'label' => '介绍', 'type' => 'textarea'],
            ['key' => 'approach.link', 'label' => '链接文字', 'type' => 'text', 'help' => '链接去关于页。'],
            ['key' => 'approach.image', 'group' => '照片', 'label' => '图片', 'type' => 'image', 'preview' => 'images/about/people-1600.webp', 'help' => $image],
            ['key' => 'approach.alt', 'group' => '照片', 'label' => '替代文字', 'type' => 'text', 'help' => $alt],
            ['key' => 'approach.caption', 'group' => '照片', 'label' => '图片说明', 'type' => 'text'],
            ['key' => 'approach.facts', 'group' => '公司数据', 'type' => 'list', 'add' => '添加一项', 'summary' => ['value', 'unit', 'label'], 'fields' => [
                'value' => ['label' => '数字', 'type' => 'text'],
                'unit' => ['label' => '单位', 'type' => 'text', 'help' => $optional],
                'label' => ['label' => '说明', 'type' => 'text'],
            ]],
            ['key' => 'approach.steps', 'group' => '三步', 'type' => 'list', 'add' => '添加一步', 'summary' => ['title'], 'fields' => [
                'title' => ['label' => '标题', 'type' => 'text'],
                'text' => ['label' => '说明', 'type' => 'rich', 'help' => $rich],
            ]],
        ]],
        ['title' => '公司动态', 'note' => '文章列表自动显示最新 3 篇企业新闻。', 'fields' => [
            ['key' => 'journal.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'journal.title', 'label' => '标题', 'type' => 'text'],
            ['key' => 'journal.link', 'label' => '链接文字', 'type' => 'text'],
        ]],
        ['title' => '底部联系', 'note' => '联系邮箱 info@boblift.com 全站统一（页脚、联系页、询盘表单都用它），不在这里单独修改。', 'fields' => [
            ['key' => 'contact.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'contact.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'contact.text', 'label' => '说明', 'type' => 'rich', 'help' => $rich],
            ['key' => 'contact.signoff', 'label' => '底部一句话', 'type' => 'text'],
        ]],
        $seo(''),
    ];
}

/* ---------- Reading values ---------- */

function bl_content_get(array $data, string $key)
{
    foreach (explode('.', $key) as $part) {
        if (!is_array($data) || !array_key_exists($part, $data)) return null;
        $data = $data[$part];
    }
    return $data;
}

function bl_content_set(array &$data, string $key, $value): void
{
    $ref = &$data;
    foreach (explode('.', $key) as $part) {
        if (!isset($ref[$part]) || !is_array($ref[$part])) $ref[$part] = [];
        $ref = &$ref[$part];
    }
    $ref = $value;
}

/* ---------- Cleaning what was submitted ---------- */

/**
 * Links to this site are kept as root-relative paths, so the content survives a move to another domain. Only the origin
 * (scheme, host, port) is removed: an install under /site/ keeps /site/contact/, which still resolves there.
 */
function bl_content_local_url(string $url): string
{
    $origin = bl_site_origin();
    return str_starts_with($url, $origin . '/') ? substr($url, strlen($origin)) : $url;
}

function bl_content_clean_url(string $url): string
{
    $url = bl_content_local_url(trim($url));
    if ($url === '') return '';
    if (preg_match('~^(/(?!/)|#|\?)~', $url)) return preg_replace('~[\s"<>]~', '', $url);
    return esc_url_raw($url, ['http', 'https', 'mailto', 'tel']);
}

function bl_content_clean(string $type, $value)
{
    if (!is_scalar($value)) $value = '';
    $value = str_replace("\r\n", "\n", (string) $value);
    switch ($type) {
        case 'text': return sanitize_text_field($value);
        case 'textarea': return trim(sanitize_textarea_field($value));
        case 'heading': return trim(implode("\n", array_map('trim', explode("\n", sanitize_textarea_field($value)))));
        case 'rich':
            $html = wp_kses(trim($value), ['a' => ['href' => true, 'target' => true, 'rel' => true], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'br' => []]);
            // An HTML parser reads href however it is written (quotes, case, entities) and writes it back normalised.
            $tags = new WP_HTML_Tag_Processor($html);
            while ($tags->next_tag('a')) {
                $href = $tags->get_attribute('href');
                if (is_string($href)) $tags->set_attribute('href', bl_content_local_url($href));
            }
            return $tags->get_updated_html();
        case 'image': case 'url': return bl_content_clean_url($value);
        case 'digits': return preg_replace('/\D+/', '', $value);
    }
    return '';
}

/** Every field of a page, cleaned, from submitted (or default) data shaped like the content itself. */
function bl_content_clean_all(string $page, array $input): array
{
    $out = [];
    foreach (bl_content_schema($page) as $section) foreach ($section['fields'] as $field) {
        $raw = bl_content_get($input, $field['key']);
        switch ($field['type']) {
            case 'list':
                $rows = [];
                foreach (array_values(is_array($raw) ? $raw : []) as $row) {
                    if (!is_array($row)) continue;
                    $clean = [];
                    foreach ($field['fields'] as $name => $sub) $clean[$name] = bl_content_clean($sub['type'], $row[$name] ?? '');
                    if (implode('', $clean) !== '') $rows[] = $clean;
                }
                $value = $rows;
                break;
            case 'terms':
                $value = [];
                foreach ($field['rows'] as $name => $_) foreach (['label', 'value', 'unit'] as $part) $value[$name][$part] = bl_content_clean('text', $raw[$name][$part] ?? '');
                break;
            case 'loading':
                $value = [];
                foreach (is_array($raw) ? $raw : [] as $slug => $fit) {
                    $slug = sanitize_title((string) $slug);
                    $fit = is_array($fit) ? array_values($fit) : [];
                    $numbers = array_map(static fn($n) => is_numeric($n) ? max(0, min(99, (int) $n)) : null, [$fit[0] ?? '', $fit[1] ?? '']);
                    $value[$slug] = in_array(null, $numbers, true) ? [] : $numbers;
                }
                break;
            default:
                $value = bl_content_clean($field['type'], $raw ?? '');
        }
        bl_content_set($out, $field['key'], $value);
    }
    return $out;
}

/** What differs from the defaults: groups compare key by key, lists and single values as a whole. */
function bl_content_diff(array $value, array $default): array
{
    $diff = [];
    foreach ($value as $key => $item) {
        $base = $default[$key] ?? null;
        if (is_array($item) && is_array($base) && !array_is_list($base) && $base !== []) {
            if ($sub = bl_content_diff($item, $base)) $diff[$key] = $sub;
        } elseif ($item !== $base) {
            $diff[$key] = $item;
        }
    }
    return $diff;
}

/* ---------- Screens ---------- */

add_action('admin_menu', static function (): void {
    add_menu_page('网站内容', '网站内容', 'edit_pages', 'bl-content-home', static fn() => bl_content_screen('home'), 'dashicons-edit-large', 21);
    foreach (BL_CONTENT_PAGES as $page => $info) {
        // The first entry shares the top-level slug, whose screen is already registered above.
        add_submenu_page('bl-content-home', $info['title'], $info['menu'], 'edit_pages', 'bl-content-' . $page, $page === 'home' ? '' : static fn() => bl_content_screen($page));
    }
});

add_action('admin_enqueue_scripts', static function (string $hook): void {
    if (!str_contains($hook, 'bl-content-')) return;
    wp_enqueue_media();
    $asset = static fn(string $file): array => [get_template_directory_uri() . '/assets/admin/' . $file, (string) filemtime(get_template_directory() . '/assets/admin/' . $file)];
    [$css, $cssVersion] = $asset('content-editor.css');
    [$js, $jsVersion] = $asset('content-editor.js');
    wp_enqueue_style('bl-content-editor', $css, ['bl-admin'], $cssVersion);
    wp_enqueue_script('bl-content-editor', $js, ['media-editor'], $jsVersion, true);
});

function bl_content_screen(string $page): void
{
    if (!isset(BL_CONTENT_PAGES[$page]) || !current_user_can('edit_pages')) wp_die('没有编辑权限。', '', ['response' => 403]);
    $info = BL_CONTENT_PAGES[$page];
    $stored = bl_content_saved_now($page);
    $values = bl_content_resolve($page, $stored);
    // The revision describes what is stored; "changed" marks and reset follow the migrated content the screen shows.
    $revision = bl_content_revision($stored);
    $saved = bl_content_migrate($page, $stored);
    $recovery = bl_content_recovery($page);
    if ($recovery) {
        $values = bl_content_clean_all($page, $recovery['input']);
        $revision = $recovery['revision'];
    }
    $sections = bl_content_schema($page);
    $changed = static fn(array $field): bool => bl_content_get($saved, $field['key']) !== null;
    $count = static fn(array $section): int => count(array_filter($section['fields'], $changed));
    $total = array_sum(array_map($count, $sections));
    echo '<div class="wrap bl-ce"><div class="bl-page-head"><div><h1>' . esc_html($info['title']) . '</h1><p>改这个页面的文字和图片，版式由主题决定。只保存改过的内容（标“已修改”），其余内容跟随主题默认。</p></div>';
    echo '<div class="bl-page-head-actions"><a class="button" href="' . esc_url(home_url($info['path'])) . '" target="_blank" rel="noopener">查看页面<span class="screen-reader-text">（在新窗口打开）</span></a></div></div><hr class="wp-header-end">';
    if (isset($_GET['bl_saved'])) echo '<div class="notice notice-success is-dismissible"><p>已保存。</p></div>';
    if (isset($_GET['bl_reset'])) echo '<div class="notice notice-success is-dismissible"><p>已恢复为主题默认内容。</p></div>';
    if ($recovery) echo '<p role="alert"><span class="bl-chip bl-chip-warn">未保存</span> ' . esc_html($recovery['message']) . ' 输入已保留 24 小时；请在新标签页打开最新内容，对照后再编辑保存。<a target="_blank" rel="noopener" href="' . esc_url(admin_url('admin.php?page=bl-content-' . $page)) . '">打开最新内容</a></p>';
    $open = count($sections) === 1;
    if (count($sections) >= 3) {
        echo '<nav class="bl-jump" aria-label="页面分区"><span class="bl-jump-label">跳到</span>';
        foreach ($sections as $i => $section) echo '<a class="bl-jump-link" href="#bl-ce-' . $i . '">' . esc_html(bl_admin_card_title($section['title'])) . '<span class="bl-chip bl-chip-warn bl-dirty-chip">未保存</span></a>';
        echo '<button type="button" class="button-link bl-jump-all" data-bl-toggle-all aria-expanded="false">全部展开</button></nav>';
    }
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="bl-ce-form"><input type="hidden" name="action" value="bl_save_content"><input type="hidden" name="bl_page" value="' . esc_attr($page) . '">';
    wp_nonce_field('bl_save_content_' . $page);
    echo bl_content_revision_input($revision);
    foreach ($sections as $i => $section) {
        $n = $count($section);
        echo '<details class="bl-panel bl-ce-section" data-bl-card id="bl-ce-' . $i . '"' . ($open ? ' open' : '') . '><summary class="bl-panel-head">';
        echo bl_admin_card_head($i + 1, $section['title'], bl_content_card_meta($section, $values), $n);
        echo '</summary><div class="bl-panel-body">';
        if (!empty($section['note'])) echo '<p class="bl-card-note">' . wp_kses($section['note'], ['a' => ['href' => true]]) . '</p>';
        $group = '';
        foreach ($section['fields'] as $field) {
            $next = $field['group'] ?? '';
            if ($next !== $group) {
                if ($group !== '') echo '</div></div>';
                if ($next !== '') echo '<div class="bl-subgroup"><h3 class="bl-subgroup-title">' . esc_html($next) . '</h3><div class="bl-ce-fields">';
                $group = $next;
            }
            bl_content_field($field, bl_content_get($values, $field['key']), $changed($field));
        }
        if ($group !== '') echo '</div></div>';
        echo '</div></details>';
    }
    echo '<input type="hidden" name="bl_content_complete" value="1"><div class="bl-savebar">';
    submit_button('保存修改', 'primary', 'submit', false);
    echo '<span class="bl-savebar-status" role="status">' . ($recovery ? '有未保存的修改（版本已过期）' : '没有未保存的修改') . '</span>';
    if ($total > 0) echo '<button type="submit" form="bl-ce-reset" class="button bl-danger-button" title="本页有 ' . $total . ' 项内容改过；恢复前保存快照，保留最近 5 份。">整页恢复默认</button>';
    echo '</div></form>';
    if ($total > 0) {
        echo '<form method="post" id="bl-ce-reset" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'确定把整页恢复为主题默认内容吗？恢复前将保存当前内容快照，可撤销。\')"><input type="hidden" name="action" value="bl_reset_content"><input type="hidden" name="bl_page" value="' . esc_attr($page) . '">';
        wp_nonce_field('bl_reset_content_' . $page);
        echo bl_content_revision_input($revision);
        echo '</form>';
    }
    if (get_option('bl_content_snapshots_' . $page, [])) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bl_restore_content"><input type="hidden" name="bl_page" value="' . esc_attr($page) . '">';
        wp_nonce_field('bl_restore_content_' . $page);
        echo bl_content_revision_input($revision);
        submit_button('撤销最近一次恢复默认', 'secondary', 'submit', false);
        echo '</form>';
    }
    echo '</div>';
}

/** A line of the section's current text (its heading, else its first paragraph or text field), so a closed card is still recognisable. */
function bl_content_card_meta(array $section, array $values): string
{
    foreach (['heading', 'textarea', 'rich', 'text'] as $type) {
        foreach ($section['fields'] as $field) {
            if (($field['type'] ?? '') !== $type) continue;
            $text = trim((string) preg_replace('/\s+/', ' ', str_replace('*', '', wp_strip_all_tags((string) bl_content_get($values, $field['key'])))));
            // wp_html_excerpt() shortens by characters and works without the mbstring extension, which WordPress does not require.
            if ($text !== '') return wp_html_excerpt($text, 90, '…');
        }
    }
    return '';
}

function bl_content_name(string $key): string
{
    $parts = explode('.', $key);
    return 'bl_content[' . implode('][', $parts) . ']';
}

function bl_content_input(string $type, string $name, string $id, string $value, string $preview = ''): string
{
    switch ($type) {
        case 'textarea': case 'rich':
            return '<textarea class="large-text" rows="3" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        case 'heading':
            return '<textarea class="large-text bl-ce-heading" rows="' . max(2, substr_count($value, "\n") + 1) . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        case 'image':
            $fallback = $preview !== '' ? bl_asset($preview) : '';
            $src = $value !== '' ? $value : $fallback;
            return '<div class="bl-ce-image' . ($value === '' ? ' is-default' : '') . '" data-fallback="' . esc_url($fallback) . '"><div class="bl-ce-image-preview"><img alt="" src="' . esc_url($src) . '"' . ($src === '' ? ' hidden' : '') . '><span class="bl-chip bl-ce-image-state">主题默认</span></div>'
                . '<div class="bl-ce-image-side"><p class="bl-ce-image-file" data-default="使用主题自带的图片">' . ($value !== '' ? esc_html(wp_basename((string) parse_url($value, PHP_URL_PATH))) : '使用主题自带的图片') . '</p>'
                . '<p class="bl-ce-image-buttons"><button type="button" class="button bl-ce-pick" id="' . esc_attr($id) . '-pick">从媒体库选择</button><button type="button" class="button-link bl-ce-clear">改回主题默认</button></p>'
                . '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"></div></div>';
        case 'url': case 'digits':
            return '<input class="large-text code" type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
    }
    return '<input class="large-text" type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
}

function bl_content_help(array $def): string
{
    return !empty($def['help']) ? '<p class="bl-field-help">' . wp_kses($def['help'], []) . '</p>' : '';
}

function bl_content_field(array $field, $value, bool $changed): void
{
    $name = bl_content_name($field['key']);
    $id = 'bl-ce-' . str_replace('.', '-', $field['key']);
    $label = $field['label'] ?? '';
    $labelled = !in_array($field['type'], ['list', 'loading', 'terms'], true);
    $wide = in_array($field['type'], ['list', 'terms'], true);
    echo '<div class="bl-field bl-ce-field bl-ce-type-' . esc_attr($field['type']) . ($wide ? ' bl-ce-wide' : '') . '">';
    if ($label !== '' || $changed) {
        echo '<div class="bl-field-label">';
        if ($label !== '') echo $labelled ? '<label for="' . esc_attr($id . ($field['type'] === 'image' ? '-pick' : '')) . '">' . esc_html($label) . '</label>' : '<span>' . esc_html($label) . '</span>';
        if ($changed) echo ' <span class="bl-chip bl-chip-info">已修改</span>';
        echo '</div>';
    }
    echo '<div class="bl-field-control">';
    switch ($field['type']) {
        case 'list':
            echo '<ol class="bl-rows bl-ce-rows" data-summary="' . esc_attr(implode(' ', $field['summary'] ?? [array_key_first($field['fields'])])) . '" data-meta="' . esc_attr($field['meta'] ?? '') . '">';
            foreach (array_values((array) $value) as $i => $row) echo bl_content_row($field, $name, $id, (string) $i, is_array($row) ? $row : [], false);
            echo '</ol><template class="bl-ce-row-template">' . bl_content_row($field, $name, $id, '__i__', [], true) . '</template>';
            echo '<p class="bl-ce-rows-add"><button type="button" class="button bl-ce-add"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>' . esc_html($field['add'] ?? '添加') . '</button></p>';
            break;
        case 'terms':
            echo '<div class="bl-ce-table-wrap"><table class="widefat striped bl-ce-table"><thead><tr><th>条款</th><th>标签</th><th>数值（大字）</th><th>单位（小字，可空）</th></tr></thead><tbody>';
            foreach ($field['rows'] as $key => $rowLabel) {
                echo '<tr><th scope="row">' . esc_html($rowLabel) . '</th>';
                foreach (['label' => '标签', 'value' => '数值', 'unit' => '单位'] as $part => $partLabel) echo '<td><input class="widefat" type="text" aria-label="' . esc_attr($rowLabel . ' ' . $partLabel) . '" name="' . esc_attr($name . '[' . $key . '][' . $part . ']') . '" value="' . esc_attr((string) ($value[$key][$part] ?? '')) . '"></td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
            break;
        case 'loading':
            echo '<div class="bl-ce-table-wrap"><table class="widefat striped bl-ce-table bl-ce-loading"><thead><tr><th>型号</th><th>20GP</th><th>40HQ</th></tr></thead><tbody>';
            foreach (bl_products() as $product) {
                $fit = (array) ($value[$product['slug']] ?? []);
                echo '<tr><th scope="row">' . esc_html($product['model'] ?: $product['name']) . '</th>';
                foreach ([0, 1] as $n) echo '<td><input type="number" min="0" max="99" step="1" aria-label="' . esc_attr(($product['model'] ?: $product['name']) . ($n ? ' 40HQ' : ' 20GP')) . '" name="' . esc_attr($name . '[' . $product['slug'] . '][' . $n . ']') . '" value="' . esc_attr(isset($fit[$n]) ? (string) $fit[$n] : '') . '"></td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
            break;
        default:
            echo bl_content_input($field['type'], $name, $id, (string) $value, $field['preview'] ?? '');
    }
    echo bl_content_help($field) . '</div></div>';
}

/** One list row: a head showing its number, its summary and the row tools; a body holding the fields, folded by default. */
function bl_content_row(array $field, string $name, string $id, string $i, array $row, bool $open): string
{
    $summary = trim(implode(' ', array_map(static fn(string $key): string => trim((string) ($row[$key] ?? '')), $field['summary'] ?? [array_key_first($field['fields'])])));
    $meta = isset($field['meta']) ? trim((string) ($row[$field['meta']] ?? '')) : '';
    $body = '';
    foreach ($field['fields'] as $sub => $def) $body .= bl_content_row_field($def, $name . '[' . $i . '][' . $sub . ']', $id . '-' . $i . '-' . $sub, (string) ($row[$sub] ?? ''));
    return '<li class="bl-row bl-ce-row"><div class="bl-row-head"><button type="button" class="bl-ce-toggle" aria-expanded="' . ($open ? 'true' : 'false') . '" aria-controls="' . esc_attr($id . '-' . $i) . '"><span class="dashicons dashicons-arrow-right-alt2 bl-ce-caret" aria-hidden="true"></span>'
        . '<span class="bl-row-title" data-empty="（未填写）">' . esc_html($summary) . '</span><span class="bl-chip bl-ce-row-meta">' . esc_html($meta) . '</span></button>' . bl_content_row_tools() . '</div>'
        . '<div class="bl-row-body" id="' . esc_attr($id . '-' . $i) . '"' . ($open ? '' : ' hidden') . '>' . $body . '</div></li>';
}

function bl_content_row_field(array $def, string $name, string $id, string $value): string
{
    return '<div class="bl-field bl-ce-subfield bl-ce-type-' . esc_attr($def['type']) . '"><div class="bl-field-label"><label for="' . esc_attr($id) . '">' . esc_html($def['label']) . '</label></div><div class="bl-field-control">' . bl_content_input($def['type'], $name, $id, $value) . bl_content_help($def) . '</div></div>';
}

function bl_content_row_tools(): string
{
    $button = static fn(string $class, string $icon, string $label): string => '<button type="button" class="button-link ' . $class . '" title="' . $label . '"><span class="dashicons dashicons-' . $icon . '" aria-hidden="true"></span><span class="screen-reader-text">' . $label . '</span></button>';
    return '<div class="bl-row-tools">' . $button('bl-ce-up', 'arrow-up-alt2', '上移') . $button('bl-ce-down', 'arrow-down-alt2', '下移') . $button('button-link-delete bl-ce-remove', 'trash', '删除') . '</div>';
}

/* ---------- Saving ---------- */

/**
 * A named lock held by one request at a time: a row in the options table whose value is "<time>|<token>". It is taken
 * with INSERT IGNORE (add_option() would overwrite an existing row) and only its owner, matched by token, can release it.
 * A lock older than five minutes belongs to a request PHP has long since stopped, and is taken over with a conditional
 * UPDATE, so of several requests trying at once only one succeeds. Returns the token, or '' after $tries attempts a
 * tenth of a second apart (about five seconds by default).
 */
function bl_lock_acquire(string $name, int $tries = 50): string
{
    global $wpdb;
    $option = 'bl_lock_' . $name;
    for ($try = 0; $try < $tries; $try++) {
        $value = sprintf('%010d|%s', time(), wp_generate_password(20, false));
        if ($wpdb->query($wpdb->prepare("INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'no')", $option, $value))) return $value;
        if ($wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value < %s", $value, $option, sprintf('%010d|', time() - 300)))) return $value;
        if ($try + 1 < $tries) usleep(100000);
    }
    return '';
}

function bl_lock_owned(string $name, string $token): bool
{
    global $wpdb;
    return $token !== '' && $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'bl_lock_' . $name)) === $token;
}

function bl_lock_release(string $name, string $token): void
{
    $GLOBALS['wpdb']->delete($GLOBALS['wpdb']->options, ['option_name' => 'bl_lock_' . $name, 'option_value' => $token]);
}

require_once __DIR__ . '/wp-content-state.php';

/* ---------- Pointers from the page editor ---------- */

/** Which content screen a page belongs to: the static front page, the top-level "support" and "applications" pages, and the application pages under the latter. */
function bl_content_page_for_post(?WP_Post $post): string
{
    if (!$post || $post->post_type !== 'page') return '';
    if ((int) get_option('page_on_front') === $post->ID) return 'home';
    if ($post->post_name === 'support' && !$post->post_parent) return 'support';
    if ($post->post_name === 'applications' && !$post->post_parent) return 'applications';
    // The three application pages sit under Applications and are edited on the same screen.
    $parent = $post->post_parent ? get_post($post->post_parent) : null;
    return $parent && $parent->post_name === 'applications' && !$parent->post_parent ? 'applications' : '';
}

// Their layout comes from the theme and the editor body is not shown, so the page editor points to the right screen.
add_action('enqueue_block_editor_assets', static function (): void {
    $page = bl_content_page_for_post(get_post());
    if (!$page) return;
    $message = '这个页面的文字和图片在“网站内容 → ' . BL_CONTENT_PAGES[$page]['menu'] . '”里修改，编辑器正文不会显示在网站上。这里只管标题、网址和发布状态。';
    wp_add_inline_script('wp-notices', 'wp.domReady(function(){wp.data.dispatch("core/notices").createNotice("warning",' . wp_json_encode($message) . ',{isDismissible:false,actions:[{label:"去修改内容",url:' . wp_json_encode(admin_url('admin.php?page=bl-content-' . $page)) . '}]});});');
    wp_enqueue_script('wp-dom-ready');
});

// On the site itself, the toolbar's "Edit page" for these two pages opens their content screen.
add_action('admin_bar_menu', static function (WP_Admin_Bar $bar): void {
    if (is_admin() || !current_user_can('edit_pages')) return;
    $page = bl_content_page_for_post(get_queried_object() instanceof WP_Post ? get_queried_object() : null);
    if ($page && $bar->get_node('edit')) $bar->add_node(['id' => 'edit', 'title' => '编辑页面内容', 'href' => admin_url('admin.php?page=bl-content-' . $page)]);
}, 81);
