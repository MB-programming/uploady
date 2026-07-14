<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$plans = Plan::all();

$pageTitle = t('admin.plans_title');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('admin.plans_title') ?></h1>

<?php if (!empty($_GET['created'])): ?>
    <div class="alert success"><?= t('admin.plan_created_success') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['updated'])): ?>
    <div class="alert success"><?= t('admin.plan_updated_success') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?>
    <div class="alert success"><?= t('admin.plan_deleted_success') ?></div>
<?php endif; ?>

<p><a class="btn" href="admin_plan_form.php"><?= t('admin.add_plan') ?></a></p>

<div class="card">
<div style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th><?= t('admin.th_plan_name') ?></th>
            <th><?= t('admin.th_price') ?></th>
            <th><?= t('admin.th_quota') ?></th>
            <th><?= t('admin.th_users_count') ?></th>
            <th><?= t('admin.th_visible') ?></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($plans === []): ?>
            <tr><td colspan="6" class="muted"><?= t('admin.no_plans_yet') ?></td></tr>
        <?php endif; ?>
        <?php foreach ($plans as $plan): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($plan['name']) ?>
                    <?php if ($plan['is_featured']): ?><span class="badge published"><?= Icons::bolt() ?></span><?php endif; ?>
                </td>
                <td><?= number_format((float) $plan['price_egp'], 0) ?> <?= t('invoice.currency_egp') ?></td>
                <td><?= (int) $plan['storage_quota_gb'] ?> GB</td>
                <td><?= Plan::countUsers((int) $plan['id']) ?></td>
                <td>
                    <span class="badge <?= $plan['is_active'] ? 'published' : 'pending' ?>">
                        <?= Icons::forBadge($plan['is_active'] ? 'published' : 'pending') ?>
                        <?= $plan['is_active'] ? t('admin.badge_active') : t('admin.badge_inactive') ?>
                    </span>
                </td>
                <td style="white-space:nowrap;">
                    <a href="admin_plan_form.php?id=<?= (int) $plan['id'] ?>"><?= t('common.edit') ?></a>
                    &nbsp;·&nbsp;
                    <form method="post" action="admin_plan_action.php" style="display:inline;" data-confirm="<?= htmlspecialchars(sprintf(t('admin.delete_plan_confirm'), $plan['name'])) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                        <button type="submit" class="btn danger" style="padding:2px 8px;font-size:12px;"><?= t('common.delete') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
