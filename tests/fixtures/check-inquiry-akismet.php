<?php
/** The theme's form fields get CF7's akismet: options as CF7 reads them; other forms and marked fields stay as they are. */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
function get_template_directory_uri() { return 'https://example.test/theme'; }
function home_url($path = '') { return 'https://example.test' . $path; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function get_post_type($id) { return 'wpcf7_contact_form'; }
function get_post_status($id) { return 'publish'; }
function add_action(...$args) {}
function add_filter(...$args) {}
class Flamingo_Inbound_Message {}
class WPCF7_ContactForm { static $current; static function get_current() { return self::$current; } function __construct(public int $n) {} function id() { return $this->n; } }
require dirname(__DIR__, 2) . '/inc/bootstrap.php';
require dirname(__DIR__, 2) . '/inc/wp-inquiry.php';
$GLOBALS['options']['bl_inquiry_form'] = 18;
$tag = static fn(string $name, array $options = []): array => ['name' => $name, 'options' => $options];
WPCF7_ContactForm::$current = new WPCF7_ContactForm(18);
if (bl_wp_inquiry_akismet_tag($tag('name', ['autocomplete:name']))['options'] !== ['autocomplete:name', 'akismet:author']) exit(1);
if (bl_wp_inquiry_akismet_tag($tag('email'))['options'] !== ['akismet:author_email']) exit(2);
if (bl_wp_inquiry_akismet_tag($tag('message')) !== $tag('message')) exit(3);
if (bl_wp_inquiry_akismet_tag($tag('email', ['akismet:author'])) !== $tag('email', ['akismet:author'])) exit(4);
WPCF7_ContactForm::$current = new WPCF7_ContactForm(19);
if (bl_wp_inquiry_akismet_tag($tag('name')) !== $tag('name')) exit(5);
WPCF7_ContactForm::$current = null;
if (bl_wp_inquiry_akismet_tag($tag('name')) !== $tag('name')) exit(6);
// CF7's form-tag object: ArrayAccess whose offsetGet returns by value, as WPCF7_FormTag does.
$object = new class implements ArrayAccess {
    public $name = 'email';
    public $options = ['autocomplete:email'];
    function offsetExists($o): bool { return property_exists($this, $o); }
    function offsetGet($o): mixed { return property_exists($this, $o) ? $this->{$o} : null; }
    function offsetSet($o, $v): void { if (property_exists($this, $o)) $this->{$o} = $v; }
    function offsetUnset($o): void {}
};
WPCF7_ContactForm::$current = new WPCF7_ContactForm(18);
set_error_handler(static function () { exit(7); });
if (bl_wp_inquiry_akismet_tag($object)->options !== ['autocomplete:email', 'akismet:author_email']) exit(8);
restore_error_handler();
echo "inquiry akismet ok\n";
