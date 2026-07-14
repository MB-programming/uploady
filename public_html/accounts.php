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
    'youtube_state' => t('accounts.err_youtube_state'),
    'youtube_no_refresh' => t('accounts.err_youtube_no_refresh'),
    'youtube_exception' => t('accounts.err_youtube_exception'),
    'tiktok_state' => t('accounts.err_tiktok_state'),
    'tiktok_exception' => t('accounts.err_tiktok_exception'),
    'instagram_state' => t('accounts.err_instagram_state'),
    'instagram_no_business_account' => t('accounts.err_instagram_no_business'),
    'instagram_exception' => t('accounts.err_instagram_exception'),
];

$pageTitle = t('accounts.title');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('accounts.title') ?></h1>

<?php if (!empty($_GET['connected'])): ?>
    <div class="alert success"><?= t('accounts.connected_success') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['error']) && isset($errorMessages[$_GET['error']])): ?>
    <div class="alert error"><?= htmlspecialchars($errorMessages[$_GET['error']]) ?></div>
<?php endif; ?>

<div class="platform-list">
    <div class="platform-card">
        <h2 style="font-size:16px;"><?= t('platform.youtube') ?></h2>
        <?php foreach ($byPlatform['youtube'] as $acc): ?>
            <p><?= htmlspecialchars($acc['display_name']) ?></p>
            <?php include __DIR__ . '/_disconnect_form.php'; ?>
        <?php endforeach; ?>
        <a class="btn secondary" href="connect/youtube.php"><?= t('accounts.connect_youtube') ?></a>
    </div>
    <div class="platform-card">
        <h2 style="font-size:16px;"><?= t('platform.tiktok') ?></h2>
        <?php foreach ($byPlatform['tiktok'] as $acc): ?>
            <p><?= htmlspecialchars($acc['display_name']) ?></p>
            <?php include __DIR__ . '/_disconnect_form.php'; ?>
        <?php endforeach; ?>
        <a class="btn secondary" href="connect/tiktok.php"><?= t('accounts.connect_tiktok') ?></a>
        <p class="muted"><?= t('accounts.tiktok_unaudited_note') ?></p>
    </div>
    <div class="platform-card">
        <h2 style="font-size:16px;"><?= t('platform.instagram') ?></h2>
        <?php foreach ($byPlatform['instagram'] as $acc): ?>
            <p><?= htmlspecialchars($acc['display_name']) ?></p>
            <?php include __DIR__ . '/_disconnect_form.php'; ?>
        <?php endforeach; ?>
        <a class="btn secondary" href="connect/instagram.php"><?= t('accounts.connect_instagram') ?></a>
        <p class="muted"><?= t('accounts.instagram_requirement_note') ?></p>
    </div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
