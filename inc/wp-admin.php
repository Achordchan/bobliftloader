<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Edit screens for products and news. Products use the classic screen so their data (model, specs, photos, videos)
 * leads the page, in boxes placed under the title; news keeps the block editor for writing, with its settings in the
 * sidebar and the event details under the text. Both save into the post's _bl_record.
 */
add_filter('use_block_editor_for_post_type', static fn(bool $use, string $type): bool => $type === 'bl_product' ? false : $use, 10, 2);
add_filter('enter_title_here', static fn(string $text, WP_Post $post): string => $post->post_type === 'bl_product' ? '产品名称（英文，显示在前台）' : $text, 10, 2);

add_action('add_meta_boxes_bl_product', static function (): void {
    add_meta_box('bl-product-basics', '基本信息', 'bl_product_basics_box', 'bl_product', 'bl_main', 'high');
    add_meta_box('bl-product-specs', '规格参数', 'bl_product_specs_box', 'bl_product', 'bl_main', 'high');
    add_meta_box('bl-product-images', '图片', 'bl_product_images_box', 'bl_product', 'bl_main', 'high');
    add_meta_box('bl-product-source', '来源', 'bl_product_source_box', 'bl_product', 'bl_main', 'low');
    remove_meta_box('pageparentdiv', 'bl_product', 'side');
    add_meta_box('bl-product-order', '排序', 'bl_product_order_box', 'bl_product', 'side', 'default');
    remove_meta_box('postexcerpt', 'bl_product', 'normal');
    add_meta_box('postexcerpt', '卡片摘要', 'bl_product_excerpt_box', 'bl_product', 'normal', 'high');
});

add_action('add_meta_boxes_bl_news', static function (): void {
    add_meta_box('bl-news-section', '栏目与来源', 'bl_news_section_box', 'bl_news', 'side', 'high');
    add_meta_box('bl-news-image', '封面补充', 'bl_news_image_box', 'bl_news', 'side', 'default');
    add_meta_box('bl-news-products', '相关产品', 'bl_news_products_box', 'bl_news', 'side', 'default');
    add_meta_box('bl-news-event', '展会 / 活动', 'bl_news_event_box', 'bl_news', 'normal', 'high');
});

// The product boxes sit between the title and the description editor, in the order the page is built.
add_action('edit_form_after_title', static function (WP_Post $post): void {
    if ($post->post_type !== 'bl_product') return;
    wp_nonce_field('bl_save_record', 'bl_record_nonce');
    do_meta_boxes(get_current_screen(), 'bl_main', $post);
    echo '<div class="bl-editor-head"><h2>详细介绍 <span class="bl-chip">可选</span></h2><p>填写后，产品页参数表上方会多一段 Overview 介绍（英文）；留空则不显示这一段。</p></div>';
});

add_action('admin_enqueue_scripts', static function (string $hook): void {
    $screen = get_current_screen();
    if (!in_array($hook, ['post.php', 'post-new.php'], true) || !$screen || !in_array($screen->post_type, ['bl_product', 'bl_news'], true)) return;
    $dir = get_template_directory();
    $uri = get_template_directory_uri();
    wp_enqueue_style('bl-post-edit', $uri . '/assets/admin/post-edit.css', ['bl-admin'], (string) filemtime($dir . '/assets/admin/post-edit.css'));
    if ($screen->post_type === 'bl_product') wp_enqueue_media();
    $deps = $screen->post_type === 'bl_product' ? ['jquery', 'jquery-ui-sortable'] : ['wp-data', 'wp-preferences'];
    wp_enqueue_script('bl-post-edit', $uri . '/assets/admin/post-edit.js', $deps, (string) filemtime($dir . '/assets/admin/post-edit.js'), true);
});

function bl_admin_record(WP_Post $post): array
{
    $record = get_post_meta($post->ID, '_bl_record', true);
    return is_array($record) ? $record : [];
}

