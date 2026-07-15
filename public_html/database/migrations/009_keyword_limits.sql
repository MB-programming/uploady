-- Migration 009: per-plan daily limits for the keyword research tool (keywords.php).
-- plans.keyword_searches_per_day: NULL = unlimited (same convention as max_social_accounts).
-- keyword_search_logs: one row per generation, counted per-day per user (or per IP for guests).
SET NAMES utf8mb4;

ALTER TABLE plans ADD COLUMN IF NOT EXISTS keyword_searches_per_day INT UNSIGNED NULL AFTER max_social_accounts;

CREATE TABLE IF NOT EXISTS keyword_search_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL, -- NULL = guest (tracked by IP)
    ip_address VARCHAR(45) NOT NULL,
    seed VARCHAR(190) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_kw_logs_user (user_id, created_at),
    KEY idx_kw_logs_ip (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sensible defaults for the seeded plans (only where the admin hasn't set anything yet).
UPDATE plans SET keyword_searches_per_day = 10 WHERE name = 'الأساسية' AND keyword_searches_per_day IS NULL;
UPDATE plans SET keyword_searches_per_day = 30 WHERE name = 'الاحترافية' AND keyword_searches_per_day IS NULL;
-- 'الأعمال' stays NULL = unlimited.
