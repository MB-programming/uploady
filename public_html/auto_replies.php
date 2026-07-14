<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$errors = [];
$success = null;
$videos = PostTarget::publishedInstagramForUser(Auth::id());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $postTargetId = (int) ($_POST['post_target_id'] ?? 0);
    $keyword = trim((string) ($_POST['keyword'] ?? ''));
    $dmMessage = trim((string) ($_POST['dm_message'] ?? ''));
    $requireFollow = !empty($_POST['require_follow']);
    $followPrompt = trim((string) ($_POST['follow_prompt'] ?? ''));

    $ownsTarget = array_filter($videos, fn ($v) => (int) $v['id'] === $postTargetId) !== [];
    if (!$ownsTarget) $errors[] = t('autoreply.err_video_required');
    if ($dmMessage === '') $errors[] = t('autoreply.err_message_required');
    if ($requireFollow && $followPrompt === '') $errors[] = t('autoreply.err_follow_prompt_required');

    if (!$errors) {
        AutoReplyRule::create(
            Auth::id(),
            $postTargetId,
            $keyword !== '' ? $keyword : null,
            $dmMessage,
            $requireFollow,
            $requireFollow ? $followPrompt : null
        );
        $success = t('autoreply.created_success');
        $keyword = $dmMessage = $followPrompt = '';
        $requireFollow = false;
    }
}

$rules = AutoReplyRule::forUser(Auth::id());

$pageTitle = t('nav.auto_replies');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('autoreply.title') ?></h1>
<p class="muted"><?= t('autoreply.intro') ?></p>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert success"><?= t('autoreply.deleted_success') ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<?php if ($videos === []): ?>
    <div class="card"><p class="muted" style="margin:0;"><?= t('autoreply.no_videos') ?></p></div>
<?php else: ?>
<form method="post" class="card" style="max-width:560px;">
    <?= Csrf::field() ?>

    <label><?= t('autoreply.video_label') ?></label>
    <select name="post_target_id" required>
        <option value=""><?= t('autoreply.video_placeholder') ?></option>
        <?php foreach ($videos as $video): ?>
            <option value="<?= (int) $video['id'] ?>"><?= htmlspecialchars($video['title']) ?> — <?= htmlspecialchars($video['account_name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label><?= t('autoreply.keyword_label') ?></label>
    <input type="text" name="keyword" value="<?= htmlspecialchars($keyword ?? '') ?>" placeholder="<?= t('autoreply.keyword_placeholder') ?>">
    <p class="muted" style="margin:4px 0 0;font-size:13px;"><?= t('autoreply.keyword_hint') ?></p>

    <label><?= t('autoreply.message_label') ?></label>
    <textarea name="dm_message" rows="4" required placeholder="<?= t('autoreply.message_placeholder') ?>"><?= htmlspecialchars($dmMessage ?? '') ?></textarea>

    <label style="display:inline-flex;align-items:center;gap:8px;font-weight:normal;margin-top:14px;">
        <input type="checkbox" name="require_follow" value="1" <?= !empty($requireFollow) ? 'checked' : '' ?>>
        <?= t('autoreply.require_follow_label') ?>
    </label>
    <p class="muted" style="margin:4px 0 0;font-size:13px;"><?= t('autoreply.require_follow_hint') ?></p>

    <label><?= t('autoreply.follow_prompt_label') ?></label>
    <textarea name="follow_prompt" rows="3" placeholder="<?= t('autoreply.follow_prompt_placeholder') ?>"><?= htmlspecialchars($followPrompt ?? '') ?></textarea>
    <p class="muted" style="margin:4px 0 0;font-size:13px;"><?= t('autoreply.follow_prompt_hint') ?></p>

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= t('autoreply.create_submit') ?></button></p>
</form>
<?php endif; ?>

<h2 style="font-size:16px;"><?= t('autoreply.list_title') ?></h2>

<?php if ($rules === []): ?>
    <div class="card"><p class="muted" style="margin:0;"><?= t('autoreply.list_empty') ?></p></div>
<?php endif; ?>

<?php foreach ($rules as $rule): ?>
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;">
            <div>
                <strong><?= htmlspecialchars($rule['target_title']) ?></strong>
                <span class="muted">— <?= htmlspecialchars($rule['account_name']) ?></span>
                <?php if ($rule['remote_url']): ?>
                    <a href="<?= htmlspecialchars($rule['remote_url']) ?>" target="_blank" rel="noopener"><?= t('common.link') ?></a>
                <?php endif; ?>
            </div>
            <span class="badge <?= $rule['is_active'] ? 'published' : 'pending' ?>">
                <?= $rule['is_active'] ? t('autoreply.status_active') : t('autoreply.status_paused') ?>
            </span>
        </div>

        <p style="margin:8px 0 4px;">
            <?php if ($rule['keyword'] !== null && $rule['keyword'] !== ''): ?>
                <?= sprintf(t('autoreply.rule_keyword'), '<strong>' . htmlspecialchars($rule['keyword']) . '</strong>') ?>
            <?php else: ?>
                <?= t('autoreply.rule_any_comment') ?>
            <?php endif; ?>
            <?php if ($rule['require_follow']): ?>
                · <?= t('autoreply.rule_follow_gated') ?>
            <?php endif; ?>
        </p>
        <p class="muted" style="margin:0 0 8px;white-space:pre-line;"><?= htmlspecialchars($rule['dm_message']) ?></p>

        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
            <span class="muted" style="font-size:13px;">
                <?= sprintf(t('autoreply.stats_sent'), (int) $rule['sent_count']) ?>
                <?php if ($rule['require_follow']): ?> · <?= sprintf(t('autoreply.stats_awaiting'), (int) $rule['awaiting_count']) ?><?php endif; ?>
            </span>
            <span style="display:inline-flex;gap:8px;">
                <form method="post" action="auto_reply_action.php" style="display:inline;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>">
                    <input type="hidden" name="action" value="<?= $rule['is_active'] ? 'pause' : 'resume' ?>">
                    <button type="submit" class="btn secondary"><?= $rule['is_active'] ? t('autoreply.pause') : t('autoreply.resume') ?></button>
                </form>
                <form method="post" action="auto_reply_action.php" style="display:inline;" data-confirm="<?= htmlspecialchars(t('autoreply.delete_confirm')) ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="rule_id" value="<?= (int) $rule['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn danger"><?= t('common.delete') ?></button>
                </form>
            </span>
        </div>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/partials_footer.php'; ?>
