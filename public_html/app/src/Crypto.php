<?php

/** AES-256-GCM encryption for OAuth tokens stored at rest in MySQL. */
class Crypto
{
    private static function key(): string
    {
        $hex = App::config('encryption_key');
        $key = hex2bin($hex);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('encryption_key must be 64 hex characters (32 bytes).');
        }
        return $key;
    }

    public static function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $encoded): string
    {
        $raw = base64_decode($encoded);
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new RuntimeException('Failed to decrypt token (wrong key or corrupted data).');
        }
        return $plaintext;
    }
}
