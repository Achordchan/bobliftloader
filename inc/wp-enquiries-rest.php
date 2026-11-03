<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * The enquiries Flamingo keeps (inbound messages, spam included) are open to the REST API read-only, so tools outside
 * wp-admin (the WordPress.com connector, scripts) can read what was sent without opening Flamingo. Only users who may
 * manage Flamingo (administrators) can read them. Nothing can be created, changed or deleted through the API: that
 * stays in Flamingo's own screens.
 *
 * Once the inquiry plugin takes enquiries (wp-inquiry.php), /wp/v2/bl_enquiries lists its records instead, Flamingo's
 * history copied in, and meta._bl_enquiry keeps the shape below (bl_inquiry_legacy_record).
 *
 * List: /wp/v2/bl_enquiries (newest first; title = the equipment chosen, status "flamingo-spam" for spam, which the
 * default list includes). One enquiry with context=edit carries meta._bl_enquiry: sender, every posted field, the page,
 * IP, browser, time and the spam verdict (why it was spam, Akismet's answer), and meta._bl_enquiry.attribution: the Google click ID
 * (gclid / gbraid / wbraid), campaign tags and landing page the sender arrived with, when they agreed to advertising storage.
 */

if (class_exists('WP_REST_Posts_Controller')) {
    /**
     * The posts controller without its writes. Reading needs Flamingo's own capability: core lets anyone read a
     * published post, and Flamingo files every enquiry as published.
     */
    final class BL_Enquiries_REST_Controller extends WP_REST_Posts_Controller
    {
        public function get_items_permissions_check($request)
        {
            return bl_enquiries_can_read() ? parent::get_items_permissions_check($request) : bl_enquiries_forbidden();
        }

        public function get_item_permissions_check($request)
        {
            return bl_enquiries_can_read() ? parent::get_item_permissions_check($request) : bl_enquiries_forbidden();
        }

        public function check_read_permission($post)
        {
            return bl_enquiries_can_read() && parent::check_read_permission($post);
        }

        public function create_item_permissions_check($request)
        {
            return bl_enquiries_read_only();
        }

        public function update_item_permissions_check($request)
        {
            return bl_enquiries_read_only();
        }

        public function delete_item_permissions_check($request)
        {
            return bl_enquiries_read_only();
        }
    }
}

function bl_enquiries_can_read(): bool
{
    return current_user_can('flamingo_edit_inbound_messages');
}

function bl_enquiries_forbidden(): WP_Error
{
    return new WP_Error('rest_forbidden', 'Only administrators can read enquiries.', ['status' => rest_authorization_required_code()]);
}

function bl_enquiries_read_only(): WP_Error
{
    return new WP_Error('bl_enquiries_read_only', 'Enquiries are read-only here; manage them in Flamingo.', ['status' => 403]);
}

/** Flamingo's own registration, plus the REST route; its capabilities (edit_users) are kept as they are. */
function bl_enquiries_post_type_args(array $args, string $type): array
{
    // Once the inquiry plugin takes enquiries, /wp/v2/bl_enquiries is its list (wp-inquiry.php), history included.
    if ($type !== 'flamingo_inbound' || !class_exists('BL_Enquiries_REST_Controller') || (function_exists('bl_wp_inquiry_ready') && bl_wp_inquiry_ready())) return $args;
    $args['show_in_rest'] = true;
    $args['rest_base'] = 'bl_enquiries';
    $args['rest_controller_class'] = 'BL_Enquiries_REST_Controller';
    // Title and meta only: no editor means no autosave or revision routes beside the read-only one.
    $args['supports'] = ['title', 'custom-fields'];
    return $args;
}
add_filter('register_post_type_args', 'bl_enquiries_post_type_args', 10, 2);

/**
 * The ad attribution a Flamingo entry was sent with (bl_attr_* fields), without the prefix. Read here for the history only:
 * new enquiries keep theirs as attr_* in the inquiry plugin.
 */
function bl_enquiry_attribution(array $fields): array
{
    $found = [];
    foreach (['gclid', 'gbraid', 'wbraid', 'gad_source', 'gad_campaignid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing', 'first_seen', 'referrer'] as $name) {
        $value = $fields['bl_attr_' . $name] ?? '';
        $value = is_array($value) ? implode(', ', array_filter($value, 'is_scalar')) : $value;
        if (!is_scalar($value)) continue;
        $value = function_exists('achord_inquiry_attribution_clean') ? achord_inquiry_attribution_clean($name, $value) : trim((string) preg_replace('/[\x00-\x1f\x7f<>]/u', ' ', (string) $value));
        if ($value !== '') $found[$name] = $value;
    }
    return $found;
}

/** One enquiry as Flamingo holds it, in the shape meta._bl_enquiry reads. */
function bl_enquiry_record(int $postId): array
{
    if (!class_exists('Flamingo_Inbound_Message') || get_post_type($postId) !== 'flamingo_inbound') return [];
    $message = new Flamingo_Inbound_Message($postId);
    return [
        'subject' => (string) $message->subject,
        'from_name' => (string) $message->from_name,
        'from_email' => (string) $message->from_email,
        'fields' => (array) $message->fields,
        // The advertisement the sender came from (Google click ID, campaign tags, landing page), when they agreed to advertising storage.
        'attribution' => bl_enquiry_attribution((array) $message->fields),
        'meta' => (array) $message->meta,
        'channel' => (string) $message->channel,
        'submission_status' => (string) $message->submission_status,
        'spam' => (bool) $message->spam,
        'spam_log' => (array) $message->spam_log,
        'akismet' => (array) $message->akismet,
    ];
}

add_action('init', static function (): void {
    register_post_meta('flamingo_inbound', '_bl_enquiry', [
        'type' => 'object',
        'single' => true,
        'description' => 'The enquiry, read-only: sender, posted fields, page, IP, browser, time and spam verdict.',
        'show_in_rest' => ['schema' => ['type' => 'object', 'context' => ['edit'], 'additionalProperties' => true]],
        'auth_callback' => '__return_false',
    ]);
}, 20);

// Nothing is stored under _bl_enquiry: reading it builds the record from Flamingo's own fields.
add_filter('get_post_metadata', static function ($value, $postId, $key) {
    if ($key !== '_bl_enquiry' || get_post_type((int) $postId) !== 'flamingo_inbound') return $value;
    return [bl_enquiry_record((int) $postId)];
}, 10, 3);

// The default list shows spam too, so a filtered enquiry can be checked; an explicit status still narrows it.
add_filter('rest_flamingo_inbound_query', static function (array $args, $request): array {
    if (!array_key_exists('status', $request->get_query_params())) $args['post_status'] = ['publish', 'flamingo-spam'];
    return $args;
}, 10, 2);

/**
 * The same meta._bl_enquiry for an enquiry the inquiry plugin keeps, so tools that read the old shape keep working after the
 * move: the sender, every saved field, the ad attribution, the page and IP, whether the mail went out and, for a blocked
 * one, why. Administrators only, like the plugin's own record (meta.bl_inquiry).
 */
function bl_inquiry_legacy_record(int $postId): array
{
    if (!function_exists('achord_inquiry_record') || !function_exists('achord_inquiry_can_read') || !achord_inquiry_can_read()) return [];
    $record = achord_inquiry_record($postId);
    $attribution = [];
    foreach ($record as $key => $value) if (str_starts_with((string) $key, 'attr_') && $value !== '') $attribution[substr((string) $key, 5)] = $value;
    $blocked = (string) ($record['blocked'] ?? '');
    return [
        'subject' => trim(($record['product'] ?? '') . ' ' . ($record['model'] ?? '')),
        'from_name' => (string) ($record['name'] ?? ''),
        'from_email' => (string) ($record['email'] ?? ''),
        'fields' => $record,
        'attribution' => $attribution,
        'meta' => ['url' => (string) ($record['source_page'] ?? ''), 'post_title' => (string) ($record['source_title'] ?? ''), 'remote_ip' => (string) ($record['ip'] ?? ''), 'device' => (string) ($record['device'] ?? '')],
        'channel' => (string) ($record['channel'] ?? ''),
        'submission_status' => ($record['mail_sent'] ?? '') === '0' ? 'mail_failed' : 'mail_sent',
        'spam' => $blocked !== '',
        'spam_log' => $blocked !== '' ? [['agent' => 'blocklist', 'reason' => $blocked]] : [],
        'akismet' => [],
    ];
}

add_action('init', static function (): void {
    if (!function_exists('bl_wp_inquiry_ready') || !bl_wp_inquiry_ready()) return;
    register_post_meta('bl_inquiry', '_bl_enquiry', [
        'type' => 'object',
        'single' => true,
        'description' => 'The enquiry in the shape Flamingo enquiries had: sender, fields, attribution, page, IP and block reason.',
        'show_in_rest' => ['schema' => ['type' => 'object', 'context' => ['edit'], 'additionalProperties' => true]],
        'auth_callback' => '__return_false',
    ]);
}, 20);

add_filter('get_post_metadata', static function ($value, $postId, $key) {
    if ($key !== '_bl_enquiry' || get_post_type((int) $postId) !== 'bl_inquiry') return $value;
    return [bl_inquiry_legacy_record((int) $postId)];
}, 10, 3);
