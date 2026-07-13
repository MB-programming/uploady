<?php
require __DIR__ . '/app/bootstrap.php';

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Uploady — ارفع فيديو واحد، انشره على كل حاجة</title>
<meta name="description" content="ارفع الفيديو مرة واحدة، حدد المنصات، وانشر فورًا أو جدول — Uploady بينشر لك على يوتيوب وتيك توك وانستجرام ويمسح الملف من السيرفر تلقائي بعد كده.">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="nav">
    <a href="index.php"><strong>Uploady</strong></a>
    <div>
        <a href="pricing.php">الأسعار</a>
        <a href="login.php">تسجيل دخول</a>
        <a href="register.php">حساب جديد</a>
    </div>
</div>

<section class="landing-hero">
    <div class="hero-canvas-wrap" data-pixel-canvas></div>
    <div class="hero-content">
        <h1 class="hero-title">فيديو واحد.<br>كل المنصات.</h1>
        <p class="hero-desc">
            ارفع الفيديو مرة واحدة، حط العنوان والوصف والتاجات والصورة المصغرة، اختار المنصات
            (يوتيوب، تيك توك، انستجرام)، وانشر فورًا أو جدول لميعاد لاحق — Uploady بيتكفل بالباقي،
            وبيمسح الفيديو من السيرفر تلقائي بعد النشر عشان يوفرلك المساحة.
        </p>
        <div class="hero-ctas">
            <a href="register.php" class="btn">ابدأ مجانًا الآن</a>
            <a href="pricing.php" class="btn secondary">شوف الأسعار</a>
        </div>
    </div>
</section>

<div class="marquee-wrap">
    <div class="marquee-track" id="marqueeTrack">
        <!-- duplicated once in PHP below for a seamless loop -->
    </div>
</div>

<section class="section">
    <h2>الفكرة ببساطة</h2>
    <p class="section-lead">مصمم لصاحب المحتوى أو الوكالة اللي بتدير فيديوهات لعملاء متعددين — كل عميل بحسابه وقنواته الخاصة.</p>
    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon">🎬</div>
            <h3>ارفع مرة واحدة</h3>
            <p>فيديو، عنوان، وصف، هاشتاجات، وصورة مصغرة — كل بيانات النشر في مكان واحد.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔗</div>
            <h3>اربط قنواتك</h3>
            <p>يوتيوب (فيديو عادي أو Shorts)، تيك توك، وانستجرام Reels — بربط آمن عن طريق OAuth.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⏱️</div>
            <h3>انشر أو جدول</h3>
            <p>دوس نشر وينشر فورًا، أو حدد ميعاد لاحق — وتقدر تعدّل الميعاد أو تلغي لحد ما يستحق.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🗑️</div>
            <h3>حذف تلقائي للمساحة</h3>
            <p>بمجرد ما الفيديو ينشر على كل المنصات، بيتمسح من السيرفر أوتوماتيك — مساحتك محفوظة.</p>
        </div>
    </div>
</section>

<section class="section">
    <h2>إزاي بيشتغل</h2>
    <div class="steps">
        <div class="step">
            <div class="step-num"></div>
            <h3>سجّل واربط حساباتك</h3>
            <p>حساب مجاني، واربط قنوات التواصل الاجتماعي اللي عايز تنشر عليها.</p>
        </div>
        <div class="step">
            <div class="step-num"></div>
            <h3>ارفع الفيديو وبياناته</h3>
            <p>العنوان، الوصف، التاجات، الصورة المصغرة، واختار المنصات المطلوبة.</p>
        </div>
        <div class="step">
            <div class="step-num"></div>
            <h3>انشر فورًا أو جدول</h3>
            <p>Uploady بينشر على كل منصة وبيتابع الحالة لحد ما يخلص.</p>
        </div>
        <div class="step">
            <div class="step-num"></div>
            <h3>يتمسح تلقائي</h3>
            <p>الفيديو بيتحذف من السيرفر أوتوماتيك بعد النشر — من غير ما تعمل حاجة.</p>
        </div>
    </div>
</section>

<div class="landing-footer-note">
    <a href="register.php" class="btn">جرّب Uploady مجانًا</a>
</div>

<div class="nav" style="justify-content:center;gap:16px;border-top:1px solid var(--border);border-bottom:none;">
    <a href="privacy.php" class="muted">سياسة الخصوصية</a>
    <a href="terms.php" class="muted">الشروط والأحكام</a>
    <a href="data-deletion.php" class="muted">حذف البيانات</a>
</div>

<script src="assets/js/vendor/gsap.min.js"></script>
<script src="assets/js/vendor/ScrollTrigger.min.js"></script>
<script src="assets/js/pixel-hero.js"></script>
<script src="assets/js/marquee.js"></script>
<script src="assets/js/landing.js"></script>
</body>
</html>
