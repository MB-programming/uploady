<?php
/**
 * Copy this file to config.local.php (same folder) and fill in real values.
 * config.local.php is gitignored — never commit real secrets.
 */
return [
    'app_url' => 'https://your-domain.com', // no trailing slash

    'db' => [
        'host' => 'localhost',
        'name' => 'u123456789_uploady',
        'user' => 'u123456789_uploady',
        'pass' => 'change-me',
    ],

    // 32-byte random key used to encrypt OAuth tokens at rest, e.g. generate with:
    // php -r "echo bin2hex(random_bytes(32));"
    'encryption_key' => 'REPLACE-WITH-64-HEX-CHARS',

    // Shared secret the Hostinger cron job must send to cron/publish.php over HTTP,
    // OR set at CLI via env var CRON_SECRET when Hostinger allows `php cron/publish.php`.
    'cron_secret' => 'REPLACE-WITH-RANDOM-STRING',

    // Per-client storage cap. Only videos still sitting on disk count against it — once a
    // post finishes publishing (or fails for good) auto-delete frees the file and the quota.
    'storage_quota_gb' => 10,

    // Failed login attempts (per email) allowed within login_lockout_minutes before a temporary lock.
    'login_max_attempts' => 5,
    'login_lockout_minutes' => 15,

    'youtube' => [
        // Google Cloud Console -> APIs & Services -> Credentials (OAuth Client, Web application)
        // Enable "YouTube Data API v3". Add {app_url}/oauth/youtube_callback.php as redirect URI.
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => '', // filled automatically from app_url if left empty
    ],

    'tiktok' => [
        // developers.tiktok.com -> app must request "Content Posting API" scope video.publish.
        // Unaudited apps can only publish as private/self-view until TikTok approves the app.
        'client_key' => '',
        'client_secret' => '',
        'redirect_uri' => '',
    ],

    'instagram' => [
        // developers.facebook.com app with Instagram Graph API + "Instagram content publishing"
        // permission (needs App Review for anyone other than admins/testers of the app).
        // Requires the client's Instagram account to be a Business/Creator account linked to a Facebook Page.
        'app_id' => '',
        'app_secret' => '',
        'redirect_uri' => '',
    ],
];
