<?php
require __DIR__ . '/../app/bootstrap.php';
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_invoices.php');
    exit;
}
Csrf::verify();

$action = $_POST['action'] ?? '';
$invoiceId = (int) ($_POST['invoice_id'] ?? 0);
$invoice = Invoice::find($invoiceId);

if (!$invoice) {
    header('Location: admin_invoices.php');
    exit;
}

switch ($action) {
    case 'mark_paid':
        Invoice::updateStatus($invoiceId, 'paid');
        header('Location: admin_invoices.php?updated=1');
        break;
    case 'mark_unpaid':
        Invoice::updateStatus($invoiceId, 'unpaid');
        header('Location: admin_invoices.php?updated=1');
        break;
    case 'cancel':
        Invoice::updateStatus($invoiceId, 'cancelled');
        header('Location: admin_invoices.php?updated=1');
        break;
    case 'delete':
        Invoice::delete($invoiceId);
        header('Location: admin_invoices.php?deleted=1');
        break;
    default:
        header('Location: admin_invoices.php');
}
exit;
