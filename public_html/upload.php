<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireLogin();

$accounts = SocialAccount::forUser(Auth::id());
$errors = [];

$quotaGb = Plan::quotaGbForUser(Auth::user());
$quotaBytes = $quotaGb * 1024 ** 3;
$usedBytes = Post::storageUsedBytes(Auth::id());

// YouTube Shorts is not a separate API — it's just a normal YouTube upload that qualifies as a
// Short (vertical, <=60s), so we expose it as a checkbox variant of the same YouTube account.
$platformLabels = [
    'youtube' => 'يوتيوب (فيديو عادي)',
    'youtube_shorts' => 'يوتيوب Shorts',
    'tiktok' => 'تيك توك',
    'instagram' => 'انستجرام (Reels)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $tags = trim((string) ($_POST['tags'] ?? ''));
    $visibility = in_array($_POST['visibility'] ?? '', ['public', 'unlisted', 'private'], true)
        ? $_POST['visibility'] : 'public';
    $publishMode = $_POST['publish_mode'] ?? 'now';
    $scheduledAtInput = (string) ($_POST['scheduled_at'] ?? '');
    $selectedTargets = $_POST['targets'] ?? []; // array of "platform:social_account_id"

    if ($title === '') $errors[] = 'العنوان مطلوب';
    if (empty($selectedTargets)) $errors[] = 'اختار منصة واحدة على الأقل تنشر عليها';

    $scheduledAt = date('Y-m-d H:i:s');
    if ($publishMode === 'schedule') {
        $timestamp = strtotime($scheduledAtInput);
        if (!$timestamp || $timestamp <= time()) {
            $errors[] = 'وقت الجدولة لازم يكون في المستقبل';
        } else {
            $scheduledAt = date('Y-m-d H:i:s', $timestamp);
        }
    }

    $tmpPath = null;
    if (empty($_FILES['video']['name'])) {
        $errors[] = 'اختار ملف فيديو';
    } elseif ($_FILES['video']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'حصل خطأ أثناء رفع الملف (الحجم أكبر من المسموح؟). كود الخطأ: ' . $_FILES['video']['error'];
    } else {
        $ext = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['mp4', 'mov', 'm4v'];
        $allowedMimes = ['video/mp4', 'video/quicktime', 'video/x-m4v'];
        $actualMime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['video']['tmp_name']);

        if (!in_array($ext, $allowedExt, true) || !in_array($actualMime, $allowedMimes, true)) {
            $errors[] = 'صيغة الفيديو لازم تكون mp4 أو mov فعليًا (مش بس الامتداد)';
        } elseif ($usedBytes + $_FILES['video']['size'] > $quotaBytes) {
            $remainingGb = max(0, ($quotaBytes - $usedBytes) / 1024 ** 3);
            $errors[] = sprintf(
                'مساحة التخزين هتخلص — متبقي %.2f GB بس من أصل %d GB. الفيديوهات المنشورة بتتحذف تلقائي وتفضي مساحة، أو استنى لحد ما فيديو تاني ينشر.',
                $remainingGb,
                $quotaGb
            );
        } else {
            $tmpPath = $_FILES['video']['tmp_name'];
        }
    }

    $thumbnailTmpPath = null;
    $thumbnailExt = null;
    if (!empty($_FILES['thumbnail']['name'])) {
        if ($_FILES['thumbnail']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'حصل خطأ أثناء رفع الصورة المصغرة.';
        } else {
            $thumbnailExt = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
            $allowedThumbExt = ['jpg', 'jpeg', 'png'];
            $allowedThumbMimes = ['image/jpeg', 'image/png'];
            $thumbMime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['thumbnail']['tmp_name']);

            if (!in_array($thumbnailExt, $allowedThumbExt, true) || !in_array($thumbMime, $allowedThumbMimes, true)) {
                $errors[] = 'الصورة المصغرة لازم تكون jpg أو png فعليًا';
            } elseif ($_FILES['thumbnail']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'الصورة المصغرة أكبر من 5MB';
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
            $errors[] = 'تعذر حفظ الفيديو على السيرفر';
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
                $description,
                $tags,
                $visibility,
                $destination,
                $_FILES['video']['name'],
                filesize($destination),
                $scheduledAt,
                $thumbnailDestination
            );

            $targets = [];
            foreach ($selectedTargets as $value) {
                [$platform, $accountId] = explode(':', $value, 2) + [null, null];
                if ($platform && $accountId) {
                    $targets[] = ['platform' => $platform, 'social_account_id' => (int) $accountId];
                }
            }
            PostTarget::createMany($postId, $targets);

            header('Location: dashboard.php?created=1');
            exit;
        }
    }
}

