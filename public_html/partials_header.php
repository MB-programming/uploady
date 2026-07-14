<?php
/** @var string $pageTitle */
$loggedIn = Auth::check();
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$isActive = fn (string ...$scripts): string => in_array($currentScript, $scripts, true) ? ' is-active' : '';
$otherLang = Lang::locale() === 'en' ? 'ar' : 'en';
$langToggleHref = 'lang.php?set=' . $otherLang;
?>
<!DOCTYPE html>
<html lang="<?= Lang::locale() ?>" dir="<?= Lang::dir() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? t('common.brand')) ?></title>
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
            <a href="dashboard.php"><?= t('common.brand') ?></a>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="<?= t('common.close') ?>"><?= Icons::close() ?></button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link<?= $isActive('dashboard.php') ?>"><?= Icons::home() ?> <?= t('nav.dashboard') ?></a>
            <a href="upload.php" class="sidebar-link<?= $isActive('upload.php') ?>"><?= Icons::upload() ?> <?= t('nav.upload') ?></a>
            <a href="accounts.php" class="sidebar-link<?= $isActive('accounts.php') ?>"><?= Icons::link() ?> <?= t('nav.accounts') ?></a>
            <a href="reports.php" class="sidebar-link<?= $isActive('reports.php', 'invoices.php', 'invoice_view.php') ?>"><?= Icons::barChart() ?> <?= t('nav.reports') ?></a>
            <a href="pricing.php" class="sidebar-link<?= $isActive('pricing.php') ?>"><?= Icons::tag() ?> <?= t('nav.pricing') ?></a>
            <a href="profile.php" class="sidebar-link<?= $isActive('profile.php') ?>"><?= Icons::user() ?> <?= t('nav.profile') ?></a>
            <?php if (Auth::isAdmin()): ?>
                <div class="sidebar-section"><?= t('nav.admin_section') ?></div>
                <a href="admin.php" class="sidebar-link<?= $isActive('admin.php', 'admin_client.php') ?>"><?= Icons::shield() ?> <?= t('nav.admin_dashboard') ?></a>
                <a href="admin_users.php" class="sidebar-link<?= $isActive('admin_users.php', 'admin_user_form.php') ?>"><?= Icons::users() ?> <?= t('nav.admin_users') ?></a>
                <a href="admin_invoices.php" class="sidebar-link<?= $isActive('admin_invoices.php', 'admin_invoice_form.php') ?>"><?= Icons::receipt() ?> <?= t('nav.admin_invoices') ?></a>
            <?php endif; ?>
            <a href="<?= $langToggleHref ?>" class="sidebar-link"><?= Icons::globe() ?> <?= t('common.lang_toggle') ?></a>
        </nav>
        <a href="logout.php" class="sidebar-link sidebar-logout"><?= Icons::logout() ?> <?= t('nav.logout') ?></a>
    </aside>
    <div class="app-main">
    <div class="container">
<?php else: ?>
<div class="nav">
    <a href="index.php"><strong><?= t('common.brand') ?></strong></a>
    <div>
        <a href="pricing.php"><?= t('nav.pricing') ?></a>
        <a href="login.php"><?= t('nav.login') ?></a>
        <a href="register.php"><?= t('nav.register') ?></a>
        <a href="<?= $langToggleHref ?>"><?= Icons::globe() ?> <?= t('common.lang_toggle') ?></a>
    </div>
</div>
<div class="container">
<?php endif; ?>
