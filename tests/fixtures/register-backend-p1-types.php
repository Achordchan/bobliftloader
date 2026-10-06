<?php
/** Isolated CPT registration probe; it never instantiates WordPress or opens a database. */
define('ABSPATH', __DIR__ . '/');
function register_post_type($type, $args) { $GLOBALS['types'][$type] = $args; }
function add_rewrite_tag(...$args) {}
function add_rewrite_rule(...$args) {}
require dirname(__DIR__, 2) . '/inc/wordpress.php';
bl_register_content_types();
echo json_encode($GLOBALS['types']['bl_news']['supports']);