$pageTitle = 'رفع فيديو جديد';
require __DIR__ . '/partials_header.php';
?>
<h1>رفع فيديو جديد</h1>

<?php $usedGb = $usedBytes / 1024 ** 3; $pct = min(100, $quotaGb > 0 ? ($usedGb / $quotaGb) * 100 : 0); ?>
<div class="card" style="padding:14px 20px;">
    <div class="muted">مساحة التخزين المستخدمة: <?= number_format($usedGb, 2) ?> GB من <?= (int) $quotaGb ?> GB</div>
    <div style="background:#0d0f14;border-radius:6px;height:8px;margin-top:8px;overflow:hidden;">
        <div style="background:<?= $pct > 90 ? 'var(--err)' : 'var(--accent)' ?>;height:100%;width:<?= round($pct, 1) ?>%;"></div>
    </div>
</div>

<?php if ($accounts === []): ?>
    <div class="alert error">لازم تربط حساب تواصل اجتماعي واحد على الأقل الأول. <a href="accounts.php">اربط حساب</a></div>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="card">
    <?= Csrf::field() ?>

    <label>ملف الفيديو (mp4 / mov)</label>
    <input type="file" name="video" accept="video/mp4,video/quicktime" required>

    <label>العنوان</label>
    <input type="text" name="title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>

    <label>الوصف</label>
    <textarea name="description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

    <label>الهاشتاجات / الكلمات المفتاحية (افصل بفاصلة)</label>
    <input type="text" name="tags" placeholder="tag1, tag2, tag3" value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>">

    <label>الصورة المصغرة (Thumbnail) — اختياري، jpg أو png</label>
    <input type="file" name="thumbnail" accept="image/jpeg,image/png">
    <p class="muted">بتتحط على يوتيوب تلقائي. تيك توك وانستجرام مش بيدوا إمكانية رفع صورة مصغرة
    مخصصة عن طريق الـ API — بيختاروا لقطة من داخل الفيديو نفسه بدل كده.</p>

    <label>الخصوصية (يوتيوب فقط - المنصات التانية بتتنشر عام)</label>
    <select name="visibility">
        <option value="public">عام</option>
        <option value="unlisted">غير مدرج (Unlisted)</option>
        <option value="private">خاص</option>
    </select>

    <label>انشر على</label>
    <div class="checks">
        <?php if ($accounts === []): ?>
            <p class="muted">مفيش حسابات متصلة.</p>
        <?php endif; ?>
        <?php foreach ($accounts as $account): ?>
            <?php
            $platformsForAccount = $account['platform'] === 'youtube' ? ['youtube', 'youtube_shorts'] : [$account['platform']];
            foreach ($platformsForAccount as $platform):
            ?>
                <label>
                    <input type="checkbox" name="targets[]" value="<?= htmlspecialchars($platform . ':' . $account['id']) ?>">
                    <?= htmlspecialchars($platformLabels[$platform]) ?> — <?= htmlspecialchars($account['display_name']) ?>
                </label>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <label>النشر</label>
    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:normal;">
        <input type="radio" name="publish_mode" value="now" checked> نشر الآن
    </label>
    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:normal;">
        <input type="radio" name="publish_mode" value="schedule"> جدولة لوقت لاحق
    </label>
    <div id="scheduleField" style="display:none;">
        <input type="datetime-local" name="scheduled_at" value="<?= htmlspecialchars($_POST['scheduled_at'] ?? '') ?>">
    </div>

    <p><button class="btn" type="submit" style="margin-top:20px;">نشر / جدولة</button></p>
</form>

<script src="assets/js/upload.js"></script>
<?php require __DIR__ . '/partials_footer.php'; ?>
