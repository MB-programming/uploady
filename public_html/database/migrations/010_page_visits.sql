-- Migration 010: lightweight page-view tracking for the admin "Website Reports" page.
-- One row per rendered page view (GET, non-admin, non-bot). Recorded from partials_header,
-- so action endpoints, cron scripts, and media serving are never counted.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS page_visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    path VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_id INT UNSIGNED NULL, -- NULL = guest
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_visits_date (created_at),
    KEY idx_visits_path (path, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
