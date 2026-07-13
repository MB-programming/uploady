<?php
require __DIR__ . '/../app/bootstrap.php';

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
        $error = sprintf(
            'محاولات دخول كتير فشلت على الحساب ده. حاول تاني بعد %d دقيقة.',
            (int) App::config('login_lockout_minutes')
        );
    } elseif (Auth::attempt($email, $password)) {
        LoginAttempt::clear($email);
        header('Location: dashboard.php');
        exit;
    } else {
        LoginAttempt::record($email, $ip);
        $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
    }
}

$pageTitle = 'تسجيل دخول';
require __DIR__ . '/partials_header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
    <h1>تسجيل دخول</h1>
    <?php if (!empty($_GET['deleted'])): ?><div class="alert success">تم حذف حسابك وكل بياناتك بنجاح.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <label>البريد الإلكتروني</label>
        <input type="email" name="email" required>
        <label>كلمة المرور</label>
        <input type="password" name="password" required>
        <p><button class="btn" type="submit" style="margin-top:16px;">دخول</button></p>
    </form>
    <p class="muted">مفيش حساب؟ <a href="register.php">أنشئ واحد</a></p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
