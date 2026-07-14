<?php

class PostTarget
{
    /**
     * @param array $targets each item: social_account_id, platform, title, description, tags,
     *                       visibility, scheduled_at (Y-m-d H:i:s)
     */
    public static function createMany(int $postId, array $targets): void
    {
        $stmt = Database::get()->prepare(
            'INSERT INTO post_targets (post_id, social_account_id, platform, title, description, tags, visibility, scheduled_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($targets as $target) {
            $stmt->execute([
                $postId,
                $target['social_account_id'],
                $target['platform'],
                $target['title'],
                $target['description'],
                $target['tags'],
                $target['visibility'],
                $target['scheduled_at'],
            ]);
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM post_targets WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** A target plus enough of its post/owner to check ownership (used by cancel/reschedule). */
    public static function findWithOwner(int $id): ?array
    {
        $stmt = Database::get()->prepare(
            'SELECT pt.*, p.user_id FROM post_targets pt JOIN posts p ON p.id = pt.post_id WHERE pt.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
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

    /** Due targets across all users: pending, their time has come, and the video is still on disk. */
    public static function due(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT pt.*, p.video_path, p.thumbnail_path, p.public_token, p.video_size_bytes
             FROM post_targets pt
             JOIN posts p ON p.id = pt.post_id
             WHERE pt.status IN ('pending','uploading') AND pt.scheduled_at <= NOW() AND p.video_path IS NOT NULL
             ORDER BY pt.scheduled_at ASC LIMIT 20"
        );
        $stmt->execute();
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

    /**
     * Cancels a single platform target while it's still untouched — guarded to status=pending
     * with scheduled_at still in the future so it can never race with cron/publish.php, which
     * only claims targets whose time has already passed.
     */
    public static function cancelIfPending(int $id, int $userId): bool
    {
        $target = self::findWithOwner($id);
        if (!$target || $target['user_id'] !== $userId || $target['status'] !== 'pending' || strtotime($target['scheduled_at']) <= time()) {
            return false;
        }
        $stmt = Database::get()->prepare('DELETE FROM post_targets WHERE id = ?');
        $stmt->execute([$id]);
        return true;
    }

    public static function rescheduleIfPending(int $id, int $userId, string $newScheduledAt): bool
    {
        $target = self::findWithOwner($id);
        if (!$target || $target['user_id'] !== $userId || $target['status'] !== 'pending' || strtotime($target['scheduled_at']) <= time()) {
            return false;
        }
        if (strtotime($newScheduledAt) <= time()) {
            return false;
        }
        $stmt = Database::get()->prepare('UPDATE post_targets SET scheduled_at = ? WHERE id = ?');
        $stmt->execute([$newScheduledAt, $id]);
        return true;
    }

    /** Aggregate progress across a post's targets: how many finished (published or failed) out of how many total. */
    public static function progressSummary(array $targets): array
    {
        $total = count($targets);
        $published = count(array_filter($targets, fn ($t) => $t['status'] === 'published'));
        $failed = count(array_filter($targets, fn ($t) => $t['status'] === 'failed'));
        $done = $published + $failed;
        $pct = $total > 0 ? (int) round($done / $total * 100) : 0;

        return [
            'total' => $total,
            'done' => $done,
            'failed' => $failed,
            'pct' => $pct,
            'class' => $failed > 0 ? 'has-failed' : ($pct >= 100 ? 'is-complete' : ''),
            'label' => "$pct% ($done من $total منصات)",
        ];
    }

    /** Display status for a target: distinguishes "about to publish" from "genuinely scheduled later". */
    public static function displayStatus(array $target): array
    {
        if ($target['status'] === 'pending') {
            if (strtotime($target['scheduled_at']) > time()) {
                return ['label' => 'مجدول لـ ' . $target['scheduled_at'], 'class' => 'pending'];
            }
            return ['label' => 'قيد النشر', 'class' => 'uploading'];
        }
        return match ($target['status']) {
            'uploading' => ['label' => 'جاري الرفع', 'class' => 'uploading'],
            'published' => ['label' => 'تم النشر', 'class' => 'published'],
            'failed' => ['label' => 'فشل', 'class' => 'failed'],
            default => ['label' => $target['status'], 'class' => 'pending'],
        };
    }
}
