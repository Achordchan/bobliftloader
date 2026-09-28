<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * "网站内容" screens for the home and Support pages. Each screen is built from the field list below; saving keeps only
 * what differs from the defaults in inc/editable.php, so text an editor never touched follows later theme updates.
 */

const BL_CONTENT_PAGES = ['home' => ['title' => '首页内容', 'menu' => '首页', 'path' => '/'], 'support' => ['title' => '采购与售后（Support）页内容', 'menu' => '采购与售后 Support', 'path' => '/support/']];

function bl_content_schema(string $page): array
{
    $heading = '换行即分行；用 *星号* 包住的文字显示为强调色。';
    $rich = '换行即分行；可以用 &lt;a href="…"&gt;链接&lt;/a&gt;、&lt;strong&gt;加粗&lt;/strong&gt;。';
    $image = '留空使用主题自带的照片。建议上传宽度 1600px 以上的横图。';
    if ($page === 'support') return [
        ['title' => '搜索结果显示', 'note' => '浏览器标签和 Google 搜索结果里的标题与摘要；标题后面会自动加“ | BOB-LIFT”。', 'fields' => [
            ['key' => 'seo.title', 'label' => '页面标题', 'type' => 'text', 'help' => '建议 60 个字符以内。'],
            ['key' => 'seo.description', 'label' => '页面描述', 'type' => 'textarea', 'help' => '建议 150 个字符左右。采购条款改了，记得同步这里的描述。'],
        ]],
        ['title' => '首屏', 'fields' => [
            ['key' => 'hero.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'hero.title', 'label' => '大标题', 'type' => 'heading', 'help' => '每行一段；整行用 *星号* 包住显示为蓝色。'],
            ['key' => 'hero.text', 'label' => '右侧介绍', 'type' => 'textarea'],
            ['key' => 'hero.link', 'label' => '向下链接文字', 'type' => 'text'],
            ['key' => 'hero.image', 'label' => '大图', 'type' => 'image', 'help' => $image],
            ['key' => 'hero.alt', 'label' => '大图替代文字（给看不到图片的访客和搜索引擎）', 'type' => 'text'],
            ['key' => 'hero.caption', 'label' => '大图说明', 'type' => 'text'],
        ]],
        ['title' => '采购条款', 'note' => '显示在 Support 首屏下方，产品页的“质保 / 交期 / 起订量”也读这里，改一处全站同步。', 'fields' => [
            ['key' => 'terms', 'type' => 'terms', 'rows' => ['moq' => '起订量', 'payment' => '付款方式', 'lead' => '交期', 'warranty' => '质保']],
        ]],
        ['title' => '01 采购流程', 'fields' => [
            ['key' => 'buying.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'buying.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading . ' 步骤数量变了记得改标题里的数字。'],
            ['key' => 'buying.intro', 'label' => '标题旁说明', 'type' => 'textarea'],
            ['key' => 'buying.steps', 'label' => '步骤', 'type' => 'list', 'add' => '添加步骤', 'fields' => [
                'title' => ['label' => '步骤名', 'type' => 'text'],
                'text' => ['label' => '说明', 'type' => 'textarea'],
                'link_label' => ['label' => '链接文字（可空）', 'type' => 'text'],
                'link_url' => ['label' => '链接地址（可空，如 /contact/ 或 #containers）', 'type' => 'url'],
            ]],
        ]],
        ['title' => '装柜数量', 'fields' => [
            ['key' => 'containers.title', 'label' => '标题', 'type' => 'text'],
            ['key' => 'containers.text', 'label' => '说明', 'type' => 'textarea'],
            ['key' => 'containers.loading', 'label' => '每柜可装台数（约数）', 'type' => 'loading', 'help' => '两格都留空，该型号不出现在表里。'],
            ['key' => 'containers.note', 'label' => '表格下方备注', 'type' => 'textarea'],
        ]],
        ['title' => '02 售后', 'fields' => [
            ['key' => 'after.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'after.title', 'label' => '标题', 'type' => 'heading', 'help' => '换行即分行；*星号* 里的文字换一种颜色显示。'],
            ['key' => 'after.intro', 'label' => '标题旁说明', 'type' => 'textarea'],
            ['key' => 'after.warranty_eyebrow', 'label' => '质保 · 小标题', 'type' => 'text'],
            ['key' => 'after.warranty_title', 'label' => '质保 · 标题', 'type' => 'text'],
            ['key' => 'after.warranty_text', 'label' => '质保 · 说明', 'type' => 'textarea'],
            ['key' => 'after.service_eyebrow', 'label' => '报修流程 · 小标题', 'type' => 'text'],
            ['key' => 'after.service_steps', 'label' => '报修流程', 'type' => 'list', 'add' => '添加一步', 'fields' => [
                'title' => ['label' => '步骤名', 'type' => 'text'],
                'text' => ['label' => '说明', 'type' => 'textarea'],
            ]],
            ['key' => 'after.parts_image', 'label' => '配件 · 图片', 'type' => 'image', 'help' => '留空使用主题自带的照片（3:2 横图显示）。'],
            ['key' => 'after.parts_alt', 'label' => '配件 · 图片替代文字', 'type' => 'text'],
            ['key' => 'after.parts_eyebrow', 'label' => '配件 · 小标题', 'type' => 'text'],
            ['key' => 'after.parts_title', 'label' => '配件 · 标题', 'type' => 'text'],
            ['key' => 'after.parts_text', 'label' => '配件 · 说明', 'type' => 'textarea'],
            ['key' => 'after.button', 'label' => '报修按钮文字', 'type' => 'text'],
            ['key' => 'after.whatsapp_label', 'label' => 'WhatsApp 链接文字', 'type' => 'text'],
            ['key' => 'after.whatsapp_number', 'label' => 'WhatsApp 号码（含国家码，只填数字；留空则不显示）', 'type' => 'digits'],
            ['key' => 'after.whatsapp_message', 'label' => 'WhatsApp 预填消息', 'type' => 'text'],
        ]],
        ['title' => '03 资料下载', 'note' => '画册 PDF 本身在“设置 → BOB-LIFT 资料下载”更换；视频条目由产品视频自动生成。', 'fields' => [
            ['key' => 'downloads.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'downloads.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'downloads.intro', 'label' => '标题旁说明', 'type' => 'textarea'],
            ['key' => 'downloads.catalogue_title', 'label' => '画册 · 名称', 'type' => 'text'],
            ['key' => 'downloads.catalogue_text', 'label' => '画册 · 说明', 'type' => 'text'],
            ['key' => 'downloads.sheets_title', 'label' => '规格表 · 名称', 'type' => 'text'],
            ['key' => 'downloads.sheets_text', 'label' => '规格表 · 说明', 'type' => 'text'],
            ['key' => 'downloads.certificates_kind', 'label' => '证书 · 标签', 'type' => 'text'],
            ['key' => 'downloads.certificates_title', 'label' => '证书 · 名称', 'type' => 'text'],
            ['key' => 'downloads.certificates_text', 'label' => '证书 · 说明', 'type' => 'text'],
            ['key' => 'downloads.videos_title', 'label' => '视频 · 名称', 'type' => 'text'],
        ]],
        ['title' => '04 常见问题', 'note' => '问答也会作为 FAQ 结构化数据提供给搜索引擎。相邻且“分组”相同的问题归在同一个小标题下。', 'fields' => [
            ['key' => 'faq.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'faq.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'faq.intro', 'label' => '左侧说明', 'type' => 'textarea'],
            ['key' => 'faq.link', 'label' => '提问链接文字', 'type' => 'text'],
            ['key' => 'faq.items', 'label' => '问答', 'type' => 'list', 'add' => '添加问答', 'fields' => [
                'group' => ['label' => '分组', 'type' => 'text'],
                'q' => ['label' => '问题', 'type' => 'text'],
                'a' => ['label' => '回答', 'type' => 'textarea'],
                'link_label' => ['label' => '链接文字（可空）', 'type' => 'text'],
                'link_url' => ['label' => '链接地址（可空）', 'type' => 'url'],
            ]],
        ]],
    ];

    $family = static fn(string $key, string $name): array => ['title' => $name . '卡片', 'note' => '型号数量、载荷和斗容由产品数据自动计算。', 'fields' => [
        ['key' => "families.$key.label", 'label' => '名称', 'type' => 'text'],
        ['key' => "families.$key.title", 'label' => '标题', 'type' => 'heading', 'help' => '换行即分行。'],
        ['key' => "families.$key.description", 'label' => '介绍', 'type' => 'textarea'],
        ['key' => "families.$key.image", 'label' => '产品图', 'type' => 'image', 'help' => '留空使用主题自带的棚拍图；建议透明或浅色背景。'],
        ['key' => "families.$key.alt", 'label' => '产品图替代文字', 'type' => 'text'],
    ]];
    return [
        ['title' => '首屏', 'fields' => [
            ['key' => 'hero.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'hero.title', 'label' => '大标题', 'type' => 'heading', 'help' => '每行一段，最后的句号显示为蓝色。'],
            ['key' => 'hero.button', 'label' => '按钮文字（进入产品）', 'type' => 'text'],
            ['key' => 'hero.link', 'label' => '链接文字（去联系页）', 'type' => 'text'],
            ['key' => 'hero.image', 'label' => '大图（宽屏）', 'type' => 'image', 'help' => '留空使用主题自带的首屏图。建议 2560×1067 左右的横图。'],
            ['key' => 'hero.image_mobile', 'label' => '大图（手机）', 'type' => 'image', 'help' => '留空时手机也用上面的宽屏图；两张都空则用主题自带的图。'],
            ['key' => 'hero.alt', 'label' => '大图替代文字', 'type' => 'text'],
        ]],
        $family('loader', '滑移装载机'),
        $family('dumper', '迷你翻斗车'),
        ['title' => '简介', 'fields' => [
            ['key' => 'intro.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'intro.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'intro.text', 'label' => '介绍', 'type' => 'textarea'],
            ['key' => 'intro.link', 'label' => '链接文字', 'type' => 'text'],
            ['key' => 'intro.facts', 'label' => '公司数据', 'type' => 'list', 'add' => '添加一项', 'fields' => [
                'value' => ['label' => '数字', 'type' => 'text'],
                'unit' => ['label' => '单位（可空）', 'type' => 'text'],
                'label' => ['label' => '说明', 'type' => 'text'],
            ]],
        ]],
        ['title' => '01 产品系列', 'fields' => [
            ['key' => 'range.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'range.title', 'label' => '标题', 'type' => 'heading', 'help' => '最后的句号显示为蓝色。'],
            ['key' => 'range.description', 'label' => '标题旁说明', 'type' => 'rich', 'help' => '{models} 会替换成当前的型号数量。' . $rich],
            ['key' => 'range.assist', 'label' => '卡片下方一句话', 'type' => 'text'],
            ['key' => 'range.assist_link', 'label' => '卡片下方链接文字', 'type' => 'text'],
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
            ['key' => 'approach.link', 'label' => '链接文字（去关于页）', 'type' => 'text'],
            ['key' => 'approach.image', 'label' => '照片', 'type' => 'image', 'help' => $image],
            ['key' => 'approach.alt', 'label' => '照片替代文字', 'type' => 'text'],
            ['key' => 'approach.caption', 'label' => '照片说明', 'type' => 'text'],
            ['key' => 'approach.steps', 'label' => '三步', 'type' => 'list', 'add' => '添加一步', 'fields' => [
                'title' => ['label' => '标题', 'type' => 'text'],
                'text' => ['label' => '说明', 'type' => 'rich', 'help' => $rich],
            ]],
        ]],
        ['title' => '公司动态', 'note' => '文章列表自动显示最新 3 篇企业新闻。', 'fields' => [
            ['key' => 'journal.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'journal.title', 'label' => '标题', 'type' => 'text'],
            ['key' => 'journal.link', 'label' => '链接文字', 'type' => 'text'],
        ]],
        ['title' => '底部联系', 'fields' => [
            ['key' => 'contact.eyebrow', 'label' => '小标题', 'type' => 'text'],
            ['key' => 'contact.title', 'label' => '标题', 'type' => 'heading', 'help' => $heading],
            ['key' => 'contact.text', 'label' => '说明', 'type' => 'rich', 'help' => $rich],
            ['key' => 'contact.email', 'label' => '邮箱', 'type' => 'email'],
            ['key' => 'contact.signoff', 'label' => '底部一句话', 'type' => 'text'],
        ]],
        ['title' => '搜索结果显示', 'note' => '浏览器标签和 Google 搜索结果里的标题与摘要；标题后面会自动加“ | BOB-LIFT”。', 'fields' => [
            ['key' => 'seo.title', 'label' => '页面标题', 'type' => 'text', 'help' => '建议 60 个字符以内。'],
            ['key' => 'seo.description', 'label' => '页面描述', 'type' => 'textarea', 'help' => '建议 150 个字符左右。'],
        ]],
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

/** Links to this site are kept as paths (/contact/), so the content survives a move to another domain. */
function bl_content_local_url(string $url): string
{
    $home = untrailingslashit(home_url());
    return $home !== '' && str_starts_with($url, $home . '/') ? substr($url, strlen($home)) : $url;
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
            return preg_replace_callback('~href="([^"]*)"~', static fn($m) => 'href="' . esc_attr(bl_content_local_url(html_entity_decode($m[1]))) . '"', $html);
        case 'image': case 'url': return bl_content_clean_url($value);
        case 'email': return sanitize_email($value);
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
                foreach (array_slice(array_values(is_array($raw) ? $raw : []), 0, 40) as $row) {
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
    wp_enqueue_style('bl-content-editor', $css, [], $cssVersion);
    wp_enqueue_script('bl-content-editor', $js, ['media-editor'], $jsVersion, true);
});

function bl_content_screen(string $page): void
{
    if (!isset(BL_CONTENT_PAGES[$page]) || !current_user_can('edit_pages')) wp_die('没有编辑权限。', '', ['response' => 403]);
    $info = BL_CONTENT_PAGES[$page];
    $values = bl_editable($page);
    $saved = get_option('bl_content_' . $page);
    $saved = is_array($saved) ? $saved : [];
    echo '<div class="wrap bl-ce"><h1 class="wp-heading-inline">' . esc_html($info['title']) . '</h1> <a class="page-title-action" href="' . esc_url(home_url($info['path'])) . '" target="_blank" rel="noopener">查看页面</a><hr class="wp-header-end">';
    if (isset($_GET['bl_saved'])) echo '<div class="notice notice-success is-dismissible"><p>已保存。</p></div>';
    if (isset($_GET['bl_reset'])) echo '<div class="notice notice-success is-dismissible"><p>已恢复为主题默认内容。</p></div>';
    echo '<p class="bl-ce-lead">页面的版式由主题决定，这里改文字和图片。只有改过的内容会被保存，标“已修改”；其余内容跟随主题默认。</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="bl-ce-form"><input type="hidden" name="action" value="bl_save_content"><input type="hidden" name="bl_page" value="' . esc_attr($page) . '">';
    wp_nonce_field('bl_save_content_' . $page);
    echo '<template class="bl-ce-row-tools-template">' . bl_content_row_tools() . '</template>';
    echo '<nav class="bl-ce-toc" aria-label="分区">';
    foreach (bl_content_schema($page) as $i => $section) echo '<a href="#bl-ce-' . $i . '">' . esc_html($section['title']) . '</a>';
    echo '</nav>';
    foreach (bl_content_schema($page) as $i => $section) {
        echo '<section class="bl-ce-section" id="bl-ce-' . $i . '"><h2>' . esc_html($section['title']) . '</h2>';
        if (!empty($section['note'])) echo '<p class="description">' . esc_html($section['note']) . '</p>';
        foreach ($section['fields'] as $field) bl_content_field($field, bl_content_get($values, $field['key']), bl_content_get($saved, $field['key']) !== null);
        echo '</section>';
    }
    echo '<div class="bl-ce-actions">';
    submit_button('保存修改', 'primary large', 'submit', false);
    echo ' <a class="button button-large" href="' . esc_url(home_url($info['path'])) . '" target="_blank" rel="noopener">查看页面</a></div></form>';
    if ($saved) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="bl-ce-reset" onsubmit="return confirm(\'确定把整页恢复为主题默认内容吗？已修改的内容会丢失。\')"><input type="hidden" name="action" value="bl_reset_content"><input type="hidden" name="bl_page" value="' . esc_attr($page) . '">';
        wp_nonce_field('bl_reset_content_' . $page);
        submit_button('整页恢复默认', 'link-delete', 'submit', false);
        echo '</form>';
    }
    echo '</div>';
}

function bl_content_name(string $key): string
{
    $parts = explode('.', $key);
    return 'bl_content[' . implode('][', $parts) . ']';
}

function bl_content_input(string $type, string $name, string $id, string $value): string
{
    switch ($type) {
        case 'textarea': case 'rich':
            return '<textarea class="large-text" rows="3" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        case 'heading':
            return '<textarea class="large-text bl-ce-heading" rows="' . max(2, substr_count($value, "\n") + 1) . '" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        case 'image':
            $preview = $value !== '' ? '<img src="' . esc_url($value) . '" alt="">' : '<span>主题默认</span>';
            return '<div class="bl-ce-image"><div class="bl-ce-image-preview">' . $preview . '</div><div><input class="large-text code" type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="留空 = 主题默认图片"><p><button type="button" class="button bl-ce-pick">从媒体库选择</button> <button type="button" class="button-link bl-ce-clear">恢复默认图片</button></p></div></div>';
        case 'email':
            return '<input class="regular-text" type="email" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        case 'url': case 'digits':
            return '<input class="large-text code" type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
    }
    return '<input class="large-text" type="text" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
}

function bl_content_field(array $field, $value, bool $changed): void
{
    $name = bl_content_name($field['key']);
    $id = 'bl-ce-' . str_replace('.', '-', $field['key']);
    $badge = $changed ? ' <span class="bl-ce-changed">已修改</span>' : '';
    echo '<div class="bl-ce-field bl-ce-type-' . esc_attr($field['type']) . '">';
    if (!empty($field['label'])) echo '<label class="bl-ce-label" for="' . esc_attr($id) . '">' . esc_html($field['label']) . $badge . '</label>';
    elseif ($changed) echo '<p class="bl-ce-label">' . $badge . '</p>';
    switch ($field['type']) {
        case 'list':
            $template = '';
            foreach ($field['fields'] as $sub => $def) $template .= bl_content_row_field($def, $name . '[__i__][' . $sub . ']', $id . '-__i__-' . $sub, '');
            echo '<ol class="bl-ce-list" data-template="' . esc_attr($template) . '">';
            foreach ((array) $value as $i => $row) {
                echo '<li class="bl-ce-row"><div class="bl-ce-row-fields">';
                foreach ($field['fields'] as $sub => $def) echo bl_content_row_field($def, $name . '[' . $i . '][' . $sub . ']', $id . '-' . $i . '-' . $sub, (string) ($row[$sub] ?? ''));
                echo '</div>' . bl_content_row_tools() . '</li>';
            }
            echo '</ol><p><button type="button" class="button bl-ce-add">' . esc_html($field['add'] ?? '添加') . '</button></p>';
            break;
        case 'terms':
            echo '<table class="widefat striped bl-ce-table"><thead><tr><th>条款</th><th>标签</th><th>数值（大字）</th><th>单位（小字，可空）</th></tr></thead><tbody>';
            foreach ($field['rows'] as $key => $label) {
                echo '<tr><th scope="row">' . esc_html($label) . '</th>';
                foreach (['label', 'value', 'unit'] as $part) echo '<td><input class="widefat" type="text" aria-label="' . esc_attr($label . ' ' . $part) . '" name="' . esc_attr($name . '[' . $key . '][' . $part . ']') . '" value="' . esc_attr((string) ($value[$key][$part] ?? '')) . '"></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            break;
        case 'loading':
            echo '<table class="widefat striped bl-ce-table bl-ce-loading"><thead><tr><th>型号</th><th>20GP</th><th>40HQ</th></tr></thead><tbody>';
            foreach (bl_products() as $product) {
                $fit = (array) ($value[$product['slug']] ?? []);
                echo '<tr><th scope="row">' . esc_html($product['model'] ?: $product['name']) . '</th>';
                foreach ([0, 1] as $n) echo '<td><input type="number" min="0" max="99" step="1" aria-label="' . esc_attr(($product['model'] ?: $product['name']) . ($n ? ' 40HQ' : ' 20GP')) . '" name="' . esc_attr($name . '[' . $product['slug'] . '][' . $n . ']') . '" value="' . esc_attr(isset($fit[$n]) ? (string) $fit[$n] : '') . '"></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            break;
        default:
            echo bl_content_input($field['type'], $name, $id, (string) $value);
    }
    if (!empty($field['help'])) echo '<p class="description">' . wp_kses($field['help'], []) . '</p>';
    echo '</div>';
}

function bl_content_row_field(array $def, string $name, string $id, string $value): string
{
    return '<div class="bl-ce-subfield bl-ce-type-' . esc_attr($def['type']) . '"><label for="' . esc_attr($id) . '">' . esc_html($def['label']) . '</label>' . bl_content_input($def['type'], $name, $id, $value) . (!empty($def['help']) ? '<p class="description">' . wp_kses($def['help'], []) . '</p>' : '') . '</div>';
}

function bl_content_row_tools(): string
{
    return '<div class="bl-ce-row-tools"><button type="button" class="button-link bl-ce-up" aria-label="上移">↑</button><button type="button" class="button-link bl-ce-down" aria-label="下移">↓</button><button type="button" class="button-link button-link-delete bl-ce-remove">删除</button></div>';
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
    $input = isset($_POST['bl_content']) && is_array($_POST['bl_content']) ? wp_unslash($_POST['bl_content']) : [];
    $diff = bl_content_diff(bl_content_clean_all($page, $input), bl_content_clean_all($page, bl_content_defaults($page)));
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
