<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireLogin();

$config = App::config('tiktok');

// TikTok's OAuth requires PKCE (code_verifier / code_challenge).
$codeVerifier = bin2hex(random_bytes(32));
$codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

$state = OAuthState::create(Auth::id(), 'tiktok', $codeVerifier);

$params = [
    'client_key' => $config['client_key'],
    'redirect_uri' => $config['redirect_uri'] ?: App::url('oauth/tiktok_callback.php'),
    'response_type' => 'code',
    'scope' => 'user.info.basic,video.publish,video.upload',
    'state' => $state,
    'code_challenge' => $codeChallenge,
    'code_challenge_method' => 'S256',
];

header('Location: https://www.tiktok.com/v2/auth/authorize/?' . http_build_query($params));
exit;
