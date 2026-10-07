<?php
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once __DIR__ . '/product-specs.php';

/** Resolve the choice without requiring JavaScript; historical key/value submissions keep their contract. */
function bl_product_posted_specs($input): array
{
    $rows = bl_admin_posted_rows($input, ['key', 'value']);
    $choices = is_array($input) && is_array($input['choice'] ?? null) ? array_values($input['choice']) : [];
    foreach ($rows as $i => &$row) {
        if (is_string($choices[$i] ?? null) && isset(bl_spec_choices()[$choices[$i]])) $row['key'] = $choices[$i];
    }
    unset($row);
    return $rows;
}

/** Hidden retry fields preserve the complete submission, including core text and all other meta boxes. */
function bl_product_retry_fields(array $input, string $prefix = ''): string
{
    $html = '';
    foreach ($input as $key => $value) {
        $name = $prefix === '' ? (string) $key : $prefix . '[' . $key . ']';
        if (is_array($value)) $html .= bl_product_retry_fields($value, $name);
        elseif (is_scalar($value)) $html .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '">';
    }
    return $html;
}

/** Reject before core writes anything. The correction form resubmits every original field, never a partial record. */
function bl_product_validate_submission(): void
{
    if (!isset($_POST['bl_record_nonce']) || !is_string($_POST['bl_record_nonce']) || !wp_verify_nonce(wp_unslash($_POST['bl_record_nonce']), 'bl_save_record')) return;
    if (!bl_product_submission_complete()) return;
    $input = wp_unslash($_POST);
    $rows = bl_product_posted_specs($input['bl_record']['specs'] ?? []);
    $specs = []; $error = '';
    foreach ($rows as $row) {
        $key = $row['key']; $value = $row['value'];
        if ($key === '' && $value === '') continue;
        if ($key === '') { $error = '有参数值但未填写参数名，请选择常用参数或填写自定义英文名。'; break; }
        if ($value === '') continue;
        if (isset($specs[$key])) { $error = '参数“' . $key . '”重复，请合并或修改参数名，避免覆盖已有行。'; break; }
        $specs[$key] = $value;
    }
    $postId = isset($_POST['post_ID']) && is_scalar($_POST['post_ID']) ? (int) $_POST['post_ID'] : 0;
    $stored = $postId ? get_post_meta($postId, '_bl_record', true) : [];
    $error = $error ?: bl_specs_error($specs, is_array($stored) ? (array) ($stored['specs'] ?? []) : []);
    if ($error === '') return;
    unset($input['bl_record']['specs'], $input['bl_product_complete']);
    $html = '<p>' . esc_html($error) . '</p><p>所有输入均已保留，尚未保存。请在下方修正规格后重新保存；其余字段将按原输入提交。</p><form method="post" action="' . esc_attr(admin_url('post.php')) . '">';
    $html .= bl_product_retry_fields($input);
    foreach ($rows as $row) {
        $html .= '<p><label>参数名 <input name="bl_record[specs][key][]" value="' . esc_attr($row['key']) . '"></label> <label>参数值 <input name="bl_record[specs][value][]" value="' . esc_attr($row['value']) . '"></label></p>';
    }
    $html .= '<input type="hidden" name="bl_product_complete" value="1"><button type="submit">修正并保存</button></form>';
    wp_die($html, '规格参数未保存', ['response' => 400, 'back_link' => true]);
}

// Also cover a programmatic wp_update_post() with a product form in $_POST but no post.php screen-load check: validate
// before core writes anything, so a refused save leaves the title and body untouched too.
add_filter('wp_insert_post_data', static function (array $data, array $postarr): array {
    $id = (int) ($postarr['ID'] ?? 0);
    if (($data['post_type'] ?? '') === 'bl_product' && ($data['post_status'] ?? '') !== 'auto-draft' && (!$id || current_user_can('edit_post', $id))) bl_product_validate_submission();
    return $data;
}, 0, 2);

add_filter('rest_pre_insert_bl_product', static function ($prepared, WP_REST_Request $request) {
    if (is_wp_error($prepared)) return $prepared;
    $meta = $request->get_param('meta');
    $record = is_array($meta) ? ($meta['_bl_record'] ?? null) : null;
    if (!is_array($record) || !array_key_exists('specs', $record)) return $prepared;
    if (!is_array($record['specs'])) return new WP_Error('bl_specs_invalid', '规格参数必须为英文参数名到显示值的对象。', ['status' => 400]);
    $stored = $request->get_param('id') ? get_post_meta((int) $request->get_param('id'), '_bl_record', true) : [];
    $error = bl_specs_error($record['specs'], is_array($stored) ? (array) ($stored['specs'] ?? []) : []);
    return $error === '' ? $prepared : new WP_Error('bl_specs_invalid', $error, ['status' => 400]);
}, 9, 2);
