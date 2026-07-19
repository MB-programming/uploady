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

    public static function sparkles(string $class = ''): string
    {
        return self::svg('<path d="M12 3l1.9 4.6L18.5 9.5l-4.6 1.9L12 16l-1.9-4.6L5.5 9.5l4.6-1.9z"/><path d="M18.5 15l.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9z"/>', $class);
    }

    public static function search(string $class = ''): string
    {
        return self::svg('<circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L21 21"/>', $class);
    }

    public static function chat(string $class = ''): string
    {
        return self::svg('<path d="M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8z"/><path d="M8.5 11h.01"/><path d="M12 11h.01"/><path d="M15.5 11h.01"/>', $class);
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

    public static function home(string $class = ''): string
    {
        return self::svg('<path d="M4 11l8-7 8 7"/><path d="M6 9.5V20a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V9.5"/><path d="M10 21v-6h4v6"/>', $class);
    }

    public static function users(string $class = ''): string
    {
        return self::svg('<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><path d="M16 8.2a3.2 3.2 0 1 1 0 6.4"/><path d="M21 20c0-2.7-1.7-4.7-4-5.3"/>', $class);
    }

    public static function barChart(string $class = ''): string
    {
        return self::svg('<path d="M4 20V10"/><path d="M12 20V4"/><path d="M20 20v-7"/>', $class);
    }

    public static function user(string $class = ''): string
    {
        return self::svg('<circle cx="12" cy="8" r="4"/><path d="M4 20c0-3.9 3.6-7 8-7s8 3.1 8 7"/>', $class);
    }

    public static function shield(string $class = ''): string
    {
        return self::svg('<path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z"/><path d="M9 12l2 2 4-4"/>', $class);
    }

    public static function logout(string $class = ''): string
    {
        return self::svg('<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M4 12h12"/><path d="M12 8l4 4-4 4"/>', $class);
    }

    public static function tag(string $class = ''): string
    {
        return self::svg('<path d="M12 3h6a2 2 0 0 1 2 2v6a2 2 0 0 1-.6 1.4l-9 9a2 2 0 0 1-2.8 0l-5-5a2 2 0 0 1 0-2.8l9-9A2 2 0 0 1 12 3z"/><circle cx="16" cy="8" r="1.4" fill="currentColor" stroke="none"/>', $class);
    }

    public static function menu(string $class = ''): string
    {
        return self::svg('<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>', $class);
    }

    public static function close(string $class = ''): string
    {
        return self::svg('<path d="M6 6l12 12"/><path d="M18 6L6 18"/>', $class);
    }

    public static function receipt(string $class = ''): string
    {
        return self::svg('<path d="M6 3h12v18l-3-2-2 2-2-2-2 2-2-2-1 2V3z"/><path d="M9 8h6"/><path d="M9 12h6"/>', $class);
    }

    public static function package(string $class = ''): string
    {
        return self::svg('<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path d="M4 7.5l8 4.5 8-4.5"/><path d="M12 12v9"/>', $class);
    }

    public static function gear(string $class = ''): string
    {
        return self::svg('<line x1="4" y1="6" x2="20" y2="6"/><circle cx="9" cy="6" r="2"/><line x1="4" y1="12" x2="20" y2="12"/><circle cx="15" cy="12" r="2"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="9" cy="18" r="2"/>', $class);
    }

    public static function bell(string $class = ''): string
    {
        return self::svg('<path d="M6 9a6 6 0 1 1 12 0c0 4 1.5 5.5 2 6H4c.5-.5 2-2 2-6z"/><path d="M9.5 18a2.5 2.5 0 0 0 5 0"/>', $class);
    }

    public static function globe(string $class = ''): string
    {
        return self::svg('<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.5 2.5 4 5.6 4 9s-1.5 6.5-4 9c-2.5-2.5-4-5.6-4-9s1.5-6.5 4-9z"/>', $class);
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
