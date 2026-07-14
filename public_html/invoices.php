<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireLogin();

$invoices = Invoice::forUser(Auth::id());
$invoiceStatusLabels = ['unpaid' => t('invoice.status_unpaid'), 'paid' => t('invoice.status_paid'), 'cancelled' => t('invoice.status_cancelled')];
$invoiceStatusBadge = ['unpaid' => 'pending', 'paid' => 'published', 'cancelled' => 'failed'];

$pageTitle = t('invoice.title_page');
require __DIR__ . '/partials_header.php';
?>
<p><a href="reports.php"><?= t('invoice.back_to_reports') ?></a></p>
<h1><?= t('invoice.title_page') ?></h1>

<div class="card">
    <?php if ($invoices === []): ?>
        <p class="muted"><?= t('invoice.none_yet') ?></p>
    <?php else: ?>
        <table>
            <thead><tr><th><?= t('invoice.period') ?></th><th><?= t('invoice.plan') ?></th><th><?= t('invoice.amount') ?></th><th><?= t('common.status') ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><?= htmlspecialchars($invoice['period_start']) ?> — <?= htmlspecialchars($invoice['period_end']) ?></td>
                    <td><?= htmlspecialchars($invoice['plan_name'] ?? '—') ?></td>
                    <td><?= number_format((float) $invoice['amount_egp'], 0) ?> <?= t('invoice.currency_egp') ?></td>
                    <td><span class="badge <?= $invoiceStatusBadge[$invoice['status']] ?>"><?= Icons::forBadge($invoice['status']) ?> <?= $invoiceStatusLabels[$invoice['status']] ?></span></td>
                    <td><a href="invoice_view.php?id=<?= (int) $invoice['id'] ?>"><?= t('invoice.view') ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
