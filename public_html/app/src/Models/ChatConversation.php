<?php

class ChatConversation
{
    public static function create(int $userId, string $title): int
    {
        $db = Database::get();
        $stmt = $db->prepare('INSERT INTO chat_conversations (user_id, title) VALUES (?, ?)');
        $stmt->execute([$userId, mb_substr($title, 0, 120)]);
        return (int) $db->lastInsertId();
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM chat_conversations WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::get()->prepare(
            'SELECT * FROM chat_conversations WHERE user_id = ? ORDER BY updated_at DESC LIMIT 50'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Bumps updated_at so the conversation floats to the top of the sidebar. */
    public static function touch(int $id): void
    {
        $stmt = Database::get()->prepare('UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function delete(int $id, int $userId): void
    {
        $stmt = Database::get()->prepare('DELETE FROM chat_conversations WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
    }
}
