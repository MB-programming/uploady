<?php
/** @var string $pageTitle */
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
<div class="nav">
    <a href="<?= Auth::check() ? 'dashboard.php' : 'index.php' ?>"><strong>Uploady</strong></a>
    <div>
        <?php if (Auth::check()): ?>
            <a href="dashboard.php">لوحة التحكم</a>
            <a href="upload.php">رفع فيديو جديد</a>
            <a href="accounts.php">حسابات التواصل</a>
            <a href="logout.php">تسجيل خروج</a>
        <?php else: ?>
            <a href="login.php">تسجيل دخول</a>
            <a href="register.php">حساب جديد</a>
        <?php endif; ?>
    </div>
</div>
<div class="container">
