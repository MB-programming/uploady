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

$invoiceStatusLabels = ['unpaid' => t('invoice.status_unpaid'), 'paid' => t('invoice.status_paid'), 'cancelled' => t('invoice.status_cancelled')];
$invoiceStatusBadge = ['unpaid' => 'pending', 'paid' => 'published', 'cancelled' => 'failed'];

$pageTitle = t('reports.title');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('reports.title') ?></h1>

<div class="platform-list">
    <div class="platform-card">
        <div class="muted"><?= t('dashboard.stat_total') ?></div>
        <div style="font-size:28px;font-weight:700;"><?= $totalVideos ?></div>
    </div>
    <div class="platform-card">
        <div class="muted"><?= t('post_status.published') ?></div>
        <div style="font-size:28px;font-weight:700;color:var(--ok);"><?= $statusCounts['published'] ?></div>
    </div>
    <div class="platform-card">
        <div class="muted"><?= t('reports.in_progress') ?></div>
        <div style="font-size:28px;font-weight:700;color:var(--accent);"><?= $statusCounts['scheduled'] + $statusCounts['processing'] ?></div>
    </div>
    <div class="platform-card">
        <div class="muted"><?= t('reports.failed_partial') ?></div>
        <div style="font-size:28px;font-weight:700;color:var(--err);"><?= $statusCounts['failed'] + $statusCounts['partially_published'] ?></div>
    </div>
</div>

<h2 style="font-size:16px;"><?= t('reports.current_plan') ?></h2>
<div class="card">
    <?php if ($plan): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-size:18px;font-weight:700;"><?= htmlspecialchars($plan['name']) ?></div>
                <div class="muted"><?= sprintf(t('reports.month_price'), number_format((float) $plan['price_egp'], 0)) ?></div>
            </div>
            <a href="pricing.php" class="btn secondary"><?= t('reports.change_plan') ?></a>
        </div>
    <?php else: ?>
        <p class="muted"><?= t('reports.no_active_plan') ?>
        <a href="pricing.php"><?= t('reports.view_plans') ?></a>.</p>
    <?php endif; ?>
    <div class="muted" style="margin-top:14px;"><?= sprintf(t('storage.used_sentence'), number_format($usedGb, 2), (int) $quotaGb) ?></div>
    <div style="background:#0d0f14;border-radius:6px;height:8px;margin-top:8px;overflow:hidden;">
        <div style="background:<?= $pct > 90 ? 'var(--err)' : 'var(--accent)' ?>;height:100%;width:<?= round($pct, 1) ?>%;"></div>
    </div>
</div>

<h2 style="font-size:16px;"><?= t('reports.recent_invoices') ?></h2>
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
        <p style="margin-top:12px;"><a href="invoices.php"><?= t('invoice.all_invoices') ?></a></p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
