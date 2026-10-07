<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Ownership shares the core body SQL write; metadata failures cannot resurrect imported sections. */
add_filter('wp_insert_post_data', static function (array $data, array $postarr): array {
    if (($data['post_type'] ?? '') !== 'bl_news') return $data;
    $editorBody = array_key_exists('content', $_POST) && array_key_exists('post_content', $postarr);
    $restBody = ($postarr['post_content_filtered'] ?? '') === 'bl-body-managed';
    if ($editorBody || $restBody) $data['post_content_filtered'] = 'bl-body-managed';
    return $data;
}, 10, 2);

/** Old structured and standalone metadata markers remain readable without creating new metadata. */
function bl_news_body_managed(WP_Post $post, array $record): bool
{
    return ($post->post_content_filtered ?? '') === 'bl-body-managed'
        || !empty($record['_bl_body_managed'])
        || (bool) get_post_meta($post->ID, '_bl_body_managed', true);
}
