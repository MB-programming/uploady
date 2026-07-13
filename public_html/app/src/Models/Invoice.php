<?php

class Invoice
{
    public static function forUser(int $userId): array
    {
        $stmt = Database::get()->prepare(
            'SELECT i.*, p.name AS plan_name FROM invoices i
             LEFT JOIN plans p ON p.id = i.plan_id
             WHERE i.user_id = ? ORDER BY i.period_start DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            'SELECT i.*, p.name AS plan_name, u.name AS user_name, u.email AS user_email
             FROM invoices i
             LEFT JOIN plans p ON p.id = i.plan_id
             JOIN users u ON u.id = i.user_id
             WHERE i.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $invoice = self::find($id);
        return ($invoice && (int) $invoice['user_id'] === $userId) ? $invoice : null;
    }

    /** All invoices across all clients, for the admin billing screen. */
    public static function all(): array
    {
        $stmt = Database::get()->query(
            'SELECT i.*, p.name AS plan_name, u.name AS user_name, u.email AS user_email
             FROM invoices i
             LEFT JOIN plans p ON p.id = i.plan_id
             JOIN users u ON u.id = i.user_id
             ORDER BY i.created_at DESC'
        );
        return $stmt->fetchAll();
    }

    public static function create(
        int $userId,
        ?int $planId,
        float $amount,
        string $periodStart,
        string $periodEnd,
        string $status,
        ?string $notes
    ): int {
        $stmt = Database::get()->prepare(
            'INSERT INTO invoices (user_id, plan_id, amount_egp, period_start, period_end, status, notes, paid_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId, $planId, $amount, $periodStart, $periodEnd, $status, $notes,
            $status === 'paid' ? date('Y-m-d H:i:s') : null,
        ]);
        return (int) Database::get()->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): void
    {
        $stmt = Database::get()->prepare(
            'UPDATE invoices SET status = ?, paid_at = ? WHERE id = ?'
        );
        $stmt->execute([$status, $status === 'paid' ? date('Y-m-d H:i:s') : null, $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::get()->prepare('DELETE FROM invoices WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function totalPaidAmount(): float
    {
        $stmt = Database::get()->query("SELECT COALESCE(SUM(amount_egp), 0) AS total FROM invoices WHERE status = 'paid'");
        return (float) $stmt->fetch()['total'];
    }

    public static function totalUnpaidAmount(): float
    {
        $stmt = Database::get()->query("SELECT COALESCE(SUM(amount_egp), 0) AS total FROM invoices WHERE status = 'unpaid'");
        return (float) $stmt->fetch()['total'];
    }
}
