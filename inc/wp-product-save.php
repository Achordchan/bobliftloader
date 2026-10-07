<?php
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once __DIR__ . '/wp-product-specs.php';

/**
 * PHP drops fields beyond max_input_vars without an error. The product form's last field is a marker: if it did not
 * arrive, the submission was cut short and saving it would silently empty the rows that were lost, so nothing is saved.
 */
function bl_product_submission_complete(): bool
{
    return ($_POST['bl_product_complete'] ?? '') === '1';
}

// Core emits this hook after all meta-box sections, immediately before closing the classic editing form.
add_action('dbx_post_sidebar', static function (WP_Post $post): void {
    if ($post->post_type === 'bl_product') echo '<input type="hidden" name="bl_product_complete" value="1">';
}, PHP_INT_MAX);

// The screen-load hook runs before edit_post(), which can update core meta before wp_insert_post_data.
add_action('load-post.php', static function (): void {
    if (($_POST['action'] ?? '') !== 'editpost') return;
    $postId = isset($_POST['post_ID']) && is_scalar($_POST['post_ID']) ? (int) $_POST['post_ID'] : 0;
    if (!$postId || get_post_type($postId) !== 'bl_product' || !current_user_can('edit_post', $postId)) return;
    bl_product_validate_submission();
    if (!bl_product_submission_complete()) wp_die('产品表单提交不完整或校验已失效，没有保存任何字段。请返回保留输入，并检查 max_input_vars 限制后重试。', '', ['response' => 400, 'back_link' => true]);
});