/** One labelled row: label on the left, control and grey help on the right (stacked in the sidebar). */
function bl_admin_field(string $label, string $for, string $control, string $help = ''): void
{
    $label = $for ? '<label class="bl-field-label" for="' . esc_attr($for) . '">' . esc_html($label) . '</label>' : '<span class="bl-field-label">' . esc_html($label) . '</span>';
    echo '<div class="bl-field">' . $label . '<div class="bl-field-control">' . $control . ($help ? '<p class="bl-field-help">' . $help . '</p>' : '') . '</div></div>';
}

function bl_admin_input(string $id, string $name, string $value, string $type = 'text', string $extra = ''): string
{
    return '<input type="' . esc_attr($type) . '"' . ($id ? ' id="' . esc_attr($id) . '"' : '') . ' name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . $extra . '>';
}

function bl_admin_select(string $id, string $name, array $options, string $current): string
{
    $html = '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
    foreach ($options as $value => $label) $html .= '<option value="' . esc_attr((string) $value) . '"' . selected($current, (string) $value, false) . '>' . esc_html($label) . '</option>';
    return $html . '</select>';
}

/** A small icon button for row tools (move, remove). */
function bl_admin_tool(string $action, string $icon, string $label): string
{
    return '<button type="button" class="button-link bl-tool" data-bl="' . esc_attr($action) . '" aria-label="' . esc_attr($label) . '" title="' . esc_attr($label) . '"><span class="dashicons dashicons-' . esc_attr($icon) . '" aria-hidden="true"></span></button>';
}

function bl_admin_row_tools(): string
{
    return '<span class="bl-row-tools">' . bl_admin_tool('up', 'arrow-up-alt2', '上移') . bl_admin_tool('down', 'arrow-down-alt2', '下移') . bl_admin_tool('remove', 'trash', '删除') . '</span>';
}

function bl_product_basics_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    bl_admin_field('型号', 'bl-model', bl_admin_input('bl-model', 'bl_record[model]', (string) ($record['model'] ?? ''), 'text', ' class="regular-text" placeholder="BL-T360-H"'), '产品页大标题和产品卡片上显示的型号。');
    bl_admin_field('产品分类', 'bl-category', bl_admin_select('bl-category', 'bl_record[category]', ['skid-steer-loaders' => '滑移装载机', 'mini-dumpers' => '迷你翻斗车'], (string) ($record['category'] ?? 'skid-steer-loaders')), '决定产品出现在哪个分类页。');
}

function bl_product_spec_row(string $key, string $value): string
{
    return '<li class="bl-row"><div class="bl-row-head">' . bl_admin_input('', 'bl_record[specs][key][]', $key, 'text', ' class="bl-spec-key" list="bl-spec-keys" aria-label="参数名" placeholder="参数名，如 Rated load"') . bl_admin_input('', 'bl_record[specs][value][]', $value, 'text', ' class="bl-spec-value" aria-label="参数值" placeholder="参数值，如 1,000 kg"') . bl_admin_row_tools() . '</div></li>';
}

