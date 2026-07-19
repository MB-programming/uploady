<?php

/** Persistent per-user facts (niche, tone, audience, ideas) injected into every chat prompt. */
class ChatMemory
{
    public static function add(int $userId, string $content): int
    {
        $db = Database::get();
        $stmt = $db->prepare('INSERT INTO chat_memories (user_id, content) VALUES (?, ?)');
        $stmt->execute([$userId, mb_substr($content, 0, 500)]);
        return (int) $db->lastInsertId();
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::get()->prepare(
            'SELECT * FROM chat_memories WHERE user_id = ? ORDER BY id ASC LIMIT 100'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function delete(int $id, int $userId): void
    {
        $stmt = Database::get()->prepare('DELETE FROM chat_memories WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
    }
}
