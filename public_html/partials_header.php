<?php
/** @var string $pageTitle */
$loggedIn = Auth::check();
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$isActive = fn (string ...$scripts): string => in_array($currentScript, $scripts, true) ? ' is-active' : '';
$otherLang = Lang::locale() === 'en' ? 'ar' : 'en';
$langToggleHref = 'lang.php?set=' . $otherLang;

$siteTitle = Settings::get('seo_site_title', t('common.brand'));
$metaDescription = Settings::get('seo_meta_description');
$metaKeywords = Settings::get('seo_meta_keywords');
$logoPath = Settings::get('site_logo_path');
$faviconPath = Settings::get('site_favicon_path');
$brandHtml = $logoPath
    ? '<img src="' . htmlspecialchars($logoPath) . '" alt="' . htmlspecialchars($siteTitle) . '" class="brand-logo">'
    : htmlspecialchars(t('common.brand'));
$unreadNotifications = $loggedIn ? Notification::unreadCountForUser(Auth::id()) : 0;
?>
<!DOCTYPE html>
<html lang="<?= Lang::locale() ?>" dir="<?= Lang::dir() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? $siteTitle) ?></title>
<?php if ($metaDescription): ?><meta name="description" content="<?= htmlspecialchars($metaDescription) ?>"><?php endif; ?>
<?php if ($metaKeywords): ?><meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>"><?php endif; ?>
<?php if ($faviconPath): ?><link rel="icon" href="<?= htmlspecialchars($faviconPath) ?>"><?php endif; ?>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if ($loggedIn): ?>
<div class="app-shell">
    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="<?= t('common.open') ?>">
        <?= Icons::menu() ?>
    </button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="dashboard.php"><?= $brandHtml ?></a>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="<?= t('common.close') ?>"><?= Icons::close() ?></button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link<?= $isActive('dashboard.php') ?>"><?= Icons::home() ?> <?= t('nav.dashboard') ?></a>
            <a href="upload.php" class="sidebar-link<?= $isActive('upload.php') ?>"><?= Icons::upload() ?> <?= t('nav.upload') ?></a>
            <a href="accounts.php" class="sidebar-link<?= $isActive('accounts.php') ?>"><?= Icons::link() ?> <?= t('nav.accounts') ?></a>
            <a href="auto_replies.php" class="sidebar-link<?= $isActive('auto_replies.php') ?>"><?= Icons::chat() ?> <?= t('nav.auto_replies') ?></a>
            <a href="keywords.php" class="sidebar-link<?= $isActive('keywords.php') ?>"><?= Icons::search() ?> <?= t('nav.keywords') ?></a>
            <a href="reports.php" class="sidebar-link<?= $isActive('reports.php', 'invoices.php', 'invoice_view.php') ?>"><?= Icons::barChart() ?> <?= t('nav.reports') ?></a>
            <a href="pricing.php" class="sidebar-link<?= $isActive('pricing.php') ?>"><?= Icons::tag() ?> <?= t('nav.pricing') ?></a>
            <a href="notifications.php" class="sidebar-link<?= $isActive('notifications.php') ?>">
                <?= Icons::bell() ?> <?= t('nav.notifications') ?>
                <?php if ($unreadNotifications > 0): ?><span class="badge failed" style="margin-inline-start:auto;padding:2px 8px;"><?= $unreadNotifications ?></span><?php endif; ?>
            </a>
            <a href="profile.php" class="sidebar-link<?= $isActive('profile.php') ?>"><?= Icons::user() ?> <?= t('nav.profile') ?></a>
            <?php if (Auth::isAdmin()): ?>
                <div class="sidebar-section"><?= t('nav.admin_section') ?></div>
                <a href="admin.php" class="sidebar-link<?= $isActive('admin.php', 'admin_client.php') ?>"><?= Icons::shield() ?> <?= t('nav.admin_dashboard') ?></a>
                <a href="admin_users.php" class="sidebar-link<?= $isActive('admin_users.php', 'admin_user_form.php') ?>"><?= Icons::users() ?> <?= t('nav.admin_users') ?></a>
                <a href="admin_plans.php" class="sidebar-link<?= $isActive('admin_plans.php', 'admin_plan_form.php') ?>"><?= Icons::package() ?> <?= t('nav.admin_plans') ?></a>
                <a href="admin_invoices.php" class="sidebar-link<?= $isActive('admin_invoices.php', 'admin_invoice_form.php') ?>"><?= Icons::receipt() ?> <?= t('nav.admin_invoices') ?></a>
                <a href="admin_notifications.php" class="sidebar-link<?= $isActive('admin_notifications.php') ?>"><?= Icons::bell() ?> <?= t('nav.admin_notifications') ?></a>
                <a href="admin_settings.php" class="sidebar-link<?= $isActive('admin_settings.php') ?>"><?= Icons::gear() ?> <?= t('nav.admin_settings') ?></a>
            <?php endif; ?>
            <a href="<?= $langToggleHref ?>" class="sidebar-link"><?= Icons::globe() ?> <?= t('common.lang_toggle') ?></a>
        </nav>
        <a href="logout.php" class="sidebar-link sidebar-logout"><?= Icons::logout() ?> <?= t('nav.logout') ?></a>
    </aside>
    <div class="app-main">
    <div class="container">
<?php else: ?>
<div class="nav">
    <a href="index.php"><strong><?= $brandHtml ?></strong></a>
    <div>
        <a href="pricing.php"><?= t('nav.pricing') ?></a>
        <a href="login.php"><?= t('nav.login') ?></a>
        <a href="register.php"><?= t('nav.register') ?></a>
        <a href="<?= $langToggleHref ?>"><?= Icons::globe() ?> <?= t('common.lang_toggle') ?></a>
    </div>
</div>
<div class="container">
<?php endif; ?>
