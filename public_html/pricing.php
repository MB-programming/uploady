<?php
require __DIR__ . '/../app/bootstrap.php';

// Placeholder plans/prices — edit freely. Prices are intentionally blurred while the
// "free for a limited time" promotion banner is up (see .price-value / .price-ribbon in style.css).
$plans = [
    [
        'name' => 'الأساسية',
        'price' => '299',
        'features' => ['10 GB مساحة تخزين', 'حساب واحد لكل منصة (يوتيوب / تيك توك / انستجرام)', 'نشر فوري أو مجدول', 'حذف الفيديو تلقائي بعد النشر'],
        'featured' => false,
    ],
    [
        'name' => 'الاحترافية',
        'price' => '750',
        'features' => ['50 GB مساحة تخزين', 'حسابات متعددة على كل منصة', 'صورة مصغرة مخصصة (يوتيوب)', 'دعم فني بأولوية'],
        'featured' => true,
    ],
    [
        'name' => 'الأعمال',
        'price' => '1500',
        'features' => ['200 GB مساحة تخزين', 'عدد غير محدود من الحسابات', 'لوحة تقارير موسعة', 'مدير حساب مخصص'],
        'featured' => false,
    ],
];

$pageTitle = 'الأسعار';
require __DIR__ . '/partials_header.php';
?>
<h1>خطط الأسعار</h1>
<p class="muted">اختار الخطة اللي تناسب حجم شغلك — وكل الخطط بتشمل رفع الفيديو ونشره تلقائي على كل منصاتك المربوطة.</p>

<div class="pricing-grid">
    <?php foreach ($plans as $plan): ?>
        <div class="card price-card <?= $plan['featured'] ? 'featured' : '' ?>">
            <div class="price-ribbon">مجانًا لمدة محدودة</div>
            <h2><?= htmlspecialchars($plan['name']) ?></h2>
            <div class="price-value"><?= htmlspecialchars($plan['price']) ?> ج.م</div>
            <div class="price-period">شهريًا</div>
            <ul class="price-features">
                <?php foreach ($plan['features'] as $feature): ?>
                    <li><?= htmlspecialchars($feature) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= Auth::check() ? 'dashboard.php' : 'register.php' ?>" class="btn <?= $plan['featured'] ? '' : 'secondary' ?>">
                ابدأ مجانًا الآن
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
