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
echo "enquiries rest ok\n";
