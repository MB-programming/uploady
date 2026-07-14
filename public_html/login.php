<?php
require __DIR__ . '/app/bootstrap.php';

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$googleErrors = [
    'google_state' => t('auth.err_google_state'),
    'google_exception' => t('auth.err_google_exception'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if (LoginAttempt::isLocked($email)) {
        $error = sprintf(t('auth.locked_out'), (int) App::config('login_lockout_minutes'));
    } elseif (Auth::attempt($email, $password)) {
        LoginAttempt::clear($email);
        header('Location: dashboard.php');
        exit;
    } else {
        LoginAttempt::record($email, $ip);
        $error = t('auth.invalid_credentials');
    }
}

$pageTitle = t('auth.login_title');
require __DIR__ . '/partials_header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
    <h1><?= t('auth.login_title') ?></h1>
    <?php if (!empty($_GET['deleted'])): ?><div class="alert success"><?= t('auth.deleted_success') ?></div><?php endif; ?>
    <?php if (!empty($_GET['reset'])): ?><div class="alert success"><?= t('auth.reset_success') ?></div><?php endif; ?>
    <?php if (!empty($_GET['error']) && isset($googleErrors[$_GET['error']])): ?>
        <div class="alert error"><?= htmlspecialchars($googleErrors[$_GET['error']]) ?></div>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <label><?= t('auth.email') ?></label>
        <input type="email" name="email" required>
        <label><?= t('auth.password') ?></label>
        <input type="password" name="password" required>
        <p class="muted" style="margin:8px 0 0;text-align:end;"><a href="forgot_password.php"><?= t('auth.forgot_password_link') ?></a></p>
        <p><button class="btn" type="submit" style="margin-top:12px;"><?= t('auth.login_submit') ?></button></p>
    </form>
    <p class="muted" style="text-align:center;margin:18px 0;"><?= t('auth.or_divider') ?></p>
    <p><a href="oauth/google_login.php" class="btn secondary" style="width:100%;"><?= Icons::globe() ?> <?= t('auth.google_signin') ?></a></p>
    <p class="muted" style="margin-top:18px;"><?= t('auth.no_account') ?> <a href="register.php"><?= t('auth.create_one') ?></a></p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
