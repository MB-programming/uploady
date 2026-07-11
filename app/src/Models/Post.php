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
}
