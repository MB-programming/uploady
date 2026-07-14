<?php

/** Tiny key-based i18n: cookie-selected locale (ar default), English falls back to Arabic for any missing key. */
class Lang
{
    private const COOKIE = 'uploady_lang';
    private static ?string $locale = null;
    private static array $strings = [];

    public static function locale(): string
    {
        if (self::$locale === null) {
            self::$locale = ($_COOKIE[self::COOKIE] ?? '') === 'en' ? 'en' : 'ar';
        }
        return self::$locale;
    }

    public static function dir(): string
    {
        return self::locale() === 'en' ? 'ltr' : 'rtl';
    }

    public static function t(string $key): string
    {
        $locale = self::locale();
        self::load($locale);
        if (isset(self::$strings[$locale][$key])) {
            return self::$strings[$locale][$key];
        }
        self::load('ar');
        return self::$strings['ar'][$key] ?? $key;
    }

    private static function load(string $locale): void
    {
        if (isset(self::$strings[$locale])) {
            return;
        }
        $file = __DIR__ . '/../lang/' . $locale . '.php';
        self::$strings[$locale] = is_file($file) ? require $file : [];
    }
}
