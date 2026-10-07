<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Both form and REST writes migrate first, merge declared maps, clean fields and store only differences. */
function bl_content_prepare(string $page, array $previous, array $input, bool $form = false): array|WP_Error
{
    if ($page === 'tracking' && !bl_can_manage_tracking()) throw new RuntimeException('没有保存统计代码的权限。');
    $previous = bl_content_migrate($page, $previous);
    if ($page === 'home') $input = bl_migrate_home_facts($input);
    // Empty HTML lists have no successful controls; a complete form explicitly clears these lists.
    if ($form) foreach (bl_content_schema($page) as $section) foreach ($section['fields'] as $field) {
        if ($field['type'] === 'list' && bl_content_get($input, $field['key']) === null) bl_content_set($input, $field['key'], []);
    }
    // The WhatsApp links on every page are built from the sales number, so it cannot be cleared.
    if ($page === 'global' && is_array($input['company'] ?? null) && array_key_exists('sales_whatsapp', $input['company']) && preg_replace('/\D/', '', (string) $input['company']['sales_whatsapp']) === '') {
        return new WP_Error('bl_content_whatsapp_invalid', '销售 WhatsApp 号码为必填项，请填写带国家区号的号码。本次修改未保存。', ['status' => 400]);
    }
    // Validate the submitted address before sanitization can turn invalid input into a different address.
    $emailSubmitted = $page === 'global' && is_array($input['company'] ?? null) && array_key_exists('email', $input['company']);
    if ($page === 'global' && ($emailSubmitted || $form)) {
        $email = $input['company']['email'] ?? null;
        if (!is_string($email) || !is_email(trim($email))) {
            return new WP_Error('bl_content_email_invalid', '销售邮箱为必填项，请填写有效的邮箱地址。本次修改未保存。', ['status' => 400]);
        }
        $input['company']['email'] = trim($email);
        // Choosing an address (the default included) survives difference-based persistence; saving other fields with the
        // address untouched leaves an older form recipient alone.
        // A REST client that sends an address has chosen it, even when it equals the default.
        if (!$form || $input['company']['email'] !== bl_content_resolve($page, $previous)['company']['email']) $previous['_bl_email_explicit'] = true;
    }
    $current = bl_content_resolve($page, $previous);
    $merged = bl_merge_content($current, $input, bl_content_dynamic_paths($page));
    $diff = bl_content_diff(bl_content_clean_all($page, $merged), bl_content_clean_all($page, bl_content_defaults($page)));
    // Keep historical fields outside the current editor schema without exposing them as new editable fields.
    foreach (bl_content_schema($page) as $section) foreach ($section['fields'] as $field) {
        $parts = explode('.', $field['key']);
        $last = array_pop($parts);
        $ref = &$previous;
        foreach ($parts as $part) {
            if (!isset($ref[$part]) || !is_array($ref[$part])) { unset($ref); continue 2; }
            $ref = &$ref[$part];
        }
        unset($ref[$last], $ref);
    }
    return array_replace_recursive($previous, $diff);
}

