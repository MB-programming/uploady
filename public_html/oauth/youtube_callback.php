<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireLogin();

$config = App::config('youtube');
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code || !$state || !OAuthState::consume($state, 'youtube')) {
    header('Location: ' . App::url('accounts.php?error=youtube_state'));
    exit;
}

try {
    $tokenResponse = Http::request('POST', 'https://oauth2.googleapis.com/token', [
        'form' => [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect_uri'] ?: App::url('oauth/youtube_callback.php'),
        ],
    ]);

    if ($tokenResponse['status'] !== 200 || empty($tokenResponse['json']['access_token'])) {
        throw new RuntimeException('Token exchange failed: ' . $tokenResponse['body']);
    }

    $accessToken = $tokenResponse['json']['access_token'];
    $refreshToken = $tokenResponse['json']['refresh_token'] ?? null;
    $expiresAt = date('Y-m-d H:i:s', time() + (int) ($tokenResponse['json']['expires_in'] ?? 3600));

    $channelResponse = Http::request('GET', 'https://www.googleapis.com/youtube/v3/channels?part=snippet&mine=true', [
        'headers' => ['Authorization' => "Bearer $accessToken"],
    ]);

    $channel = $channelResponse['json']['items'][0] ?? null;
    if (!$channel) {
        throw new RuntimeException('No YouTube channel found on this Google account.');
    }

    if (!$refreshToken) {
        // Google only returns a refresh_token the first time a user consents (prompt=consent forces
        // it, but if it's still missing we can't do unattended uploads later — surface this clearly).
        header('Location: ' . App::url('accounts.php?error=youtube_no_refresh'));
        exit;
    }

    SocialAccount::upsert(
        Auth::id(),
        'youtube',
        $channel['id'],
        $channel['snippet']['title'] ?? 'YouTube Channel',
        $accessToken,
        $refreshToken,
        $expiresAt
    );

    header('Location: ' . App::url('accounts.php?connected=youtube'));
    exit;
} catch (Throwable $e) {
    error_log('[youtube_callback] ' . $e->getMessage());
    header('Location: ' . App::url('accounts.php?error=youtube_exception'));
    exit;
}
