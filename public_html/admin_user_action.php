<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_users.php');
    exit;
}
Csrf::verify();

$action = $_POST['action'] ?? '';
$userId = (int) ($_POST['user_id'] ?? 0);

if ($action === 'delete') {
    if ($userId === Auth::id()) {
        // Self-deletion goes through data-deletion.php, which also logs the admin out cleanly.
        header('Location: admin_users.php?error=1');
        exit;
    }
    $target = User::findById($userId);
    if (!$target) {
        header('Location: admin_users.php?error=1');
        exit;
    }
    User::deleteAccount($userId);
    header('Location: admin_users.php?deleted=1');
    exit;
}

header('Location: admin_users.php');
exit;
