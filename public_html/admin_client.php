<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$clientId = (int) ($_GET['id'] ?? 0);
$client = User::findById($clientId);

if (!$client || $client['is_admin']) {
    http_response_code(404);
    exit(t('common.not_found_client'));
}

$accounts = SocialAccount::forUser($clientId);
$posts = Post::forUser($clientId);
$quotaGb = Plan::quotaGbForUser($client);
$usedGb = Post::storageUsedBytes($clientId) / 1024 ** 3;

$statusLabels = [
    'scheduled' => t('post_status.scheduled'),
    'processing' => t('post_status.processing'),
    'published' => t('post_status.published'),
    'partially_published' => t('post_status.partially_published'),
    'failed' => t('post_status.failed'),
];
$platformNames = [
    'youtube' => t('platform.youtube'),
    'youtube_shorts' => t('platform.youtube_shorts'),
    'tiktok' => t('platform.tiktok'),
    'instagram' => t('platform.instagram'),
];

$pageTitle = t('admin.client_detail_title');
require __DIR__ . '/partials_header.php';
?>
<p><a href="admin.php"><?= t('admin.back_to_clients') ?></a></p>
<h1><?= htmlspecialchars($client['name']) ?></h1>
<p class="muted"><?= htmlspecialchars($client['email']) ?> — <?= sprintf(t('admin.registered_from'), htmlspecialchars($client['created_at'])) ?></p>

<div class="card" style="padding:14px 20px;">
    <div class="muted"><?= sprintf(t('storage.used_sentence'), number_format($usedGb, 2), (int) $quotaGb) ?></div>
</div>

<h2 style="font-size:16px;"><?= sprintf(t('admin.connected_accounts_count'), count($accounts)) ?></h2>
<div class="card">
    <?php if ($accounts === []): ?>
        <p class="muted"><?= t('upload.no_connected_accounts') ?></p>
    <?php endif; ?>
    <table>
        <thead><tr><th><?= t('common.platform') ?></th><th><?= t('common.account') ?></th><th><?= t('admin.connected_on') ?></th></tr></thead>
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

<h2 style="font-size:16px;"><?= sprintf(t('admin.videos_count'), count($posts)) ?></h2>
<?php if ($posts === []): ?>
    <p class="muted"><?= t('dashboard.no_videos') ?></p>
<?php endif; ?>
<?php foreach ($posts as $post):
    $targets = PostTarget::forPost((int) $post['id']);
    $progress = PostTarget::progressSummary($targets);
?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:start;gap:14px;">
            <?php if ($post['thumbnail_path']): ?>
                <img src="media/thumbnail.php?post_id=<?= (int) $post['id'] ?>" alt="" style="width:96px;height:54px;object-fit:cover;border-radius:6px;flex-shrink:0;">
            <?php endif; ?>
            <div style="flex:1;">
                <h3 style="font-size:15px;margin-bottom:4px;"><?= htmlspecialchars($post['title']) ?></h3>
                <p class="muted">
                    <?= htmlspecialchars($post['created_at']) ?>
                    <?php if (!$post['video_path']): ?> · <?= t('dashboard.video_deleted_note') ?><?php endif; ?>
                </p>
            </div>
            <?php $postBadgeClass = $post['status'] === 'failed' ? 'failed' : ($post['status'] === 'published' ? 'published' : 'pending'); ?>
            <span class="badge <?= $postBadgeClass ?>">
                <?= Icons::forBadge($postBadgeClass) ?> <?= htmlspecialchars($statusLabels[$post['status']] ?? $post['status']) ?><?php if ($progress['total'] > 0): ?> — <?= htmlspecialchars($progress['label']) ?><?php endif; ?>
            </span>
        </div>

        <?php if ($progress['total'] > 0): ?>
            <div class="post-progress">
                <div class="post-progress-label">
                    <span><?= t('progress.label') ?></span>
                    <span><?= $progress['done'] ?> / <?= $progress['total'] ?></span>
                </div>
                <div class="post-progress-bar">
                    <div class="post-progress-fill <?= $progress['class'] ?>" style="width:<?= $progress['pct'] ?>%;"></div>
                </div>
            </div>
        <?php endif; ?>

        <table>
            <thead><tr><th><?= t('common.platform') ?></th><th><?= t('common.title') ?></th><th><?= t('common.status') ?></th><th><?= t('common.link') ?></th></tr></thead>
            <tbody>
            <?php foreach ($targets as $target): $display = PostTarget::displayStatus($target); ?>
                <tr>
                    <td><?= htmlspecialchars($platformNames[$target['platform']] ?? $target['platform']) ?> — <?= htmlspecialchars($target['display_name']) ?></td>
                    <td><?= htmlspecialchars($target['title']) ?></td>
                    <td><span class="badge <?= htmlspecialchars($display['class']) ?>"><?= Icons::forBadge($target['status']) ?> <?= htmlspecialchars($display['label']) ?></span></td>
                    <td><?php if ($target['remote_url']): ?><a href="<?= htmlspecialchars($target['remote_url']) ?>" target="_blank" rel="noopener"><?= t('common.open') ?></a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/partials_footer.php'; ?>
