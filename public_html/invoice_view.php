<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireLogin();

$invoiceId = (int) ($_GET['id'] ?? 0);
$invoice = Invoice::find($invoiceId);
$currentUser = Auth::user();

if (!$invoice || (!$currentUser['is_admin'] && (int) $invoice['user_id'] !== Auth::id())) {
    http_response_code(404);
    exit('Invoice not found.');
}

$invoiceStatusLabels = ['unpaid' => 'غير مدفوعة', 'paid' => 'مدفوعة', 'cancelled' => 'ملغاة'];
$invoiceStatusBadge = ['unpaid' => 'pending', 'paid' => 'published', 'cancelled' => 'failed'];

$pageTitle = 'فاتورة #' . $invoice['id'];
require __DIR__ . '/partials_header.php';
?>
<p><a href="<?= $currentUser['is_admin'] ? 'admin_invoices.php' : 'invoices.php' ?>">&larr; رجوع للفواتير</a></p>

<div class="card" style="max-width:560px;">
    <div style="display:flex;justify-content:space-between;align-items:start;">
        <h1 style="font-size:20px;">فاتورة #<?= (int) $invoice['id'] ?></h1>
        <span class="badge <?= $invoiceStatusBadge[$invoice['status']] ?>"><?= $invoiceStatusLabels[$invoice['status']] ?></span>
    </div>

    <p class="muted">العميل: <?= htmlspecialchars($invoice['user_name']) ?> (<?= htmlspecialchars($invoice['user_email']) ?>)</p>

    <table>
        <tr><th>الخطة</th><td><?= htmlspecialchars($invoice['plan_name'] ?? '—') ?></td></tr>
        <tr><th>الفترة</th><td><?= htmlspecialchars($invoice['period_start']) ?> — <?= htmlspecialchars($invoice['period_end']) ?></td></tr>
        <tr><th>المبلغ</th><td><?= number_format((float) $invoice['amount_egp'], 2) ?> ج.م</td></tr>
        <tr><th>تاريخ الإصدار</th><td><?= htmlspecialchars($invoice['created_at']) ?></td></tr>
        <?php if ($invoice['paid_at']): ?>
            <tr><th>تاريخ الدفع</th><td><?= htmlspecialchars($invoice['paid_at']) ?></td></tr>
        <?php endif; ?>
        <?php if ($invoice['notes']): ?>
            <tr><th>ملاحظات</th><td><?= htmlspecialchars($invoice['notes']) ?></td></tr>
        <?php endif; ?>
    </table>

    <p style="margin-top:16px;"><button class="btn secondary js-print">طباعة</button></p>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
