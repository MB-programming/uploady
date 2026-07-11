<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireLogin();

$config = App::config('tiktok');
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

$stateRow = ($code && $state) ? OAuthState::consume($state, 'tiktok') : null;
if (!$stateRow) {
    header('Location: ' . App::url('accounts.php?error=tiktok_state'));
    exit;
}

try {
    $tokenResponse = Http::request('POST', 'https://open.tiktokapis.com/v2/oauth/token/', [
        'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        'form' => [
            'client_key' => $config['client_key'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect_uri'] ?: App::url('oauth/tiktok_callback.php'),
            'code_verifier' => $stateRow['code_verifier'],
        ],
    ]);

    $tokenData = $tokenResponse['json'];
    if ($tokenResponse['status'] !== 200 || empty($tokenData['access_token'])) {
        throw new RuntimeException('TikTok token exchange failed: ' . $tokenResponse['body']);
    }

    $accessToken = $tokenData['access_token'];
    $refreshToken = $tokenData['refresh_token'] ?? null;
    $expiresAt = date('Y-m-d H:i:s', time() + (int) ($tokenData['expires_in'] ?? 86400));
    $openId = $tokenData['open_id'];

    $userInfo = Http::request('GET', 'https://open.tiktokapis.com/v2/user/info/?fields=open_id,display_name', [
        'headers' => ['Authorization' => "Bearer $accessToken"],
    ]);
    $displayName = $userInfo['json']['data']['user']['display_name'] ?? 'TikTok Account';

    SocialAccount::upsert(
        Auth::id(),
        'tiktok',
        $openId,
        $displayName,
        $accessToken,
        $refreshToken,
        $expiresAt
    );

    header('Location: ' . App::url('accounts.php?connected=tiktok'));
    exit;
} catch (Throwable $e) {
    error_log('[tiktok_callback] ' . $e->getMessage());
    header('Location: ' . App::url('accounts.php?error=tiktok_exception'));
    exit;
}
