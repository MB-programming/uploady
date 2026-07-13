-- Run this only if your database was created from an earlier version of schema.sql
-- that didn't yet have these columns (MariaDB 10.0+ / MySQL 8.0.29+ support IF NOT EXISTS here).
ALTER TABLE posts ADD COLUMN IF NOT EXISTS thumbnail_path VARCHAR(255) NULL AFTER video_size_bytes;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0;

-- Make yourself an admin (replace with your real account email):
-- UPDATE users SET is_admin = 1 WHERE email = 'you@example.com';
