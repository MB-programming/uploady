<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$posts = Post::forUser(Auth::id());

$statusLabels = [
    'scheduled' => 'قيد الانتظار',
    'processing' => 'جاري النشر',
    'published' => 'تم النشر',
    'partially_published' => 'نُشر جزئيًا',
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
    <div class="alert success">تم حفظ الفيديو وهيتم نشره حسب الميعاد المحدد لكل منصة.</div>
<?php endif; ?>
<?php if (!empty($_GET['cancelled'])): ?>
    <div class="alert success">تم إلغاء النشر على المنصة دي.</div>
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
                    أُنشئ في <?= htmlspecialchars($post['created_at']) ?>
                    <?php if (!$post['video_path']): ?> · تم حذف الفيديو من السيرفر بعد النشر<?php endif; ?>
                </p>
            </div>
            <?php $postBadgeClass = $post['status'] === 'failed' ? 'failed' : ($post['status'] === 'published' ? 'published' : 'pending'); ?>
            <span class="badge <?= $postBadgeClass ?>">
                <?= Icons::forBadge($postBadgeClass) ?> <?= htmlspecialchars($statusLabels[$post['status']] ?? $post['status']) ?>
            </span>
        </div>

        <table>
            <thead><tr><th>المنصة</th><th>العنوان</th><th>الحالة</th><th>رابط</th><th></th></tr></thead>
            <tbody>
            <?php foreach (PostTarget::forPost((int) $post['id']) as $target):
                $display = PostTarget::displayStatus($target);
                $stillCancellable = $target['status'] === 'pending' && strtotime($target['scheduled_at']) > time();
            ?>
                <tr>
                    <td><?= htmlspecialchars($platformNames[$target['platform']] ?? $target['platform']) ?> — <?= htmlspecialchars($target['display_name']) ?></td>
                    <td><?= htmlspecialchars($target['title']) ?></td>
                    <td>
                        <span class="badge <?= htmlspecialchars($display['class']) ?>"><?= Icons::forBadge($target['status']) ?> <?= htmlspecialchars($display['label']) ?></span>
                        <?php if ($target['status'] === 'failed' && $target['error_message']): ?>
                            <div class="muted"><?= htmlspecialchars($target['error_message']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?php if ($target['remote_url']): ?><a href="<?= htmlspecialchars($target['remote_url']) ?>" target="_blank" rel="noopener">فتح</a><?php endif; ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($stillCancellable): ?>
                            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                <form method="post" action="target_action.php" style="display:flex;gap:4px;align-items:center;">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="action" value="reschedule">
                                    <input type="hidden" name="target_id" value="<?= (int) $target['id'] ?>">
                                    <input type="datetime-local" name="scheduled_at" required style="width:auto;font-size:12px;padding:4px 6px;">
                                    <button type="submit" class="btn secondary" style="padding:4px 8px;font-size:12px;">تعديل</button>
                                </form>
                                <form method="post" action="target_action.php" data-confirm="إلغاء النشر على المنصة دي؟">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="target_id" value="<?= (int) $target['id'] ?>">
                                    <button type="submit" class="btn danger" style="padding:4px 8px;font-size:12px;">إلغاء</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/partials_footer.php'; ?>
