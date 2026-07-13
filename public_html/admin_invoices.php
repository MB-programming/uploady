<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$invoices = Invoice::all();
$invoiceStatusLabels = ['unpaid' => 'غير مدفوعة', 'paid' => 'مدفوعة', 'cancelled' => 'ملغاة'];
$invoiceStatusBadge = ['unpaid' => 'pending', 'paid' => 'published', 'cancelled' => 'failed'];

$pageTitle = 'الفواتير';
require __DIR__ . '/partials_header.php';
?>
<h1>الفواتير</h1>

<?php if (!empty($_GET['created'])): ?>
    <div class="alert success">تم إصدار الفاتورة.</div>
<?php endif; ?>
<?php if (!empty($_GET['updated'])): ?>
    <div class="alert success">تم تحديث حالة الفاتورة.</div>
<?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?>
    <div class="alert success">تم حذف الفاتورة.</div>
<?php endif; ?>

<div class="platform-list">
    <div class="platform-card">
        <div class="muted">إجمالي المدفوع</div>
        <div style="font-size:22px;font-weight:700;color:var(--ok);"><?= number_format(Invoice::totalPaidAmount(), 0) ?> ج.م</div>
    </div>
    <div class="platform-card">
        <div class="muted">إجمالي غير المدفوع</div>
        <div style="font-size:22px;font-weight:700;color:var(--warn);"><?= number_format(Invoice::totalUnpaidAmount(), 0) ?> ج.م</div>
    </div>
</div>

<p><a class="btn" href="admin_invoice_form.php">+ إصدار فاتورة جديدة</a></p>

<div class="card">
<div style="overflow-x:auto;">
<table>
    <thead>
        <tr><th>العميل</th><th>الخطة</th><th>الفترة</th><th>المبلغ</th><th>الحالة</th><th></th></tr>
    </thead>
    <tbody>
        <?php if ($invoices === []): ?>
            <tr><td colspan="6" class="muted">مفيش فواتير لسه.</td></tr>
        <?php endif; ?>
        <?php foreach ($invoices as $invoice): ?>
            <tr>
                <td>
                    <div><?= htmlspecialchars($invoice['user_name']) ?></div>
                    <div class="muted"><?= htmlspecialchars($invoice['user_email']) ?></div>
                </td>
                <td><?= htmlspecialchars($invoice['plan_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($invoice['period_start']) ?> — <?= htmlspecialchars($invoice['period_end']) ?></td>
                <td><?= number_format((float) $invoice['amount_egp'], 0) ?> ج.م</td>
                <td><span class="badge <?= $invoiceStatusBadge[$invoice['status']] ?>"><?= $invoiceStatusLabels[$invoice['status']] ?></span></td>
                <td style="white-space:nowrap;">
                    <a href="invoice_view.php?id=<?= (int) $invoice['id'] ?>">عرض</a>
                    <?php if ($invoice['status'] !== 'paid'): ?>
                        &nbsp;·&nbsp;
                        <form method="post" action="admin_invoice_action.php" style="display:inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="mark_paid">
                            <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
                            <button type="submit" class="btn secondary" style="padding:2px 8px;font-size:12px;">تحديد كمدفوعة</button>
                        </form>
                    <?php endif; ?>
                    &nbsp;·&nbsp;
                    <form method="post" action="admin_invoice_action.php" style="display:inline;" data-confirm="حذف الفاتورة نهائيًا؟">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
                        <button type="submit" class="btn danger" style="padding:2px 8px;font-size:12px;">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
