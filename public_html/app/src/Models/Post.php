<?php

class Post
{
    /** title is just the user's own reference label for the dashboard — not published anywhere. */
    public static function create(
        int $userId,
        string $title,
        string $videoPath,
        string $originalName,
        int $sizeBytes,
        ?string $thumbnailPath = null
    ): int {
        $publicToken = bin2hex(random_bytes(24));
        $stmt = Database::get()->prepare(
            'INSERT INTO posts (user_id, title, video_path, video_original_name,
                video_size_bytes, thumbnail_path, public_token, status, scheduled_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, "scheduled", NOW())'
        );
        $stmt->execute([
            $userId, $title, $videoPath, $originalName, $sizeBytes, $thumbnailPath, $publicToken,
        ]);
        return (int) Database::get()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM posts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByToken(string $token): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM posts WHERE public_token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::get()->prepare('SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): void
    {
        $stmt = Database::get()->prepare('UPDATE posts SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function markVideoDeleted(int $id): void
    {
        $stmt = Database::get()->prepare(
            'UPDATE posts SET video_path = NULL, video_deleted_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    /**
     * Bytes currently occupied on disk for this user — only posts whose video file still
     * exists count (once auto-delete clears video_path after publishing, it stops counting
     * against the quota, so the 10GB limit is really about videos still pending/in-flight).
     */
    public static function storageUsedBytes(int $userId): int
    {
        $stmt = Database::get()->prepare(
            'SELECT COALESCE(SUM(video_size_bytes), 0) AS total FROM posts WHERE user_id = ? AND video_path IS NOT NULL'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['total'];
    }

    public static function countForUser(int $userId): int
    {
        $stmt = Database::get()->prepare('SELECT COUNT(*) AS c FROM posts WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['c'];
    }

    /** Video counts by status, for the client reports dashboard. */
    public static function statusCountsForUser(int $userId): array
    {
        $counts = ['scheduled' => 0, 'processing' => 0, 'published' => 0, 'partially_published' => 0, 'failed' => 0];
        $stmt = Database::get()->prepare('SELECT status, COUNT(*) AS c FROM posts WHERE user_id = ? GROUP BY status');
        $stmt->execute([$userId]);
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }
        return $counts;
    }

    /** Total videos uploaded across every client, for the admin aggregate reports. */
    public static function countAll(): int
    {
        $stmt = Database::get()->query('SELECT COUNT(*) AS c FROM posts');
        return (int) $stmt->fetch()['c'];
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
