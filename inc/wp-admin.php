<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

require_once __DIR__ . '/wp-product-save.php';
require_once __DIR__ . '/wp-effective-image.php';

/**
 * Edit screens for products and news. Products use the classic screen so their data (model, specs, photos, videos)
 * leads the page, in boxes placed under the title; news keeps the block editor for writing, with its settings in the
 * sidebar and the event details under the text. Both save into the post's _bl_record.
 */
add_filter('use_block_editor_for_post_type', static fn(bool $use, string $type): bool => $type === 'bl_product' ? false : $use, 10, 2);
add_filter('enter_title_here', static fn(string $text, WP_Post $post): string => $post->post_type === 'bl_product' ? '产品名称（英文，显示在前台）' : $text, 10, 2);

add_action('add_meta_boxes_bl_product', static function (): void {
    add_meta_box('bl-product-basics', '基本信息', 'bl_product_basics_box', 'bl_product', 'bl_main', 'high');
    add_meta_box('bl-product-specs', '规格参数', 'bl_product_specs_box', 'bl_product', 'normal', 'high');
    add_meta_box('bl-product-images', '图片', 'bl_product_images_box', 'bl_product', 'normal', 'high');
    add_meta_box('bl-product-source', '来源', 'bl_product_source_box', 'bl_product', 'normal', 'low');
    remove_meta_box('pageparentdiv', 'bl_product', 'side');
    add_meta_box('bl-product-order', '排序', 'bl_product_order_box', 'bl_product', 'side', 'default');
    remove_meta_box('postexcerpt', 'bl_product', 'normal');
    add_meta_box('postexcerpt', '卡片摘要', 'bl_product_excerpt_box', 'bl_product', 'bl_main', 'high');
});

add_action('add_meta_boxes_bl_news', static function (): void {
    add_meta_box('bl-news-section', '栏目与来源', 'bl_news_section_box', 'bl_news', 'side', 'high');
    add_meta_box('bl-news-image', '封面补充', 'bl_news_image_box', 'bl_news', 'side', 'default');
    add_meta_box('bl-news-products', '相关产品', 'bl_news_products_box', 'bl_news', 'side', 'default');
    add_meta_box('bl-news-event', '展会 / 活动', 'bl_news_event_box', 'bl_news', 'normal', 'high');
});

