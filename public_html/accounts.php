<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'disconnect') {
    Csrf::verify();
    SocialAccount::delete((int) $_POST['id'], Auth::id());
    header('Location: accounts.php');
    exit;
}

$accounts = SocialAccount::forUser(Auth::id());
$byPlatform = ['youtube' => [], 'tiktok' => [], 'instagram' => []];
foreach ($accounts as $account) {
    $byPlatform[$account['platform']][] = $account;
}

$errorMessages = [
    'youtube_state' => 'حصل خطأ في عملية الربط مع يوتيوب، حاول تاني.',
    'youtube_no_refresh' => 'يوتيوب محتاج تدّي الموافقة الكاملة (Consent) عشان نقدر ننشر بدون تدخلك كل مرة. جرب افصل الاتصال من حساب جوجل نفسه (myaccount.google.com/permissions) وأعد الربط.',
    'youtube_exception' => 'حصل خطأ أثناء الربط مع يوتيوب.',
    'tiktok_state' => 'حصل خطأ في عملية الربط مع تيك توك، حاول تاني.',
    'tiktok_exception' => 'حصل خطأ أثناء الربط مع تيك توك.',
    'instagram_state' => 'حصل خطأ في عملية الربط مع انستجرام، حاول تاني.',
    'instagram_no_business_account' => 'محتاج تحول حساب الانستجرام لـ Business/Creator وتربطه بصفحة فيسبوك الأول.',
    'instagram_exception' => 'حصل خطأ أثناء الربط مع انستجرام.',
];

$pageTitle = 'حسابات التواصل الاجتماعي';
require __DIR__ . '/partials_header.php';
?>
<h1>حسابات التواصل الاجتماعي</h1>

<?php if (!empty($_GET['connected'])): ?>
    <div class="alert success">تم ربط الحساب بنجاح.</div>
<?php endif; ?>
<?php if (!empty($_GET['error']) && isset($errorMessages[$_GET['error']])): ?>
    <div class="alert error"><?= htmlspecialchars($errorMessages[$_GET['error']]) ?></div>
<?php endif; ?>

<div class="platform-list">
    <div class="platform-card">
        <h2 style="font-size:16px;">يوتيوب</h2>
        <?php foreach ($byPlatform['youtube'] as $acc): ?>
            <p><?= htmlspecialchars($acc['display_name']) ?></p>
            <?php include __DIR__ . '/_disconnect_form.php'; ?>
        <?php endforeach; ?>
        <a class="btn secondary" href="connect/youtube.php">+ ربط قناة يوتيوب</a>
    </div>
    <div class="platform-card">
        <h2 style="font-size:16px;">تيك توك</h2>
        <?php foreach ($byPlatform['tiktok'] as $acc): ?>
            <p><?= htmlspecialchars($acc['display_name']) ?></p>
            <?php include __DIR__ . '/_disconnect_form.php'; ?>
        <?php endforeach; ?>
        <a class="btn secondary" href="connect/tiktok.php">+ ربط حساب تيك توك</a>
        <p class="muted">لو الأبلكيشن لسه مش معتمد (Audited) من تيك توك، الفيديو هينشر كـ Private على حسابك بس.</p>
    </div>
    <div class="platform-card">
        <h2 style="font-size:16px;">انستجرام</h2>
        <?php foreach ($byPlatform['instagram'] as $acc): ?>
            <p><?= htmlspecialchars($acc['display_name']) ?></p>
            <?php include __DIR__ . '/_disconnect_form.php'; ?>
        <?php endforeach; ?>
        <a class="btn secondary" href="connect/instagram.php">+ ربط حساب انستجرام</a>
        <p class="muted">لازم يكون الحساب Business أو Creator ومربوط بصفحة فيسبوك.</p>
    </div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
