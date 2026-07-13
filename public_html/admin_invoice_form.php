<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$clients = User::allClients();
$plans = Plan::all();
$errors = [];

$userId = '';
$planId = '';
$amount = '';
$periodStart = date('Y-m-d');
$periodEnd = date('Y-m-d', strtotime('+1 month'));
$status = 'unpaid';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $userId = (int) ($_POST['user_id'] ?? 0);
    $planId = $_POST['plan_id'] !== '' ? (int) $_POST['plan_id'] : null;
    $amount = (string) ($_POST['amount'] ?? '');
    $periodStart = (string) ($_POST['period_start'] ?? '');
    $periodEnd = (string) ($_POST['period_end'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['unpaid', 'paid', 'cancelled'], true) ? $_POST['status'] : 'unpaid';
    $notes = trim((string) ($_POST['notes'] ?? ''));

    $client = User::findById($userId);
    if (!$client || $client['is_admin']) $errors[] = 'اختار عميل صحيح';
    if (!is_numeric($amount) || (float) $amount <= 0) $errors[] = 'المبلغ لازم يكون رقم أكبر من صفر';
    if (!strtotime($periodStart) || !strtotime($periodEnd) || strtotime($periodEnd) < strtotime($periodStart)) {
        $errors[] = 'فترة الفاتورة غير صحيحة';
    }

    if (!$errors) {
        Invoice::create($userId, $planId, (float) $amount, $periodStart, $periodEnd, $status, $notes ?: null);
        header('Location: admin_invoices.php?created=1');
        exit;
    }
}

$pageTitle = 'فاتورة جديدة';
require __DIR__ . '/partials_header.php';
?>
<p><a href="admin_invoices.php">&larr; رجوع للفواتير</a></p>
<h1>فاتورة جديدة</h1>

<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<form method="post" class="card" style="max-width:520px;">
    <?= Csrf::field() ?>

    <label>العميل</label>
    <select name="user_id" required>
        <option value="">اختار عميل</option>
        <?php foreach ($clients as $client): ?>
            <option value="<?= (int) $client['id'] ?>" <?= (string) $userId === (string) $client['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['email']) ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <label>الخطة</label>
    <select name="plan_id" id="planSelect">
        <option value="">بدون خطة محددة</option>
        <?php foreach ($plans as $plan): ?>
            <option value="<?= (int) $plan['id'] ?>" data-price="<?= (float) $plan['price_egp'] ?>" <?= (string) $planId === (string) $plan['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($plan['name']) ?> — <?= number_format((float) $plan['price_egp'], 0) ?> ج.م
            </option>
        <?php endforeach; ?>
    </select>

    <label>المبلغ (ج.م)</label>
    <input type="text" name="amount" id="amountInput" value="<?= htmlspecialchars((string) $amount) ?>" required>

    <label>بداية الفترة</label>
    <input type="date" name="period_start" value="<?= htmlspecialchars($periodStart) ?>" required>

    <label>نهاية الفترة</label>
    <input type="date" name="period_end" value="<?= htmlspecialchars($periodEnd) ?>" required>

    <label>الحالة</label>
    <select name="status">
        <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : '' ?>>غير مدفوعة</option>
        <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>مدفوعة</option>
        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>ملغاة</option>
    </select>

    <label>ملاحظات (اختياري)</label>
    <textarea name="notes"><?= htmlspecialchars($notes) ?></textarea>

    <p><button type="submit" class="btn" style="margin-top:20px;">إصدار الفاتورة</button></p>
</form>

<script src="assets/js/admin_invoice.js"></script>
<?php require __DIR__ . '/partials_footer.php'; ?>
