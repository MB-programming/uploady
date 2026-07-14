<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$errors = [];
$success = null;
$clients = User::allClients();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $target = $_POST['target'] ?? 'all';
    $title = trim((string) ($_POST['title'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    $targetUserId = (int) ($_POST['target_user_id'] ?? 0);

    if ($title === '') $errors[] = t('notif.err_title_required');
    if ($body === '') $errors[] = t('notif.err_body_required');
    if ($target === 'single') {
        $targetUser = User::findById($targetUserId);
        if (!$targetUser) $errors[] = t('notif.err_select_user');
    }

    if (!$errors) {
        if ($target === 'single') {
            Notification::sendToUser($targetUserId, $title, $body);
            $success = t('notif.sent_success_single');
        } else {
            $count = Notification::broadcastToAllClients($title, $body);
            $success = sprintf(t('notif.sent_success_broadcast'), $count);
        }
        $title = '';
        $body = '';
    }
}

$recent = Notification::recentSentSummary();

$pageTitle = t('nav.admin_notifications');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('notif.admin_title') ?></h1>

<?php if ($success): ?>
    <div class="alert success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" class="card" style="max-width:560px;">
    <?= Csrf::field() ?>

    <label><?= t('notif.target_label') ?></label>
    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:normal;margin-inline-end:18px;">
        <input type="radio" name="target" value="all" id="notifTargetAll" checked> <?= t('notif.target_all') ?>
    </label>
    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:normal;">
        <input type="radio" name="target" value="single" id="notifTargetSingle"> <?= t('notif.target_single') ?>
    </label>

    <div id="notifTargetUser" style="display:none;">
        <label><?= t('notif.select_user') ?></label>
        <select name="target_user_id">
            <?php foreach ($clients as $client): ?>
                <option value="<?= (int) $client['id'] ?>"><?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <label><?= t('notif.title_label') ?></label>
    <input type="text" name="title" value="<?= htmlspecialchars($title ?? '') ?>" required>

    <label><?= t('notif.body_label') ?></label>
    <textarea name="body" rows="4" required><?= htmlspecialchars($body ?? '') ?></textarea>

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= t('notif.send') ?></button></p>
</form>

<h2 style="font-size:16px;"><?= t('notif.recent_sent') ?></h2>
<div class="card">
    <?php if ($recent === []): ?>
        <p class="muted"><?= t('notif.empty') ?></p>
    <?php endif; ?>
    <?php foreach ($recent as $item): ?>
        <div style="padding:10px 0;border-bottom:1px solid var(--border);">
            <div style="display:flex;justify-content:space-between;gap:10px;">
                <strong><?= htmlspecialchars($item['title']) ?></strong>
                <span class="muted" style="white-space:nowrap;"><?= htmlspecialchars($item['created_at']) ?></span>
            </div>
            <p class="muted" style="margin:4px 0;"><?= htmlspecialchars($item['body']) ?></p>
            <span class="badge pending"><?= sprintf(t('notif.recipients_count'), $item['recipients']) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<script src="assets/js/admin_notifications.js"></script>
<?php require __DIR__ . '/partials_footer.php'; ?>
