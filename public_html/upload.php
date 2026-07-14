<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$accounts = SocialAccount::forUser(Auth::id());
$errors = [];

$quotaGb = Plan::quotaGbForUser(Auth::user());
$quotaBytes = $quotaGb * 1024 ** 3;
$usedBytes = Post::storageUsedBytes(Auth::id());

// YouTube Shorts is not a separate API — it's just a normal YouTube upload that qualifies as a
// Short (vertical, <=60s), so we expose it as a checkbox variant of the same YouTube account.
$platformLabels = [
    'youtube' => t('upload.label_youtube'),
    'youtube_shorts' => t('upload.label_youtube_shorts'),
    'tiktok' => t('upload.label_tiktok'),
    'instagram' => t('upload.label_instagram'),
];

// Every (platform, connected account) combination the user can choose to publish to.
$availableTargets = [];
foreach ($accounts as $account) {
    $platforms = $account['platform'] === 'youtube' ? ['youtube', 'youtube_shorts'] : [$account['platform']];
    foreach ($platforms as $platform) {
        $key = $platform . ':' . $account['id'];
        $availableTargets[$key] = ['platform' => $platform, 'account' => $account];
    }
}

$postedFields = $_POST['fields'] ?? [];
$postedTitle = (string) ($_POST['title'] ?? '');
$postedPlatforms = $_POST['platforms'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $title = trim($postedTitle);
    if ($title === '') $errors[] = t('upload.err_title_required');
    if (empty($postedPlatforms)) $errors[] = t('upload.err_select_platform');

    // Build + validate the per-platform publish data.
    $targetsData = [];
    foreach ($postedPlatforms as $key) {
        if (!isset($availableTargets[$key])) {
            continue; // ignore tampered/unknown keys
        }
        $platform = $availableTargets[$key]['platform'];
        $account = $availableTargets[$key]['account'];
        $label = $platformLabels[$platform] . ' — ' . $account['display_name'];
        $f = $postedFields[$key] ?? [];

        $targetTitle = trim((string) ($f['title'] ?? ''));
        $targetDescription = trim((string) ($f['description'] ?? ''));
        $targetTags = trim((string) ($f['tags'] ?? ''));
        $isYoutube = in_array($platform, ['youtube', 'youtube_shorts'], true);
        $targetVisibility = $isYoutube && in_array($f['visibility'] ?? '', ['public', 'unlisted', 'private'], true)
            ? $f['visibility'] : 'public';
        $publishMode = ($f['publish_mode'] ?? 'now') === 'schedule' ? 'schedule' : 'now';

        if ($targetTitle === '') {
            $errors[] = sprintf(t('upload.err_title_required_for'), $label);
        }

        $scheduledAt = date('Y-m-d H:i:s');
        if ($publishMode === 'schedule') {
            $timestamp = strtotime((string) ($f['scheduled_at'] ?? ''));
            if (!$timestamp || $timestamp <= time()) {
                $errors[] = sprintf(t('upload.err_schedule_future_for'), $label);
            } else {
                $scheduledAt = date('Y-m-d H:i:s', $timestamp);
            }
        }

        $targetsData[] = [
            'social_account_id' => (int) $account['id'],
            'platform' => $platform,
            'title' => $targetTitle,
            'description' => $targetDescription,
            'tags' => $targetTags,
            'visibility' => $targetVisibility,
            'scheduled_at' => $scheduledAt,
        ];
    }

    $tmpPath = null;
    if (empty($_FILES['video']['name'])) {
        $errors[] = t('upload.err_select_video');
    } elseif ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = sprintf(t('upload.err_upload_failed'), $_FILES['video']['error']);
    } else {
        $ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['mp4', 'mov', 'm4v'];
        $allowedMimes = ['video/mp4', 'video/quicktime', 'video/x-m4v'];
        $actualMime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['video']['tmp_name']);

        if (!in_array($ext, $allowedExt, true) || !in_array($actualMime, $allowedMimes, true)) {
            $errors[] = t('upload.err_video_format');
        } elseif ($usedBytes + $_FILES['video']['size'] > $quotaBytes) {
            $remainingGb = max(0, ($quotaBytes - $usedBytes) / 1024 ** 3);
            $errors[] = sprintf(t('upload.err_storage_full'), $remainingGb, $quotaGb);
        } else {
            $tmpPath = $_FILES['video']['tmp_name'];
        }
    }

    $thumbnailTmpPath = null;
    $thumbnailExt = null;
    if (!empty($_FILES['thumbnail']['name'])) {
        if ($_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = t('upload.err_thumbnail_upload');
        } else {
            $thumbnailExt = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowedThumbExt = ['jpg', 'jpeg', 'png'];
            $allowedThumbMimes = ['image/jpeg', 'image/png'];
            $thumbMime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['thumbnail']['tmp_name']);

            if (!in_array($thumbnailExt, $allowedThumbExt, true) || !in_array($thumbMime, $allowedThumbMimes, true)) {
                $errors[] = t('upload.err_thumbnail_format');
            } elseif ($_FILES['thumbnail']['size'] > 5 * 1024 * 1024) {
                $errors[] = t('upload.err_thumbnail_size');
            } else {
                $thumbnailTmpPath = $_FILES['thumbnail']['tmp_name'];
            }
        }
    }

    if (!$errors) {
        $userDir = App::storagePath('uploads/' . Auth::id());
        if (!is_dir($userDir)) {
            mkdir($userDir, 0700, true);
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destination = $userDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $destination)) {
            $errors[] = t('upload.err_save_failed');
        } else {
            $thumbnailDestination = null;
            if ($thumbnailTmpPath) {
                $thumbCandidate = $userDir . '/' . bin2hex(random_bytes(16)) . '.' . $thumbnailExt;
                if (move_uploaded_file($thumbnailTmpPath, $thumbCandidate)) {
                    $thumbnailDestination = $thumbCandidate;
                }
            }

            $postId = Post::create(
                Auth::id(),
                $title,
                $destination,
                $_FILES['video']['name'],
                filesize($destination),
                $thumbnailDestination
            );

            PostTarget::createMany($postId, $targetsData);

            header('Location: dashboard.php?created=1');
            exit;
        }
    }
}