add_action('add_meta_boxes_bl_application', static function (): void {
    add_meta_box('bl-case-hub', '所属应用', 'bl_case_hub_box', 'bl_application', 'side', 'high');
    add_meta_box('bl-case-products', '使用的机型', 'bl_case_products_box', 'bl_application', 'side', 'default');
    add_meta_box('bl-case-facts', '项目概况', 'bl_case_facts_box', 'bl_application', 'normal', 'high');
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
    if (!in_array($hook, ['post.php', 'post-new.php'], true) || !$screen || !in_array($screen->post_type, ['bl_product', 'bl_news', 'bl_application'], true)) return;
    $dir = get_template_directory();
    $uri = get_template_directory_uri();
    wp_enqueue_style('bl-post-edit', $uri . '/assets/admin/post-edit.css', ['bl-admin'], (string) filemtime($dir . '/assets/admin/post-edit.css'));
    wp_enqueue_media();
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

/** Card titles carry the front page's own numbering ("01 产品系列"); the card shows its own step number, so the prefix goes. */
function bl_admin_card_title(string $title): string
{
    return (string) preg_replace('/^\d{2}\s+/', '', $title);
}

/** The head of a folded card: step number, title, one line of what is inside, and how many fields were changed. */
function bl_admin_card_head(int $number, string $title, string $meta, int $changed): string
{
    return '<h2 class="bl-card-title"><span class="bl-card-num">' . $number . '</span><span class="bl-card-name">' . esc_html(bl_admin_card_title($title)) . '</span>'
        . '<span class="bl-card-meta">' . esc_html($meta) . '</span></h2>'
        . ($changed ? '<span class="bl-chip bl-chip-info">已修改 ' . $changed . ' 项</span>' : '') . '<span class="bl-chip bl-chip-warn bl-dirty-chip">未保存</span>';
}

/**
 * A file chosen only from the media library: name (and a thumbnail for images), a pick button and a remove button. The
 * address itself lives in a hidden field, so nobody types or pastes one; a theme path already stored (images/…) still
 * shows and is kept until the file is replaced or removed.
 */
function bl_admin_media(string $name, string $value, string $type = 'image', string $empty = '未选择'): string
{
    $file = $value !== '' ? wp_basename((string) parse_url($value, PHP_URL_PATH)) : '';
    $thumb = $type === 'image' ? '<span class="bl-media-thumb">' . ($value !== '' ? '<img src="' . esc_url(bl_wp_image($value)) . '" alt="">' : '') . '</span>' : '';
    return '<div class="bl-media" data-bl-media="' . esc_attr($type) . '" data-empty="' . esc_attr($empty) . '">' . $thumb . '<div class="bl-media-side"><span class="bl-media-name' . ($file === '' ? ' is-empty' : '') . '">' . esc_html($file !== '' ? $file : $empty) . '</span>'
        . '<span class="bl-media-buttons"><button type="button" class="button" data-bl="pick">' . ($type === 'video' ? '从媒体库选择视频' : '从媒体库选择图片') . '</button><button type="button" class="button-link bl-media-clear" data-bl="clear-media"' . ($file === '' ? ' hidden' : '') . '>' . ($type === 'image' ? '移除图片' : '移除视频') . '</button></span></div>'
        . '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"></div>';
}

function bl_product_basics_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    bl_admin_field('型号', 'bl-model', bl_admin_input('bl-model', 'bl_record[model]', (string) ($record['model'] ?? ''), 'text', ' class="regular-text" placeholder="BL-T360-H"'), '产品页大标题和产品卡片上显示的型号。已发布产品修改 slug（网址别名）后，旧引用仍可解析；草稿改 slug 不会记录旧 slug，请同步检查引用。');
    bl_admin_field('产品栏目', 'bl-category', bl_admin_select('bl-category', 'bl_record[category]', ['skid-steer-loaders' => '滑移装载机', 'mini-dumpers' => '迷你翻斗车'], (string) ($record['category'] ?? 'skid-steer-loaders')), '决定产品出现在哪个栏目页。');
}

function bl_product_spec_row(string $key, string $value): string
{
    $known = array_key_exists($key, bl_spec_choices());
    $options = ['' => '自定义参数（英文名）'];
    foreach (bl_spec_choices() as $name => $label) $options[$name] = $label . ' · ' . $name;
    return '<li class="bl-row"><div class="bl-row-head"><div class="bl-spec-key">'
        . str_replace('<select id=""', '<select aria-label="常用参数"', bl_admin_select('', 'bl_record[specs][choice][]', $options, $known ? $key : ''))
        . bl_admin_input('', 'bl_record[specs][key][]', $key, 'text', ' class="regular-text" aria-label="自定义参数名" placeholder="自定义英文参数名"')
        . '</div>' . bl_admin_input('', 'bl_record[specs][value][]', $value, 'text', ' class="bl-spec-value" aria-label="参数值" placeholder="数值与单位，如 1,000 kg"') . bl_admin_row_tools() . '</div></li>';
}

function bl_product_specs_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    echo '<p class="bl-box-intro">产品页“Specifications”参数表，前台按固定参数组排列，组内使用固定参数顺序，未归组参数按录入顺序显示。常用参数按中文名称选择，保存为英文显示名；列表之外可选择自定义。</p>';
    echo '<div class="bl-specs" data-bl-spec-rules="' . esc_attr((string) wp_json_encode(array_map(static fn($rule) => ['pattern' => $rule[0], 'example' => $rule[1]], bl_spec_rules()))) . '"><div class="bl-specs-head" aria-hidden="true"><span>参数名</span><span>参数值</span></div><ol class="bl-rows bl-rows-compact" data-bl-rows="specs" data-bl-max="' . BL_CONTENT_LIMITS['specs'] . '">';
    // A new product starts with the three parameters the filters read; rows left without a value are not saved.
    foreach ((array) ($record['specs'] ?? []) ?: ['Rated load' => '', 'Bucket capacity' => '', 'Engine power' => ''] as $key => $value) echo bl_product_spec_row((string) $key, (string) $value);
    echo '</ol><template data-bl-template="specs">' . bl_product_spec_row('', '') . '</template>';
    echo '<p class="bl-rows-add"><button type="button" class="button" data-bl="add" data-bl-target="specs"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> 添加参数</button></p></div>';
    $filters = [['额定载荷', (int) ($record['load'] ?? 0) ?: '', ' kg'], ['斗容', (string) ($record['capacity'] ?? ''), ' m³'], ['功率', (string) ($record['power'] ?? ''), ' kW']];
    echo '<div class="bl-spec-filters"><p class="bl-field-help">关键参数在保存前校验；尺寸使用 mm、标准尺寸使用“长 × 宽 × 高 mm”。例如：<code>Rated load</code> 填“1,000 kg”，<code>Bucket capacity</code> 填“0.5 m³”，<code>Engine power</code> 填“12.1 kW”。</p><p class="bl-spec-chips"><span class="bl-spec-chips-label">当前筛选值（保存后更新）</span>';
    foreach ($filters as [$label, $value, $unit]) echo $value !== '' ? '<span class="bl-chip bl-chip-info">' . esc_html($label . ' ' . $value . $unit) . '</span>' : '<span class="bl-chip">' . esc_html($label . ' 未识别') . '</span>';
    echo '</p></div>';
}

