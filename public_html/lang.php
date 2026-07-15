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

// Rebuild the redirect target from the referer's path+query ONLY — never echo the raw header
// back. This kills every open-redirect variant (foreign host, scheme tricks, //host forms).
$back = 'index.php';
$parsed = parse_url($_SERVER['HTTP_REFERER'] ?? '');
$host = $_SERVER['HTTP_HOST'] ?? '';
if (!empty($parsed['path']) && (empty($parsed['host']) || $parsed['host'] === $host) && str_starts_with($parsed['path'], '/')) {
    $back = $parsed['path'] . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
}

header('Location: ' . $back);
exit;
