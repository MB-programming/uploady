<?php

class ChatMessage
{
    public static function add(int $conversationId, string $role, string $content): int
    {
        $db = Database::get();
        $stmt = $db->prepare('INSERT INTO chat_messages (conversation_id, role, content) VALUES (?, ?, ?)');
        $stmt->execute([$conversationId, $role, $content]);
        return (int) $db->lastInsertId();
    }

    public static function forConversation(int $conversationId): array
    {
        $stmt = Database::get()->prepare(
            'SELECT * FROM chat_messages WHERE conversation_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$conversationId]);
        return $stmt->fetchAll();
    }

    /** The last N messages, oldest-first — the context window sent to the model. */
    public static function recentForConversation(int $conversationId, int $limit = 20): array
    {
        $stmt = Database::get()->prepare(
            'SELECT role, content FROM chat_messages WHERE conversation_id = ? ORDER BY id DESC LIMIT ' . (int) $limit
        );
        $stmt->execute([$conversationId]);
        return array_reverse($stmt->fetchAll());
    }
}
