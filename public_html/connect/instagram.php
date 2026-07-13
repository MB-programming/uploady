<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireLogin();

$config = App::config('instagram');
$state = OAuthState::create(Auth::id(), 'instagram');

$params = [
    'client_id' => $config['app_id'],
    'redirect_uri' => $config['redirect_uri'] ?: App::url('oauth/instagram_callback.php'),
    'response_type' => 'code',
    // Instagram publishing is done through a Facebook Page connected to an Instagram
    // Business/Creator account, so we need Page + Instagram permissions.
    'scope' => 'instagram_basic,instagram_content_publish,pages_show_list,pages_read_engagement,business_management',
    'state' => $state,
];

header('Location: https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query($params));
exit;
