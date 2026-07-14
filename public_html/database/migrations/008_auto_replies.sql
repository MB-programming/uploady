-- Migration 008: comment-to-DM auto replies (Instagram)
-- A rule watches one published Instagram video: when a new comment matches the keyword,
-- we DM the commenter. Optionally the rule is follow-gated: non-followers first get a
-- "follow me then message me" prompt, and once they follow (checked when they DM back)
-- they receive the real message/link.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS auto_reply_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    post_target_id INT UNSIGNED NOT NULL, -- the published Instagram target being watched
    keyword VARCHAR(190) NULL, -- NULL/empty = trigger on every comment; otherwise case-insensitive "contains" match
    dm_message TEXT NOT NULL, -- the message/link sent once the commenter qualifies
    require_follow TINYINT(1) NOT NULL DEFAULT 0,
    follow_prompt TEXT NULL, -- sent instead of dm_message when require_follow=1 and the commenter isn't a follower yet
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_auto_reply_rules_user (user_id),
    CONSTRAINT fk_auto_reply_rules_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_auto_reply_rules_target FOREIGN KEY (post_target_id) REFERENCES post_targets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per handled comment: dedupes cron polling and tracks the follow-gate conversation.
CREATE TABLE IF NOT EXISTS auto_reply_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_id INT UNSIGNED NOT NULL,
    comment_id VARCHAR(190) NOT NULL,
    commenter_id VARCHAR(190) NULL, -- Instagram-scoped user id (IGSID) from the comment's `from` field
    commenter_username VARCHAR(190) NULL,
    status ENUM('awaiting_follow','completed','failed','expired') NOT NULL,
    error_message TEXT NULL,
    prompt_sent_at DATETIME NULL, -- when the last follow prompt went out (private reply or DM)
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_auto_reply_comment (rule_id, comment_id), -- two rules may watch the same video
    KEY idx_auto_reply_events_status (rule_id, status),
    CONSTRAINT fk_auto_reply_events_rule FOREIGN KEY (rule_id) REFERENCES auto_reply_rules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
