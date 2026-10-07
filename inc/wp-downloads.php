<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Validate the whole submitted list before the unified writer touches content or history. */
function bl_downloads_prepare(array $previous, array $input, bool $form = false): array|WP_Error
{
    if (!array_key_exists('items', $input) && !$form) return $previous;
    $items = $input['items'] ?? [];
    $invalid = static fn(string $message) => new WP_Error('bl_downloads_invalid', $message, ['status' => 400]);
    if (!is_array($items) || count($items) > 50) return $invalid('资料列表最多 50 项。');
    $clean = []; $ids = [];
    foreach ($items as $row) {
        if (!is_array($row)) return $invalid('资料格式无效。');
        if (array_key_exists('size', $row)) return $invalid('文件大小只读，不能写入。');
        foreach (['id', 'url', 'title', 'description', 'type', 'scope', 'models'] as $key) if (isset($row[$key]) && !is_string($row[$key])) return $invalid('资料字段必须是文本。');
        $id = $row['id'] ?? '';
        if ($id === '') $id = wp_generate_uuid4();
        if (!preg_match('/^[a-zA-Z0-9_-]{1,80}$/', $id) || isset($ids[$id])) return $invalid('资料标识重复或无效。');
        $ids[$id] = true;
        $url = trim($row['url'] ?? '');
        if ($url !== '' && (!preg_match('~^https?://[^\s<>"\x27]+$~i', $url) || !filter_var($url, FILTER_VALIDATE_URL))) return $invalid('文件地址必须是有效的 HTTP 或 HTTPS 链接。');
        $title = sanitize_text_field($row['title'] ?? '');
        if ($id !== 'catalogue' && ($url === '' || $title === '')) return $invalid('新增资料必须填写文件地址和标题。');
        $type = $row['type'] ?? 'other'; $scope = $row['scope'] ?? 'all';
        if (!isset(BL_DOWNLOAD_TYPES[$type], BL_DOWNLOAD_SCOPES[$scope]) || ($id === 'catalogue' && $type !== 'catalogue')) return $invalid('资料类型或适用范围无效，默认画册须保留产品画册类型。');
        $models = sanitize_text_field($row['models'] ?? '');
        if ($scope === 'models' && trim($models) === '') return $invalid('指定型号时请至少选择一个型号。');
        $clean[] = ['id' => $id, 'url' => esc_url_raw($url, ['http', 'https']), 'title' => $title, 'description' => sanitize_textarea_field($row['description'] ?? ''), 'type' => $type, 'scope' => $scope, 'models' => $models];
    }
    if (!isset($ids['catalogue'])) return $invalid('请保留默认画册；清空其文件地址可使用主题自带画册。');
    $previous['items'] = $clean;
    return $previous;
}

function bl_downloads_screen(): void
{
    if (!current_user_can('edit_pages')) wp_die('没有编辑权限。', '', ['response' => 403]);
    $saved = bl_content_saved_now('downloads');
    $revision = bl_content_revision($saved, 'downloads');
    $content = bl_content_resolve('downloads', $saved);
    $recovery = bl_content_recovery('downloads');
    if ($recovery) { $content = $recovery['input']; $revision = $recovery['revision']; }
    echo '<div class="wrap bl-ce bl-downloads"><div class="bl-page-head"><div><h1>资料下载</h1><p>按列表顺序显示。默认画册供感谢页等位置引用；清空地址使用主题自带画册。默认画册的标题、说明留空时沿用采购与售后文案。</p></div></div><hr class="wp-header-end">';
    if (isset($_GET['bl_saved'])) echo '<p role="status"><span class="bl-chip">已保存</span></p>';
    if ($recovery) echo '<p role="alert">' . esc_html($recovery['message']) . ' 输入已保留，请与最新版本对照。</p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="bl-ce-form"><input type="hidden" name="action" value="bl_save_content"><input type="hidden" name="bl_page" value="downloads">';
    wp_nonce_field('bl_save_content_downloads');
    echo bl_content_revision_input($revision);
    echo '<details id="bl-download-list" class="bl-panel" data-bl-card open><summary class="bl-panel-head"><h2 class="bl-card-title">资料列表</h2></summary><div class="bl-panel-body"><div class="bl-field bl-ce-field bl-ce-wide"><div class="bl-field-control">';
    echo '<ol class="bl-rows bl-ce-rows" data-bl-limit="50" data-summary="title" data-meta="">';
    foreach (array_values(is_array($content['items'] ?? null) ? $content['items'] : []) as $i => $row) if (is_array($row)) echo bl_downloads_row((string) $i, $row);
    echo '</ol><template class="bl-ce-row-template">' . bl_downloads_row('__i__', []) . '</template><p class="bl-ce-rows-add"><button type="button" class="button bl-ce-add">添加资料</button></p></div></div></div></details>';
    echo '<input type="hidden" name="bl_content_complete" value="1"><div class="bl-savebar">';
    submit_button('保存资料列表', 'primary', 'submit', false);
    echo '<span class="bl-savebar-status" role="status">没有未保存的修改</span></div></form>';
    bl_content_history_screen('downloads', $revision);
    echo '</div>';
}

