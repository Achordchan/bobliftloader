<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * "网站内容" screens for the home and Support pages. Each screen is built from the field list below; saving keeps only
 * what differs from the defaults in inc/editable.php, so text an editor never touched follows later theme updates.
 */

const BL_CONTENT_PAGES = ['home' => ['title' => '首页内容', 'menu' => '首页', 'path' => '/'], 'support' => ['title' => '采购与售后（Support）页内容', 'menu' => '采购与售后 Support', 'path' => '/support/']];

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
    $seo = static fn(string $extra): array => ['title' => '搜索结果显示', 'note' => '浏览器标签和 Google 搜索结果里的标题与摘要；标题后面会自动加“ | BOB-LIFT”。', 'fields' => [
        ['key' => 'seo.title', 'label' => '页面标题', 'type' => 'text', 'help' => '建议 60 个字符以内。'],
        ['key' => 'seo.description', 'label' => '页面描述', 'type' => 'textarea', 'help' => '建议 150 个字符左右。' . $extra],
    ]];
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
            ['key' => 'downloads.certificates_kind', 'group' => '证书', 'label' => '标签', 'type' => 'text'],
            ['key' => 'downloads.certificates_title', 'group' => '证书', 'label' => '名称', 'type' => 'text'],
            ['key' => 'downloads.certificates_text', 'group' => '证书', 'label' => '说明', 'type' => 'text'],
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
        ['key' => "families.$key.image", 'group' => '产品图', 'label' => '图片', 'type' => 'image', 'preview' => $preview, 'help' => '留空使用主题自带的棚拍图；建议透明或浅色背景。'],
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
        $family('loader', '滑移装载机', 'images/range-loader-studio.webp'),
        $family('dumper', '迷你翻斗车', 'images/range-dumper-studio.webp'),
        ['title' => '简介', 'fields' => [
            ['key' => 'intro.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'intro.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'intro.text', 'label' => '介绍', 'type' => 'textarea'],
            ['key' => 'intro.link', 'label' => '链接文字', 'type' => 'text'],
            ['key' => 'intro.facts', 'label' => '公司数据', 'type' => 'list', 'add' => '添加一项', 'summary' => ['value', 'unit', 'label'], 'fields' => [
                'value' => ['label' => '数字', 'type' => 'text'],
                'unit' => ['label' => '单位', 'type' => 'text', 'help' => $optional],
                'label' => ['label' => '说明', 'type' => 'text'],
            ]],
        ]],
        ['title' => '01 产品系列', 'fields' => [
            ['key' => 'range.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'range.title', 'label' => '标题', 'type' => 'heading', 'help' => '最后的句号显示为蓝色。'],
            ['key' => 'range.description', 'label' => '标题旁说明', 'type' => 'rich', 'help' => '{models} 会替换成当前的型号数量。' . $rich],
            ['key' => 'range.assist', 'group' => '卡片下方', 'label' => '一句话', 'type' => 'text'],
            ['key' => 'range.assist_link', 'group' => '卡片下方', 'label' => '链接文字', 'type' => 'text'],
        ]],
        ['title' => '02 应用场景', 'note' => '三个场景图块的文字与应用详情页共用，暂在主题代码里维护。', 'fields' => [
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
    $values = bl_editable($page);
    $saved = get_option('bl_content_' . $page);
    $saved = is_array($saved) ? $saved : [];
    $sections = bl_content_schema($page);
    $changed = static fn(array $field): bool => bl_content_get($saved, $field['key']) !== null;
    $count = static fn(array $section): int => count(array_filter($section['fields'], $changed));
    $total = array_sum(array_map($count, $sections));
    echo '<div class="wrap bl-ce"><div class="bl-page-head"><div><h1>' . esc_html($info['title']) . '</h1><p>改这个页面的文字和图片，版式由主题决定。只保存改过的内容（标“已修改”），其余内容跟随主题默认。</p></div>';
    echo '<div class="bl-page-head-actions"><a class="button" href="' . esc_url(home_url($info['path'])) . '" target="_blank" rel="noopener">查看页面<span class="screen-reader-text">（在新窗口打开）</span></a></div></div><hr class="wp-header-end">';
    if (isset($_GET['bl_saved'])) echo '<div class="notice notice-success is-dismissible"><p>已保存。</p></div>';
    if (isset($_GET['bl_reset'])) echo '<div class="notice notice-success is-dismissible"><p>已恢复为主题默认内容。</p></div>';
    echo '<div class="bl-ce-layout"><nav class="bl-ce-toc" aria-labelledby="bl-ce-toc-title"><h2 class="bl-ce-toc-title" id="bl-ce-toc-title">页面分区</h2><ol>';
    foreach ($sections as $i => $section) {
        $n = $count($section);
        echo '<li><a href="#bl-ce-' . $i . '"><span class="bl-ce-toc-name">' . esc_html($section['title']) . '</span>' . ($n ? '<span class="bl-chip bl-chip-info" title="已修改 ' . $n . ' 项">' . $n . '</span>' : '') . '<span class="bl-chip bl-chip-warn bl-ce-toc-dirty">未保存</span></a></li>';
    }
    echo '</ol></nav><div class="bl-ce-main">';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="bl-ce-form"><input type="hidden" name="action" value="bl_save_content"><input type="hidden" name="bl_page" value="' . esc_attr($page) . '">';
    wp_nonce_field('bl_save_content_' . $page);
    foreach ($sections as $i => $section) {
        $n = $count($section);
        echo '<section class="bl-panel bl-ce-section" id="bl-ce-' . $i . '" aria-labelledby="bl-ce-' . $i . '-title"><div class="bl-panel-head"><div><h2 id="bl-ce-' . $i . '-title">' . esc_html($section['title']) . '</h2>';
        if (!empty($section['note'])) echo '<p>' . wp_kses($section['note'], ['a' => ['href' => true]]) . '</p>';
        echo '</div>' . ($n ? '<span class="bl-chip bl-chip-info">已修改 ' . $n . ' 项</span>' : '') . '</div><div class="bl-panel-body">';
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
        echo '</div></section>';
    }
    echo '<input type="hidden" name="bl_content_complete" value="1"><div class="bl-savebar bl-ce-savebar">';
    submit_button('保存修改', 'primary', 'submit', false);
    echo '<span class="bl-savebar-status" role="status">没有未保存的修改</span></div></form>';
    if ($saved) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="bl-ce-reset" onsubmit="return confirm(\'确定把整页恢复为主题默认内容吗？已修改的内容会丢失，无法撤销。\')"><input type="hidden" name="action" value="bl_reset_content"><input type="hidden" name="bl_page" value="' . esc_attr($page) . '">';
        wp_nonce_field('bl_reset_content_' . $page);
        echo '<p>本页有 ' . $total . ' 项内容改过。恢复默认会清除全部修改，改回主题自带的文字和图片。</p>';
        echo '<button type="submit" class="button bl-ce-reset-button">整页恢复默认</button>';
        echo '</form>';
    }
    echo '</div></div></div>';
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
                . '<div class="bl-ce-image-side"><p class="bl-ce-image-buttons"><button type="button" class="button bl-ce-pick">从媒体库选择</button><button type="button" class="button-link bl-ce-clear">改回主题默认</button></p>'
                . '<label class="bl-ce-image-url" for="' . esc_attr($id) . '">图片地址</label><input class="large-text code" type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="留空 = 主题默认图片"></div></div>';
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
        if ($label !== '') echo $labelled ? '<label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>' : '<span>' . esc_html($label) . '</span>';
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

function bl_content_request_page(): string
{
    $page = isset($_POST['bl_page']) && is_string($_POST['bl_page']) ? sanitize_key(wp_unslash($_POST['bl_page'])) : '';
    if (!isset(BL_CONTENT_PAGES[$page])) wp_die('未知页面。', '', ['response' => 400]);
    if (!current_user_can('edit_pages')) wp_die('没有编辑权限。', '', ['response' => 403]);
    return $page;
}

add_action('admin_post_bl_save_content', static function (): void {
    $page = bl_content_request_page();
    check_admin_referer('bl_save_content_' . $page);
    // PHP drops fields beyond max_input_vars without an error. The form's last field is a marker: if it did not arrive,
    // the submission is incomplete and saving it would silently lose content, so nothing is saved.
    if (!isset($_POST['bl_content_complete'])) wp_die('提交的内容不完整（可能超出了服务器的 max_input_vars 限制），没有保存任何修改。请返回后减少列表条目，或调高服务器的 max_input_vars。', '', ['response' => 400, 'back_link' => true]);
    $input = isset($_POST['bl_content']) && is_array($_POST['bl_content']) ? wp_unslash($_POST['bl_content']) : [];
    $diff = bl_content_diff(bl_content_clean_all($page, $input), bl_content_clean_all($page, bl_content_defaults($page)));
    // The loading table lists published products only; figures saved for a model not on the form (unpublished for now)
    // are carried over rather than dropped.
    $previous = get_option('bl_content_' . $page);
    if (is_array($previous['containers']['loading'] ?? null)) {
        $posted = is_array($input['containers']['loading'] ?? null) ? array_map('sanitize_title', array_keys($input['containers']['loading'])) : [];
        foreach ($previous['containers']['loading'] as $slug => $fit) if (!in_array((string) $slug, $posted, true)) $diff['containers']['loading'][$slug] = $fit;
    }
    if ($diff) update_option('bl_content_' . $page, $diff);
    else delete_option('bl_content_' . $page);
    wp_safe_redirect(admin_url('admin.php?page=bl-content-' . $page . '&bl_saved=1'));
    exit;
});

add_action('admin_post_bl_reset_content', static function (): void {
    $page = bl_content_request_page();
    check_admin_referer('bl_reset_content_' . $page);
    delete_option('bl_content_' . $page);
    wp_safe_redirect(admin_url('admin.php?page=bl-content-' . $page . '&bl_reset=1'));
    exit;
});

/* ---------- Pointers from the page editor ---------- */

/** Which content screen a page belongs to: the static front page and the top-level "support" page. */
function bl_content_page_for_post(?WP_Post $post): string
{
    if (!$post || $post->post_type !== 'page') return '';
    if ((int) get_option('page_on_front') === $post->ID) return 'home';
    return $post->post_name === 'support' && !$post->post_parent ? 'support' : '';
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
