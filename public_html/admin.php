<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAdmin();

$clients = User::allClients();
$quotaGb = App::config('storage_quota_gb');

$platformNames = [
    'youtube' => 'يوتيوب',
    'tiktok' => 'تيك توك',
    'instagram' => 'انستجرام',
];

$rows = [];
foreach ($clients as $client) {
    $accounts = SocialAccount::forUser((int) $client['id']);
    $platformCounts = ['youtube' => 0, 'tiktok' => 0, 'instagram' => 0];
    foreach ($accounts as $account) {
        $platformCounts[$account['platform']]++;
    }
    $rows[] = [
        'client' => $client,
        'accounts_total' => count($accounts),
        'platform_counts' => $platformCounts,
        'videos_total' => Post::countForUser((int) $client['id']),
        'used_gb' => Post::storageUsedBytes((int) $client['id']) / 1024 ** 3,
    ];
}

$pageTitle = 'لوحة تحكم الأدمن';
require __DIR__ . '/partials_header.php';
?>
<h1>العملاء</h1>
<p class="muted">إجمالي العملاء: <?= count($rows) ?></p>

<div class="card">
<div style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>العميل</th>
            <th>تاريخ التسجيل</th>
            <th>الحسابات المتصلة</th>
            <th>عدد الفيديوهات</th>
            <th>المساحة المستخدمة</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php if ($rows === []): ?>
            <tr><td colspan="6" class="muted">لسه مفيش عملاء مسجلين.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td>
                    <div><?= htmlspecialchars($row['client']['name']) ?></div>
                    <div class="muted"><?= htmlspecialchars($row['client']['email']) ?></div>
                </td>
                <td><?= htmlspecialchars($row['client']['created_at']) ?></td>
                <td>
                    <?php if ($row['accounts_total'] === 0): ?>
                        <span class="muted">مفيش حسابات متصلة</span>
                    <?php else: ?>
                        <?php foreach ($platformNames as $key => $label): ?>
                            <?php if ($row['platform_counts'][$key] > 0): ?>
                                <span class="badge published"><?= htmlspecialchars($label) ?> × <?= $row['platform_counts'][$key] ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
                <td><?= $row['videos_total'] ?></td>
                <td><?= number_format($row['used_gb'], 2) ?> / <?= (int) $quotaGb ?> GB</td>
                <td><a href="admin_client.php?id=<?= (int) $row['client']['id'] ?>">التفاصيل</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
