<?php
require __DIR__ . '/app/bootstrap.php';

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    $email = trim((string) ($_POST['email'] ?? ''));
    $user = User::findByEmail($email);

    if ($user) {
        try {
            $token = PasswordReset::create((int) $user['id']);
            $resetUrl = App::url('reset_password.php?token=' . $token);
            $body = '<p>' . htmlspecialchars(t('auth.reset_email_intro')) . '</p>'
                . '<p><a href="' . htmlspecialchars($resetUrl) . '">' . htmlspecialchars($resetUrl) . '</a></p>';
            Mailer::send($user['email'], $user['name'], t('auth.reset_email_subject'), $body);
        } catch (Throwable $e) {
            // Never leak send failures to the client (would out whether the email exists / SMTP is broken).
            error_log('[forgot_password] ' . $e->getMessage());
        }
    }

    $submitted = true;
}

$pageTitle = t('auth.forgot_password_title');
require __DIR__ . '/partials_header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
    <h1><?= t('auth.forgot_password_title') ?></h1>
    <?php if ($submitted): ?>
        <div class="alert success"><?= t('auth.reset_link_sent') ?></div>
    <?php else: ?>
        <form method="post">
            <?= Csrf::field() ?>
            <label><?= t('auth.email') ?></label>
            <input type="email" name="email" required>
            <p><button class="btn" type="submit" style="margin-top:16px;"><?= t('auth.send_reset_link') ?></button></p>
        </form>
    <?php endif; ?>
    <p class="muted"><a href="login.php"><?= t('auth.back_to_login') ?></a></p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
