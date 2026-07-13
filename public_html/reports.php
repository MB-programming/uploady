<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
$statusCounts = Post::statusCountsForUser(Auth::id());
$totalVideos = array_sum($statusCounts);

$plan = $user['plan_id'] ? Plan::find((int) $user['plan_id']) : null;
$quotaGb = Plan::quotaGbForUser($user);
$usedGb = Post::storageUsedBytes(Auth::id()) / 1024 ** 3;
$pct = min(100, $quotaGb > 0 ? ($usedGb / $quotaGb) * 100 : 0);

$invoices = array_slice(Invoice::forUser(Auth::id()), 0, 5);

$invoiceStatusLabels = ['unpaid' => 'غير مدفوعة', 'paid' => 'مدفوعة', 'cancelled' => 'ملغاة'];
$invoiceStatusBadge = ['unpaid' => 'pending', 'paid' => 'published', 'cancelled' => 'failed'];

$pageTitle = 'التقارير';
require __DIR__ . '/partials_header.php';
?>
<h1>التقارير</h1>

<div class="platform-list">
    <div class="platform-card">
        <div class="muted">إجمالي الفيديوهات</div>
        <div style="font-size:28px;font-weight:700;"><?= $totalVideos ?></div>
    </div>
    <div class="platform-card">
        <div class="muted">تم النشر</div>
        <div style="font-size:28px;font-weight:700;color:var(--ok);"><?= $statusCounts['published'] ?></div>
    </div>
    <div class="platform-card">
        <div class="muted">قيد التنفيذ</div>
        <div style="font-size:28px;font-weight:700;color:var(--accent);"><?= $statusCounts['scheduled'] + $statusCounts['processing'] ?></div>
    </div>
    <div class="platform-card">
        <div class="muted">فشل / جزئي</div>
        <div style="font-size:28px;font-weight:700;color:var(--err);"><?= $statusCounts['failed'] + $statusCounts['partially_published'] ?></div>
    </div>
</div>

<h2 style="font-size:16px;">الخطة الحالية</h2>
<div class="card">
    <?php if ($plan): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-size:18px;font-weight:700;"><?= htmlspecialchars($plan['name']) ?></div>
                <div class="muted"><?= number_format((float) $plan['price_egp'], 0) ?> ج.م / شهريًا</div>
            </div>
            <a href="pricing.php" class="btn secondary">تغيير الخطة</a>
        </div>
    <?php else: ?>
        <p class="muted">مفيش خطة مفعّلة على حسابك دلوقتي — بتستخدم الحد الافتراضي للمساحة.
        <a href="pricing.php">شوف الخطط المتاحة</a>.</p>
    <?php endif; ?>
    <div class="muted" style="margin-top:14px;">مساحة التخزين: <?= number_format($usedGb, 2) ?> GB من <?= (int) $quotaGb ?> GB</div>
    <div style="background:#0d0f14;border-radius:6px;height:8px;margin-top:8px;overflow:hidden;">
        <div style="background:<?= $pct > 90 ? 'var(--err)' : 'var(--accent)' ?>;height:100%;width:<?= round($pct, 1) ?>%;"></div>
    </div>
</div>

<h2 style="font-size:16px;">آخر الفواتير</h2>
<div class="card">
    <?php if ($invoices === []): ?>
        <p class="muted">مفيش فواتير لسه.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>الفترة</th><th>الخطة</th><th>المبلغ</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?= htmlspecialchars($invoice['period_start']) ?> — <?= htmlspecialchars($invoice['period_end']) ?></td>
                    <td><?= htmlspecialchars($invoice['plan_name'] ?? '—') ?></td>
                    <td><?= number_format((float) $invoice['amount_egp'], 0) ?> ج.م</td>
                    <td><span class="badge <?= $invoiceStatusBadge[$invoice['status']] ?>"><?= $invoiceStatusLabels[$invoice['status']] ?></span></td>
                    <td><a href="invoice_view.php?id=<?= (int) $invoice['id'] ?>">عرض</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p style="margin-top:12px;"><a href="invoices.php">كل الفواتير &larr;</a></p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
