<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Validate within the existing option save; records and references are never rewritten. */
function bl_application_prepare_input(array $previous, array $input): array|WP_Error
{
    $error = static fn(string $message) => new WP_Error('bl_application_invalid', $message . ' 本次修改未保存。', ['status' => 400]);
    $current = bl_content_resolve('applications', $previous)['jobs'];
    if (isset($input['jobs']) && !is_array($input['jobs'])) return $error('场景数据格式无效。');
    if (isset($input['new_jobs']) && !is_array($input['new_jobs'])) return $error('新场景数据格式无效。');
    foreach (($input['new_jobs'] ?? []) as $job) {
        if (!is_array($job) || (isset($job['title']) && !is_string($job['title'])) || (isset($job['slug']) && !is_string($job['slug']))) return $error('新场景数据格式无效。');
        if (trim((string) ($job['title'] ?? '')) === '' && trim((string) ($job['slug'] ?? '')) === '') continue;
        $slug = trim((string) ($job['slug'] ?? ''));
        if ($slug === '') $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) ($job['title'] ?? '')), '-'));
        if (isset($current[$slug]) || isset($input['jobs'][$slug])) return $error('网址标识已存在，请使用不同的标识。');
        unset($job['slug']);
        $input['jobs'][$slug] = array_replace(bl_application_empty(), $job);
    }
    unset($input['new_jobs']);
    foreach ($input['jobs'] ?? [] as $slug => $job) {
        if (!is_array($job) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', (string) $slug)) return $error('网址标识只能使用小写英文字母、数字和单个连字符。');
        if (isset($job['slug']) && $job['slug'] !== $slug) return $error('已保存场景的网址不能修改。');
        if (isset($job['title']) && !is_string($job['title'])) return $error('场景名称格式无效。');
        if (!isset(bl_applications_defaults()['jobs'][$slug]) && trim((string) ($job['title'] ?? $current[$slug]['title'] ?? '')) === '') return $error('新场景必须填写名称。');
        if (isset($job['hidden']) && is_bool($job['hidden'])) {
            $job['hidden'] = $job['hidden'] ? '1' : '0';
            $input['jobs'][$slug]['hidden'] = $job['hidden'];
        }
        foreach (['product' => ['loader', 'dumper'], 'focus' => ['width', 'load', 'payload'], 'hidden' => ['0', '1']] as $key => $allowed) {
            if (array_key_exists($key, $job) && (!is_scalar($job[$key]) || !in_array((string) $job[$key], $allowed, true))) return $error('显示状态、设备系列或关键参数无效。');
        }
        if (isset($job['order']) && (!is_scalar($job['order']) || !preg_match('/^\d{1,6}$/D', (string) $job['order']))) return $error('顺序必须为 0 至 999999 的整数。');
    }
    return $input;
}

function bl_application_control(string $type, string $name, string $id, string $value): string
{
    if ($type === 'application_order') return '<input type="number" min="0" max="999999" step="1" id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
    $choices = match ($type) {
        'application_visibility' => ['0' => '显示', '1' => '隐藏（保留内容）'],
        'application_product' => ['loader' => '滑移装载机', 'dumper' => '迷你翻斗车'],
        'application_focus' => ['width' => '最窄宽度（窄机型优先）', 'load' => '额定载重（大载重优先）', 'payload' => '每趟载重（小载重优先）'],
    };
    $html = '<select id="' . esc_attr($id) . '" name="' . esc_attr($name) . '">';
    foreach ($choices as $key => $label) $html .= '<option value="' . esc_attr((string) $key) . '"' . ((string) $key === $value ? ' selected' : '') . '>' . esc_html($label) . '</option>';
    return $html . '</select>';
}

/** A new scenario uses the same field renderer and save form; its slug becomes the immutable map key. */
function bl_application_add_screen(array $recovered = []): void
{
    echo '<details class="bl-panel bl-ce-section" data-bl-new-application><summary class="bl-panel-head">新增应用场景</summary><div class="bl-panel-body"><p class="bl-card-note">填写英文名称后自动生成网址标识，首次保存前可修改；保存后锁定。填写完毕后点击“保存修改”。原生页面和应用案例保持各自独立，不会随场景保存而改写。</p>';
    echo '<div class="bl-field"><label for="bl-new-application-slug">网址标识</label><input class="large-text code" id="bl-new-application-slug" name="bl_content[new_jobs][0][slug]" pattern="[a-z0-9]+(-[a-z0-9]+)*" type="text" value="' . esc_attr(is_string($recovered['slug'] ?? null) ? $recovered['slug'] : '') . '"><p class="bl-field-help">网址：/applications/网址标识/</p></div>';
    $job = bl_application_empty();
    $orders = array_column(bl_editable('applications')['jobs'], 'order');
    $job['order'] = (string) min(999999, max(array_map('intval', $orders ?: [0])) + 1);
    $job = array_replace($job, array_intersect_key($recovered, $job));
    $job = bl_content_clean_all('applications', ['jobs' => ['new-scenario' => $job]])['jobs']['new-scenario'];
    foreach (bl_content_schema('applications', ['jobs' => ['new-scenario' => $job]]) as $section) foreach ($section['fields'] as $field) {
        if (!str_starts_with($field['key'], 'jobs.new-scenario.')) continue;
        $key = substr($field['key'], strlen('jobs.new-scenario.'));
        $field['key'] = 'new_jobs.0.' . $key;
        if ($key === 'title') $field['help'] = '填写英文名称；未填写时不会创建场景。';
        if ($key === 'image') $field['help'] = '上传场景横图（3:2）。不上传时显示所属设备系列的产品图。';
        bl_content_field($field, $job[$key], false);
    }
    echo '</div></details>';
}

add_action('admin_enqueue_scripts', static function (string $hook): void {
    if (!str_contains($hook, 'bl-content-applications')) return;
    wp_enqueue_script('bl-application-content', get_template_directory_uri() . '/assets/admin/application-content.js', ['bl-content-editor'], (string) filemtime(__DIR__ . '/../assets/admin/application-content.js'), true);
});

