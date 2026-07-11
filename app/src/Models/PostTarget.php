<?php

class PostTarget
{
    public static function createMany(int $postId, array $targets): void
    {
        $stmt = Database::get()->prepare(
            'INSERT INTO post_targets (post_id, social_account_id, platform) VALUES (?, ?, ?)'
        );
        foreach ($targets as $target) {
            $stmt->execute([$postId, $target['social_account_id'], $target['platform']]);
        }
    }

    public static function forPost(int $postId): array
    {
        $stmt = Database::get()->prepare(
            'SELECT pt.*, sa.display_name FROM post_targets pt
             JOIN social_accounts sa ON sa.id = pt.social_account_id
             WHERE pt.post_id = ? ORDER BY pt.platform'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    /** Targets still needing work for a given post (used by the cron worker and to gate auto-delete). */
    public static function pendingForPost(int $postId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT * FROM post_targets WHERE post_id = ? AND status IN ('pending','uploading')"
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public static function markUploading(int $id): void
    {
        $stmt = Database::get()->prepare(
            'UPDATE post_targets SET status = "uploading", attempts = attempts + 1 WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    public static function markPublished(int $id, string $remotePostId, ?string $remoteUrl): void
    {
        $stmt = Database::get()->prepare(
            'UPDATE post_targets SET status = "published", remote_post_id = ?, remote_url = ?, error_message = NULL WHERE id = ?'
        );
        $stmt->execute([$remotePostId, $remoteUrl, $id]);
    }

    public static function markFailed(int $id, string $errorMessage): void
    {
        $stmt = Database::get()->prepare(
            'UPDATE post_targets SET status = "failed", error_message = ? WHERE id = ?'
        );
        $stmt->execute([$errorMessage, $id]);
    }

    /** Records an error without changing status, so the next cron tick retries the same target. */
    public static function recordError(int $id, string $errorMessage): void
    {
        $stmt = Database::get()->prepare('UPDATE post_targets SET error_message = ? WHERE id = ?');
        $stmt->execute([$errorMessage, $id]);
    }

    public static function saveUploadSession(int $id, array $data): void
    {
        $stmt = Database::get()->prepare('UPDATE post_targets SET upload_session = ? WHERE id = ?');
        $stmt->execute([json_encode($data), $id]);
    }

    public static function uploadSession(array $target): array
    {
        return $target['upload_session'] !== null ? json_decode($target['upload_session'], true) : [];
    }
}
