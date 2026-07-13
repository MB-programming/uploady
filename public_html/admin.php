<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAdmin();

$clients = User::allClients();

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
    $plan = $client['plan_id'] ? Plan::find((int) $client['plan_id']) : null;
    $rows[] = [
        'client' => $client,
        'plan_name' => $plan['name'] ?? null,
        'accounts_total' => count($accounts),
        'platform_counts' => $platformCounts,
        'videos_total' => Post::countForUser((int) $client['id']),
        'used_gb' => Post::storageUsedBytes((int) $client['id']) / 1024 ** 3,
        'quota_gb' => Plan::quotaGbForUser($client),
    ];
}

$pageTitle = 'لوحة تحكم الأدمن';
require __DIR__ . '/partials_header.php';
?>
<h1>لوحة تحكم الأدمن</h1>

<div class="platform-list">
    <div class="platform-card">
        <div class="muted">إجمالي العملاء</div>
        <div style="font-size:22px;font-weight:700;"><?= count($rows) ?></div>
    </div>
    <div class="platform-card">
        <div class="muted">إجمالي الفيديوهات المرفوعة</div>
        <div style="font-size:22px;font-weight:700;"><?= Post::countAll() ?></div>
    </div>
    <div class="platform-card">
        <div class="muted">إجمالي المدفوع</div>
        <div style="font-size:22px;font-weight:700;color:var(--ok);"><?= number_format(Invoice::totalPaidAmount(), 0) ?> ج.م</div>
    </div>
    <div class="platform-card">
        <div class="muted">إجمالي غير المدفوع</div>
        <div style="font-size:22px;font-weight:700;color:var(--warn);"><?= number_format(Invoice::totalUnpaidAmount(), 0) ?> ج.م</div>
    </div>
</div>

<h2 style="font-size:16px;">العملاء</h2>

<div class="card">
<div style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>العميل</th>
            <th>الخطة</th>
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
                <td><?= $row['plan_name'] ? htmlspecialchars($row['plan_name']) : '<span class="muted">بدون خطة</span>' ?></td>
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
                <td><?= number_format($row['used_gb'], 2) ?> / <?= (int) $row['quota_gb'] ?> GB</td>
                <td>
                    <a href="admin_client.php?id=<?= (int) $row['client']['id'] ?>">التفاصيل</a>
                    ·
                    <a href="admin_user_form.php?id=<?= (int) $row['client']['id'] ?>">تعديل</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
