<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data-deletion.php');
    exit;
}
Csrf::verify();

$userId = Auth::id();

// Remove any video/thumbnail files still on disk before the DB rows (and their paths) disappear.
foreach (Post::forUser($userId) as $post) {
    if ($post['video_path'] && is_file($post['video_path'])) {
        @unlink($post['video_path']);
    }
    if ($post['thumbnail_path'] && is_file($post['thumbnail_path'])) {
        @unlink($post['thumbnail_path']);
    }
}
$userDir = App::storagePath('uploads/' . $userId);
if (is_dir($userDir)) {
    array_map('unlink', glob("$userDir/*") ?: []);
    @rmdir($userDir);
}

// social_accounts, posts, and post_targets all cascade-delete via FK ON DELETE CASCADE.
$stmt = Database::get()->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$userId]);

Auth::logout();
header('Location: login.php?deleted=1');
exit;
