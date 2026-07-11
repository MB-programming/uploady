<?php
require __DIR__ . '/../app/bootstrap.php';
header('Location: ' . (Auth::check() ? 'dashboard.php' : 'login.php'));
exit;
