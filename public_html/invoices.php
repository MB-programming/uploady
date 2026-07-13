<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$invoices = Invoice::forUser(Auth::id());
$invoiceStatusLabels = ['unpaid' => 'غير مدفوعة', 'paid' => 'مدفوعة', 'cancelled' => 'ملغاة'];
$invoiceStatusBadge = ['unpaid' => 'pending', 'paid' => 'published', 'cancelled' => 'failed'];

$pageTitle = 'الفواتير';
require __DIR__ . '/partials_header.php';
?>
<p><a href="reports.php">&larr; رجوع للتقارير</a></p>
<h1>الفواتير</h1>

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
                    <td><span class="badge <?= $invoiceStatusBadge[$invoice['status']] ?>"><?= Icons::forBadge($invoice['status']) ?> <?= $invoiceStatusLabels[$invoice['status']] ?></span></td>
                    <td><a href="invoice_view.php?id=<?= (int) $invoice['id'] ?>">عرض</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