$pageTitle = t('upload.title');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('upload.title') ?></h1>

<?php $usedGb = $usedBytes / 1024 ** 3; $pct = min(100, $quotaGb > 0 ? ($usedGb / $quotaGb) * 100 : 0); ?>
<div class="card" style="padding:14px 20px;">
    <div class="muted"><?= sprintf(t('storage.used_sentence'), number_format($usedGb, 2), (int) $quotaGb) ?></div>
    <div style="background:#0d0f14;border-radius:6px;height:8px;margin-top:8px;overflow:hidden;">
        <div style="background:<?= $pct > 90 ? 'var(--err)' : 'var(--accent)' ?>;height:100%;width:<?= round($pct, 1) ?>%;"></div>
    </div>
</div>

<?php if ($accounts === []): ?>
    <div class="alert error"><?= t('upload.need_account_link') ?> <a href="accounts.php"><?= t('upload.link_account') ?></a></div>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="card">
    <?= Csrf::field() ?>

    <label><?= t('upload.internal_title_label') ?></label>
    <input type="text" name="title" value="<?= htmlspecialchars($postedTitle) ?>" required>

    <label><?= t('upload.video_file_label') ?></label>
    <input type="file" name="video" accept="video/mp4,video/quicktime" required>

    <label><?= t('upload.thumbnail_label') ?></label>
    <input type="file" name="thumbnail" accept="image/jpeg,image/png">
    <p class="muted"><?= t('upload.thumbnail_note') ?></p>

    <label><?= t('upload.publish_to_label') ?></label>
    <div class="checks">
        <?php if ($accounts === []): ?>
            <p class="muted"><?= t('upload.no_connected_accounts') ?></p>
        <?php endif; ?>
        <?php foreach ($availableTargets as $key => $target): ?>
            <label>
                <input type="checkbox" class="platform-toggle" data-key="<?= htmlspecialchars($key) ?>" name="platforms[]" value="<?= htmlspecialchars($key) ?>" <?= in_array($key, $postedPlatforms, true) ? 'checked' : '' ?>>
                <?= htmlspecialchars($platformLabels[$target['platform']]) ?> — <?= htmlspecialchars($target['account']['display_name']) ?>
            </label>
        <?php endforeach; ?>
    </div>

    <div id="platformFieldsets">
        <?php foreach ($availableTargets as $key => $target):
            $platform = $target['platform'];
            $account = $target['account'];
            $isYoutube = in_array($platform, ['youtube', 'youtube_shorts'], true);
            $f = $postedFields[$key] ?? [];
        ?>
            <div class="platform-fieldset" data-key="<?= htmlspecialchars($key) ?>" style="display:<?= in_array($key, $postedPlatforms, true) ? 'block' : 'none' ?>;">
                <h3 style="font-size:15px;margin:20px 0 4px;"><?= htmlspecialchars($platformLabels[$platform]) ?> — <?= htmlspecialchars($account['display_name']) ?></h3>

                <label><?= t('common.title') ?></label>
                <input type="text" name="fields[<?= htmlspecialchars($key) ?>][title]" value="<?= htmlspecialchars($f['title'] ?? '') ?>">

                <label><?= t('common.description') ?></label>
                <textarea name="fields[<?= htmlspecialchars($key) ?>][description]"><?= htmlspecialchars($f['description'] ?? '') ?></textarea>

                <label><?= t('upload.field_tags') ?></label>
                <input type="text" name="fields[<?= htmlspecialchars($key) ?>][tags]" placeholder="tag1, tag2, tag3" value="<?= htmlspecialchars($f['tags'] ?? '') ?>">

                <?php if ($isYoutube): ?>
                    <label><?= t('upload.field_visibility') ?></label>
                    <select name="fields[<?= htmlspecialchars($key) ?>][visibility]">
                        <option value="public" <?= ($f['visibility'] ?? 'public') === 'public' ? 'selected' : '' ?>><?= t('upload.visibility_public') ?></option>
                        <option value="unlisted" <?= ($f['visibility'] ?? '') === 'unlisted' ? 'selected' : '' ?>><?= t('upload.visibility_unlisted') ?></option>
                        <option value="private" <?= ($f['visibility'] ?? '') === 'private' ? 'selected' : '' ?>><?= t('upload.visibility_private') ?></option>
                    </select>
                <?php endif; ?>

                <label><?= t('upload.publish_on_platform_label') ?></label>
                <label style="display:inline-flex;align-items:center;gap:6px;font-weight:normal;">
                    <input type="radio" class="publish-mode-toggle" name="fields[<?= htmlspecialchars($key) ?>][publish_mode]" value="now" <?= ($f['publish_mode'] ?? 'now') === 'now' ? 'checked' : '' ?>> <?= t('upload.publish_now') ?>
                </label>
                <label style="display:inline-flex;align-items:center;gap:6px;font-weight:normal;">
                    <input type="radio" class="publish-mode-toggle" name="fields[<?= htmlspecialchars($key) ?>][publish_mode]" value="schedule" <?= ($f['publish_mode'] ?? '') === 'schedule' ? 'checked' : '' ?>> <?= t('upload.publish_schedule') ?>
                </label>
                <div class="schedule-field" style="display:<?= ($f['publish_mode'] ?? '') === 'schedule' ? 'block' : 'none' ?>;">
                    <input type="datetime-local" name="fields[<?= htmlspecialchars($key) ?>][scheduled_at]" value="<?= htmlspecialchars($f['scheduled_at'] ?? '') ?>">
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <p><button class="btn" type="submit" style="margin-top:20px;"><?= t('upload.submit') ?></button></p>
</form>

<script src="assets/js/upload.js"></script>
<?php require __DIR__ . '/partials_footer.php'; ?>
