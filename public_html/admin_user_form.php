<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$editId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$editingUser = $editId ? User::findById($editId) : null;
if ($editId && !$editingUser) {
    http_response_code(404);
    exit('User not found.');
}

$errors = [];
$name = $editingUser['name'] ?? '';
$email = $editingUser['email'] ?? '';
$isAdmin = (bool) ($editingUser['is_admin'] ?? false);
$planId = $editingUser['plan_id'] ?? null;
$plans = Plan::all();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $isAdmin = !empty($_POST['is_admin']);
    $planId = $_POST['plan_id'] !== '' ? (int) $_POST['plan_id'] : null;

    if ($name === '') $errors[] = 'الاسم مطلوب';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
    if (User::emailTaken($email, $editingUser['id'] ?? null)) $errors[] = 'البريد الإلكتروني مستخدم بالفعل';

    // Password is required when creating a new user, optional when editing (blank = keep current).
    if (!$editingUser && strlen($password) < 8) {
        $errors[] = 'كلمة المرور لازم تكون 8 أحرف على الأقل';
    } elseif ($editingUser && $password !== '' && strlen($password) < 8) {
        $errors[] = 'كلمة المرور الجديدة لازم تكون 8 أحرف على الأقل';
    }

    // An admin editing their own row can't strip their own admin flag — avoids locking everyone out.
    if ($editingUser && (int) $editingUser['id'] === Auth::id()) {
        $isAdmin = true;
    }

    if (!$errors) {
        if ($editingUser) {
            User::updateProfile((int) $editingUser['id'], $name, $email, $isAdmin);
            User::updatePlan((int) $editingUser['id'], $planId);
            if ($password !== '') {
                User::updatePassword((int) $editingUser['id'], $password);
            }
            header('Location: admin_users.php?updated=1');
        } else {
            $newId = User::createByAdmin($name, $email, $password, $isAdmin);
            User::updatePlan($newId, $planId);
            header('Location: admin_users.php?created=1');
        }
        exit;
    }
}

$pageTitle = $editingUser ? 'تعديل مستخدم' : 'مستخدم جديد';
require __DIR__ . '/partials_header.php';
?>
<p><a href="admin_users.php">&larr; رجوع لإدارة المستخدمين</a></p>
<h1><?= $editingUser ? 'تعديل مستخدم' : 'مستخدم جديد' ?></h1>

<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" class="card" style="max-width:480px;">
    <?= Csrf::field() ?>

    <label>الاسم</label>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

    <label>البريد الإلكتروني</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label><?= $editingUser ? 'كلمة مرور جديدة (سيبها فاضية عشان تسيب الحالية زي ما هي)' : 'كلمة المرور' ?></label>
    <input type="password" name="password" <?= $editingUser ? '' : 'required' ?>>

    <label>الخطة</label>
    <select name="plan_id">
        <option value="">بدون خطة</option>
        <?php foreach ($plans as $planOption): ?>
            <option value="<?= (int) $planOption['id'] ?>" <?= (int) $planId === (int) $planOption['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($planOption['name']) ?> — <?= number_format((float) $planOption['price_egp'], 0) ?> ج.م (<?= $planOption['storage_quota_gb'] ?> GB)
            </option>
        <?php endforeach; ?>
    </select>

    <?php $isSelf = $editingUser && (int) $editingUser['id'] === Auth::id(); ?>
    <label style="display:flex;align-items:center;gap:8px;font-weight:normal;margin-top:16px;">
        <input type="checkbox" name="is_admin" value="1" style="width:auto;" <?= $isAdmin ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
        صلاحيات أدمن
    </label>
    <?php if ($isSelf): ?>
        <input type="hidden" name="is_admin" value="1">
        <p class="muted">مايمكنش تشيل صلاحية الأدمن من حسابك انت بنفسك.</p>
    <?php endif; ?>

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= $editingUser ? 'حفظ التعديلات' : 'إنشاء المستخدم' ?></button></p>
</form>

<?php require __DIR__ . '/partials_footer.php'; ?>
