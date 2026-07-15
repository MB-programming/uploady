<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0'); // never leak errors to visitors on shared hosting; see storage/logs

spl_autoload_register(function (string $class) {
    $paths = [
        __DIR__ . '/src/' . $class . '.php',
        __DIR__ . '/src/Models/' . $class . '.php',
        __DIR__ . '/src/Services/' . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (is_file($path)) {
            require $path;
            return;
        }
    }
});

ini_set('log_errors', '1');
ini_set('error_log', App::storagePath('logs/php-error.log'));

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // blob: in img/media powers the client-side video/thumbnail preview on upload.php
    // (object URLs of local files — nothing external is loaded).
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; style-src 'self' 'unsafe-inline'; script-src 'self'; frame-ancestors 'none';");
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function t(string $key): string
{
    return Lang::t($key);
}