function bl_product_gallery_item(string $path, string $alt = ''): string
{
    return '<li class="bl-gallery-item" title="' . esc_attr($path) . '"><img src="' . esc_url(bl_wp_image($path)) . '" alt=""><input type="hidden" name="bl_record[gallery][]" value="' . esc_attr($path) . '"><input type="text" class="bl-gallery-alt" name="bl_record[gallery_alt][]" value="' . esc_attr($alt) . '" placeholder="替代文字（英文）" aria-label="这张图的替代文字（英文）"><span class="bl-gallery-tools">' . bl_admin_tool('up', 'arrow-left-alt2', '前移') . bl_admin_tool('down', 'arrow-right-alt2', '后移') . bl_admin_tool('remove', 'no-alt', '移除') . '</span></li>';
}

function bl_product_images_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    $image = (string) ($record['image'] ?? '');
    echo '<details><summary>备用图片</summary>';
    bl_admin_field('备用主图', '', bl_admin_media('bl_record[image]', $image), '未设置特色图片时生效；当前图片与来源见右侧“当前生效图片”。历史值保留。');
    echo '</details>';
    $items = '';
    $alts = is_array($record['gallery_alt'] ?? null) ? $record['gallery_alt'] : [];
    foreach ((array) ($record['gallery'] ?? []) as $path) $items .= bl_product_gallery_item((string) $path, (string) ($alts[$path] ?? ''));
    $control = '<ul class="bl-gallery" data-bl-gallery data-bl-max="' . BL_CONTENT_LIMITS['gallery'] . '" data-bl-base="' . esc_url(get_template_directory_uri() . '/assets/') . '">' . $items . '</ul>'
        . '<template data-bl-template="gallery">' . bl_product_gallery_item('') . '</template>'
        . '<div class="bl-gallery-add"><button type="button" class="button" data-bl="media-gallery"><span class="dashicons dashicons-images-alt2" aria-hidden="true"></span> 从媒体库添加</button></div>';
    bl_admin_field('图库', '', $control, '图库有两张及以上时，产品页大图旁显示缩略图，点击切换；通常把主图也放在第一张。最多 12 张，可用箭头调整顺序或拖动排序。每张图下方填英文替代文字，描述画面（如 BL-T1000-H loader lifting a pallet），供谷歌图片和读屏软件使用；留空时自动用产品名。');
}

