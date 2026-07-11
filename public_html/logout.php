<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::logout();
header('Location: login.php');
exit;
