<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$invoiceId = (int) ($_GET['id'] ?? 0);
$invoice = Invoice::find($invoiceId);
$currentUser = Auth::user();

if (!$invoice || (!$currentUser['is_admin'] && (int) $invoice['user_id'] !== Auth::id())) {
    http_response_code(404);
    exit(t('invoice.not_found'));
}

$invoiceStatusLabels = ['unpaid' => t('invoice.status_unpaid'), 'paid' => t('invoice.status_paid'), 'cancelled' => t('invoice.status_cancelled')];
$invoiceStatusBadge = ['unpaid' => 'pending', 'paid' => 'published', 'cancelled' => 'failed'];

$pageTitle = sprintf(t('invoice.number_title'), $invoice['id']);
require __DIR__ . '/partials_header.php';
?>
<p><a href="<?= $currentUser['is_admin'] ? 'admin_invoices.php' : 'invoices.php' ?>"><?= t('invoice.back_to_invoices') ?></a></p>

<div class="card" style="max-width:560px;">
    <div style="display:flex;justify-content:space-between;align-items:start;">
        <h1 style="font-size:20px;"><?= htmlspecialchars(sprintf(t('invoice.number_title'), (int) $invoice['id'])) ?></h1>
        <span class="badge <?= $invoiceStatusBadge[$invoice['status']] ?>"><?= Icons::forBadge($invoice['status']) ?> <?= $invoiceStatusLabels[$invoice['status']] ?></span>
    </div>

    <p class="muted"><?= sprintf(t('invoice.client_label'), htmlspecialchars($invoice['user_name']), htmlspecialchars($invoice['user_email'])) ?></p>

    <table>
        <tr><th><?= t('invoice.plan') ?></th><td><?= htmlspecialchars($invoice['plan_name'] ?? '—') ?></td></tr>
        <tr><th><?= t('invoice.period') ?></th><td><?= htmlspecialchars($invoice['period_start']) ?> — <?= htmlspecialchars($invoice['period_end']) ?></td></tr>
        <tr><th><?= t('invoice.amount') ?></th><td><?= number_format((float) $invoice['amount_egp'], 2) ?> <?= t('invoice.currency_egp') ?></td></tr>
        <tr><th><?= t('invoice.issue_date') ?></th><td><?= htmlspecialchars($invoice['created_at']) ?></td></tr>
        <?php if ($invoice['paid_at']): ?>
            <tr><th><?= t('invoice.paid_date') ?></th><td><?= htmlspecialchars($invoice['paid_at']) ?></td></tr>
        <?php endif; ?>
        <?php if ($invoice['notes']): ?>
            <tr><th><?= t('invoice.notes') ?></th><td><?= htmlspecialchars($invoice['notes']) ?></td></tr>
        <?php endif; ?>
    </table>

    <p style="margin-top:16px;"><button class="btn secondary js-print"><?= t('invoice.print') ?></button></p>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