function bl_product_source_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    bl_admin_field('原始产品网址', 'bl-source', bl_admin_input('bl-source', 'bl_record[source]', (string) ($record['source'] ?? ''), 'url', ' class="regular-text code" placeholder="https://www.boblift.com/…"'), '产品资料的出处，便于以后核对参数；前台不显示。');
    bl_admin_field('参数出处', 'bl-spec-source', bl_admin_input('bl-spec-source', 'bl_record[spec_source]', (string) ($record['spec_source'] ?? ''), 'text', ' class="regular-text" placeholder="BOB-LIFT mini dumper brochure"'), '英文，显示在产品页参数表上方：“Figures from the 这里的文字.”。留空则显示“抄自主站产品页和资料”的默认说明。');
}

function bl_product_order_box(WP_Post $post): void
{
    echo '<p class="bl-side-field"><label for="menu_order">排序数字</label>' . bl_admin_input('menu_order', 'menu_order', (string) $post->menu_order, 'number', ' class="small-text" step="1"') . '</p><p class="bl-field-help">数字小的排在前面；产品列表和栏目页按这个顺序显示。</p>';
}

function bl_product_excerpt_box(WP_Post $post): void
{
    echo '<label class="screen-reader-text" for="excerpt">卡片摘要</label><textarea rows="3" cols="40" name="excerpt" id="excerpt">' . esc_textarea($post->post_excerpt) . '</textarea>';
    echo '<p class="bl-field-help">一两句英文，用于搜索描述回退和产品页首屏简介；产品卡片不显示此摘要。留空则自动截取详细介绍的开头。</p>';
}

function bl_news_section_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    $record['line'] = bl_news_line($record, $post->post_name); // the same line the site shows, so saving keeps it
    wp_nonce_field('bl_save_record', 'bl_record_nonce');
    echo '<div class="bl-field-stack">';
    echo '<div data-bl-news-category>';
    bl_admin_field('栏目', 'bl-category', bl_admin_select('bl-category', 'bl_record[category]', ['' => '不归入栏目', 'product-news' => '产品新闻 Product news', 'customer-stories' => '客户故事 Customer stories', 'industry-news' => '行业新闻 Industry news'], (string) ($record['category'] ?? '')), '“不归入栏目”的文章只出现在“全部”里。');
    echo '</div>';
    bl_admin_field('归属', 'bl-line', bl_admin_select('bl-line', 'bl_record[line]', ['compact' => '紧凑设备（本站新闻）', 'group' => 'BOB-LIFT 集团'], $record['line']), '集团新闻（起重机等）在列表中标注 BOB-LIFT group，并且不归入任何栏目。');
    bl_admin_field('原文网址', 'bl-source', bl_admin_input('bl-source', 'bl_record[source]', (string) ($record['source'] ?? ''), 'url', ' class="code"'), '转载时填写原文地址，文章页会显示原文链接；原创留空。');
    echo '</div>';
}

function bl_news_image_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    echo '<div class="bl-field-stack">';
    bl_admin_field('图片说明', 'bl-caption', bl_admin_input('bl-caption', 'bl_record[caption]', (string) ($record['caption'] ?? '')), '英文，显示在文章顶部大图下方。');
    echo '<details><summary>备用图片</summary>';
    bl_admin_field('备用图片', '', bl_admin_media('bl_record[image]', (string) ($record['image'] ?? '')), '未设置特色图片时生效；两者都为空则使用主题默认图片。');
    echo '<p><label><input type="checkbox" name="bl_record[hide_cover]" value="1"' . checked(!empty($record['hide_cover']), true, false) . '> 备用图片是从展位海报裁出来的，文章页不重复显示</label></p>';
    echo '</details>';
    $poster = (array) ($record['poster'] ?? []);
    bl_admin_field('正文海报', '', bl_admin_media('bl_record[poster][image]', (string) ($poster['image'] ?? '')), '显示当前海报；可从媒体库替换或移除，保存后生效。');
    echo '<details><summary>海报说明（可选）</summary>';
    foreach (['alt' => '海报替代文字', 'caption' => '海报说明'] as $key => $label) {
        bl_admin_field($label, 'bl-poster-' . $key, bl_admin_input('bl-poster-' . $key, 'bl_record[poster][' . $key . ']', (string) ($poster[$key] ?? '')));
    }
    echo '</details></div>';
}

