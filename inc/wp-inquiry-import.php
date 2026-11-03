<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * The enquiries Flamingo kept before the site moved to the inquiry plugin, copied once into the plugin's 询盘 list so the
 * team finds every enquiry in one place. It runs on its own when an administrator opens the admin, a few dozen at a time,
 * and works whether or not Flamingo is still active (its entries stay in the database either way). Flamingo's entries are
 * left as they are. Each copy remembers which entry it came from (_flamingo_id), so nothing is copied twice; spam is copied
 * as a blocked draft, as the plugin keeps a blocked enquiry.
 *
 * Progress: option bl_flamingo_import = ['last' => highest Flamingo ID done, 'done' => true once nothing is left, 'count'].
 */

const BL_FLAMINGO_IMPORT_BATCH = 40;

/** One Flamingo field as text (either storage format, bl_flamingo_fields): a choice list (CF7 radio, checkbox) is joined. */
function bl_flamingo_field(int $id, string $name): string
{
    static $cache = [];
    $cache[$id] ??= bl_flamingo_fields($id);
    $value = $cache[$id][$name] ?? '';
    if (is_array($value)) $value = implode(', ', array_filter(array_map('strval', array_filter($value, 'is_scalar')), 'strlen'));
    return is_scalar($value) ? trim((string) $value) : '';
}

/** The plugin's fields for one Flamingo entry, worked out as the plugin works out a new one. */
function bl_flamingo_inquiry_fields(WP_Post $entry): array
{
    $id = (int) $entry->ID;
    $meta = get_post_meta($id, '_meta', true);
    $meta = is_array($meta) ? $meta : [];
    $field = static fn(string $name): string => bl_flamingo_field($id, $name);
    // The theme's field names first, then CF7's default form (your-name …), for an entry another form sent.
    $first = static function (string ...$names) use ($field): string {
        foreach ($names as $name) if (($value = $field($name)) !== '') return $value;
        return '';
    };
    $family = $field('product');
    $used = ['product', 'model', 'name', 'your-name', 'email', 'your-email', 'company', 'country', 'message', 'your-message', 'your-subject', 'bl_website', 'bl_page_referrer', 'bl_ad_consent', 'bl_touch'];
    $message = $first('message', 'your-message');
    // Anything else the entry holds stays with it, under the message, so no field of an older or other form is lost.
    $other = [];
    foreach (bl_flamingo_fields($id) as $name => $_) {
        if (in_array($name, $used, true) || str_starts_with($name, 'bl_attr_') || str_starts_with($name, '_')) continue;
        if (($value = $field($name)) !== '') $other[] = $name . ': ' . $value;
    }
    if ($field('your-subject') !== '') array_unshift($other, 'your-subject: ' . $field('your-subject'));
    if ($other) $message = trim($message . "\n\n—— 旧表单其他字段 ——\n" . implode("\n", $other));
    $fields = [
        'product' => BL_ENQUIRY_FAMILIES_ZH[$family] ?? $family,
        'model' => $field('model'),
        'name' => $first('name', 'your-name') !== '' ? $first('name', 'your-name') : trim((string) get_post_meta($id, '_from_name', true)),
        'email' => $first('email', 'your-email') !== '' ? $first('email', 'your-email') : trim((string) get_post_meta($id, '_from_email', true)),
        'phone' => '',
        'company' => $field('company'),
        // The form asked for a country until October 2026; later entries have none.
        'country' => $field('country'),
        'message' => $message,
        'source_entry' => '',
        'source_page' => (string) ($meta['url'] ?? ''),
        'source_title' => trim((string) ($meta['post_title'] ?? '')),
    ];
    foreach (array_keys(ACHORD_INQUIRY_ATTRIBUTION) as $name) {
        $value = achord_inquiry_attribution_clean($name, $field('bl_attr_' . $name));
        if ($value !== '') $fields['attr_' . $name] = $value;
    }
    $fields['page_referrer'] = achord_inquiry_referrer_host($field('bl_page_referrer'));
    $fields['ad_consent'] = in_array($field('bl_ad_consent'), ['true', 'false'], true) ? $field('bl_ad_consent') : '';
    $fields['device'] = achord_inquiry_device((string) ($meta['user_agent'] ?? ''), $field('bl_touch') === '1');
    $fields['channel'] = implode(' · ', array_filter(achord_inquiry_channel($fields), 'strlen'));
    $fields['ip'] = filter_var((string) ($meta['remote_ip'] ?? ''), FILTER_VALIDATE_IP) ? (string) $meta['remote_ip'] : '';
    $fields['language'] = 'en';
    // What happened to it then, as CF7 said (mail_sent, mail_failed, spam …); whether a mail went is recorded only when known.
    $status = (string) get_post_meta($id, '_submission_status', true);
    $fields['flamingo_status'] = $status;
    $fields['mail_sent'] = ['mail_sent' => '1', 'mail_failed' => '0'][$status] ?? '';
    $fields['flamingo_id'] = (string) $id;
    return $fields;
}

