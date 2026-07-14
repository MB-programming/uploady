<?php

class Plan
{
    public static function all(): array
    {
        $stmt = Database::get()->query('SELECT * FROM plans ORDER BY sort_order ASC');
        return $stmt->fetchAll();
    }

    /** Only plans meant to be shown publicly on pricing.php. */
    public static function allActive(): array
    {
        $stmt = Database::get()->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order ASC');
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM plans WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Storage quota (GB) that applies to a user: their assigned plan, or the app-wide default. */
    public static function quotaGbForUser(array $user): float
    {
        if (!empty($user['plan_id'])) {
            $plan = self::find((int) $user['plan_id']);
            if ($plan) {
                return (float) $plan['storage_quota_gb'];
            }
        }
        return (float) App::config('storage_quota_gb');
    }

    /** Splits the stored newline-separated features text into a clean list for display. */
    public static function featuresList(?string $features): array
    {
        if (!$features) {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode("\n", $features)), fn ($line) => $line !== ''));
    }

    public static function create(array $data): int
    {
        $stmt = Database::get()->prepare(
            'INSERT INTO plans (name, price_egp, storage_quota_gb, max_social_accounts, features, badge_text, is_featured, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['price_egp'],
            $data['storage_quota_gb'],
            $data['max_social_accounts'],
            $data['features'],
            $data['badge_text'],
            $data['is_featured'] ? 1 : 0,
            $data['is_active'] ? 1 : 0,
            $data['sort_order'],
        ]);
        return (int) Database::get()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::get()->prepare(
            'UPDATE plans SET name = ?, price_egp = ?, storage_quota_gb = ?, max_social_accounts = ?,
                features = ?, badge_text = ?, is_featured = ?, is_active = ?, sort_order = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['price_egp'],
            $data['storage_quota_gb'],
            $data['max_social_accounts'],
            $data['features'],
            $data['badge_text'],
            $data['is_featured'] ? 1 : 0,
            $data['is_active'] ? 1 : 0,
            $data['sort_order'],
            $id,
        ]);
    }

    /** Users on a deleted plan fall back to the app-wide default quota (ON DELETE SET NULL on users.plan_id). */
    public static function delete(int $id): void
    {
        $stmt = Database::get()->prepare('DELETE FROM plans WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function countUsers(int $id): int
    {
        $stmt = Database::get()->prepare('SELECT COUNT(*) FROM users WHERE plan_id = ?');
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn();
    }
}
