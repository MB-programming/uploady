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

    if (Auth::attempt($email, $password)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
}

$pageTitle = 'تسجيل دخول';
require __DIR__ . '/partials_header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
    <h1>تسجيل دخول</h1>
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
