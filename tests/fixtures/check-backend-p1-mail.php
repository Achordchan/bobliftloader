<?php
/** CF7 boundary objects exercise recipient selection without sending mail or loading a database. */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
function get_template_directory_uri() { return 'https://example.test/theme'; }
function home_url($path = '') { return 'https://example.test' . $path; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function add_action(...$args) {}
function add_filter(...$args) {}
require dirname(__DIR__, 2) . '/inc/bootstrap.php';
require dirname(__DIR__, 2) . '/inc/wp-inquiry.php';
$form = new class { function id() { return 18; } };
$mail = new class { function name() { return 'mail'; } };
$reply = new class { function name() { return 'mail_2'; } };
$other = new class { function id() { return 19; } };
$GLOBALS['options']['bl_inquiry_form'] = 18;
$components = ['recipient' => 'legacy@example.test', 'body' => 'Original notification'];
if (bl_wp_inquiry_recipient($components, $form, $mail) !== $components) exit(1);
$GLOBALS['options']['bl_content_global'] = ['company' => ['email' => 'sales@example.test']];
if (bl_wp_inquiry_recipient($components, $form, $mail) !== array_replace($components, ['recipient' => 'sales@example.test'])) exit(2);
if (bl_wp_inquiry_recipient($components, $form, $reply) !== $components) exit(3);
if (bl_wp_inquiry_recipient($components, $other, $mail) !== $components) exit(4);
$GLOBALS['options']['bl_content_global'] = ['_bl_email_explicit' => true];
if (bl_wp_inquiry_recipient($components, $form, $mail)['recipient'] !== 'info@boblift.com') exit(5);
if (bl_wp_inquiry_recipient($components, $form, $reply) !== $components) exit(6);
echo "mail recipient boundaries ok\n";
