<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$editId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$editingUser = $editId ? User::findById($editId) : null;
if ($editId && !$editingUser) {
    http_response_code(404);
    exit(t('common.not_found_user'));
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

    if ($name === '') $errors[] = t('auth.err_name_required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('auth.err_invalid_email');
    if (User::emailTaken($email, $editingUser['id'] ?? null)) $errors[] = t('profile.err_email_taken');

    // Password is required when creating a new user, optional when editing (blank = keep current).
    if (!$editingUser && strlen($password) < 8) {
        $errors[] = t('auth.err_password_length');
    } elseif ($editingUser && $password !== '' && strlen($password) < 8) {
        $errors[] = t('profile.err_new_password_length');
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

$pageTitle = $editingUser ? t('admin.edit_user_title') : t('admin.new_user_title');
require __DIR__ . '/partials_header.php';
?>
<p><a href="admin_users.php"><?= t('admin.back_to_users') ?></a></p>
<h1><?= $editingUser ? t('admin.edit_user_title') : t('admin.new_user_title') ?></h1>

<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" class="card" style="max-width:480px;">
    <?= Csrf::field() ?>

    <label><?= t('auth.name') ?></label>
    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>

    <label><?= t('auth.email') ?></label>
    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

    <label><?= $editingUser ? t('admin.new_password_optional') : t('auth.password') ?></label>
    <input type="password" name="password" <?= $editingUser ? '' : 'required' ?>>

    <label><?= t('invoice.plan') ?></label>
    <select name="plan_id">
        <option value=""><?= t('admin.no_plan') ?></option>
        <?php foreach ($plans as $planOption): ?>
            <option value="<?= (int) $planOption['id'] ?>" <?= (int) $planId === (int) $planOption['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars(sprintf(t('admin.plan_option_format'), $planOption['name'], number_format((float) $planOption['price_egp'], 0), $planOption['storage_quota_gb'])) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <?php $isSelf = $editingUser && (int) $editingUser['id'] === Auth::id(); ?>
    <label style="display:flex;align-items:center;gap:8px;font-weight:normal;margin-top:16px;">
        <input type="checkbox" name="is_admin" value="1" style="width:auto;" <?= $isAdmin ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
        <?= t('admin.admin_permissions_label') ?>
    </label>
    <?php if ($isSelf): ?>
        <input type="hidden" name="is_admin" value="1">
        <p class="muted"><?= t('admin.cant_remove_own_admin') ?></p>
    <?php endif; ?>

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= $editingUser ? t('admin.save_changes') : t('admin.create_user') ?></button></p>
</form>

<?php require __DIR__ . '/partials_footer.php'; ?>
