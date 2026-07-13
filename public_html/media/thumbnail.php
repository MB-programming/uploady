<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireLogin();

$postId = (int) ($_GET['post_id'] ?? 0);
$post = Post::find($postId);
$currentUser = Auth::user();

if (!$post || (!$currentUser['is_admin'] && (int) $post['user_id'] !== Auth::id())) {
    http_response_code(404);
    exit;
}

if (!$post['thumbnail_path'] || !is_file($post['thumbnail_path'])) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($post['thumbnail_path']) ?: 'image/jpeg';
header('Content-Type: ' . $mime);
header('Cache-Control: private, max-age=3600');
readfile($post['thumbnail_path']);