function bl_news_products_box(WP_Post $post): void
{
    bl_admin_product_checklist($post, '勾选的产品显示在文章右侧栏。');
}

/** Every published product as a checkbox, grouped by family; the ticked ones are saved as the post's related products. */
function bl_admin_product_checklist(WP_Post $post, string $intro): void
{
    $chosen = (array) (bl_admin_record($post)['products'] ?? []);
    $index = bl_record_slug_index(bl_products());
    $chosen = array_map(static fn($slug) => $index[$slug]['slug'] ?? $slug, $chosen);
    echo '<p class="bl-field-help bl-box-intro">' . esc_html($intro) . '</p>';
    $groups = ['skid-steer-loaders' => ['滑移装载机', ''], 'mini-dumpers' => ['迷你翻斗车', '']];
    $products = get_posts(['post_type' => 'bl_product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']);
    $shown = array_column($products, 'post_name');
    foreach ($chosen as $slug) {
        if (!is_string($slug) || in_array($slug, $shown, true)) continue;
        $product = get_page_by_path($slug, OBJECT, 'bl_product');
        $products[] = $product ?: (object) ['ID' => 0, 'post_name' => $slug, 'post_title' => $slug, 'post_status' => 'draft'];
    }
    foreach ($products as $product) {
        $meta = get_post_meta($product->ID, '_bl_record', true);
        $group = is_array($meta) && ($meta['category'] ?? '') === 'mini-dumpers' ? 'mini-dumpers' : 'skid-steer-loaders';
        $slug = $product->post_name;
        $groups[$group][1] .= '<li><label><input type="checkbox" name="bl_record[products][]" value="' . esc_attr($slug) . '"' . checked(in_array($slug, $chosen, true), true, false) . '> ' . esc_html($product->post_title) . ($product->post_status !== 'publish' ? ' <span class="bl-chip bl-chip-warn">未发布</span>' : '') . '</label></li>';
    }
    echo '<div class="bl-checklist">';
    foreach ($groups as [$title, $items]) if ($items) echo '<p class="bl-subgroup-title">' . esc_html($title) . '</p><ul>' . $items . '</ul>';
    echo '</div>';
}

/** Current scenario choices; hidden selections remain identifiable without rewriting saved case references. */
function bl_case_hubs(): array
{
    $hubs = [];
    $names = ['landscaping' => '景观绿化', 'site-preparation' => '场地准备', 'material-transport' => '物料运输', 'gardens-and-homesteads' => '园林家用', 'farms-and-orchards' => '农场果园', 'building-sites' => '工地'];
    $content = bl_content_resolve('applications', (array) get_option('bl_content_applications', []));
    foreach ($GLOBALS['bl_application_base'] ?? [] as $application) {
        $slug = bl_application_slug($application);
        $title = (string) ($content['jobs'][$slug]['title'] ?? $application['title']);
        $hubs[$slug] = $title !== $application['title'] && $title !== '' ? $title : ($names[$slug] ?? $title);
    }
    foreach ($content['jobs'] as $slug => $job) {
        if (!isset($hubs[$slug])) $hubs[$slug] = (string) $job['title'];
        if (!empty($job['hidden'])) $hubs[$slug] .= '（场景已隐藏）';
    }
    return $hubs;
}

function bl_case_hub_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    wp_nonce_field('bl_save_record', 'bl_record_nonce');
    echo '<div class="bl-field-stack">';
    bl_admin_field('应用类别', 'bl-case-hub-select', bl_admin_select('bl-case-hub-select', 'bl_record[hub]', ['' => '不归入具体类别'] + bl_case_hubs(), (string) ($record['hub'] ?? '')), '案例显示在这个应用页的“In practice”里，面包屑也挂在它下面；不选则只出现在应用总页。');
    echo '</div>';
}

function bl_case_products_box(WP_Post $post): void
{
    bl_admin_product_checklist($post, '勾选的机型显示在案例右侧栏“Machines used”，询价按钮也会带上第一款。');
}