/** Copy one Flamingo entry; returns the new inquiry's ID, the earlier copy's ID, or 0 when it could not be saved. */
function bl_flamingo_import_entry(WP_Post $entry): int
{
    $type = achord_inquiry_post_type();
    $done = get_posts(['post_type' => $type, 'post_status' => 'any', 'meta_key' => '_flamingo_id', 'meta_value' => (string) $entry->ID, 'numberposts' => 1, 'fields' => 'ids']);
    if ($done) return (int) $done[0];
    $fields = bl_flamingo_inquiry_fields($entry);
    $spam = $entry->post_status === 'flamingo-spam';
    $customer = $fields['name'] !== '' ? $fields['name'] : ($fields['company'] !== '' ? $fields['company'] : $fields['email']);
    $title = sprintf('Inquiry from %s - %s', $customer, mysql2date('Y-m-d H:i', $entry->post_date));
    $id = wp_insert_post([
        'post_type' => $type,
        'post_status' => $spam ? 'draft' : 'publish',
        'post_title' => wp_slash(($spam ? '[已拦截] ' : '') . $title),
        'post_date' => $entry->post_date,
        'post_date_gmt' => $entry->post_date_gmt,
    ], true);
    if (is_wp_error($id) || !$id) return 0;
    foreach ($fields as $key => $value) {
        if ($value !== '') update_post_meta((int) $id, '_' . $key, wp_slash($value));
    }
    if ($spam) update_post_meta((int) $id, '_blocked', wp_slash('旧表单（Flamingo）判为垃圾'));
    return (int) $id;
}

/** Copy the next batch; returns how many were copied. */
function bl_flamingo_import_batch(): int
{
    $state = get_option('bl_flamingo_import', []);
    $state = is_array($state) ? $state + ['last' => 0, 'done' => false, 'count' => 0] : ['last' => 0, 'done' => false, 'count' => 0];
    if ($state['done']) return 0;
    $after = (int) $state['last'];
    $filter = static function (string $where) use ($after): string {
        global $wpdb;
        return $where . $wpdb->prepare(" AND {$wpdb->posts}.ID > %d", $after);
    };
    // With Flamingo switched off its spam status is unknown to WordPress, which would leave those entries out of the query.
    if (!get_post_status_object('flamingo-spam')) register_post_status('flamingo-spam', ['internal' => true, 'label' => 'Spam']);
    add_filter('posts_where', $filter);
    $entries = get_posts(['post_type' => 'flamingo_inbound', 'post_status' => ['publish', 'flamingo-spam'], 'orderby' => 'ID', 'order' => 'ASC', 'numberposts' => BL_FLAMINGO_IMPORT_BATCH, 'suppress_filters' => false]);
    remove_filter('posts_where', $filter);
    $copied = 0;
    foreach ($entries as $entry) {
        if (!bl_flamingo_import_entry($entry)) break;
        $state['last'] = (int) $entry->ID;
        $copied++;
    }
    $state['count'] = (int) $state['count'] + $copied;
    $state['done'] = count($entries) < BL_FLAMINGO_IMPORT_BATCH && $copied === count($entries);
    update_option('bl_flamingo_import', $state, false);
    return $copied;
}

add_action('admin_init', static function (): void {
    if (!bl_wp_inquiry_ready() || !current_user_can('manage_options') || wp_doing_ajax()) return;
    $state = get_option('bl_flamingo_import', []);
    if (is_array($state) && !empty($state['done'])) return;
    if (!($token = bl_lock_acquire('flamingo_import', 1))) return;
    try {
        bl_flamingo_import_batch();
    } finally {
        bl_lock_release('flamingo_import', $token);
    }
});
