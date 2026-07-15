<?php

/** Page-view rows behind the admin "Website Reports" page. */
class PageVisit
{
    /**
     * Best-effort recording: analytics must NEVER break page rendering, so any failure
     * (e.g. the table not migrated yet) is swallowed and only logged.
     */
    public static function record(string $path, string $ip, ?int $userId): void
    {
        try {
            $stmt = Database::get()->prepare(
                'INSERT INTO page_visits (path, ip_address, user_id) VALUES (?, ?, ?)'
            );
            $stmt->execute([mb_substr($path, 0, 190), $ip, $userId]);
        } catch (Throwable $e) {
            error_log('[PageVisit] record failed: ' . $e->getMessage());
        }
    }

    public static function countSince(string $sinceDateTime): int
    {
        $stmt = Database::get()->prepare('SELECT COUNT(*) FROM page_visits WHERE created_at >= ?');
        $stmt->execute([$sinceDateTime]);
        return (int) $stmt->fetchColumn();
    }

    public static function uniqueVisitorsSince(string $sinceDateTime): int
    {
        $stmt = Database::get()->prepare('SELECT COUNT(DISTINCT ip_address) FROM page_visits WHERE created_at >= ?');
        $stmt->execute([$sinceDateTime]);
        return (int) $stmt->fetchColumn();
    }

    /** [date => ['views' => n, 'uniques' => n]] for the last N days, oldest first, gaps filled. */
    public static function dailySeries(int $days): array
    {
        $stmt = Database::get()->prepare(
            'SELECT DATE(created_at) AS d, COUNT(*) AS views, COUNT(DISTINCT ip_address) AS uniques
             FROM page_visits
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)'
        );
        $stmt->execute([$days - 1]);
        $byDate = [];
        foreach ($stmt->fetchAll() as $row) {
            $byDate[$row['d']] = ['views' => (int) $row['views'], 'uniques' => (int) $row['uniques']];
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $series[$date] = $byDate[$date] ?? ['views' => 0, 'uniques' => 0];
        }
        return $series;
    }

    public static function topPages(int $days, int $limit = 10): array
    {
        $stmt = Database::get()->prepare(
            'SELECT path, COUNT(*) AS views, COUNT(DISTINCT ip_address) AS uniques
             FROM page_visits
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY path ORDER BY views DESC LIMIT ' . (int) $limit
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
