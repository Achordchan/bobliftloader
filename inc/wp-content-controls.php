<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

const BL_CONTENT_MENU_ORDER = ['bl-content-global', 'bl-content-home', 'bl-content-loaders', 'bl-content-dumpers', 'bl-content-applications', 'bl-content-support', 'bl-content-about', 'bl-content-news', 'bl-content-contact', 'bl-content-thanks', 'bl-content-privacy', 'bl-content-404', 'bl-downloads', 'bl-inquiry-mail', 'bl-content-tracking'];

add_action('admin_menu', static function (): void {
    if (bl_can_manage_tracking()) add_submenu_page('bl-content-home', '统计代码', '统计代码', 'manage_options', 'bl-content-tracking', static function (): void {
        echo '<div class="wrap bl-ce"><div class="bl-page-head"><div><h1>统计代码</h1><p>Google Tag Manager、GA4 或广告像素要求“粘贴到网页里”的代码放在这里，保存后全站生效，不用改主题文件。</p></div></div><hr class="wp-header-end">';
        if (isset($_GET['bl_saved'])) echo '<div class="notice notice-success is-dismissible"><p>已保存。</p></div>';
        bl_tracking_screen();
        echo '</div>';
    });
    global $submenu;
    if (isset($submenu['bl-content-home'])) usort($submenu['bl-content-home'], static fn($a, $b) => (array_search($a[2], BL_CONTENT_MENU_ORDER, true) === false ? 99 : array_search($a[2], BL_CONTENT_MENU_ORDER, true)) <=> (array_search($b[2], BL_CONTENT_MENU_ORDER, true) === false ? 99 : array_search($b[2], BL_CONTENT_MENU_ORDER, true)));
}, 999);

/** Presentation-only ordering and controls; storage paths and field shapes do not change. */
function bl_content_editor_schema(string $page, array $sections): array
{
    if ($page === 'home') {
        $range = $sections[4];
        $after = array_splice($range['fields'], 3);
        $sections = [$sections[0], $sections[1], $range, $sections[2], $sections[3], ['title' => '产品系列下方', 'fields' => $after], ...array_slice($sections, 5)];
    }
    if ($page === 'applications') {
        $send = array_splice($sections, 1, 1);
        // Search metadata remains last.
        $seo = array_pop($sections);
        $sections = [...$sections, ...$send, $seo];
    }
    if ($page === 'applications') foreach ($sections as &$section) {
        $models = []; $fields = [];
        foreach ($section['fields'] as $field) {
            if (str_ends_with($field['key'], '.models')) $models[] = $field;
            else $fields[] = $field;
        }
        $section['fields'] = [...$fields, ...$models];
    }
    unset($section);
    $sections = bl_content_visual_fields($page, $sections);
    foreach ($sections as &$section) foreach ($section['fields'] as &$field) {
        $key = $field['key'];
        if ($key === 'product.attachments') {
            $field['type'] = 'attachments';
            $field['help'] = '勾选并按前台顺序排列；产品未单独选择时使用这里。未找到的旧值保留，可主动取消。';
        }
        if ($page === 'applications' && str_ends_with($key, '.models')) {
            $field['type'] = 'models';
            $field['help'] = '勾选并按推荐顺序排列；前台仅显示所属系列的机型；留空时按场景自动推荐。未找到的旧值保留，可主动取消。';
        }
        if ($key === 'product.factory.images') $field['max'] = BL_CONTENT_LIMITS['factory'];
        if ($key === 'product.faq') $field['max'] = BL_CONTENT_LIMITS['faq'];
        if ($key === 'terms') $section['note'] = '统一业务值：质保月数、交期、起订量。首页与本页文案可引用 {warranty_months}、{lead_time}、{minimum_order}；英文格式可用 {warranty_months_upper}、{lead_time_words}、{minimum_order_words}、{minimum_order_sentence}。自定义文案若仍写固定数字，请复核并换成变量。';

    }
    unset($section, $field);
    // SEO applies to the whole page, independently of the visible section order.
    $seo = []; $body = [];
    foreach ($sections as $section) {
        if (str_starts_with($section['fields'][0]['key'] ?? '', 'seo.')) $seo[] = $section;
        else $body[] = $section;
    }
    return [...$body, ...$seo];
}

