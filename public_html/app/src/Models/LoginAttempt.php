<?php

/** Simple brute-force guard: locks an email out after too many failed logins in a time window. */
class LoginAttempt
{
    public static function record(string $email, string $ip): void
    {
        $stmt = Database::get()->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)');
        $stmt->execute([$email, $ip]);
    }

    public static function isLocked(string $email): bool
    {
        $minutes = (int) App::config('login_lockout_minutes');
        $maxAttempts = (int) App::config('login_max_attempts');

        $stmt = Database::get()->prepare(
            'SELECT COUNT(*) AS c FROM login_attempts WHERE email = ? AND created_at > (NOW() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([$email, $minutes]);
        return (int) $stmt->fetch()['c'] >= $maxAttempts;
    }

    public static function clear(string $email): void
    {
        $stmt = Database::get()->prepare('DELETE FROM login_attempts WHERE email = ?');
        $stmt->execute([$email]);
    }
}
