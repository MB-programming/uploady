-- Moves title/description/tags/visibility/scheduled_at from posts to post_targets so each
-- platform can have its own caption and its own publish time. Run this once on any database
-- that already has the old post_targets structure (without these columns).
ALTER TABLE post_targets
    ADD COLUMN IF NOT EXISTS title VARCHAR(200) NOT NULL DEFAULT '' AFTER platform,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER title,
    ADD COLUMN IF NOT EXISTS tags VARCHAR(500) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS visibility ENUM('public','unlisted','private') NOT NULL DEFAULT 'public' AFTER tags,
    ADD COLUMN IF NOT EXISTS scheduled_at DATETIME NULL AFTER visibility;

-- Backfill existing rows from their parent post so nothing already scheduled loses its data.
UPDATE post_targets pt
JOIN posts p ON p.id = pt.post_id
SET pt.title = p.title,
    pt.description = p.description,
    pt.tags = p.tags,
    pt.visibility = p.visibility,
    pt.scheduled_at = p.scheduled_at
WHERE pt.scheduled_at IS NULL;

ALTER TABLE post_targets MODIFY COLUMN scheduled_at DATETIME NOT NULL;

-- MariaDB doesn't support "ADD INDEX IF NOT EXISTS" — if this errors with a duplicate key
-- name, the index already exists; skip it and continue.
ALTER TABLE post_targets ADD KEY idx_targets_due (status, scheduled_at);
