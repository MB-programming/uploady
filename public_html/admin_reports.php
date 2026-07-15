<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$today = date('Y-m-d 00:00:00');
$since7 = date('Y-m-d 00:00:00', strtotime('-6 days'));
$since30 = date('Y-m-d 00:00:00', strtotime('-29 days'));

$stats = [
    'views_today' => PageVisit::countSince($today),
    'uniques_today' => PageVisit::uniqueVisitorsSince($today),
    'views_7' => PageVisit::countSince($since7),
    'uniques_7' => PageVisit::uniqueVisitorsSince($since7),
    'views_30' => PageVisit::countSince($since30),
    'uniques_30' => PageVisit::uniqueVisitorsSince($since30),
];

$series = PageVisit::dailySeries(30);
$maxViews = max(1, max(array_column($series, 'views')));
$topPages = PageVisit::topPages(30);

$totalUsers = count(User::allClients());
$totalVideos = Post::countAll();

$pageTitle = t('nav.admin_reports');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('sitereports.title') ?></h1>
<p class="muted"><?= t('sitereports.intro') ?></p>

<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;">
    <div class="card" style="text-align:center;">
        <div style="font-size:26px;font-weight:800;"><?= number_format($stats['views_today']) ?></div>
        <div class="muted" style="font-size:13px;"><?= t('sitereports.views_today') ?></div>
        <div class="muted" style="font-size:12px;"><?= sprintf(t('sitereports.uniques_inline'), number_format($stats['uniques_today'])) ?></div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:26px;font-weight:800;"><?= number_format($stats['views_7']) ?></div>
        <div class="muted" style="font-size:13px;"><?= t('sitereports.views_7') ?></div>
        <div class="muted" style="font-size:12px;"><?= sprintf(t('sitereports.uniques_inline'), number_format($stats['uniques_7'])) ?></div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:26px;font-weight:800;"><?= number_format($stats['views_30']) ?></div>
        <div class="muted" style="font-size:13px;"><?= t('sitereports.views_30') ?></div>
        <div class="muted" style="font-size:12px;"><?= sprintf(t('sitereports.uniques_inline'), number_format($stats['uniques_30'])) ?></div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:26px;font-weight:800;"><?= number_format($totalUsers) ?></div>
        <div class="muted" style="font-size:13px;"><?= t('sitereports.total_users') ?></div>
    </div>
    <div class="card" style="text-align:center;">
        <div style="font-size:26px;font-weight:800;"><?= number_format($totalVideos) ?></div>
        <div class="muted" style="font-size:13px;"><?= t('sitereports.total_videos') ?></div>
    </div>
</div>

<h2 style="font-size:16px;"><?= t('sitereports.chart_title') ?></h2>
<div class="card">
    <div style="display:flex;align-items:flex-end;gap:3px;height:160px;">
        <?php foreach ($series as $date => $day): ?>
            <div title="<?= htmlspecialchars($date) ?>: <?= (int) $day['views'] ?> / <?= (int) $day['uniques'] ?>"
                 style="flex:1;min-width:4px;height:<?= max(2, round($day['views'] / $maxViews * 100)) ?>%;background:linear-gradient(180deg,var(--accent) 0%,rgba(59,130,246,.35) 100%);border-radius:3px 3px 0 0;"></div>
        <?php endforeach; ?>
    </div>
    <div class="muted" style="display:flex;justify-content:space-between;font-size:12px;margin-top:6px;">
        <span><?= htmlspecialchars(array_key_first($series)) ?></span>
        <span><?= htmlspecialchars(array_key_last($series)) ?></span>
    </div>
    <p class="muted" style="font-size:12px;margin:8px 0 0;"><?= t('sitereports.chart_hint') ?></p>
</div>

<h2 style="font-size:16px;"><?= t('sitereports.top_pages') ?></h2>
<div class="card" style="overflow-x:auto;">
    <?php if ($topPages === []): ?>
        <p class="muted" style="margin:0;"><?= t('sitereports.no_data') ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= t('sitereports.col_page') ?></th>
                    <th><?= t('sitereports.col_views') ?></th>
                    <th><?= t('sitereports.col_uniques') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topPages as $page): ?>
                    <tr>
                        <td dir="ltr"><?= htmlspecialchars($page['path']) ?></td>
                        <td><?= number_format((int) $page['views']) ?></td>
                        <td><?= number_format((int) $page['uniques']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
