<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

Csrf::verify();

$postId = (int) ($_POST['post_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($action === 'cancel') {
    $ok = Post::cancelIfPending($postId, Auth::id());
    header('Location: dashboard.php?' . ($ok ? 'cancelled=1' : 'error=cancel_failed'));
    exit;
}

if ($action === 'reschedule') {
    $timestamp = strtotime((string) ($_POST['scheduled_at'] ?? ''));
    if (!$timestamp) {
        header('Location: dashboard.php?error=reschedule_failed');
        exit;
    }
    $ok = Post::rescheduleIfPending($postId, Auth::id(), date('Y-m-d H:i:s', $timestamp));
    header('Location: dashboard.php?' . ($ok ? 'rescheduled=1' : 'error=reschedule_failed'));
    exit;
}

header('Location: dashboard.php');
exit;
