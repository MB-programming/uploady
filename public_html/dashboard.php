<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$posts = Post::forUser(Auth::id());

$statusLabels = [
    'scheduled' => 'مجدول',
    'processing' => 'جاري النشر',
    'published' => 'تم النشر',
    'partially_published' => 'نُشر جزئيًا',
    'failed' => 'فشل',
];
$targetStatusLabels = [
    'pending' => 'في الانتظار',
    'uploading' => 'جاري الرفع',
    'published' => 'تم النشر',
    'failed' => 'فشل',
];
$platformNames = [
    'youtube' => 'يوتيوب',
    'youtube_shorts' => 'يوتيوب Shorts',
    'tiktok' => 'تيك توك',
    'instagram' => 'انستجرام',
];

$usedGb = Post::storageUsedBytes(Auth::id()) / 1024 ** 3;
$quotaGb = Plan::quotaGbForUser(Auth::user());
$pct = min(100, $quotaGb > 0 ? ($usedGb / $quotaGb) * 100 : 0);

$pageTitle = 'لوحة التحكم';
require __DIR__ . '/partials_header.php';
?>
<h1>الفيديوهات</h1>

<?php if (!empty($_GET['created'])): ?>
    <div class="alert success">تم حفظ الفيديو وهيتم نشره حسب الميعاد المحدد.</div>
<?php endif; ?>
<?php if (!empty($_GET['cancelled'])): ?>
    <div class="alert success">تم إلغاء الفيديو وحذفه من السيرفر.</div>
<?php endif; ?>
<?php if (!empty($_GET['rescheduled'])): ?>
    <div class="alert success">تم تعديل ميعاد النشر.</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="alert error">تعذر تنفيذ العملية — ممكن يكون النشر بدأ بالفعل.</div>
<?php endif; ?>

<div class="card" style="padding:14px 20px;">
    <div class="muted">مساحة التخزين المستخدمة: <?= number_format($usedGb, 2) ?> GB من <?= (int) $quotaGb ?> GB</div>
    <div style="background:#0d0f14;border-radius:6px;height:8px;margin-top:8px;overflow:hidden;">
        <div style="background:<?= $pct > 90 ? 'var(--err)' : 'var(--accent)' ?>;height:100%;width:<?= round($pct, 1) ?>%;"></div>
    </div>
</div>

<p><a class="btn" href="upload.php">+ رفع فيديو جديد</a></p>

<?php if ($posts === []): ?>
    <p class="muted">لسه مفيش فيديوهات مرفوعة.</p>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:start;gap:14px;">
            <?php if ($post['thumbnail_path']): ?>
                <img src="media/thumbnail.php?post_id=<?= (int) $post['id'] ?>" alt="" style="width:96px;height:54px;object-fit:cover;border-radius:6px;flex-shrink:0;">
            <?php endif; ?>
            <div style="flex:1;">
                <h2 style="font-size:17px;margin-bottom:4px;"><?= htmlspecialchars($post['title']) ?></h2>
                <p class="muted">
                    <?= $post['status'] === 'scheduled' && strtotime($post['scheduled_at']) > time()
                        ? 'مجدول لـ ' . htmlspecialchars($post['scheduled_at'])
                        : 'أُنشئ في ' . htmlspecialchars($post['created_at']) ?>
                    <?php if (!$post['video_path']): ?> · تم حذف الفيديو من السيرفر بعد النشر<?php endif; ?>
                </p>
            </div>
            <?php $postBadgeClass = $post['status'] === 'failed' ? 'failed' : ($post['status'] === 'published' ? 'published' : 'pending'); ?>
            <span class="badge <?= $postBadgeClass ?>">
                <?= Icons::forBadge($postBadgeClass) ?> <?= htmlspecialchars($statusLabels[$post['status']] ?? $post['status']) ?>
            </span>
        </div>

        <?php $stillCancellable = $post['status'] === 'scheduled' && strtotime($post['scheduled_at']) > time(); ?>
        <?php if ($stillCancellable): ?>
            <div style="display:flex;gap:10px;align-items:center;margin:10px 0;flex-wrap:wrap;">
                <form method="post" action="post_action.php" style="display:flex;gap:6px;align-items:center;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="reschedule">
                    <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                    <input type="datetime-local" name="scheduled_at" required style="width:auto;">
                    <button type="submit" class="btn secondary" style="padding:6px 12px;font-size:13px;">تعديل الميعاد</button>
                </form>
                <form method="post" action="post_action.php" data-confirm="إلغاء الفيديو وحذفه من السيرفر؟">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                    <button type="submit" class="btn danger" style="padding:6px 12px;font-size:13px;">إلغاء</button>
                </form>
            </div>
        <?php endif; ?>

        <table>
            <thead><tr><th>المنصة</th><th>الحالة</th><th>رابط</th></tr></thead>
            <tbody>
            <?php foreach (PostTarget::forPost((int) $post['id']) as $target): ?>
                <tr>
                    <td><?= htmlspecialchars($platformNames[$target['platform']] ?? $target['platform']) ?> — <?= htmlspecialchars($target['display_name']) ?></td>
                    <td><span class="badge <?= htmlspecialchars($target['status']) ?>"><?= Icons::forBadge($target['status']) ?> <?= htmlspecialchars($targetStatusLabels[$target['status']] ?? $target['status']) ?></span>
                        <?php if ($target['status'] === 'failed' && $target['error_message']): ?>
                            <div class="muted"><?= htmlspecialchars($target['error_message']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php if ($target['remote_url']): ?><a href="<?= htmlspecialchars($target['remote_url']) ?>" target="_blank" rel="noopener">فتح</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/partials_footer.php'; ?>