function bl_product_specs_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    echo '<p class="bl-box-intro">产品页“Specifications”参数表，按这里的顺序显示。参数名和参数值都用英文。</p>';
    echo '<div class="bl-specs"><div class="bl-specs-head" aria-hidden="true"><span>参数名</span><span>参数值</span></div><ol class="bl-rows bl-rows-compact" data-bl-rows="specs">';
    // A new product starts with the three parameters the filters read; rows left without a value are not saved.
    foreach ((array) ($record['specs'] ?? []) ?: ['Rated load' => '', 'Bucket capacity' => '', 'Engine power' => ''] as $key => $value) echo bl_product_spec_row((string) $key, (string) $value);
    echo '</ol><template data-bl-template="specs">' . bl_product_spec_row('', '') . '</template>';
    echo '<p class="bl-rows-add"><button type="button" class="button" data-bl="add" data-bl-target="specs"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> 添加参数</button></p></div>';
    // Names already used on other products, offered while typing so the same parameter is always spelled the same way.
    $keys = ['Rated load', 'Bucket capacity', 'Engine power'];
    foreach (get_posts(['post_type' => 'bl_product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']) as $id) {
        $other = get_post_meta($id, '_bl_record', true);
        if (is_array($other)) $keys = array_merge($keys, array_map('strval', array_keys((array) ($other['specs'] ?? []))));
    }
    echo '<datalist id="bl-spec-keys">';
    foreach (array_unique($keys) as $key) echo '<option value="' . esc_attr($key) . '">';
    echo '</datalist>';
    $filters = [['额定载重', (int) ($record['load'] ?? 0) ?: '', ' kg'], ['斗容', (string) ($record['capacity'] ?? ''), ' m³'], ['功率', (string) ($record['power'] ?? ''), ' kW']];
    echo '<div class="bl-spec-filters"><p class="bl-field-help">筛选和首页范围从三项参数自动计算，参数名要一字不差：<code>Rated load</code> 填“1,000 kg”，<code>Bucket capacity</code> 填“0.5 m³”，<code>Engine power</code> 填“12.1 kW”。</p><p class="bl-spec-chips"><span class="bl-spec-chips-label">当前筛选值（保存后更新）</span>';
    foreach ($filters as [$label, $value, $unit]) echo $value !== '' ? '<span class="bl-chip bl-chip-info">' . esc_html($label . ' ' . $value . $unit) . '</span>' : '<span class="bl-chip">' . esc_html($label . ' 未识别') . '</span>';
    echo '</p></div>';
}

function bl_product_gallery_item(string $path): string
{
    return '<li class="bl-gallery-item" title="' . esc_attr($path) . '"><img src="' . esc_url(bl_wp_image($path)) . '" alt=""><input type="hidden" name="bl_record[gallery][]" value="' . esc_attr($path) . '"><span class="bl-gallery-tools">' . bl_admin_tool('up', 'arrow-left-alt2', '前移') . bl_admin_tool('down', 'arrow-right-alt2', '后移') . bl_admin_tool('remove', 'no-alt', '移除') . '</span></li>';
}

function bl_product_images_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    $image = (string) ($record['image'] ?? '');
    $preview = $image ? '<img class="bl-image-preview" src="' . esc_url(bl_wp_image($image)) . '" alt="">' : '';
    bl_admin_field('主图地址', 'bl-image', bl_admin_input('bl-image', 'bl_record[image]', $image, 'text', ' class="regular-text code"') . $preview, '产品页大图和产品卡片用的图片。右侧“产品主图”设置了就优先用它；没设置时用这里：填图片网址或主题里的 <code>images/</code> 路径。');
    $items = '';
    foreach ((array) ($record['gallery'] ?? []) as $path) $items .= bl_product_gallery_item((string) $path);
    $control = '<ul class="bl-gallery" data-bl-gallery data-bl-base="' . esc_url(get_template_directory_uri() . '/assets/') . '">' . $items . '</ul>'
        . '<template data-bl-template="gallery">' . bl_product_gallery_item('') . '</template>'
        . '<div class="bl-gallery-add"><button type="button" class="button" data-bl="media-gallery"><span class="dashicons dashicons-images-alt2" aria-hidden="true"></span> 从媒体库添加</button>'
        . '<span class="bl-gallery-or">或</span><input type="text" class="regular-text code" data-bl-gallery-path aria-label="图片网址或 images/ 路径" placeholder="图片网址或 images/… 路径"><button type="button" class="button" data-bl="add-path">添加</button></div>';
    bl_admin_field('图库', '', $control, '图库有两张及以上时，产品页大图旁显示缩略图，点击切换；通常把主图也放在第一张。最多 12 张，可用箭头调整顺序或拖动排序。');
}

function bl_product_source_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    bl_admin_field('原始产品网址', 'bl-source', bl_admin_input('bl-source', 'bl_record[source]', (string) ($record['source'] ?? ''), 'url', ' class="regular-text code" placeholder="https://www.boblift.com/…"'), '产品资料的出处，便于以后核对参数；前台不显示。');
}

