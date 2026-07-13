<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$users = User::all();

$pageTitle = 'إدارة المستخدمين';
require __DIR__ . '/partials_header.php';
?>
<h1>إدارة المستخدمين</h1>

<?php if (!empty($_GET['created'])): ?>
    <div class="alert success">تم إنشاء المستخدم بنجاح.</div>
<?php endif; ?>
<?php if (!empty($_GET['updated'])): ?>
    <div class="alert success">تم حفظ التعديلات.</div>
<?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?>
    <div class="alert success">تم حذف المستخدم وكل بياناته.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="alert error">تعذر تنفيذ العملية.</div>
<?php endif; ?>

<p><a class="btn" href="admin_user_form.php">+ إضافة مستخدم جديد</a></p>

<div class="card">
<div style="overflow-x:auto;">
<table>
    <thead>
        <tr><th>الاسم</th><th>البريد الإلكتروني</th><th>النوع</th><th>الفيديوهات</th><th>تاريخ التسجيل</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['name']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= $user['is_admin'] ? '<span class="badge published">أدمن</span>' : '<span class="badge pending">عميل</span>' ?></td>
                <td><?= Post::countForUser((int) $user['id']) ?></td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
                <td style="white-space:nowrap;">
                    <a href="admin_user_form.php?id=<?= (int) $user['id'] ?>">تعديل</a>
                    <?php if ((int) $user['id'] !== Auth::id()): ?>
                        &nbsp;·&nbsp;
                        <form method="post" action="admin_user_action.php" style="display:inline;" data-confirm="حذف المستخدم <?= htmlspecialchars($user['name']) ?> وكل بياناته نهائيًا؟">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                            <button type="submit" class="btn danger" style="padding:2px 8px;font-size:12px;">حذف</button>
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
