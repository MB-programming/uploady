<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: auto_replies.php');
    exit;
}

Csrf::verify();

$ruleId = (int) ($_POST['rule_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!AutoReplyRule::findForUser($ruleId, Auth::id())) {
    header('Location: auto_replies.php');
    exit;
}

if ($action === 'pause' || $action === 'resume') {
    AutoReplyRule::setActive($ruleId, Auth::id(), $action === 'resume');
    header('Location: auto_replies.php');
    exit;
}

if ($action === 'delete') {
    AutoReplyRule::delete($ruleId, Auth::id());
    header('Location: auto_replies.php?deleted=1');
    exit;
}

header('Location: auto_replies.php');
exit;
