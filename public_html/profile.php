<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
$errors = [];
$name = $user['name'];
$email = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');

    if ($name === '') $errors[] = t('auth.err_name_required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('auth.err_invalid_email');
    if (User::emailTaken($email, Auth::id())) $errors[] = t('profile.err_email_taken');

    if ($newPassword !== '') {
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = t('profile.err_current_password_wrong');
        } elseif (strlen($newPassword) < 8) {
            $errors[] = t('profile.err_new_password_length');
        }
    }

    if (!$errors) {
        User::updateProfile(Auth::id(), $name, $email);
        if ($newPassword !== '') {
            User::updatePassword(Auth::id(), $newPassword);
        }
        header('Location: profile.php?updated=1');
        exit;
    }
}

$pageTitle = t('nav.profile');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('nav.profile') ?></h1>

<?php if (!empty($_GET['updated'])): ?>
    <div class="alert success"><?= t('profile.updated_success') ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" class="card" style="max-width:480px;">
    <?= Csrf::field() ?>

    <label><?= t('auth.name') ?></label>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

    <label><?= t('auth.email') ?></label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <hr style="border-color:var(--border);margin:20px 0;">

    <label><?= t('profile.current_password_label') ?></label>
    <input type="password" name="current_password">

    <label><?= t('profile.new_password_label') ?></label>
    <input type="password" name="new_password">

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= t('common.save') ?></button></p>
</form>

<div class="card" style="max-width:480px;">
    <p class="muted"><?= t('profile.delete_account_prompt') ?> <a href="data-deletion.php"><?= t('profile.delete_account_link') ?></a>.</p>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
