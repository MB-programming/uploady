<?php
require __DIR__ . '/app/bootstrap.php';

$to = ($_GET['set'] ?? '') === 'en' ? 'en' : 'ar';
setcookie('uploady_lang', $to, [
    'expires' => time() + 60 * 60 * 24 * 365,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => false,
    'samesite' => 'Lax',
]);

$back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
$host = $_SERVER['HTTP_HOST'] ?? '';
$parsed = parse_url($back);
if (!empty($parsed['host']) && $parsed['host'] !== $host) {
    $back = 'index.php';
}

header('Location: ' . $back);
exit;
