<?php
require __DIR__ . '/../app/bootstrap.php';
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

$pageTitle = 'لوحة التحكم';
require __DIR__ . '/partials_header.php';
?>
<h1>الفيديوهات</h1>

<?php if (!empty($_GET['created'])): ?>
    <div class="alert success">تم حفظ الفيديو وهيتم نشره حسب الميعاد المحدد.</div>
<?php endif; ?>

<p><a class="btn" href="upload.php">+ رفع فيديو جديد</a></p>

<?php if ($posts === []): ?>
    <p class="muted">لسه مفيش فيديوهات مرفوعة.</p>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:start;">
            <div>
                <h2 style="font-size:17px;margin-bottom:4px;"><?= htmlspecialchars($post['title']) ?></h2>
                <p class="muted">
                    <?= $post['status'] === 'scheduled' && strtotime($post['scheduled_at']) > time()
                        ? 'مجدول لـ ' . htmlspecialchars($post['scheduled_at'])
                        : 'أُنشئ في ' . htmlspecialchars($post['created_at']) ?>
                    <?php if (!$post['video_path']): ?> · تم حذف الفيديو من السيرفر بعد النشر<?php endif; ?>
                </p>
            </div>
            <span class="badge <?= $post['status'] === 'failed' ? 'failed' : ($post['status'] === 'published' ? 'published' : 'pending') ?>">
                <?= htmlspecialchars($statusLabels[$post['status']] ?? $post['status']) ?>
            </span>
        </div>
        <table>
            <thead><tr><th>المنصة</th><th>الحالة</th><th>رابط</th></tr></thead>
            <tbody>
            <?php foreach (PostTarget::forPost((int) $post['id']) as $target): ?>
                <tr>
                    <td><?= htmlspecialchars($platformNames[$target['platform']] ?? $target['platform']) ?> — <?= htmlspecialchars($target['display_name']) ?></td>
                    <td><span class="badge <?= htmlspecialchars($target['status']) ?>"><?= htmlspecialchars($targetStatusLabels[$target['status']] ?? $target['status']) ?></span>
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
