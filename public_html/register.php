<?php
require __DIR__ . '/app/bootstrap.php';

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($name === '') $errors[] = t('auth.err_name_required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('auth.err_invalid_email');
    if (strlen($password) < 8) $errors[] = t('auth.err_password_length');
    if ($password !== $passwordConfirm) $errors[] = t('auth.err_password_mismatch');
    if (!$errors && User::findByEmail($email)) $errors[] = t('auth.err_email_taken');

    if (!$errors) {
        $userId = User::create($name, $email, $password);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        header('Location: dashboard.php');
        exit;
    }
}

$pageTitle = t('auth.register_title');
require __DIR__ . '/partials_header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
    <h1><?= t('auth.register_title') ?></h1>
    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <label><?= t('auth.name') ?></label>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        <label><?= t('auth.email') ?></label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <label><?= t('auth.password') ?></label>
        <input type="password" name="password" required>
        <label><?= t('auth.password_confirm') ?></label>
        <input type="password" name="password_confirm" required>
        <p><button class="btn" type="submit" style="margin-top:16px;"><?= t('auth.register_submit') ?></button></p>
    </form>
    <p class="muted" style="text-align:center;margin:18px 0;"><?= t('auth.or_divider') ?></p>
    <p><a href="oauth/google_login.php" class="btn secondary" style="width:100%;"><?= Icons::globe() ?> <?= t('auth.google_signin') ?></a></p>
    <p class="muted" style="margin-top:18px;"><?= t('auth.have_account') ?> <a href="login.php"><?= t('auth.login_link') ?></a></p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
