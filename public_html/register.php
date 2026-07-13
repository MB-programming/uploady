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

    if ($name === '') $errors[] = 'الاسم مطلوب';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
    if (strlen($password) < 8) $errors[] = 'كلمة المرور لازم تكون 8 أحرف على الأقل';
    if ($password !== $passwordConfirm) $errors[] = 'كلمة المرور غير متطابقة';
    if (!$errors && User::findByEmail($email)) $errors[] = 'البريد الإلكتروني مسجل بالفعل';

    if (!$errors) {
        $userId = User::create($name, $email, $password);
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        header('Location: dashboard.php');
        exit;
    }
}

$pageTitle = 'حساب جديد';
require __DIR__ . '/partials_header.php';
?>
<div class="card" style="max-width:420px;margin:40px auto;">
    <h1>إنشاء حساب</h1>
    <?php foreach ($errors as $error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endforeach; ?>
    <form method="post">
        <?= Csrf::field() ?>
        <label>الاسم</label>
        <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
        <label>البريد الإلكتروني</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        <label>كلمة المرور</label>
        <input type="password" name="password" required>
        <label>تأكيد كلمة المرور</label>
        <input type="password" name="password_confirm" required>
        <p><button class="btn" type="submit" style="margin-top:16px;">تسجيل</button></p>
    </form>
    <p class="muted">عندك حساب؟ <a href="login.php">سجّل دخول</a></p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
