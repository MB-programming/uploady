<?php
require __DIR__ . '/app/bootstrap.php';

$plans = Plan::allActive();

$pageTitle = t('nav.pricing');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('pricing.h1') ?></h1>
<p class="muted"><?= t('pricing.lead') ?></p>

<div class="pricing-grid">
    <?php foreach ($plans as $plan): ?>
        <div class="card price-card <?= $plan['is_featured'] ? 'featured' : '' ?>">
            <?php if (!empty($plan['badge_text'])): ?>
                <div class="price-ribbon"><?= htmlspecialchars($plan['badge_text']) ?></div>
            <?php endif; ?>
            <h2><?= htmlspecialchars($plan['name']) ?></h2>
            <div class="price-value"><?= number_format((float) $plan['price_egp'], 0) ?> <?= t('invoice.currency_egp') ?></div>
            <div class="price-period"><?= t('pricing.per_month') ?></div>
            <ul class="price-features">
                <?php foreach (Plan::featuresList($plan['features']) as $feature): ?>
                    <li><?= htmlspecialchars($feature) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= Auth::check() ? 'dashboard.php' : 'register.php' ?>" class="btn <?= $plan['is_featured'] ? '' : 'secondary' ?>">
                <?= t('pricing.cta') ?>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
