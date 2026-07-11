<?php
require __DIR__ . '/../../app/bootstrap.php';

/**
 * Streams a pending post's video file so Instagram's Graph API can fetch it while building
 * the media container. Gated by the post's random public_token (not the video's real ID) so
 * URLs aren't guessable, and it stops working the moment the post leaves "pending work" state
 * — auto-delete removes the file from disk right after publishing finishes anyway.
 */
$token = $_GET['token'] ?? '';
if (!preg_match('/^[a-f0-9]{48}$/', $token)) {
    http_response_code(404);
    exit;
}

$post = Post::findByToken($token);
if (!$post || !$post['video_path'] || !is_file($post['video_path'])) {
    http_response_code(404);
    exit;
}

$pendingTargets = PostTarget::pendingForPost((int) $post['id']);
if (empty($pendingTargets)) {
    // Nothing left to publish for this post — the file is about to be/already deleted; don't serve it.
    http_response_code(404);
    exit;
}

$path = $post['video_path'];
$size = filesize($path);

header('Content-Type: video/mp4');
header('Content-Length: ' . $size);
header('Content-Disposition: inline; filename="video.mp4"');
header('Accept-Ranges: none');

readfile($path);
