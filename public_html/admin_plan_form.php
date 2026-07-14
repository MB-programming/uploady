<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$editId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$editingPlan = $editId ? Plan::find($editId) : null;
if ($editId && !$editingPlan) {
    http_response_code(404);
    exit(t('common.not_found_plan'));
}

$errors = [];
$name = $editingPlan['name'] ?? '';
$price = $editingPlan['price_egp'] ?? '';
$quota = $editingPlan['storage_quota_gb'] ?? '';
$maxAccounts = $editingPlan['max_social_accounts'] ?? '';
$features = $editingPlan['features'] ?? '';
$badgeText = $editingPlan['badge_text'] ?? '';
$isFeatured = (bool) ($editingPlan['is_featured'] ?? false);
$isActive = $editingPlan === null ? true : (bool) $editingPlan['is_active'];
$sortOrder = $editingPlan['sort_order'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $name = trim((string) ($_POST['name'] ?? ''));
    $price = (string) ($_POST['price_egp'] ?? '');
    $quota = (string) ($_POST['storage_quota_gb'] ?? '');
    $maxAccounts = trim((string) ($_POST['max_social_accounts'] ?? ''));
    $features = trim((string) ($_POST['features'] ?? ''));
    $badgeText = trim((string) ($_POST['badge_text'] ?? ''));
    $isFeatured = !empty($_POST['is_featured']);
    $isActive = !empty($_POST['is_active']);
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    if ($name === '') $errors[] = t('admin.err_plan_name_required');
    if (!is_numeric($price) || (float) $price < 0) $errors[] = t('admin.err_plan_price_invalid');
    if (!is_numeric($quota) || (float) $quota <= 0) $errors[] = t('admin.err_plan_quota_invalid');

    if (!$errors) {
        $data = [
            'name' => $name,
            'price_egp' => (float) $price,
            'storage_quota_gb' => (int) $quota,
            'max_social_accounts' => $maxAccounts !== '' ? (int) $maxAccounts : null,
            'features' => $features !== '' ? $features : null,
            'badge_text' => $badgeText !== '' ? $badgeText : null,
            'is_featured' => $isFeatured,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
        ];

        if ($editingPlan) {
            Plan::update((int) $editingPlan['id'], $data);
            header('Location: admin_plans.php?updated=1');
        } else {
            Plan::create($data);
            header('Location: admin_plans.php?created=1');
        }
        exit;
    }
}

$pageTitle = $editingPlan ? t('admin.edit_plan_title') : t('admin.new_plan_title');
require __DIR__ . '/partials_header.php';
?>
<p><a href="admin_plans.php"><?= t('admin.back_to_plans') ?></a></p>
<h1><?= $editingPlan ? t('admin.edit_plan_title') : t('admin.new_plan_title') ?></h1>

<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" class="card" style="max-width:560px;">
    <?= Csrf::field() ?>

    <label><?= t('admin.plan_name_label') ?></label>
    <input type="text" name="name" value="<?= htmlspecialchars((string) $name) ?>" required>

    <label><?= t('admin.plan_price_label') ?></label>
    <input type="text" name="price_egp" value="<?= htmlspecialchars((string) $price) ?>" required>

    <label><?= t('admin.plan_quota_label') ?></label>
    <input type="text" name="storage_quota_gb" value="<?= htmlspecialchars((string) $quota) ?>" required>

    <label><?= t('admin.plan_max_accounts_label') ?></label>
    <input type="text" name="max_social_accounts" value="<?= htmlspecialchars((string) $maxAccounts) ?>">

    <label><?= t('admin.plan_features_label') ?></label>
    <textarea name="features" rows="5"><?= htmlspecialchars((string) $features) ?></textarea>

    <label><?= t('admin.plan_badge_label') ?></label>
    <input type="text" name="badge_text" value="<?= htmlspecialchars((string) $badgeText) ?>">

    <label style="display:flex;align-items:center;gap:8px;font-weight:normal;margin-top:16px;">
        <input type="checkbox" name="is_featured" value="1" style="width:auto;" <?= $isFeatured ? 'checked' : '' ?>>
        <?= t('admin.plan_featured_label') ?>
    </label>

    <label style="display:flex;align-items:center;gap:8px;font-weight:normal;margin-top:10px;">
        <input type="checkbox" name="is_active" value="1" style="width:auto;" <?= $isActive ? 'checked' : '' ?>>
        <?= t('admin.plan_active_label') ?>
    </label>

    <label><?= t('admin.plan_sort_label') ?></label>
    <input type="text" name="sort_order" value="<?= htmlspecialchars((string) $sortOrder) ?>">

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= $editingPlan ? t('admin.save_changes') : t('admin.create_plan') ?></button></p>
</form>

<?php require __DIR__ . '/partials_footer.php'; ?>
