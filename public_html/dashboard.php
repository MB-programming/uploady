<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$posts = Post::forUser(Auth::id());

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

$usedGb = Post::storageUsedBytes(Auth::id()) / 1024 ** 3;
$quotaGb = Plan::quotaGbForUser(Auth::user());
$pct = min(100, $quotaGb > 0 ? ($usedGb / $quotaGb) * 100 : 0);

$totalVideos = count($posts);
$publishedVideos = count(array_filter($posts, fn ($p) => $p['status'] === 'published'));
$pendingVideos = count(array_filter($posts, fn ($p) => in_array($p['status'], ['scheduled', 'processing', 'partially_published'], true)));

$pageTitle = t('dashboard.title');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('dashboard.title') ?></h1>

<?php if (!empty($_GET['created'])): ?>
    <div class="alert success"><?= t('dashboard.alert_created') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['cancelled'])): ?>
    <div class="alert success"><?= t('dashboard.alert_cancelled') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['rescheduled'])): ?>
    <div class="alert success"><?= t('dashboard.alert_rescheduled') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
    <div class="alert error"><?= t('dashboard.alert_error') ?></div>
<?php endif; ?>

<div class="stat-grid">
    <div class="stat-tile stat-tile--blue">
        <div class="stat-icon"><?= Icons::film() ?></div>
        <div class="stat-value"><?= $totalVideos ?></div>
        <div class="stat-label"><?= t('dashboard.stat_total') ?></div>
    </div>
    <div class="stat-tile stat-tile--green">
        <div class="stat-icon"><?= Icons::trendUp() ?></div>
        <div class="stat-value"><?= $publishedVideos ?></div>
        <div class="stat-label"><?= t('dashboard.stat_published') ?></div>
    </div>
    <div class="stat-tile stat-tile--orange">
        <div class="stat-icon"><?= Icons::clock() ?></div>
        <div class="stat-value"><?= $pendingVideos ?></div>
        <div class="stat-label"><?= t('dashboard.stat_pending') ?></div>
    </div>
    <div class="stat-tile stat-tile--pink">
        <div class="stat-icon"><?= Icons::database() ?></div>
        <div class="stat-value"><?= number_format($usedGb, 1) ?><span style="font-size:15px;"> / <?= (int) $quotaGb ?> GB</span></div>
        <div class="stat-label"><?= t('storage.used_label') ?></div>
        <div class="post-progress" style="margin-top:12px;">
            <div class="post-progress-bar" style="background:rgba(255,255,255,.25);">
                <div class="post-progress-fill" style="background:#fff;width:<?= round($pct, 1) ?>%;"></div>
            </div>
        </div>
    </div>
</div>

<div class="dash-toolbar">
    <h2 style="font-size:16px;margin:0;"><?= t('dashboard.your_videos') ?></h2>
    <a class="btn" href="upload.php"><?= Icons::upload() ?> <?= t('nav.upload') ?></a>
</div>

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
                <h2 style="font-size:17px;margin-bottom:4px;"><?= htmlspecialchars($post['title']) ?></h2>
                <p class="muted">
                    <?= t('dashboard.created_at') ?> <?= htmlspecialchars($post['created_at']) ?>
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
            <thead><tr><th><?= t('common.platform') ?></th><th><?= t('common.title') ?></th><th><?= t('common.status') ?></th><th><?= t('common.link') ?></th><th></th></tr></thead>
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
                    <td><?php if ($target['remote_url']): ?><a href="<?= htmlspecialchars($target['remote_url']) ?>" target="_blank" rel="noopener"><?= t('common.open') ?></a><?php endif; ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($stillCancellable): ?>
                            <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                <form method="post" action="target_action.php" style="display:flex;gap:4px;align-items:center;">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="action" value="reschedule">
                                    <input type="hidden" name="target_id" value="<?= (int) $target['id'] ?>">
                                    <input type="datetime-local" name="scheduled_at" required style="width:auto;font-size:12px;padding:4px 6px;">
                                    <button type="submit" class="btn secondary" style="padding:4px 8px;font-size:12px;"><?= t('common.edit') ?></button>
                                </form>
                                <form method="post" action="target_action.php" data-confirm="<?= htmlspecialchars(t('dashboard.cancel_confirm')) ?>">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="target_id" value="<?= (int) $target['id'] ?>">
                                    <button type="submit" class="btn danger" style="padding:4px 8px;font-size:12px;"><?= t('common.cancel') ?></button>
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
