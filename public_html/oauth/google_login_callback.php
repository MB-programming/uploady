<?php
require __DIR__ . '/../app/bootstrap.php';

$config = App::config('youtube');
$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;
$expectedState = $_SESSION['google_login_state'] ?? null;
unset($_SESSION['google_login_state']);

if (!$code || !$state || !$expectedState || !hash_equals($expectedState, $state)) {
    header('Location: ' . App::url('login.php?error=google_state'));
    exit;
}

try {
    $tokenResponse = Http::request('POST', 'https://oauth2.googleapis.com/token', [
        'form' => [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => App::url('oauth/google_login_callback.php'),
        ],
    ]);

    if ($tokenResponse['status'] !== 200 || empty($tokenResponse['json']['access_token'])) {
        throw new RuntimeException('Token exchange failed: ' . $tokenResponse['body']);
    }

    $userInfoResponse = Http::request('GET', 'https://www.googleapis.com/oauth2/v3/userinfo', [
        'headers' => ['Authorization' => 'Bearer ' . $tokenResponse['json']['access_token']],
    ]);

    if ($userInfoResponse['status'] !== 200 || empty($userInfoResponse['json']['email'])) {
        throw new RuntimeException('Failed to fetch Google user info: ' . $userInfoResponse['body']);
    }

    $email = $userInfoResponse['json']['email'];
    $name = $userInfoResponse['json']['name'] ?? explode('@', $email)[0];

    // Sign-in by email: an existing account with that email is reused, otherwise a new one is created.
    $user = User::findByEmail($email);
    $userId = $user ? (int) $user['id'] : User::create($name, $email, bin2hex(random_bytes(16)));

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    header('Location: ' . App::url('dashboard.php'));
    exit;
} catch (Throwable $e) {
    error_log('[google_login_callback] ' . $e->getMessage());
    header('Location: ' . App::url('login.php?error=google_exception'));
    exit;
}
