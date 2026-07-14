<?php

class Notification
{
    public static function sendToUser(int $userId, string $title, string $body): void
    {
        $stmt = Database::get()->prepare(
            'INSERT INTO notifications (user_id, title, body) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $body]);
    }

    /** Fans out one row per current client — simplest way to give each recipient an independent read state. */
    public static function broadcastToAllClients(string $title, string $body): int
    {
        $clients = User::allClients();
        foreach ($clients as $client) {
            self::sendToUser((int) $client['id'], $title, $body);
        }
        return count($clients);
    }

    public static function forUser(int $userId, int $limit = 30): array
    {
        $stmt = Database::get()->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function unreadCountForUser(int $userId): int
    {
        $stmt = Database::get()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markAllReadForUser(int $userId): void
    {
        $stmt = Database::get()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
    }

    /** Recently sent notifications for the admin's own log, grouped so a broadcast shows as one row with a recipient count. */
    public static function recentSentSummary(int $limit = 20): array
    {
        $stmt = Database::get()->query(
            "SELECT title, body, created_at, COUNT(*) AS recipients
             FROM notifications
             GROUP BY title, body, created_at
             ORDER BY created_at DESC
             LIMIT " . (int) $limit
        );
        return $stmt->fetchAll();
    }
}
