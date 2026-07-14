<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$notifications = Notification::forUser(Auth::id());
Notification::markAllReadForUser(Auth::id());

$pageTitle = t('notif.inbox_title');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('notif.inbox_title') ?></h1>

<?php if ($notifications === []): ?>
    <p class="muted"><?= t('notif.empty') ?></p>
<?php endif; ?>

<?php foreach ($notifications as $notification): ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:10px;">
            <strong><?= htmlspecialchars($notification['title']) ?></strong>
            <span class="muted" style="white-space:nowrap;"><?= htmlspecialchars($notification['created_at']) ?></span>
        </div>
        <p style="margin:8px 0 0;"><?= nl2br(htmlspecialchars($notification['body'])) ?></p>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/partials_footer.php'; ?>
