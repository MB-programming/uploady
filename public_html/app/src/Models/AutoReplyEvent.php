<?php

class AutoReplyEvent
{
    /** Comment ids already handled for a rule — used to skip old comments when polling. */
    public static function handledCommentIds(int $ruleId): array
    {
        $stmt = Database::get()->prepare('SELECT comment_id FROM auto_reply_events WHERE rule_id = ?');
        $stmt->execute([$ruleId]);
        return array_column($stmt->fetchAll(), 'comment_id');
    }

    public static function create(int $ruleId, string $commentId, ?string $commenterId, ?string $commenterUsername, string $status, ?string $error = null): int
    {
        $db = Database::get();
        $stmt = $db->prepare(
            'INSERT INTO auto_reply_events (rule_id, comment_id, commenter_id, commenter_username, status, error_message, prompt_sent_at, completed_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $now = date('Y-m-d H:i:s');
        $stmt->execute([
            $ruleId,
            $commentId,
            $commenterId,
            $commenterUsername,
            $status,
            $error,
            $status === 'awaiting_follow' ? $now : null,
            $status === 'completed' ? $now : null,
        ]);
        return (int) $db->lastInsertId();
    }

    /** Follow-gated commenters we prompted and are still waiting on, across all active rules. */
    public static function awaitingFollow(): array
    {
        $stmt = Database::get()->prepare(
            "SELECT e.*, r.dm_message, r.follow_prompt, r.user_id, pt.social_account_id
             FROM auto_reply_events e
             JOIN auto_reply_rules r ON r.id = e.rule_id
             JOIN post_targets pt ON pt.id = r.post_target_id
             WHERE e.status = 'awaiting_follow' AND r.is_active = 1
             ORDER BY e.id ASC LIMIT 50"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function markCompleted(int $id): void
    {
        $stmt = Database::get()->prepare(
            "UPDATE auto_reply_events SET status = 'completed', completed_at = NOW(), error_message = NULL WHERE id = ?"
        );
        $stmt->execute([$id]);
    }

    public static function markFailed(int $id, string $error): void
    {
        $stmt = Database::get()->prepare("UPDATE auto_reply_events SET status = 'failed', error_message = ? WHERE id = ?");
        $stmt->execute([$error, $id]);
    }

    public static function markExpired(int $id): void
    {
        $stmt = Database::get()->prepare("UPDATE auto_reply_events SET status = 'expired' WHERE id = ?");
        $stmt->execute([$id]);
    }

    /** Re-prompt bookkeeping: remember when we last nudged this commenter to follow. */
    public static function touchPromptSent(int $id): void
    {
        $stmt = Database::get()->prepare('UPDATE auto_reply_events SET prompt_sent_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }
}
