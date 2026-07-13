<?php

class Plan
{
    public static function all(): array
    {
        $stmt = Database::get()->query('SELECT * FROM plans ORDER BY sort_order ASC');
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
}
