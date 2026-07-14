<?php

class PasswordReset
{
    /** Invalidates any previous tokens for this user before issuing a fresh one. */
    public static function create(int $userId): string
    {
        $stmt = Database::get()->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $stmt->execute([$userId]);

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        $stmt = Database::get()->prepare('INSERT INTO password_resets (token, user_id, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$token, $userId, $expiresAt]);

        return $token;
    }

    public static function findValid(string $token): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function delete(string $token): void
    {
        $stmt = Database::get()->prepare('DELETE FROM password_resets WHERE token = ?');
        $stmt->execute([$token]);
    }
}