function bl_product_order_box(WP_Post $post): void
{
    echo '<p class="bl-side-field"><label for="menu_order">排序数字</label>' . bl_admin_input('menu_order', 'menu_order', (string) $post->menu_order, 'number', ' class="small-text" step="1"') . '</p><p class="bl-field-help">数字小的排在前面；产品列表和分类页按这个顺序显示。</p>';
}

function bl_product_excerpt_box(WP_Post $post): void
{
    echo '<label class="screen-reader-text" for="excerpt">卡片摘要</label><textarea rows="3" cols="40" name="excerpt" id="excerpt">' . esc_textarea($post->post_excerpt) . '</textarea>';
    echo '<p class="bl-field-help">一两句英文，用在产品卡片、搜索结果描述和产品页首屏简介。留空则自动截取详细介绍的开头。</p>';
}

function bl_news_section_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    $record['line'] = bl_news_line($record, $post->post_name); // the same line the site shows, so saving keeps it
    wp_nonce_field('bl_save_record', 'bl_record_nonce');
    echo '<div class="bl-field-stack">';
    bl_admin_field('分类', 'bl-category', bl_admin_select('bl-category', 'bl_record[category]', ['' => '不归入栏目', 'product-news' => '产品新闻 Product news', 'customer-stories' => '应用案例 Customer stories', 'industry-news' => '行业新闻 Industry news'], bl_news_category($record)), '“不归入栏目”的文章只出现在“全部”里。');
    bl_admin_field('归属', 'bl-line', bl_admin_select('bl-line', 'bl_record[line]', ['compact' => '紧凑设备（本站新闻）', 'group' => 'BOB-LIFT 集团'], $record['line']), '集团新闻（起重机等）在列表中标注 BOB-LIFT group，并且不归入任何栏目。');
    bl_admin_field('原文网址', 'bl-source', bl_admin_input('bl-source', 'bl_record[source]', (string) ($record['source'] ?? ''), 'url', ' class="code"'), '转载时填写原文地址，文章页会显示原文链接；原创留空。');
    echo '</div>';
}

function bl_news_image_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    echo '<div class="bl-field-stack">';
    bl_admin_field('图片说明', 'bl-caption', bl_admin_input('bl-caption', 'bl_record[caption]', (string) ($record['caption'] ?? '')), '英文，显示在文章顶部大图下方。');
    bl_admin_field('备用图片', 'bl-image', bl_admin_input('bl-image', 'bl_record[image]', (string) ($record['image'] ?? ''), 'text', ' class="code"'), '只有没设置上面的“封面图片”时才用：填图片网址或主题里的 <code>images/</code> 路径。');
    echo '</div>';
}

function bl_news_products_box(WP_Post $post): void
{
    $chosen = (array) (bl_admin_record($post)['products'] ?? []);
    echo '<p class="bl-field-help bl-box-intro">勾选的产品显示在文章右侧栏。</p>';
    $groups = ['skid-steer-loaders' => ['滑移装载机', ''], 'mini-dumpers' => ['迷你翻斗车', '']];
    foreach (get_posts(['post_type' => 'bl_product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']) as $product) {
        $meta = get_post_meta($product->ID, '_bl_record', true);
        $group = is_array($meta) && ($meta['category'] ?? '') === 'mini-dumpers' ? 'mini-dumpers' : 'skid-steer-loaders';
        $slug = $product->post_name;
        $groups[$group][1] .= '<li><label><input type="checkbox" name="bl_record[products][]" value="' . esc_attr($slug) . '"' . checked(in_array($slug, $chosen, true), true, false) . '> ' . esc_html(get_the_title($product)) . '</label></li>';
    }
    echo '<div class="bl-checklist">';
    foreach ($groups as [$title, $items]) if ($items) echo '<p class="bl-subgroup-title">' . esc_html($title) . '</p><ul>' . $items . '</ul>';
    echo '</div>';
}

