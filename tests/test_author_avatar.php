<?php
/** The author card photo: the one set for the author's login on the news content screen, else their Gravatar. */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wordpress.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-content-editor.php';
function get_the_author_meta($field, $user) { return $field === 'user_login' ? ([1 => 'achordchan', 2 => 'editor'][$user] ?? '') : ''; }
function get_avatar_url($user, $args = []) { return 'https://secure.gravatar.com/avatar/' . $user . '?s=' . $args['size']; }
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

$keys = [];
foreach (bl_content_schema('news', bl_content_defaults('news')) as $section) foreach ($section['fields'] as $field) $keys[$field['key']] = $field;
$check(($keys['authors.items']['type'] ?? '') === 'list' && ($keys['authors.items']['fields']['image']['type'] ?? '') === 'image' && isset($keys['authors.items']['fields']['login']), 'author photos are not editable');
$clean = bl_content_clean_all('news', ['authors' => ['items' => [['login' => ' AchordChan ', 'image' => 'https://example.test/wp-content/uploads/2026/10/achord.webp'], ['login' => '', 'image' => '']]]]);
$check($clean['authors']['items'] === [['login' => 'AchordChan', 'image' => '/wp-content/uploads/2026/10/achord.webp']], 'a saved photo is not kept as a root-relative path, or an empty row survives');

$GLOBALS['options']['bl_content_news'] = $clean;
$check(bl_author_avatar(1) === '/wp-content/uploads/2026/10/achord.webp', 'the set photo is not used (login matched case-insensitively)');
$check(bl_author_avatar(2) === 'https://secure.gravatar.com/avatar/2?s=128', 'an author without a photo lost the Gravatar');
echo "author avatar: $checks checks passed\n";
