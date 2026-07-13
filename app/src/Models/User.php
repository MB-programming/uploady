<?php

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::get()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $email, string $password): int
    {
        return self::createByAdmin($name, $email, $password, false);
    }

    public static function createByAdmin(string $name, string $email, string $password, bool $isAdmin): int
    {
        $stmt = Database::get()->prepare(
            'INSERT INTO users (name, email, password_hash, is_admin) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $isAdmin ? 1 : 0]);
        return (int) Database::get()->lastInsertId();
    }

    /** All clients, for the admin overview dashboard — excludes other admins from the "clients" view. */
    public static function allClients(): array
    {
        $stmt = Database::get()->query(
            'SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }

    /** Every account (clients + admins), for the admin user-management screen. */
    public static function all(): array
    {
        $stmt = Database::get()->query('SELECT * FROM users ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function emailTaken(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = Database::get()->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = Database::get()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
        }
        return (bool) $stmt->fetch();
    }

    public static function updateProfile(int $id, string $name, string $email, ?bool $isAdmin = null): void
    {
        if ($isAdmin === null) {
            $stmt = Database::get()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
            $stmt->execute([$name, $email, $id]);
            return;
        }
        $stmt = Database::get()->prepare('UPDATE users SET name = ?, email = ?, is_admin = ? WHERE id = ?');
        $stmt->execute([$name, $email, $isAdmin ? 1 : 0, $id]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        $stmt = Database::get()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    /**
     * Deletes an account and everything tied to it: video/thumbnail files still on disk,
     * their storage folder, and the DB row (social_accounts/posts/post_targets cascade via FK).
     * Shared by the client's own "delete my account" flow and the admin user-management panel.
     */
    public static function deleteAccount(int $id): void
    {
        foreach (Post::forUser($id) as $post) {
            if ($post['video_path'] && is_file($post['video_path'])) {
                @unlink($post['video_path']);
            }
            if ($post['thumbnail_path'] && is_file($post['thumbnail_path'])) {
                @unlink($post['thumbnail_path']);
            }
        }
        $userDir = App::storagePath('uploads/' . $id);
        if (is_dir($userDir)) {
            array_map('unlink', glob("$userDir/*") ?: []);
            @rmdir($userDir);
        }

        $stmt = Database::get()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}