function bl_news_event_box(WP_Post $post): void
{
    $event = (array) (bl_admin_record($post)['event'] ?? []);
    $value = static fn(string $key): string => (string) ($event[$key] ?? '');
    $input = static fn(string $key, string $type = 'text', string $extra = ' class="regular-text"'): string => bl_admin_input('bl-event-' . $key, 'bl_record[event_' . $key . ']', $value($key), $type, $extra);
    $chip = $value('start') ? '<span class="bl-chip bl-chip-info">文章页显示活动信息</span>' : '<span class="bl-chip">未填写 · 不显示</span>';
    echo '<p class="bl-box-intro">' . $chip . ' 只有展会、活动类新闻才填。填了开始日期，文章页就会显示活动信息条和“还有几天开幕”；留空则整块不显示。以下内容用英文填写。</p>';
    echo '<div class="bl-subgroup bl-subgroup-first"><h3 class="bl-subgroup-title">名称与日期</h3>';
    bl_admin_field('活动名称', 'bl-event-name', $input('name'), '例如 140th Canton Fair · Phase 1。');
    bl_admin_field('开始日期', 'bl-event-start', $input('start', 'date', ''));
    bl_admin_field('结束日期', 'bl-event-end', $input('end', 'date', ''), '单日活动可留空；早于开始日期时不保存。');
    echo '</div><div class="bl-subgroup"><h3 class="bl-subgroup-title">场馆与展位</h3>';
    bl_admin_field('场馆', 'bl-event-venue', $input('venue', 'text', ' class="large-text"'));
    bl_admin_field('地址', 'bl-event-address', $input('address', 'text', ' class="large-text"'));
    bl_admin_field('展位号', 'bl-event-booth', $input('booth'), '例如 12.0B34。');
    bl_admin_field('展位说明', 'bl-event-booth_note', $input('booth_note'), '例如 Outdoor booth。');
    echo '</div><div class="bl-subgroup"><h3 class="bl-subgroup-title">日期来源</h3>';
    bl_admin_field('来源网址', 'bl-event-source', $input('source', 'url', ' class="large-text code"'), '主办方公布日期的页面或文件，文章页会显示为链接。');
    bl_admin_field('来源说明', 'bl-event-source_label', $input('source_label', 'text', ' class="large-text"'), '链接文字，例如 Dates from the Canton Fair’s 2026 Attendance Guide。');
    echo '</div>';
}

/** Rows posted as parallel lists (specs[key][], specs[value][]) or, from older forms, as one text block. */
function bl_admin_posted_rows($value, array $fields, string $separator = '|'): array
{
    $rows = [];
    if (is_string($value)) {
        foreach (preg_split('/\R/', $value) as $line) $rows[] = array_combine($fields, array_pad(array_map('trim', explode($separator, $line, count($fields))), count($fields), ''));
    } elseif (is_array($value)) {
        foreach (array_keys((array) ($value[$fields[0]] ?? [])) as $i) {
            $row = [];
            foreach ($fields as $field) $row[$field] = isset($value[$field][$i]) && is_scalar($value[$field][$i]) ? trim((string) $value[$field][$i]) : '';
            $rows[] = $row;
        }
    }
    return $rows;
}

