<?php
require __DIR__ . '/app/bootstrap.php';

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

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
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <label><?= t('auth.email') ?></label>
        <input type="email" name="email" required>
        <label><?= t('auth.password') ?></label>
        <input type="password" name="password" required>
        <p><button class="btn" type="submit" style="margin-top:16px;"><?= t('auth.login_submit') ?></button></p>
    </form>
    <p class="muted"><?= t('auth.no_account') ?> <a href="register.php"><?= t('auth.create_one') ?></a></p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
