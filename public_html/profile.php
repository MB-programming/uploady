<?php
require __DIR__ . '/../app/bootstrap.php';
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

    if ($name === '') $errors[] = 'الاسم مطلوب';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
    if (User::emailTaken($email, Auth::id())) $errors[] = 'البريد الإلكتروني مستخدم بالفعل';

    if ($newPassword !== '') {
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'كلمة المرور الحالية غير صحيحة';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'كلمة المرور الجديدة لازم تكون 8 أحرف على الأقل';
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

$pageTitle = 'حسابي';
require __DIR__ . '/partials_header.php';
?>
<h1>حسابي</h1>

<?php if (!empty($_GET['updated'])): ?>
    <div class="alert success">تم حفظ التعديلات.</div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" class="card" style="max-width:480px;">
    <?= Csrf::field() ?>

    <label>الاسم</label>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

    <label>البريد الإلكتروني</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <hr style="border-color:var(--border);margin:20px 0;">

    <label>كلمة المرور الحالية (لازم بس لو عايز تغيّر كلمة المرور)</label>
    <input type="password" name="current_password">

    <label>كلمة مرور جديدة (سيبها فاضية لو مش عايز تغيّرها)</label>
    <input type="password" name="new_password">

    <p><button type="submit" class="btn" style="margin-top:20px;">حفظ</button></p>
</form>

<div class="card" style="max-width:480px;">
    <p class="muted">عايز تحذف حسابك نهائيًا؟ <a href="data-deletion.php">من هنا</a>.</p>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
