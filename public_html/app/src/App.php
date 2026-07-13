<?php

class App
{
    private static ?array $config = null;

    public static function config(?string $key = null)
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/config.php';
        }

        if ($key === null) {
            return self::$config;
        }

        return self::$config[$key] ?? null;
    }

    public static function basePath(string $path = ''): string
    {
        return dirname(__DIR__, 2) . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    public static function storagePath(string $path = ''): string
    {
        return self::basePath('storage') . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    public static function url(string $path = ''): string
    {
        return rtrim(self::config('app_url'), '/') . '/' . ltrim($path, '/');
    }
}
