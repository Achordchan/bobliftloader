<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * The enquiries Flamingo keeps (inbound messages, spam included) are open to the REST API read-only, so tools outside
 * wp-admin (the WordPress.com connector, scripts) can read what was sent without opening Flamingo. Only users who may
 * manage Flamingo (administrators) can read them. Nothing can be created, changed or deleted through the API: that
 * stays in Flamingo's own screens.
 *
 * The site now takes enquiries through the inquiry plugin (wp-inquiry.php: /wp/v2/bl_inquiries, history copied in). This
 * old route stays as it was for Flamingo's own entries, same IDs and same shape, also once Flamingo is switched off: the
 * theme then registers the type itself, read-only and out of the admin menus, and reads the entries from the database.
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
    // Flamingo maps its capability to administrators; without Flamingo the same people are its administrators.
    return current_user_can('flamingo_edit_inbound_messages') || (!class_exists('Flamingo_Inbound_Message') && current_user_can('manage_options'));
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
    if ($type !== 'flamingo_inbound' || !class_exists('BL_Enquiries_REST_Controller')) return $args;
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

/**
 * One enquiry as Flamingo holds it, in the shape meta._bl_enquiry reads. Read from Flamingo's own post meta (_subject,
 * _from_name, _from_email, _fields with one _field_<name> each, _meta, _submission_status, _spam_log, _akismet), so it
 * works with Flamingo switched off too.
 */
function bl_enquiry_record(int $postId): array
{
    if (get_post_type($postId) !== 'flamingo_inbound') return [];
    $meta = static fn(string $key) => get_post_meta($postId, $key, true);
    $fields = [];
    foreach (array_keys(is_array($meta('_fields')) ? $meta('_fields') : []) as $name) $fields[(string) $name] = $meta('_field_' . $name);
    $channel = function_exists('wp_get_object_terms') ? wp_get_object_terms($postId, 'flamingo_inbound_channel', ['fields' => 'slugs']) : [];
    return [
        'subject' => (string) $meta('_subject'),
        'from_name' => (string) $meta('_from_name'),
        'from_email' => (string) $meta('_from_email'),
        'fields' => $fields,
        // The advertisement the sender came from (Google click ID, campaign tags, landing page), when they agreed to advertising storage.
        'attribution' => bl_enquiry_attribution($fields),
        'meta' => is_array($meta('_meta')) ? $meta('_meta') : [],
        'channel' => is_array($channel) && $channel ? (string) $channel[0] : '',
        'submission_status' => (string) $meta('_submission_status'),
        'spam' => get_post_status($postId) === 'flamingo-spam',
        'spam_log' => is_array($meta('_spam_log')) ? $meta('_spam_log') : [],
        'akismet' => is_array($meta('_akismet')) ? $meta('_akismet') : [],
    ];
}

/** With Flamingo switched off, its entries stay readable here: the type and its spam status, without any admin screen. */
add_action('init', static function (): void {
    if (post_type_exists('flamingo_inbound')) return;
    register_post_type('flamingo_inbound', ['public' => false, 'show_ui' => false, 'show_in_menu' => false, 'capability_type' => 'post', 'map_meta_cap' => true, 'capabilities' => ['create_posts' => 'do_not_allow']]);
    if (!get_post_status_object('flamingo-spam')) register_post_status('flamingo-spam', ['internal' => true, 'label' => 'Spam']);
}, 30);

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
