<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$clientId = (int) ($_GET['id'] ?? 0);
$client = User::findById($clientId);

if (!$client || $client['is_admin']) {
    http_response_code(404);
    exit('Client not found.');
}

$accounts = SocialAccount::forUser($clientId);
$posts = Post::forUser($clientId);
$quotaGb = Plan::quotaGbForUser($client);
$usedGb = Post::storageUsedBytes($clientId) / 1024 ** 3;

$statusLabels = [
    'scheduled' => 'مجدول',
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

$pageTitle = 'تفاصيل العميل';
require __DIR__ . '/partials_header.php';
?>
<p><a href="admin.php">&larr; رجوع لكل العملاء</a></p>
<h1><?= htmlspecialchars($client['name']) ?></h1>
<p class="muted"><?= htmlspecialchars($client['email']) ?> — مسجل من <?= htmlspecialchars($client['created_at']) ?></p>

<div class="card" style="padding:14px 20px;">
    <div class="muted">مساحة التخزين المستخدمة: <?= number_format($usedGb, 2) ?> GB من <?= (int) $quotaGb ?> GB</div>
</div>

<h2 style="font-size:16px;">الحسابات المتصلة (<?= count($accounts) ?>)</h2>
<div class="card">
    <?php if ($accounts === []): ?>
        <p class="muted">مفيش حسابات متصلة.</p>
    <?php endif; ?>
    <table>
        <thead><tr><th>المنصة</th><th>الحساب</th><th>تاريخ الربط</th></tr></thead>
        <tbody>
        <?php foreach ($accounts as $account): ?>
            <tr>
                <td><?= htmlspecialchars($platformNames[$account['platform']] ?? $account['platform']) ?></td>
                <td><?= htmlspecialchars($account['display_name']) ?></td>
                <td><?= htmlspecialchars($account['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2 style="font-size:16px;">الفيديوهات (<?= count($posts) ?>)</h2>
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
                <h3 style="font-size:15px;margin-bottom:4px;"><?= htmlspecialchars($post['title']) ?></h3>
                <p class="muted">
                    <?= htmlspecialchars($post['created_at']) ?>
                    <?php if (!$post['video_path']): ?> · تم حذف الفيديو من السيرفر بعد النشر<?php endif; ?>
                </p>
            </div>
            <?php $postBadgeClass = $post['status'] === 'failed' ? 'failed' : ($post['status'] === 'published' ? 'published' : 'pending'); ?>
            <span class="badge <?= $postBadgeClass ?>">
                <?= Icons::forBadge($postBadgeClass) ?> <?= htmlspecialchars($statusLabels[$post['status']] ?? $post['status']) ?>
            </span>
        </div>
        <table>
            <thead><tr><th>المنصة</th><th>الحالة</th><th>رابط</th></tr></thead>
            <tbody>
            <?php foreach (PostTarget::forPost((int) $post['id']) as $target): ?>
                <tr>
                    <td><?= htmlspecialchars($platformNames[$target['platform']] ?? $target['platform']) ?> — <?= htmlspecialchars($target['display_name']) ?></td>
                    <td><span class="badge <?= htmlspecialchars($target['status']) ?>"><?= Icons::forBadge($target['status']) ?> <?= htmlspecialchars($target['status']) ?></span></td>
                    <td><?php if ($target['remote_url']): ?><a href="<?= htmlspecialchars($target['remote_url']) ?>" target="_blank" rel="noopener">فتح</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/partials_footer.php'; ?>
