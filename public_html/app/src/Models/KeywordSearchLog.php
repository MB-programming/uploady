<?php

/** Daily usage counters for the keyword tool: per user for members, per IP for guests. */
class KeywordSearchLog
{
    public static function record(?int $userId, string $ip, string $seed): void
    {
        $stmt = Database::get()->prepare(
            'INSERT INTO keyword_search_logs (user_id, ip_address, seed) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $ip, mb_substr($seed, 0, 190)]);
    }

    public static function countTodayForUser(int $userId): int
    {
        $stmt = Database::get()->prepare(
            'SELECT COUNT(*) FROM keyword_search_logs WHERE user_id = ? AND created_at >= CURDATE()'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function countTodayForIp(string $ip): int
    {
        $stmt = Database::get()->prepare(
            'SELECT COUNT(*) FROM keyword_search_logs WHERE user_id IS NULL AND ip_address = ? AND created_at >= CURDATE()'
        );
        $stmt->execute([$ip]);
        return (int) $stmt->fetchColumn();
    }
}
