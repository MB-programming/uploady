<?php

/** Small inline SVG icon set (stroke-based, currentColor) — no emoji, no icon-font/CDN dependency. */
class Icons
{
    private static function svg(string $inner, string $class = '', string $fill = 'none'): string
    {
        return '<svg class="icon ' . $class . '" viewBox="0 0 24 24" fill="' . $fill . '" stroke="currentColor" '
            . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
    }

    public static function upload(string $class = ''): string
    {
        return self::svg('<path d="M12 3v12"/><path d="M7 8l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>', $class);
    }

    public static function link(string $class = ''): string
    {
        return self::svg('<path d="M9 17H7A5 5 0 0 1 7 7h2"/><path d="M15 7h2a5 5 0 1 1 0 10h-2"/><path d="M8 12h8"/>', $class);
    }

    public static function clock(string $class = ''): string
    {
        return self::svg('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/>', $class);
    }

    public static function trash(string $class = ''): string
    {
        return self::svg('<path d="M4 7h16"/><path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12"/><path d="M10 11v6"/><path d="M14 11v6"/>', $class);
    }

    public static function checkCircle(string $class = ''): string
    {
        return self::svg('<circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2.5 2.5 4.5-5"/>', $class);
    }

    public static function xCircle(string $class = ''): string
    {
        return self::svg('<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5l5 5"/><path d="M14.5 9.5l-5 5"/>', $class);
    }

    public static function bolt(string $class = ''): string
    {
        return self::svg('<path d="M13 2L4 14h6l-1 8 9-12h-6z"/>', $class, 'currentColor');
    }

    public static function chevronLeft(string $class = ''): string
    {
        return self::svg('<path d="M15 6l-6 6 6 6"/>', $class);
    }

    public static function film(string $class = ''): string
    {
        return self::svg('<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M8 4v5"/><path d="M8 15v5"/><path d="M16 4v5"/><path d="M16 15v5"/>', $class);
    }

    public static function database(string $class = ''): string
    {
        return self::svg('<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/><path d="M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/>', $class);
    }

    public static function trendUp(string $class = ''): string
    {
        return self::svg('<path d="M3 17l6-6 4 4 7-8"/><path d="M15 6h5v5"/>', $class);
    }

    /** Small icon for a badge, based on its CSS status class (published/paid, failed/cancelled, pending/uploading/unpaid). */
    public static function forBadge(string $status): string
    {
        return match ($status) {
            'published', 'paid' => self::checkCircle(),
            'failed', 'cancelled' => self::xCircle(),
            default => self::clock(),
        };
    }
}
