<?php
/** Loads config.local.php (real secrets, gitignored) over the example defaults. */

$example = require __DIR__ . '/config.example.php';
$localFile = __DIR__ . '/config.local.php';

if (!is_file($localFile)) {
    // Fail loudly and safely instead of running with empty API credentials.
    http_response_code(500);
    die('Missing app/config/config.local.php — copy config.example.php and fill it in.');
}

$local = require $localFile;

return array_replace_recursive($example, $local);