/** The successful control stays a CSV string or the original ordered array. Unknown choices remain explicit. */
function bl_content_selector(string $kind, string $name, string|array $value): string
{
    $array = is_array($value);
    $chosen = $array ? $value : array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
    $options = [];
    if ($kind === 'models') {
        // Both families remain available while the scenario family is changed in this same form.
        foreach (bl_products() as $product) if ($product['model'] !== '') $options[$product['model']] = $product['model'];
    } else foreach (bl_attachments() as $items) foreach ($items as $item) $options[$item['name']] = $item['name'];
    $records = $kind === 'models' ? bl_products() : array_merge([], ...array_values(bl_attachments()));
    $field = $kind === 'models' ? 'model' : 'name';
    $index = bl_named_record_index($records, $field, $kind === 'models' ? 'model_aliases' : 'aliases');
    $chosen = array_values(array_unique(array_map(static fn($choice) => isset($index[strtolower($choice)]) && isset($options[$index[strtolower($choice)][$field]]) ? $index[strtolower($choice)][$field] : $choice, $chosen)));
    if (!$array) $value = implode(', ', $chosen);
    $html = '<div class="bl-picker" data-bl-selector data-bl-format="' . ($array ? 'array' : 'csv') . '" data-bl-name="' . esc_attr($name) . '" data-bl-choice-limit="' . ($kind === 'attachments' ? BL_CONTENT_LIMITS['attachments'] : 0) . '">';
    if (!$array) $html .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
    $html .= '<p class="bl-picker-hint">勾选的项目排在前面，用箭头调整前台的显示顺序。</p><ul class="bl-picker-list">';
    foreach (array_unique([...$chosen, ...array_keys($options)]) as $choice) {
        $selected = in_array($choice, $chosen, true);
        $label = $options[$choice] ?? '未找到：' . $choice;
        $move = static fn(string $way, string $icon, string $text): string => '<button type="button" class="button-link bl-tool" data-bl-choice="' . $way . '" aria-label="' . esc_attr($text . '：' . $label) . '" title="' . esc_attr($text) . '"><span class="dashicons dashicons-' . $icon . '" aria-hidden="true"></span></button>';
        $html .= '<li class="bl-picker-item' . (isset($options[$choice]) ? '' : ' is-missing') . '"><label><input type="checkbox" value="' . esc_attr($choice) . '"' . ($array ? ' name="' . esc_attr($name) . '[]"' : '') . ($selected ? ' checked' : '') . ' data-bl-choice-valid="' . (isset($options[$choice]) ? '1' : '0') . '"> <span>' . esc_html($label) . '</span></label><span class="bl-picker-move">' . $move('up', 'arrow-up-alt2', '上移') . $move('down', 'arrow-down-alt2', '下移') . '</span></li>';
    }
    return $html . '</ul></div>';
}

/** Explicit field positions follow template reading order; an unlisted field retains its schema position. */
function bl_content_visual_fields(string $page, array $sections): array
{
    $orders = [
        'home' => [
            'families.loader.image families.loader.alt families.loader.label families.loader.title families.loader.description',
            'families.dumper.image families.dumper.alt families.dumper.label families.dumper.title families.dumper.description',
        ],
        'about' => [
            'hero.breadcrumb hero.eyebrow hero.line1 hero.line2 hero.line3 hero.text hero.link_label hero.link_url hero.image_small hero.image hero.image_alt hero.caption group.facts',
            'group.eyebrow group.line1 group.line2 group.image factory.image_alt_2 group.company_label group.company_brand group.website_label group.website_domain group.cranes_label group.cranes_title group.cranes_text group.loader_label group.current_label group.loader_title group.loader_text group.product_links',
            'promises.company_title promises.company_text promises.order_title promises.order_text promises.team_title promises.team_text promises.service_label promises.service_url promises.team_suffix',
            'factory.eyebrow factory.line1 factory.line2 factory.text factory.link_label factory.link_url factory.assembly_small factory.assembly factory.image_alt_3 factory.welding factory.image_alt_4 factory.painting factory.image_alt_5',
        ],
        'contact' => [
            'hero.breadcrumb hero.eyebrow hero.line1 hero.line2 hero.text channels.whatsapp channels.email form.prepare_title form.eyebrow form.line1 form.line2',
            'visit.eyebrow visit.line1 visit.line2 visit.image_small visit.image hero.image_alt visit.caption visit.caption_detail visit.plan_title visit.plan_line1 visit.plan_line2 visit.text visit.address_label visit.hours_label visit.button mail.subject mail.body visit.map_label',
        ],
    ];
    foreach ($orders[$page] ?? [] as $order) {
        $keys = explode(' ', $order);
        $collected = []; $target = null;
        foreach ($sections as $i => &$section) {
            foreach ($section['fields'] as $j => $field) if (in_array($field['key'], $keys, true)) {
                if ($field['key'] === $keys[0]) $target = $i;
                $collected[$field['key']] = $field;
                unset($section['fields'][$j]);
            }
        }
        unset($section);
        if ($target === null) continue;
        $ordered = [];
        foreach ($keys as $key) if (isset($collected[$key])) $ordered[] = $collected[$key];
        $sections[$target]['fields'] = [...$ordered, ...array_values($sections[$target]['fields'])];
    }
    return array_values(array_filter($sections, static fn($section) => $section['fields'] !== []));
}