/** Read past option caches so checks under the lock observe the latest committed content. */
function bl_content_saved_now(string $page): array
{
    global $wpdb;
    foreach (['alloptions', 'notoptions', 'bl_content_' . $page] as $key) wp_cache_delete($key, 'options');
    $saved = maybe_unserialize((string) $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", 'bl_content_' . $page)));
    return is_array($saved) ? $saved : [];
}

/** Version checks, backups and writes share the page's lock. */
function bl_content_locked(string $page, callable $compute, ?string $expected = null): bool|WP_Error
{
    $token = bl_lock_acquire('content_' . $page);
    if ($token === '') return new WP_Error('bl_content_busy', '另一个保存正在进行，本次未保存。请稍后重试。', ['status' => 409]);
    try {
        $previous = bl_content_saved_now($page);
        if ($expected !== null && !hash_equals(bl_content_revision($previous, $page), $expected)) {
            return new WP_Error('bl_content_conflict', '内容已被其他编辑者或接口修改，本表单版本已过期，本次未保存。', ['status' => 409]);
        }
        $diff = $compute($previous);
        if (is_wp_error($diff)) return $diff;
        if (!bl_lock_owned('content_' . $page, $token)) return new WP_Error('bl_content_busy', '保存锁已失效，本次未保存。', ['status' => 409]);
        $key = 'bl_content_snapshots_' . $page;
        wp_cache_delete($key, 'options');
        $history = get_option($key, []);
        $history = is_array($history) ? $history : [];
        array_unshift($history, ['id' => wp_generate_uuid4(), 'saved_at' => gmdate('c'), 'user_id' => get_current_user_id(), 'content' => $previous, 'changes' => bl_content_change_count(bl_content_resolve($page, $previous), bl_content_resolve($page, $diff))]);
        if (!update_option($key, array_slice($history, 0, 10), false)) return new WP_Error('bl_content_backup_failed', '历史版本保存失败，本次未保存。', ['status' => 500]);
        if (!bl_lock_owned('content_' . $page, $token)) return new WP_Error('bl_content_busy', '保存锁已失效，本次未保存。', ['status' => 409]);
        $diff['_bl_schema_version'] = 1;
        $diff['_bl_revision'] = wp_generate_uuid4();
        if (!update_option('bl_content_' . $page, $diff)) return new WP_Error('bl_content_write_failed', '内容写入失败，本次未保存。', ['status' => 500]);
        return true;
    } finally {
        bl_lock_release('content_' . $page, $token);
    }
}

function bl_content_revision_input(string $revision): string
{
    return '<input type="hidden" name="bl_content_revision" value="' . esc_attr($revision) . '">';
}

function bl_content_request_page(): string
{
    $page = isset($_POST['bl_page']) && is_string($_POST['bl_page']) ? sanitize_key(wp_unslash($_POST['bl_page'])) : '';
    if ($page === 'tracking' && !bl_can_manage_tracking()) wp_die('没有保存统计代码的权限。', '', ['response' => 403]);
    if ($page !== 'tracking' && !isset(BL_CONTENT_PAGES[$page])) wp_die('未知页面。', '', ['response' => 400]);
    if (!current_user_can('edit_pages')) wp_die('没有编辑权限。', '', ['response' => 403]);
    return $page;
}

/** Recovery is isolated by user and random token; the old revision prevents accidental overwrite on resubmission. */
function bl_content_recovery(string $page): array
{
    $id = isset($_GET['bl_recovery']) && is_string($_GET['bl_recovery']) ? sanitize_key($_GET['bl_recovery']) : '';
    $value = $id ? get_transient('bl_content_recovery_' . get_current_user_id() . '_' . $id) : false;
    return is_array($value) && ($value['page'] ?? '') === $page ? $value : [];
}

function bl_content_handle_submission(string $operation): void
{
    $page = bl_content_request_page();
    check_admin_referer('bl_' . $operation . '_content_' . $page);
    $expected = isset($_POST['bl_content_revision']) && is_string($_POST['bl_content_revision']) ? wp_unslash($_POST['bl_content_revision']) : '';
    $input = isset($_POST['bl_content']) && is_array($_POST['bl_content']) ? wp_unslash($_POST['bl_content']) : [];
    if ($operation === 'save' && ($_POST['bl_content_complete'] ?? '') !== '1') {
        wp_die('提交的内容不完整，没有保存任何修改。请返回检查服务器的 max_input_vars 限制。', '', ['response' => 400, 'back_link' => true]);
    }
    $result = bl_content_locked($page, static function (array $previous) use ($operation, $page, $input) {
        if ($operation === 'reset') return [];
        if ($operation === 'restore') {
            $history = get_option('bl_content_snapshots_' . $page, []);
            $selected = isset($_POST['bl_snapshot']) && is_string($_POST['bl_snapshot']) ? wp_unslash($_POST['bl_snapshot']) : '';
            if ($selected !== '') $history = array_values(array_filter((array) $history, static fn($entry) => ($entry['id'] ?? '') === $selected));
            if (!isset($history[0]['content']) || !is_array($history[0]['content'])) return new WP_Error('bl_content_snapshot_missing', '没有可恢复的快照。', ['status' => 404]);
            return bl_content_restore_values($page, $history[0]['content']);
        }
        return bl_content_prepare($page, $previous, $input, true);
    }, $expected);
    if (is_wp_error($result)) {
        if ($operation !== 'save') wp_die(esc_html($result->get_error_message()), '', ['response' => 409, 'back_link' => true]);
        $id = wp_generate_uuid4();
        $recovery = ['page' => $page, 'input' => $input, 'revision' => $expected, 'message' => $result->get_error_message()];
        if (!set_transient('bl_content_recovery_' . get_current_user_id() . '_' . $id, $recovery, DAY_IN_SECONDS)) {
            // If temporary storage is unavailable, keep an escaped copy in the response instead of losing the submission.
            wp_die(esc_html($result->get_error_message()) . '<p>临时保存失败，请复制以下输入后返回：</p><textarea readonly rows="25" cols="100">' . esc_textarea(wp_json_encode($input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</textarea>', '', ['response' => 409, 'back_link' => true]);
        }
        wp_safe_redirect(admin_url('admin.php?page=bl-content-' . $page . '&bl_recovery=' . $id));
        exit;
    }
    wp_safe_redirect(admin_url('admin.php?page=bl-content-' . $page . ($operation === 'reset' ? '&bl_reset=1' : '&bl_saved=1')));
    exit;
}

foreach (['save', 'reset', 'restore'] as $bl_operation) {
    add_action('admin_post_bl_' . $bl_operation . '_content', static fn() => bl_content_handle_submission($bl_operation));
}
unset($bl_operation);

require_once __DIR__ . '/wp-content-history.php';
require_once __DIR__ . '/wp-record-state.php';
