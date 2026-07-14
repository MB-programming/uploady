<?php
require __DIR__ . '/app/bootstrap.php';

// Placeholder plans/prices — edit freely. Prices are intentionally blurred while the
// "free for a limited time" promotion banner is up (see .price-value / .price-ribbon in style.css).
$plans = [
    [
        'name' => t('pricing.plan_basic'),
        'price' => '299',
        'features' => [t('pricing.basic_f1'), t('pricing.basic_f2'), t('pricing.basic_f3'), t('pricing.basic_f4')],
        'featured' => false,
    ],
    [
        'name' => t('pricing.plan_pro'),
        'price' => '750',
        'features' => [t('pricing.pro_f1'), t('pricing.pro_f2'), t('pricing.pro_f3'), t('pricing.pro_f4')],
        'featured' => true,
    ],
    [
        'name' => t('pricing.plan_business'),
        'price' => '1500',
        'features' => [t('pricing.business_f1'), t('pricing.business_f2'), t('pricing.business_f3'), t('pricing.business_f4')],
        'featured' => false,
    ],
];

$pageTitle = t('nav.pricing');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('pricing.h1') ?></h1>
<p class="muted"><?= t('pricing.lead') ?></p>

<div class="pricing-grid">
    <?php foreach ($plans as $plan): ?>
        <div class="card price-card <?= $plan['featured'] ? 'featured' : '' ?>">
            <div class="price-ribbon"><?= t('pricing.free_ribbon') ?></div>
            <h2><?= htmlspecialchars($plan['name']) ?></h2>
            <div class="price-value"><?= htmlspecialchars($plan['price']) ?> <?= t('invoice.currency_egp') ?></div>
            <div class="price-period"><?= t('pricing.per_month') ?></div>
            <ul class="price-features">
                <?php foreach ($plan['features'] as $feature): ?>
                    <li><?= htmlspecialchars($feature) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= Auth::check() ? 'dashboard.php' : 'register.php' ?>" class="btn <?= $plan['featured'] ? '' : 'secondary' ?>">
                <?= t('pricing.cta') ?>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
