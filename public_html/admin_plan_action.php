<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_plans.php');
    exit;
}
Csrf::verify();

$action = $_POST['action'] ?? '';
$planId = (int) ($_POST['plan_id'] ?? 0);
$plan = Plan::find($planId);

if (!$plan) {
    header('Location: admin_plans.php');
    exit;
}

if ($action === 'delete') {
    Plan::delete($planId);
    header('Location: admin_plans.php?deleted=1');
    exit;
}

header('Location: admin_plans.php');
exit;
