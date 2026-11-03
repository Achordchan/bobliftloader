<?php
/** Flamingo's enquiries reach REST read-only and only for users who may manage Flamingo. */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
function add_action(...$args) {}
function add_filter(...$args) {}
function rest_authorization_required_code() { return 401; }
function current_user_can($cap) { return $GLOBALS['can'] && $cap === 'flamingo_edit_inbound_messages'; }
class WP_Error { function __construct(public string $code = '', public string $message = '', public array $data = []) {} }
class WP_REST_Posts_Controller {
    function get_items_permissions_check($request) { return true; }
    function get_item_permissions_check($request) { return true; }
    function check_read_permission($post) { return true; }
    function create_item_permissions_check($request) { return true; }
    function update_item_permissions_check($request) { return true; }
    function delete_item_permissions_check($request) { return true; }
}
require dirname(__DIR__, 2) . '/inc/wp-enquiries-rest.php';
$args = bl_enquiries_post_type_args(['public' => false, 'show_in_rest' => false, 'capability_type' => ['flamingo_edit_options', 'flamingo_edit_options']], 'flamingo_inbound');
if (!$args['show_in_rest'] || $args['rest_base'] !== 'bl_enquiries' || $args['rest_controller_class'] !== 'BL_Enquiries_REST_Controller') exit(1);
if ($args['supports'] !== ['title', 'custom-fields'] || $args['capability_type'] !== ['flamingo_edit_options', 'flamingo_edit_options'] || $args['public']) exit(2);
if (bl_enquiries_post_type_args(['show_in_rest' => false], 'post') !== ['show_in_rest' => false]) exit(3);
$c = new BL_Enquiries_REST_Controller();
foreach ([true, false] as $GLOBALS['can']) {
    foreach (['create_item_permissions_check', 'update_item_permissions_check', 'delete_item_permissions_check'] as $write) if (!($c->$write(null) instanceof WP_Error) || $c->$write(null)->data['status'] !== 403) exit(4);
}
$GLOBALS['can'] = false;
if (!($c->get_items_permissions_check(null) instanceof WP_Error) || !($c->get_item_permissions_check(null) instanceof WP_Error) || $c->check_read_permission(null) !== false) exit(5);
$GLOBALS['can'] = true;
if ($c->get_items_permissions_check(null) !== true || $c->get_item_permissions_check(null) !== true || $c->check_read_permission(null) !== true) exit(6);
// Flamingo's history keeps its ad attribution; the plugin's records answer in the same shape, to administrators only.
$attribution = bl_enquiry_attribution(['bl_attr_gclid' => 'Cj0-abc', 'bl_attr_utm_source' => ['google'], 'bl_attr_landing' => '', 'name' => 'x']);
if ($attribution !== ['gclid' => 'Cj0-abc', 'utm_source' => 'google']) exit(7);
function achord_inquiry_can_read() { return $GLOBALS['admin']; }
function achord_inquiry_record($id) { return ['product' => '迷你翻斗车', 'model' => 'FY20', 'name' => 'Ann', 'email' => 'a@x.test', 'attr_gclid' => 'Cj0', 'source_page' => 'https://x.test/p/', 'ip' => '203.0.113.7', 'mail_sent' => '0', 'blocked' => '黑名单邮箱']; }
$GLOBALS['admin'] = false;
if (bl_inquiry_legacy_record(5) !== []) exit(8);
$GLOBALS['admin'] = true;
$legacy = bl_inquiry_legacy_record(5);
if ($legacy['subject'] !== '迷你翻斗车 FY20' || $legacy['from_email'] !== 'a@x.test' || $legacy['attribution'] !== ['gclid' => 'Cj0'] || $legacy['meta']['remote_ip'] !== '203.0.113.7') exit(9);
if ($legacy['submission_status'] !== 'mail_failed' || $legacy['spam'] !== true || $legacy['fields']['name'] !== 'Ann') exit(10);
// While the plugin takes enquiries, Flamingo no longer claims the old address.
if (true) { function bl_wp_inquiry_ready() { return true; } }
if (bl_enquiries_post_type_args(['show_in_rest' => false], 'flamingo_inbound') !== ['show_in_rest' => false]) exit(11);
echo "enquiries rest ok\n";