/** Use the shared list and model selector controls, retaining a stable ID across sorting and history restores. */
function bl_downloads_row(string $i, array $row): string
{
    $name = 'bl_content[items][' . $i . ']'; $id = 'bl-download-' . $i;
    $primary = ($row['id'] ?? '') === 'catalogue';
    $open = $i === '0' || $i === '__i__';
    $text = static fn(string $key): string => is_string($row[$key] ?? null) ? $row[$key] : '';
    $body = '<input type="hidden" name="' . esc_attr($name . '[id]') . '" value="' . esc_attr($text('id')) . '">';
    foreach (['url' => ['文件地址', 'url'], 'title' => ['标题', 'text'], 'description' => ['说明', 'textarea']] as $key => [$label, $type]) {
        $field = bl_content_row_field(['label' => $label, 'type' => $type], $name . '[' . $key . ']', $id . '-' . $key, $text($key));
        // The default catalogue may be left blank: say what shows then, instead of an empty box that looks unset.
        $hint = $primary ? ['url' => '留空则使用主题自带的产品画册 PDF', 'title' => '留空则使用“采购与售后”资料区里画册的标题', 'description' => '留空则使用“采购与售后”资料区里画册的说明'][$key] : '';
        if ($hint !== '') $field = preg_replace('~<(input|textarea)\b~', '<$1 placeholder="' . esc_attr($hint) . '"', $field, 1);
        $body .= $field;
        if ($key === 'url') {
            $size = ($primary || $text('url') !== '') ? bl_download_file($text('url'))['size'] : 0;
            $body .= '<p class="bl-dl-tools"><button type="button" class="button" data-bl-download-pick>从媒体库选择文件</button> <span>文件大小：<output data-bl-download-size>' . esc_html($size ? size_format($size, 1) : '大小未知') . '</output></span></p>';
        }
    }
    foreach (['type' => ['类型', BL_DOWNLOAD_TYPES], 'scope' => ['适用范围', BL_DOWNLOAD_SCOPES]] as $key => [$label, $choices]) {
        $value = $text($key) ?: ($key === 'type' ? 'other' : 'all');
        $body .= '<div class="bl-field"><label class="bl-field-label" for="' . $id . '-' . $key . '">' . $label . '</label><div class="bl-field-control"><select id="' . $id . '-' . $key . '" name="' . esc_attr($name . '[' . $key . ']') . '">';
        foreach ($choices as $option => $caption) if (!$primary || $key !== 'type' || $option === 'catalogue') $body .= '<option value="' . $option . '"' . ($value === $option ? ' selected' : '') . '>' . $caption . '</option>';
        $body .= '</select></div></div>';
    }
    $body .= '<div data-bl-download-models' . ($text('scope') === 'models' ? '' : ' hidden') . '>' . bl_content_selector('models', $name . '[models]', $text('models')) . '</div>';
    $tools = bl_content_row_tools();
    if ($primary) $tools = preg_replace('~<button[^>]*bl-ce-remove[^>]*>.*?</button>~s', '', $tools);
    return '<li class="bl-row bl-ce-row"><div class="bl-row-head"><button type="button" class="bl-ce-toggle" aria-expanded="' . ($open ? 'true' : 'false') . '" aria-controls="' . $id . '"><span class="dashicons dashicons-arrow-right-alt2 bl-ce-caret" aria-hidden="true"></span><span class="bl-row-title">' . esc_html($text('title') ?: ($primary ? '默认产品画册' : '新资料')) . '</span><span class="bl-chip bl-ce-row-meta"></span></button>' . $tools . '</div><div class="bl-row-body" id="' . $id . '"' . ($open ? '' : ' hidden') . '>' . $body . '</div></li>';
}
