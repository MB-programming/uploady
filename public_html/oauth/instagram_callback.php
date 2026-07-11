<?php
require __DIR__ . '/../../app/bootstrap.php';
Auth::requireLogin();

$config = App::config('instagram');
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code || !$state || !OAuthState::consume($state, 'instagram')) {
    header('Location: ' . App::url('accounts.php?error=instagram_state'));
    exit;
}

try {
    $redirectUri = $config['redirect_uri'] ?: App::url('oauth/instagram_callback.php');

    $tokenResponse = Http::request('GET', 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
        'client_id' => $config['app_id'],
        'redirect_uri' => $redirectUri,
        'client_secret' => $config['app_secret'],
        'code' => $code,
    ]));

    if ($tokenResponse['status'] !== 200 || empty($tokenResponse['json']['access_token'])) {
        throw new RuntimeException('Facebook token exchange failed: ' . $tokenResponse['body']);
    }
    $shortLivedToken = $tokenResponse['json']['access_token'];

    // Exchange for a long-lived (~60 day) user token so the client doesn't have to reconnect often.
    $longLivedResponse = Http::request('GET', 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
        'grant_type' => 'fb_exchange_token',
        'client_id' => $config['app_id'],
        'client_secret' => $config['app_secret'],
        'fb_exchange_token' => $shortLivedToken,
    ]));
    $longLivedToken = $longLivedResponse['json']['access_token'] ?? $shortLivedToken;
    $expiresIn = $longLivedResponse['json']['expires_in'] ?? 5184000; // ~60 days fallback
    $expiresAt = date('Y-m-d H:i:s', time() + (int) $expiresIn);

    // Find Facebook Pages managed by this user, then the Instagram Business account linked to each.
    $pagesResponse = Http::request('GET', 'https://graph.facebook.com/v19.0/me/accounts?' . http_build_query([
        'access_token' => $longLivedToken,
        'fields' => 'id,name,access_token',
    ]));

    $pages = $pagesResponse['json']['data'] ?? [];
    $connectedAny = false;

    foreach ($pages as $page) {
        $igResponse = Http::request('GET', "https://graph.facebook.com/v19.0/{$page['id']}?" . http_build_query([
            'fields' => 'instagram_business_account{id,username}',
            'access_token' => $page['access_token'],
        ]));

        $igAccount = $igResponse['json']['instagram_business_account'] ?? null;
        if (!$igAccount) {
            continue; // this Page has no linked Instagram Business/Creator account
        }

        // Publishing calls target the IG user id but must be authenticated with the Page's access token.
        SocialAccount::upsert(
            Auth::id(),
            'instagram',
            $igAccount['id'],
            '@' . ($igAccount['username'] ?? $page['name']),
            $page['access_token'],
            null, // Page tokens don't use refresh tokens; we re-derive a long-lived one on reconnect
            $expiresAt,
            ['facebook_page_id' => $page['id'], 'facebook_page_name' => $page['name']]
        );
        $connectedAny = true;
    }

    if (!$connectedAny) {
        header('Location: ' . App::url('accounts.php?error=instagram_no_business_account'));
        exit;
    }

    header('Location: ' . App::url('accounts.php?connected=instagram'));
    exit;
} catch (Throwable $e) {
    error_log('[instagram_callback] ' . $e->getMessage());
    header('Location: ' . App::url('accounts.php?error=instagram_exception'));
    exit;
}
