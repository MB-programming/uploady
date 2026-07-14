<?php
require __DIR__ . '/app/bootstrap.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$reset = $token !== '' ? PasswordReset::findValid($token) : null;
$errors = [];

if (!$reset) {
    $pageTitle = t('auth.reset_invalid_title');
    require __DIR__ . '/partials_header.php';
    ?>
    <div class="card" style="max-width:420px;margin:40px auto;">
        <h1><?= t('auth.reset_invalid_title') ?></h1>
        <p class="muted"><?= t('auth.reset_invalid_body') ?></p>
        <p><a href="forgot_password.php"><?= t('auth.request_new_link') ?></a></p>
    </div>
    <?php
    require __DIR__ . '/partials_footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (strlen($password) < 8) $errors[] = t('auth.err_password_length');
    if ($password !== $passwordConfirm) $errors[] = t('auth.err_password_mismatch');

    if (!$errors) {
        User::updatePassword((int) $reset['user_id'], $password);
        PasswordReset::delete($token);
        header('Location: login.php?reset=1');
        exit;
    }
}

$pageTitle = t('auth.reset_password_title');
require __DIR__ . '/partials_header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
    <h1><?= t('auth.reset_password_title') ?></h1>
    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <label><?= t('auth.new_password_label2') ?></label>
        <input type="password" name="password" required>
        <label><?= t('auth.password_confirm') ?></label>
        <input type="password" name="password_confirm" required>
        <p><button class="btn" type="submit" style="margin-top:16px;"><?= t('common.save') ?></button></p>
    </form>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
