<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: data-deletion.php');
    exit;
}
Csrf::verify();

$userId = Auth::id();
User::deleteAccount($userId);

Auth::logout();
header('Location: login.php?deleted=1');
exit;
