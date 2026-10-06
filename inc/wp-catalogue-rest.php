<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

function bl_register_catalogue_setting(): void
{
    register_setting('bl_downloads', 'bl_catalogue_url', [
        'type' => 'string', 'default' => '', 'show_in_rest' => true,
        'description' => '产品画册 URL；具有 edit_pages 权限的用户也可通过 boblift/v1/catalogue 读写，文件大小（只读）也在那里。',
        'sanitize_callback' => static fn($value): string => esc_url_raw((string) $value, ['http', 'https']),
    ]);
}
add_action('admin_init', 'bl_register_catalogue_setting');
add_action('rest_api_init', 'bl_register_catalogue_setting');

function bl_catalogue_rest_read(): array
{
    return ['bl_catalogue_url' => (string) get_option('bl_catalogue_url', ''), 'bl_catalogue_size' => (int) get_option('bl_catalogue_size', 0)];
}

function bl_catalogue_rest_save(WP_REST_Request $request): array|WP_Error
{
    if (!current_user_can('edit_pages')) return new WP_Error('rest_forbidden', '没有画册编辑权限。', ['status' => 403]);
    if ($request->get_param('bl_catalogue_size') !== null) return new WP_Error('bl_catalogue_readonly', '文件大小由媒体文件自动计算，不能写入。', ['status' => 400]);
    $url = $request->get_param('bl_catalogue_url');
    if ($url !== null) {
        $url = esc_url_raw((string) $url, ['http', 'https']);
        if ($url !== (string) get_option('bl_catalogue_url', '') && !update_option('bl_catalogue_url', $url)) return new WP_Error('bl_catalogue_write_failed', '画册地址保存失败。', ['status' => 500]);
    }
    return bl_catalogue_rest_read();
}

add_action('rest_api_init', static function (): void {
    // Core settings retain manage_options; this narrowly scoped endpoint grants editors only catalogue access.
    register_rest_route('boblift/v1', '/catalogue', [
        ['methods' => 'GET', 'permission_callback' => static fn() => current_user_can('edit_pages'), 'callback' => 'bl_catalogue_rest_read'],
        ['methods' => 'POST,PUT,PATCH', 'permission_callback' => static fn() => current_user_can('edit_pages'), 'callback' => 'bl_catalogue_rest_save', 'args' => [
            'bl_catalogue_url' => ['type' => 'string', 'validate_callback' => static fn($value) => is_string($value) && ($value === '' || (bool) preg_match('~^https?://[^\s<>]+$~i', $value))],
        ]],
        'schema' => static fn() => ['type' => 'object', 'properties' => [
            'bl_catalogue_url' => ['type' => 'string'],
            'bl_catalogue_size' => ['type' => 'integer', 'readonly' => true, 'description' => '自动计算的文件大小（字节），只读。'],
        ]],
    ]);
});
