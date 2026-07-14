<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$invoices = Invoice::all();
$invoiceStatusLabels = ['unpaid' => t('invoice.status_unpaid'), 'paid' => t('invoice.status_paid'), 'cancelled' => t('invoice.status_cancelled')];
$invoiceStatusBadge = ['unpaid' => 'pending', 'paid' => 'published', 'cancelled' => 'failed'];

$pageTitle = t('invoice.title_page');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('invoice.title_page') ?></h1>

<?php if (!empty($_GET['created'])): ?>
    <div class="alert success"><?= t('admin.invoice_issued_success') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['updated'])): ?>
    <div class="alert success"><?= t('admin.invoice_updated_success') ?></div>
<?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?>
    <div class="alert success"><?= t('admin.invoice_deleted_success') ?></div>
<?php endif; ?>

<div class="platform-list">
    <div class="platform-card">
        <div class="muted"><?= t('admin.stat_total_paid') ?></div>
        <div style="font-size:22px;font-weight:700;color:var(--ok);"><?= number_format(Invoice::totalPaidAmount(), 0) ?> <?= t('invoice.currency_egp') ?></div>
    </div>
    <div class="platform-card">
        <div class="muted"><?= t('admin.stat_total_unpaid') ?></div>
        <div style="font-size:22px;font-weight:700;color:var(--warn);"><?= number_format(Invoice::totalUnpaidAmount(), 0) ?> <?= t('invoice.currency_egp') ?></div>
    </div>
</div>

<p><a class="btn" href="admin_invoice_form.php"><?= t('admin.issue_new_invoice') ?></a></p>

<div class="card">
<div style="overflow-x:auto;">
<table>
    <thead>
        <tr><th><?= t('admin.th_client') ?></th><th><?= t('invoice.plan') ?></th><th><?= t('invoice.period') ?></th><th><?= t('invoice.amount') ?></th><th><?= t('common.status') ?></th><th></th></tr>
    </thead>
    <tbody>
        <?php if ($invoices === []): ?>
            <tr><td colspan="6" class="muted"><?= t('invoice.none_yet') ?></td></tr>
        <?php endif; ?>
        <?php foreach ($invoices as $invoice): ?>
            <tr>
                <td>
                    <div><?= htmlspecialchars($invoice['user_name']) ?></div>
                    <div class="muted"><?= htmlspecialchars($invoice['user_email']) ?></div>
                </td>
                <td><?= htmlspecialchars($invoice['plan_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($invoice['period_start']) ?> — <?= htmlspecialchars($invoice['period_end']) ?></td>
                <td><?= number_format((float) $invoice['amount_egp'], 0) ?> <?= t('invoice.currency_egp') ?></td>
                <td><span class="badge <?= $invoiceStatusBadge[$invoice['status']] ?>"><?= Icons::forBadge($invoice['status']) ?> <?= $invoiceStatusLabels[$invoice['status']] ?></span></td>
                <td style="white-space:nowrap;">
                    <a href="invoice_view.php?id=<?= (int) $invoice['id'] ?>"><?= t('invoice.view') ?></a>
                    <?php if ($invoice['status'] !== 'paid'): ?>
                        &nbsp;·&nbsp;
                        <form method="post" action="admin_invoice_action.php" style="display:inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="mark_paid">
                            <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
                            <button type="submit" class="btn secondary" style="padding:2px 8px;font-size:12px;"><?= t('admin.mark_paid') ?></button>
                        </form>
                    <?php endif; ?>
                    &nbsp;·&nbsp;
                    <form method="post" action="admin_invoice_action.php" style="display:inline;" data-confirm="<?= htmlspecialchars(t('admin.delete_invoice_confirm')) ?>">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
                        <button type="submit" class="btn danger" style="padding:2px 8px;font-size:12px;"><?= t('common.delete') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
