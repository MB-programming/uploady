<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$users = User::all();

$pageTitle = t('nav.admin_users');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('nav.admin_users') ?></h1>

<?php if (!empty($_GET['created'])): ?>
    <div class="alert success"><?= t('admin.user_created_success') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['updated'])): ?>
    <div class="alert success"><?= t('profile.updated_success') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?>
    <div class="alert success"><?= t('admin.user_deleted_success') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="alert error"><?= t('admin.generic_error') ?></div>
<?php endif; ?>

<p><a class="btn" href="admin_user_form.php"><?= t('admin.add_user') ?></a></p>

<div class="card">
<div style="overflow-x:auto;">
<table>
    <thead>
        <tr><th><?= t('auth.name') ?></th><th><?= t('auth.email') ?></th><th><?= t('admin.th_type') ?></th><th><?= t('admin.th_videos') ?></th><th><?= t('admin.th_registered') ?></th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['is_admin'] ? '<span class="badge published">' . t('admin.badge_admin') . '</span>' : '<span class="badge pending">' . t('admin.badge_client') . '</span>' ?></td>
                <td><?= Post::countForUser((int) $user['id']) ?></td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
                <td style="white-space:nowrap;">
                    <a href="admin_user_form.php?id=<?= (int) $user['id'] ?>"><?= t('common.edit') ?></a>
                    <?php if ((int) $user['id'] !== Auth::id()): ?>
                        &nbsp;·&nbsp;
                        <form method="post" action="admin_user_action.php" style="display:inline;" data-confirm="<?= htmlspecialchars(sprintf(t('admin.delete_user_confirm'), $user['name'])) ?>">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                            <button type="submit" class="btn danger" style="padding:2px 8px;font-size:12px;"><?= t('common.delete') ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