function bl_case_facts_box(WP_Post $post): void
{
    $facts = (array) (bl_admin_record($post)['facts'] ?? []);
    echo '<p class="bl-box-intro">案例正文上方的“At a glance”，用英文填；留空的项不显示。正文在上面的编辑器里写，封面图用右侧“封面图片”。</p>';
    foreach (['location' => ['地点', 'Bavaria, Germany', ''], 'customer' => ['客户类型', 'Landscaping contractor', ''], 'task' => ['工作内容', 'Terrace levelling and gravel spreading', ''], 'site' => ['场地条件', 'Garden gate 900 mm wide, soft ground', '最窄通道、地面情况等。'], 'duration' => ['工期', '3 days', ''], 'result' => ['成果', 'Finished a day ahead of plan', '']] as $key => [$label, $placeholder, $help]) {
        bl_admin_field($label, 'bl-fact-' . $key, bl_admin_input('bl-fact-' . $key, 'bl_record[facts][' . $key . ']', (string) ($facts[$key] ?? ''), 'text', ' class="large-text" placeholder="' . esc_attr($placeholder) . '"'), $help);
    }
}

function bl_news_event_box(WP_Post $post): void
{
    $event = (array) (bl_admin_record($post)['event'] ?? []);
    $value = static fn(string $key): string => (string) ($event[$key] ?? '');
    $input = static fn(string $key, string $type = 'text', string $extra = ' class="regular-text"'): string => bl_admin_input('bl-event-' . $key, 'bl_record[event_' . $key . ']', $value($key), $type, $extra);
    $chip = $value('start') ? '<span class="bl-chip bl-chip-info">文章页显示活动信息</span>' : '<span class="bl-chip">未填写 · 不显示</span>';
    echo '<p class="bl-box-intro">' . $chip . ' 只有展会、活动类新闻才填。填了开始日期，文章页就会显示活动信息条和“还有几天开幕”；留空则整块不显示。以下内容用英文填写。</p>';
    echo '<details data-bl-event' . ($value('start') !== '' ? ' open' : '') . '><summary>展会信息（填写开始日期后前台显示）</summary>';
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
    echo '</div></details>';
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

/** Load, bucket and power feed the home page ranges and excerpts, so they always follow the spec rows. */
function bl_product_derived(array $record): array
{
    $specs = is_array($record['specs'] ?? null) ? $record['specs'] : [];
    $record['load'] = 0;
    if (preg_match('/^([\d,. ]+)\s*kg$/i', (string) ($specs['Rated load'] ?? ''), $matches)) {
        $record['load'] = min(1000000, max(0, (int) str_replace([',', ' '], '', $matches[1])));
    }
    $record['capacity'] = preg_match('/^([\d.]+)\s*m³$/u', (string) ($specs['Bucket capacity'] ?? ''), $matches) ? $matches[1] : '';
    $record['power'] = preg_match('/^([\d.]+)\s*kW$/i', (string) ($specs['Engine power'] ?? ''), $matches) ? $matches[1] : '';
    return $record;
}

add_action('save_post', static function (int $postId): void {
    if (!in_array(get_post_type($postId), ['bl_product', 'bl_news', 'bl_application'], true) || wp_is_post_revision($postId) || wp_is_post_autosave($postId)) return;
    if (!current_user_can('edit_post', $postId) || !isset($_POST['bl_record_nonce']) || !is_string($_POST['bl_record_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bl_record_nonce'])), 'bl_save_record')) return;
    if (!isset($_POST['bl_record']) || !is_array($_POST['bl_record'])) return;
    if (get_post_type($postId) === 'bl_product' && !bl_product_submission_complete()) return;
    $input = wp_unslash($_POST['bl_record']);
    $text = static fn(string $key): string => isset($input[$key]) && is_scalar($input[$key]) ? sanitize_text_field((string) $input[$key]) : '';
    $result = bl_record_locked($postId, static function (array $record) use ($postId, $input, $text): array {
        $record['source'] = esc_url_raw($text('source'), ['http', 'https']);
        $image = $text('image');
        $record['image'] = str_starts_with($image, 'images/') && !str_contains($image, '..') ? $image : esc_url_raw($image, ['http', 'https']);
        if (get_post_type($postId) === 'bl_product') {
            $previous = $record;
            $record['model'] = $text('model');
            $record = bl_product_model_aliases($record, $previous);
            // Older forms without the field keep the stored value instead of clearing it.
            if (array_key_exists('spec_source', $input)) $record['spec_source'] = $text('spec_source');
            $record['category'] = in_array($text('category'), ['skid-steer-loaders', 'mini-dumpers'], true) ? $text('category') : 'skid-steer-loaders';
            $record['gallery'] = [];
            $gallery = $input['gallery'] ?? [];
            $alts = is_array($input['gallery_alt'] ?? null) ? array_values($input['gallery_alt']) : [];
            // Older forms without the alt fields keep the stored text for the photographs still in the gallery.
            $kept = array_key_exists('gallery_alt', $input) ? [] : (is_array($record['gallery_alt'] ?? null) ? $record['gallery_alt'] : []);
            $record['gallery_alt'] = [];
            foreach ((is_string($gallery) ? preg_split('/\R/', $gallery) : (array) $gallery) as $i => $line) {
                $line = is_scalar($line) ? trim(sanitize_text_field((string) $line)) : '';
                $url = str_starts_with($line, 'images/') && !str_contains($line, '..') ? $line : esc_url_raw($line, ['http', 'https']);
                if (!$url) continue;
                $record['gallery'][] = $url;
                $alt = isset($alts[$i]) && is_scalar($alts[$i]) ? mb_substr(sanitize_text_field((string) $alts[$i]), 0, 160) : (string) ($kept[$url] ?? '');
                if ($alt !== '') $record['gallery_alt'][$url] = $alt;
            }
            $record['specs'] = [];
            foreach (bl_product_posted_specs($input['specs'] ?? '') as $row) {
                $key = sanitize_text_field($row['key']);
                $value = sanitize_text_field($row['value']);
                if ($key !== '' && $value !== '') $record['specs'][$key] = $value;
            }
            $record = bl_product_derived($record);
        } elseif (get_post_type($postId) === 'bl_application') {
            $record['hub'] = isset(bl_case_hubs()[$text('hub')]) ? $text('hub') : '';
            $record['products'] = bl_rest_product_slugs($input['products'] ?? [], (array) ($record['products'] ?? []));
            $record['facts'] = [];
            foreach (['location', 'customer', 'task', 'site', 'duration', 'result'] as $key) $record['facts'][$key] = isset($input['facts'][$key]) && is_scalar($input['facts'][$key]) ? sanitize_text_field((string) $input['facts'][$key]) : '';
        } else {
            $record['category'] = isset(BL_NEWS_CATEGORIES[$text('category')]) ? $text('category') : '';
            $record['products'] = bl_rest_product_slugs($input['products'] ?? [], (array) ($record['products'] ?? []));
            $record['line'] = $text('line') === 'group' ? 'group' : 'compact';
            $record['caption'] = $text('caption');
            $record['hide_cover'] = !empty($input['hide_cover']);
            if (array_key_exists('poster', $input)) $record['poster'] = array_replace((array) ($record['poster'] ?? []), bl_rest_field('bl_news', 'poster', $input['poster']));
            $event = [];
            foreach (['name', 'venue', 'address', 'booth', 'booth_note', 'source_label'] as $key) $event[$key] = $text('event_' . $key);
            // Only real dates are kept (the pages compute the status from them); an end before the start is dropped.
            foreach (['start', 'end'] as $key) $event[$key] = bl_is_date($text('event_' . $key)) ? $text('event_' . $key) : '';
            if ($event['end'] && $event['end'] < $event['start']) $event['end'] = '';
            $event['source'] = esc_url_raw($text('event_source'), ['http', 'https']);
            $record['event'] = $event;
        }
        return $record;
    });
    if (is_wp_error($result)) wp_die(esc_html($result->get_error_message()), '', ['response' => 500, 'back_link' => true]);
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
