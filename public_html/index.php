<?php
require __DIR__ . '/app/bootstrap.php';

if (Auth::check()) {
    header('Location: dashboard.php');
    exit;
}

$otherLang = Lang::locale() === 'en' ? 'ar' : 'en';
$marqueeItems = [
    t('platform.youtube'),
    t('platform.youtube_shorts'),
    t('platform.tiktok'),
    t('marquee.instagram_reels'),
    t('marquee.auto_schedule'),
    t('marquee.instant_publish'),
    t('marquee.auto_delete'),
];
?>
<!DOCTYPE html>
<html lang="<?= Lang::locale() ?>" dir="<?= Lang::dir() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars(t('landing.meta_title')) ?></title>
<meta name="description" content="<?= htmlspecialchars(t('landing.meta_description')) ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="nav">
    <a href="index.php"><strong>Uploady</strong></a>
    <div>
        <a href="pricing.php"><?= t('nav.pricing') ?></a>
        <a href="login.php"><?= t('nav.login') ?></a>
        <a href="register.php"><?= t('nav.register') ?></a>
        <a href="lang.php?set=<?= $otherLang ?>"><?= Icons::globe() ?> <?= t('common.lang_toggle') ?></a>
    </div>
</div>

<section class="landing-hero">
    <div class="hero-grid-bg"></div>
    <div class="hero-aurora"></div>
    <div class="hero-canvas-wrap" data-pixel-canvas></div>
    <div class="hero-content">
        <span class="hero-eyebrow"><span class="dot"></span> <?= t('landing.hero_eyebrow') ?></span>
        <h1 class="hero-title">
            <span class="line-solid"><?= t('landing.hero_title_1') ?></span>
            <span class="line-glow"><?= t('landing.hero_title_2') ?></span>
        </h1>
        <p class="hero-desc">
            <?= t('landing.hero_desc') ?>
        </p>
        <div class="hero-ctas">
            <a href="register.php" class="btn"><?= Icons::bolt() ?> <?= t('pricing.cta') ?></a>
            <a href="pricing.php" class="btn secondary"><?= t('landing.hero_cta_secondary') ?></a>
        </div>
        <div class="hero-scroll-cue">
            <span><?= t('landing.scroll_cue') ?></span>
            <span class="stem"></span>
        </div>
    </div>
</section>

<div class="marquee-wrap">
    <div class="marquee-track" id="marqueeTrack" data-items="<?= htmlspecialchars(json_encode($marqueeItems), ENT_QUOTES) ?>">
        <!-- duplicated once in JS below for a seamless loop -->
    </div>
</div>

<section class="section">
    <span class="kicker"><?= t('landing.kicker_idea') ?></span>
    <h2><?= t('landing.idea_h2') ?></h2>
    <p class="section-lead"><?= t('landing.idea_lead') ?></p>
    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon"><?= Icons::upload() ?></div>
            <h3><?= t('landing.feature1_title') ?></h3>
            <p><?= t('landing.feature1_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><?= Icons::link() ?></div>
            <h3><?= t('landing.feature2_title') ?></h3>
            <p><?= t('landing.feature2_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><?= Icons::clock() ?></div>
            <h3><?= t('landing.feature3_title') ?></h3>
            <p><?= t('landing.feature3_desc') ?></p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><?= Icons::trash() ?></div>
            <h3><?= t('landing.feature4_title') ?></h3>
            <p><?= t('landing.feature4_desc') ?></p>
        </div>
    </div>
</section>

<section class="section">
    <span class="kicker"><?= t('landing.kicker_steps') ?></span>
    <h2><?= t('landing.steps_h2') ?></h2>
    <div class="steps">
        <div class="step">
            <div class="step-num"></div>
            <h3><?= t('landing.step1_title') ?></h3>
            <p><?= t('landing.step1_desc') ?></p>
        </div>
        <div class="step">
            <div class="step-num"></div>
            <h3><?= t('landing.step2_title') ?></h3>
            <p><?= t('landing.step2_desc') ?></p>
        </div>
        <div class="step">
            <div class="step-num"></div>
            <h3><?= t('landing.step3_title') ?></h3>
            <p><?= t('landing.step3_desc') ?></p>
        </div>
        <div class="step">
            <div class="step-num"></div>
            <h3><?= t('landing.step4_title') ?></h3>
            <p><?= t('landing.step4_desc') ?></p>
        </div>
    </div>
</section>

<div class="landing-cta">
    <h2><?= t('landing.cta_h2') ?></h2>
    <p><?= t('landing.cta_desc') ?></p>
    <a href="register.php" class="btn"><?= Icons::bolt() ?> <?= t('landing.cta_button') ?></a>
</div>

<div class="nav nav--footer">
    <a href="privacy.php" class="muted"><?= t('footer.privacy') ?></a>
    <a href="terms.php" class="muted"><?= t('footer.terms') ?></a>
    <a href="data-deletion.php" class="muted"><?= t('footer.data_deletion') ?></a>
</div>

<script src="assets/js/site.js"></script>
<script src="assets/js/vendor/gsap.min.js"></script>
<script src="assets/js/vendor/ScrollTrigger.min.js"></script>
<script src="assets/js/pixel-hero.js"></script>
<script src="assets/js/marquee.js"></script>
<script src="assets/js/landing.js"></script>
</body>
</html>
