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
        $stmt = Database::get()->prepare(
            'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
        return (int) Database::get()->lastInsertId();
    }

    /** All clients, for the admin dashboard — excludes other admins from the "clients" view. */
    public static function allClients(): array
    {
        $stmt = Database::get()->query(
            'SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC'
        );
        return $stmt->fetchAll();
    }
}
