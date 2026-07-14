<?php
/** @var string $pageTitle */
$loggedIn = Auth::check();
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$isActive = fn (string ...$scripts): string => in_array($currentScript, $scripts, true) ? ' is-active' : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? 'Uploady') ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php if ($loggedIn): ?>
<div class="app-shell">
    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="فتح القائمة">
        <?= Icons::menu() ?>
    </button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="dashboard.php">Uploady</a>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="إغلاق القائمة"><?= Icons::close() ?></button>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link<?= $isActive('dashboard.php') ?>"><?= Icons::home() ?> لوحة التحكم</a>
            <a href="upload.php" class="sidebar-link<?= $isActive('upload.php') ?>"><?= Icons::upload() ?> رفع فيديو جديد</a>
            <a href="accounts.php" class="sidebar-link<?= $isActive('accounts.php') ?>"><?= Icons::link() ?> حسابات التواصل</a>
            <a href="reports.php" class="sidebar-link<?= $isActive('reports.php', 'invoices.php', 'invoice_view.php') ?>"><?= Icons::barChart() ?> التقارير</a>
            <a href="pricing.php" class="sidebar-link<?= $isActive('pricing.php') ?>"><?= Icons::tag() ?> الأسعار</a>
            <a href="profile.php" class="sidebar-link<?= $isActive('profile.php') ?>"><?= Icons::user() ?> حسابي</a>
            <?php if (Auth::isAdmin()): ?>
                <div class="sidebar-section">الأدمن</div>
                <a href="admin.php" class="sidebar-link<?= $isActive('admin.php', 'admin_client.php') ?>"><?= Icons::shield() ?> لوحة الأدمن</a>
                <a href="admin_users.php" class="sidebar-link<?= $isActive('admin_users.php', 'admin_user_form.php') ?>"><?= Icons::users() ?> إدارة المستخدمين</a>
                <a href="admin_invoices.php" class="sidebar-link<?= $isActive('admin_invoices.php', 'admin_invoice_form.php') ?>"><?= Icons::receipt() ?> الفواتير</a>
            <?php endif; ?>
        </nav>
        <a href="logout.php" class="sidebar-link sidebar-logout"><?= Icons::logout() ?> تسجيل خروج</a>
    </aside>
    <div class="app-main">
    <div class="container">
<?php else: ?>
<div class="nav">
    <a href="index.php"><strong>Uploady</strong></a>
    <div>
        <a href="pricing.php">الأسعار</a>
        <a href="login.php">تسجيل دخول</a>
        <a href="register.php">حساب جديد</a>
    </div>
</div>
<div class="container">
<?php endif; ?>
