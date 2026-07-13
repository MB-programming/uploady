<?php

class Post
{
    public static function create(
        int $userId,
        string $title,
        string $description,
        string $tags,
        string $visibility,
        string $videoPath,
        string $originalName,
        int $sizeBytes,
        string $scheduledAt
    ): int {
        $publicToken = bin2hex(random_bytes(24));
        $stmt = Database::get()->prepare(
            'INSERT INTO posts (user_id, title, description, tags, visibility, video_path, video_original_name,
                video_size_bytes, public_token, status, scheduled_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "scheduled", ?)'
        );
        $stmt->execute([
            $userId, $title, $description, $tags, $visibility, $videoPath, $originalName,
            $sizeBytes, $publicToken, $scheduledAt,
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

    /** Posts due for publishing: scheduled time has passed and there's still a video file to send. */
    public static function due(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM posts WHERE status IN ('scheduled','processing') AND scheduled_at <= NOW()
             AND video_path IS NOT NULL ORDER BY scheduled_at ASC LIMIT 20"
        );
        $stmt->execute();
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

    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM posts WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Cancels a post that hasn't been picked up by the cron worker yet, deleting its video
     * file immediately. Guarded to status=scheduled with scheduled_at still in the future so
     * it can never race with cron/publish.php, which only claims posts whose time has passed.
     */
    public static function cancelIfPending(int $id, int $userId): bool
    {
        $post = self::findForUser($id, $userId);
        if (!$post || $post['status'] !== 'scheduled' || strtotime($post['scheduled_at']) <= time()) {
            return false;
        }
        if ($post['video_path'] && is_file($post['video_path'])) {
            @unlink($post['video_path']);
        }
        $stmt = Database::get()->prepare('DELETE FROM posts WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return true;
    }

    public static function rescheduleIfPending(int $id, int $userId, string $newScheduledAt): bool
    {
        $post = self::findForUser($id, $userId);
        if (!$post || $post['status'] !== 'scheduled' || strtotime($post['scheduled_at']) <= time()) {
            return false;
        }
        if (strtotime($newScheduledAt) <= time()) {
            return false;
        }
        $stmt = Database::get()->prepare('UPDATE posts SET scheduled_at = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$newScheduledAt, $id, $userId]);
        return true;
    }
}
