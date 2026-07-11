<?php

class SocialAccount
{
    public static function forUser(int $userId): array
    {
        $stmt = Database::get()->prepare('SELECT * FROM social_accounts WHERE user_id = ? ORDER BY platform, display_name');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM social_accounts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function upsert(
        int $userId,
        string $platform,
        string $platformAccountId,
        string $displayName,
        string $accessToken,
        ?string $refreshToken,
        ?string $expiresAt,
        array $meta = []
    ): int {
        $db = Database::get();
        $stmt = $db->prepare(
            'SELECT id FROM social_accounts WHERE user_id = ? AND platform = ? AND platform_account_id = ? LIMIT 1'
        );
        $stmt->execute([$userId, $platform, $platformAccountId]);
        $existing = $stmt->fetch();

        $encryptedAccess = Crypto::encrypt($accessToken);
        $encryptedRefresh = $refreshToken !== null ? Crypto::encrypt($refreshToken) : null;
        $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);

        if ($existing) {
            $stmt = $db->prepare(
                'UPDATE social_accounts SET display_name = ?, access_token = ?, refresh_token = COALESCE(?, refresh_token),
                 token_expires_at = ?, meta = ? WHERE id = ?'
            );
            $stmt->execute([$displayName, $encryptedAccess, $encryptedRefresh, $expiresAt, $metaJson, $existing['id']]);
            return (int) $existing['id'];
        }

        $stmt = $db->prepare(
            'INSERT INTO social_accounts (user_id, platform, platform_account_id, display_name, access_token, refresh_token, token_expires_at, meta)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $platform, $platformAccountId, $displayName, $encryptedAccess, $encryptedRefresh, $expiresAt, $metaJson]);
        return (int) $db->lastInsertId();
    }

    public static function delete(int $id, int $userId): void
    {
        $stmt = Database::get()->prepare('DELETE FROM social_accounts WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
    }

    public static function accessToken(array $account): string
    {
        return Crypto::decrypt($account['access_token']);
    }

    public static function refreshToken(array $account): ?string
    {
        return $account['refresh_token'] !== null ? Crypto::decrypt($account['refresh_token']) : null;
    }

    public static function meta(array $account): array
    {
        return $account['meta'] !== null ? json_decode($account['meta'], true) : [];
    }

    public static function updateTokens(int $id, string $accessToken, ?string $refreshToken, ?string $expiresAt): void
    {
        $db = Database::get();
        if ($refreshToken !== null) {
            $stmt = $db->prepare('UPDATE social_accounts SET access_token = ?, refresh_token = ?, token_expires_at = ? WHERE id = ?');
            $stmt->execute([Crypto::encrypt($accessToken), Crypto::encrypt($refreshToken), $expiresAt, $id]);
        } else {
            $stmt = $db->prepare('UPDATE social_accounts SET access_token = ?, token_expires_at = ? WHERE id = ?');
            $stmt->execute([Crypto::encrypt($accessToken), $expiresAt, $id]);
        }
    }
}
