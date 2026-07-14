<?php
require __DIR__ . '/../app/bootstrap.php';

if (Auth::check()) {
    header('Location: ' . App::url('dashboard.php'));
    exit;
}

// Reuses the same Google OAuth client already registered for YouTube — just needs its own
// redirect URI added to the client's "Authorized redirect URIs" in Google Cloud Console.
$config = App::config('youtube');
$state = bin2hex(random_bytes(24));
$_SESSION['google_login_state'] = $state;

$params = [
    'client_id' => $config['client_id'],
    'redirect_uri' => App::url('oauth/google_login_callback.php'),
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
];

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
exit;
