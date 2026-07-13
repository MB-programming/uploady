<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireLogin();

$config = App::config('youtube');
$state = OAuthState::create(Auth::id(), 'youtube');

$params = [
    'client_id' => $config['client_id'],
    'redirect_uri' => $config['redirect_uri'] ?: App::url('oauth/youtube_callback.php'),
    'response_type' => 'code',
    'access_type' => 'offline',
    'prompt' => 'consent',
    'include_granted_scopes' => 'true',
    'scope' => 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube.readonly',
    'state' => $state,
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
