<?php

class AutoReplyRule
{
    public static function create(
        int $userId,
        int $postTargetId,
        ?string $keyword,
        string $dmMessage,
        bool $requireFollow,
        ?string $followPrompt
    ): int {
        $db = Database::get();
        $stmt = $db->prepare(
            'INSERT INTO auto_reply_rules (user_id, post_target_id, keyword, dm_message, require_follow, follow_prompt)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $postTargetId, $keyword, $dmMessage, $requireFollow ? 1 : 0, $followPrompt]);
        return (int) $db->lastInsertId();
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM auto_reply_rules WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Rules for the dashboard list, with the watched video's label and per-status event counts. */
    public static function forUser(int $userId): array
    {
        $stmt = Database::get()->prepare(
            "SELECT r.*, pt.title AS target_title, pt.remote_url, sa.display_name AS account_name,
                    (SELECT COUNT(*) FROM auto_reply_events e WHERE e.rule_id = r.id AND e.status = 'completed') AS sent_count,
                    (SELECT COUNT(*) FROM auto_reply_events e WHERE e.rule_id = r.id AND e.status = 'awaiting_follow') AS awaiting_count
             FROM auto_reply_rules r
             JOIN post_targets pt ON pt.id = r.post_target_id
             JOIN social_accounts sa ON sa.id = pt.social_account_id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Active rules joined with everything the cron worker needs to poll and reply. */
    public static function activeWithTargets(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT r.*, pt.remote_post_id AS media_id, pt.social_account_id
             FROM auto_reply_rules r
             JOIN post_targets pt ON pt.id = r.post_target_id
             WHERE r.is_active = 1 AND pt.status = 'published' AND pt.remote_post_id IS NOT NULL
             ORDER BY r.id ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function setActive(int $id, int $userId, bool $active): void
    {
        $stmt = Database::get()->prepare('UPDATE auto_reply_rules SET is_active = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$active ? 1 : 0, $id, $userId]);
    }

    public static function delete(int $id, int $userId): void
    {
        $stmt = Database::get()->prepare('DELETE FROM auto_reply_rules WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
    }

    /** Case-insensitive "contains" keyword match; an empty keyword matches every comment. */
    public static function commentMatches(array $rule, string $commentText): bool
    {
        $keyword = trim((string) ($rule['keyword'] ?? ''));
        if ($keyword === '') {
            return true;
        }
        return mb_stripos($commentText, $keyword) !== false;
    }
}
