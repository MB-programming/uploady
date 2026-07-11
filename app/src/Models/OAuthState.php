<?php

class OAuthState
{
    public static function create(int $userId, string $platform, ?string $codeVerifier = null): string
    {
        $state = bin2hex(random_bytes(24));
        $stmt = Database::get()->prepare(
            'INSERT INTO oauth_states (state, user_id, platform, code_verifier) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$state, $userId, $platform, $codeVerifier]);
        return $state;
    }

    /** Fetches and immediately deletes the state row (single use). */
    public static function consume(string $state, string $platform): ?array
    {
        $db = Database::get();
        $stmt = $db->prepare('SELECT * FROM oauth_states WHERE state = ? AND platform = ? LIMIT 1');
        $stmt->execute([$state, $platform]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $db->prepare('DELETE FROM oauth_states WHERE state = ?')->execute([$state]);

        // States older than 15 minutes are considered expired.
        if (strtotime($row['created_at']) < time() - 900) {
            return null;
        }
        return $row;
    }
}