add_action('save_post', static function (int $postId): void {
    if (!in_array(get_post_type($postId), ['bl_product', 'bl_news'], true) || wp_is_post_revision($postId) || wp_is_post_autosave($postId)) return;
    if (!current_user_can('edit_post', $postId) || !isset($_POST['bl_record_nonce']) || !is_string($_POST['bl_record_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bl_record_nonce'])), 'bl_save_record')) return;
    if (!isset($_POST['bl_record']) || !is_array($_POST['bl_record'])) return;
    $input = wp_unslash($_POST['bl_record']);
    $text = static fn(string $key): string => isset($input[$key]) && is_scalar($input[$key]) ? sanitize_text_field((string) $input[$key]) : '';
    $record = get_post_meta($postId, '_bl_record', true);
    if (!is_array($record)) $record = [];
    $record['source'] = esc_url_raw($text('source'), ['http', 'https']);
    $image = $text('image');
    $record['image'] = str_starts_with($image, 'images/') && !str_contains($image, '..') ? $image : esc_url_raw($image, ['http', 'https']);
    if (get_post_type($postId) === 'bl_product') {
        $record['model'] = $text('model');
        $record['category'] = in_array($text('category'), ['skid-steer-loaders', 'mini-dumpers'], true) ? $text('category') : 'skid-steer-loaders';
        $record['gallery'] = [];
        $gallery = $input['gallery'] ?? [];
        foreach (array_slice(is_string($gallery) ? preg_split('/\R/', $gallery) : (array) $gallery, 0, 12) as $line) {
            $line = is_scalar($line) ? trim(sanitize_text_field((string) $line)) : '';
            $url = str_starts_with($line, 'images/') && !str_contains($line, '..') ? $line : esc_url_raw($line, ['http', 'https']);
            if ($url) $record['gallery'][] = $url;
        }
        $record['specs'] = [];
        foreach (array_slice(bl_admin_posted_rows($input['specs'] ?? '', ['key', 'value']), 0, 40) as $row) {
            $key = sanitize_text_field($row['key']);
            $value = sanitize_text_field($row['value']);
            if ($key !== '' && $value !== '') $record['specs'][$key] = $value;
        }
        $record['load'] = 0;
        if (preg_match('/^([\d,. ]+)\s*kg$/i', $record['specs']['Rated load'] ?? '', $matches)) {
            $record['load'] = min(1000000, max(0, (int) str_replace([',', ' '], '', $matches[1])));
        }
        // Bucket and power feed the home page ranges and excerpts, so they follow the spec rows too.
        $record['capacity'] = preg_match('/^([\d.]+)\s*m³$/u', $record['specs']['Bucket capacity'] ?? '', $matches) ? $matches[1] : '';
        $record['power'] = preg_match('/^([\d.]+)\s*kW$/i', $record['specs']['Engine power'] ?? '', $matches) ? $matches[1] : '';
    } else {
        $record['category'] = isset(BL_NEWS_CATEGORIES[$text('category')]) ? $text('category') : '';
        $known = array_map(static fn($p) => $p->post_name, get_posts(['post_type' => 'bl_product', 'post_status' => 'publish', 'numberposts' => -1]));
        $record['products'] = array_values(array_intersect(array_map('sanitize_title', (array) ($input['products'] ?? [])), $known));
        $record['line'] = $text('line') === 'group' ? 'group' : 'compact';
        $record['caption'] = $text('caption');
        $event = [];
        foreach (['name', 'venue', 'address', 'booth', 'booth_note', 'source_label'] as $key) $event[$key] = $text('event_' . $key);
        // Only real dates are kept (the pages compute the status from them); an end before the start is dropped.
        foreach (['start', 'end'] as $key) $event[$key] = bl_is_date($text('event_' . $key)) ? $text('event_' . $key) : '';
        if ($event['end'] && $event['end'] < $event['start']) $event['end'] = '';
        $event['source'] = esc_url_raw($text('event_source'), ['http', 'https']);
        $record['event'] = $event['start'] ? $event : [];
    }
    update_post_meta($postId, '_bl_record', $record);
});

// A job title for news authors (shown under their name on stories), kept on the user's own profile.
$bl_title_field = static function (WP_User $user): void {
    echo '<h2>BOB-LIFT 新闻署名</h2><table class="form-table" role="presentation"><tr><th><label for="bl-title">职位</label></th><td><input class="regular-text" type="text" id="bl-title" name="bl_title" value="' . esc_attr((string) get_user_meta($user->ID, 'bl_title', true)) . '"><p class="description">英文，显示在文章右侧作者姓名下方，例如 Content Editor。</p></td></tr></table>';
};
add_action('show_user_profile', $bl_title_field);
add_action('edit_user_profile', $bl_title_field);
$bl_title_save = static function (int $userId): void {
    // The profile form's own nonce has been checked by WordPress before these hooks run.
    if (!current_user_can('edit_user', $userId) || !isset($_POST['bl_title']) || !is_string($_POST['bl_title'])) return;
    update_user_meta($userId, 'bl_title', sanitize_text_field(wp_unslash($_POST['bl_title'])));
};
add_action('personal_options_update', $bl_title_save);
add_action('edit_user_profile_update', $bl_title_save);
