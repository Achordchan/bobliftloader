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

add_filter('rest_pre_get_setting', static function ($value, string $name) {
    return $name === 'bl_catalogue_url' ? bl_downloads_catalogue_row()['url'] : $value;
}, 10, 2);

/** Both REST interfaces update only the primary row under the unified content lock. */
function bl_catalogue_save_url(string $url): bool|WP_Error
{
    return bl_content_locked('downloads', static function (array $previous) use ($url) {
        $input = bl_content_resolve('downloads', $previous);
        foreach ($input['items'] as &$row) if ($row['id'] === 'catalogue') $row['url'] = $url;
        unset($row);
        return bl_content_prepare('downloads', $previous, $input);
    });
}

/** Dispatch after core validation/permissions so writer errors remain REST errors, not successful setting updates. */
add_filter('rest_dispatch_request', static function ($result, WP_REST_Request $request, string $route, array $handler) {
    if ($result !== null || $route !== '/wp/v2/settings' || !in_array($request->get_method(), ['POST', 'PUT', 'PATCH'], true)
        || !array_key_exists('bl_catalogue_url', $request->get_params())) return $result;
    if (!current_user_can('manage_options')) return new WP_Error('rest_forbidden', '没有设置编辑权限。', ['status' => 403]);
    // Keep the original setting sanitizer; null resets the primary URL to its empty default.
    $saved = bl_catalogue_save_url(esc_url_raw((string) $request->get_param('bl_catalogue_url'), ['http', 'https']));
    if (is_wp_error($saved)) return $saved;
    // Core still handles all other settings and builds its usual response; never write the legacy option.
    $remaining = clone $request;
    $remaining->offsetUnset('bl_catalogue_url');
    return call_user_func($handler['callback'], $remaining);
}, 10, 4);

/** Keep the legacy response keys while sourcing the primary row from the unified content state. */
function bl_catalogue_rest_read(): array
{
    $row = bl_downloads_catalogue_row();
    $file = bl_download_file($row['url']);
    // The old endpoint exposed an empty configured URL when the theme's bundled file was in use.
    return ['bl_catalogue_url' => $row['url'], 'bl_catalogue_size' => $file['size']];
}

function bl_catalogue_rest_save(WP_REST_Request $request): array|WP_Error
{
    if (!current_user_can('edit_pages')) return new WP_Error('rest_forbidden', '没有画册编辑权限。', ['status' => 403]);
    if ($request->get_param('bl_catalogue_size') !== null) return new WP_Error('bl_catalogue_readonly', '文件大小由媒体文件自动计算，不能写入。', ['status' => 400]);
    $url = $request->get_param('bl_catalogue_url');
    if ($url !== null) {
        if (!is_string($url)) return new WP_Error('bl_catalogue_invalid', '画册地址必须是文本。', ['status' => 400]);
        $result = bl_catalogue_save_url($url);
        if (is_wp_error($result)) return $result;
    }
    return bl_catalogue_rest_read();
}

function bl_downloads_rest_read(): array
{
    $saved = bl_content_saved_now('downloads');
    $content = bl_content_resolve('downloads', $saved);
    foreach ($content['items'] as &$row) $row['size'] = bl_download_file($row['url'])['size'];
    unset($row);
    return $content + ['bl_content_revision' => bl_content_revision($saved, 'downloads')];
}

function bl_downloads_rest_save(WP_REST_Request $request): array|WP_Error
{
    if (!current_user_can('edit_pages')) return new WP_Error('rest_forbidden', '没有资料编辑权限。', ['status' => 403]);
    $items = $request->get_param('items');
    $revision = $request->get_param('bl_content_revision');
    if (!is_array($items) || ($revision !== null && !is_string($revision))) return new WP_Error('bl_downloads_invalid', '资料列表或版本无效。', ['status' => 400]);
    $result = bl_content_locked('downloads', static fn(array $previous) => bl_content_prepare('downloads', $previous, ['items' => $items]), $revision);
    return is_wp_error($result) ? $result : bl_downloads_rest_read();
}

add_action('rest_api_init', static function (): void {
    $permission = static fn() => current_user_can('edit_pages');
    register_rest_route('boblift/v1', '/catalogue', [
        ['methods' => 'GET', 'permission_callback' => $permission, 'callback' => 'bl_catalogue_rest_read'],
        ['methods' => 'POST,PUT,PATCH', 'permission_callback' => $permission, 'callback' => 'bl_catalogue_rest_save', 'args' => [
            'bl_catalogue_url' => ['type' => 'string', 'validate_callback' => static fn($value) => is_string($value) && ($value === '' || (bool) preg_match('~^https?://[^\s<>]+$~i', $value))],
        ]],
        'schema' => static fn() => ['type' => 'object', 'properties' => [
            'bl_catalogue_url' => ['type' => 'string'],
            'bl_catalogue_size' => ['type' => 'integer', 'readonly' => true, 'description' => '自动计算的文件大小（字节），只读。'],
        ]],
    ]);
    $item = ['type' => 'object', 'additionalProperties' => false, 'required' => ['url', 'type', 'scope'], 'properties' => [
        'id' => ['type' => 'string', 'description' => '稳定标识；新行可省略。catalogue 是默认画册。'],
        'url' => ['type' => 'string'], 'title' => ['type' => 'string'], 'description' => ['type' => 'string'],
        'type' => ['type' => 'string', 'enum' => array_keys(BL_DOWNLOAD_TYPES)],
        'scope' => ['type' => 'string', 'enum' => array_keys(BL_DOWNLOAD_SCOPES)],
        'models' => ['type' => 'string', 'description' => '型号名称，英文逗号分隔；读取时解析历史名称。'],
        'size' => ['type' => 'integer', 'readonly' => true, 'description' => '字节；未知为 0。写入时须省略。'],
    ]];
    $properties = ['items' => ['type' => 'array', 'maxItems' => 50, 'items' => $item, 'description' => '完整替换列表，数组顺序即显示顺序。'], 'bl_content_revision' => ['type' => 'string']];
    register_rest_route('boblift/v1', '/downloads', [
        ['methods' => 'GET', 'permission_callback' => $permission, 'callback' => 'bl_downloads_rest_read'],
        ['methods' => 'POST,PUT,PATCH', 'permission_callback' => $permission, 'callback' => 'bl_downloads_rest_save', 'args' => array_replace($properties, ['items' => $properties['items'] + ['required' => true]])],
        'schema' => static fn() => ['type' => 'object', 'properties' => $properties],
    ]);
});
