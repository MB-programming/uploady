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

$totalVideos = count($posts);
$publishedVideos = count(array_filter($posts, fn ($p) => $p['status'] === 'published'));
$pendingVideos = count(array_filter($posts, fn ($p) => in_array($p['status'], ['scheduled', 'processing', 'partially_published'], true)));

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

<div class="stat-grid">
    <div class="stat-tile stat-tile--blue">
        <div class="stat-icon"><?= Icons::film() ?></div>
        <div class="stat-value"><?= $totalVideos ?></div>
        <div class="stat-label">إجمالي الفيديوهات</div>
    </div>
    <div class="stat-tile stat-tile--green">
        <div class="stat-icon"><?= Icons::trendUp() ?></div>
        <div class="stat-value"><?= $publishedVideos ?></div>
        <div class="stat-label">تم نشرها بالكامل</div>
    </div>
    <div class="stat-tile stat-tile--orange">
        <div class="stat-icon"><?= Icons::clock() ?></div>
        <div class="stat-value"><?= $pendingVideos ?></div>
        <div class="stat-label">قيد الانتظار / النشر</div>
    </div>
    <div class="stat-tile stat-tile--pink">
        <div class="stat-icon"><?= Icons::database() ?></div>
        <div class="stat-value"><?= number_format($usedGb, 1) ?><span style="font-size:15px;"> / <?= (int) $quotaGb ?> GB</span></div>
        <div class="stat-label">مساحة التخزين المستخدمة</div>
        <div class="post-progress" style="margin-top:12px;">
            <div class="post-progress-bar" style="background:rgba(255,255,255,.25);">
                <div class="post-progress-fill" style="background:#fff;width:<?= round($pct, 1) ?>%;"></div>
            </div>
        </div>
    </div>
</div>

<div class="dash-toolbar">
    <h2 style="font-size:16px;margin:0;">فيديوهاتك</h2>
    <a class="btn" href="upload.php"><?= Icons::upload() ?> رفع فيديو جديد</a>
</div>

<?php if ($posts === []): ?>
    <p class="muted">لسه مفيش فيديوهات مرفوعة.</p>
<?php endif; ?>

<?php foreach ($posts as $post):
    $targets = PostTarget::forPost((int) $post['id']);
    $targetsTotal = count($targets);
    $targetsPublished = count(array_filter($targets, fn ($t) => $t['status'] === 'published'));
    $targetsFailed = count(array_filter($targets, fn ($t) => $t['status'] === 'failed'));
    $targetsDone = $targetsPublished + $targetsFailed;
    $progressPct = $targetsTotal > 0 ? round($targetsDone / $targetsTotal * 100) : 0;
    $progressClass = $targetsFailed > 0 ? 'has-failed' : ($progressPct >= 100 ? 'is-complete' : '');
?>
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

        <?php if ($targetsTotal > 0): ?>
            <div class="post-progress">
                <div class="post-progress-label">
                    <span>تقدّم النشر على المنصات</span>
                    <span><?= $targetsDone ?> / <?= $targetsTotal ?></span>
                </div>
                <div class="post-progress-bar">
                    <div class="post-progress-fill <?= $progressClass ?>" style="width:<?= $progressPct ?>%;"></div>
                </div>
            </div>
        <?php endif; ?>

        <table>
            <thead><tr><th>المنصة</th><th>العنوان</th><th>الحالة</th><th>رابط</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($targets as $target):
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
