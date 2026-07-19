-- Uploady schema (MySQL 5.7+/MariaDB 10.3+, compatible with Hostinger shared hosting)
SET NAMES utf8mb4;

-- Generic key/value store for admin-configurable settings: SEO meta tags, logo/favicon
-- paths, and SMTP credentials (smtp_password stored encrypted via Crypto::encrypt()).
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subscription tiers shown on pricing.php. Seeded below; manage from the admin panel
-- (admin_plans.php) — create, edit, deactivate, or delete plans without touching SQL.
CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    price_egp DECIMAL(10,2) NOT NULL,
    storage_quota_gb INT UNSIGNED NOT NULL,
    max_social_accounts INT UNSIGNED NULL, -- NULL = unlimited; informational only, not enforced yet
    keyword_searches_per_day INT UNSIGNED NULL, -- daily quota for keywords.php; NULL = unlimited
    features TEXT NULL, -- one feature bullet per line, shown on the public pricing page
    badge_text VARCHAR(60) NULL, -- optional ribbon text (e.g. "Most Popular"); NULL = no ribbon
    is_featured TINYINT(1) NOT NULL DEFAULT 0, -- highlights the card on the pricing page
    is_active TINYINT(1) NOT NULL DEFAULT 1, -- inactive plans stay assignable to users but are hidden from pricing.php
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    plan_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per connected social channel (a user can connect several YouTube channels, etc.)
CREATE TABLE IF NOT EXISTS social_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    platform ENUM('youtube','tiktok','instagram') NOT NULL,
    platform_account_id VARCHAR(190) NOT NULL,
    display_name VARCHAR(190) NOT NULL DEFAULT '',
    access_token TEXT NOT NULL,
    refresh_token TEXT NULL,
    token_expires_at DATETIME NULL,
    -- extra platform-specific data as JSON, e.g. Instagram needs the linked Facebook Page id
    meta JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_platform_account (user_id, platform, platform_account_id),
    CONSTRAINT fk_social_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    tags VARCHAR(500) NULL,
    visibility ENUM('public','unlisted','private') NOT NULL DEFAULT 'public',
    video_path VARCHAR(255) NULL, -- absolute path in storage/uploads, cleared once deleted
    video_original_name VARCHAR(255) NULL,
    video_size_bytes BIGINT UNSIGNED NULL,
    thumbnail_path VARCHAR(255) NULL, -- custom cover image; only pushed to platforms that support it (YouTube)
    public_token VARCHAR(64) NOT NULL, -- used by media/serve.php while a target still needs a fetchable URL (Instagram)
    status ENUM('scheduled','processing','published','partially_published','failed') NOT NULL DEFAULT 'scheduled',
    scheduled_at DATETIME NOT NULL, -- publish-now posts get scheduled_at = NOW() at creation time
    video_deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_posts_due (status, scheduled_at),
    KEY idx_posts_user (user_id),
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per (post, platform) publish attempt. Each target carries its OWN caption and its
-- OWN publish time — a video can go out now on YouTube and tomorrow on TikTok, with different
-- text on each. posts.title/description/tags/visibility are legacy/unused for publishing now;
-- posts.title survives only as the user's own reference label in the dashboard.
CREATE TABLE IF NOT EXISTS post_targets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    social_account_id INT UNSIGNED NOT NULL,
    platform ENUM('youtube','youtube_shorts','tiktok','instagram') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    tags VARCHAR(500) NULL,
    visibility ENUM('public','unlisted','private') NOT NULL DEFAULT 'public', -- meaningful for YouTube only
    scheduled_at DATETIME NOT NULL, -- publish-now targets get scheduled_at = NOW() at creation time
    status ENUM('pending','uploading','published','failed') NOT NULL DEFAULT 'pending',
    remote_post_id VARCHAR(190) NULL, -- video id/permalink on the destination platform
    remote_url VARCHAR(500) NULL,
    error_message TEXT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    -- resumable upload session data (e.g. YouTube resumable session URI, TikTok publish_id) so a
    -- large upload can be resumed across several cron runs instead of one long PHP request
    upload_session JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_targets_post (post_id),
    KEY idx_targets_due (status, scheduled_at),
    CONSTRAINT fk_targets_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_targets_account FOREIGN KEY (social_account_id) REFERENCES social_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login brute-force protection: one row per failed attempt, pruned by age in LoginAttempt::record().
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts_email (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS oauth_states (
    state VARCHAR(64) NOT NULL PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    platform ENUM('youtube','tiktok','instagram') NOT NULL,
    code_verifier VARCHAR(190) NULL, -- PKCE, used by TikTok
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Forgot-password tokens: one active token per user, expiring after 1 hour.
CREATE TABLE IF NOT EXISTS password_resets (
    token VARCHAR(64) NOT NULL PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications sent from the admin panel to users. Broadcasts are fanned out into one row
-- per recipient at send time, so each user's read state is tracked independently.
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_user (user_id, is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Manually-managed billing records — there's no payment gateway wired up, so an admin creates
-- these and marks them paid/unpaid by hand (e.g. after a bank transfer or cash payment).
CREATE TABLE IF NOT EXISTS invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NULL,
    amount_egp DECIMAL(10,2) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    status ENUM('unpaid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
    notes VARCHAR(500) NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_invoices_user (user_id),
    CONSTRAINT fk_invoices_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoices_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comment-to-DM auto replies (Instagram): a rule watches one published Instagram video and
-- DMs commenters matching the keyword, optionally gated on the commenter following the account.
-- See database/migrations/008_auto_replies.sql for the full column-by-column commentary.
CREATE TABLE IF NOT EXISTS auto_reply_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    post_target_id INT UNSIGNED NOT NULL,
    keyword VARCHAR(190) NULL,
    dm_message TEXT NOT NULL,
    require_follow TINYINT(1) NOT NULL DEFAULT 0,
    follow_prompt TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_auto_reply_rules_user (user_id),
    CONSTRAINT fk_auto_reply_rules_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_auto_reply_rules_target FOREIGN KEY (post_target_id) REFERENCES post_targets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auto_reply_events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_id INT UNSIGNED NOT NULL,
    comment_id VARCHAR(190) NOT NULL,
    commenter_id VARCHAR(190) NULL,
    commenter_username VARCHAR(190) NULL,
    status ENUM('awaiting_follow','completed','failed','expired') NOT NULL,
    error_message TEXT NULL,
    prompt_sent_at DATETIME NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_auto_reply_comment (rule_id, comment_id),
    KEY idx_auto_reply_events_status (rule_id, status),
    CONSTRAINT fk_auto_reply_events_rule FOREIGN KEY (rule_id) REFERENCES auto_reply_rules(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rate limiting for the keyword research tool: one row per generation. Guests are counted
-- by IP address (user_id NULL); logged-in users by user_id against their plan's daily quota.
CREATE TABLE IF NOT EXISTS keyword_search_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    seed VARCHAR(190) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_kw_logs_user (user_id, created_at),
    KEY idx_kw_logs_ip (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lightweight page-view tracking for the admin "Website Reports" page (admin_reports.php).
-- One row per rendered page view (GET, non-admin, non-bot), recorded from partials_header.
CREATE TABLE IF NOT EXISTS page_visits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    path VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_visits_date (created_at),
    KEY idx_visits_path (path, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- AI script-writing chat (script_chat.php): persistent conversations, message history,
-- and per-user "memory" facts injected into every prompt for personalization.
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(120) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_chat_conv_user (user_id, updated_at),
    CONSTRAINT fk_chat_conv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    role ENUM('user','assistant') NOT NULL,
    content MEDIUMTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_chat_msg_conv (conversation_id, id),
    CONSTRAINT fk_chat_msg_conv FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_memories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    content VARCHAR(500) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_chat_mem_user (user_id),
    CONSTRAINT fk_chat_mem_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO plans (name, price_egp, storage_quota_gb, max_social_accounts, keyword_searches_per_day, features, badge_text, is_featured, sort_order) VALUES
    ('الأساسية', 299.00, 10, 3, 10, '10 GB مساحة تخزين\nحساب واحد لكل منصة (يوتيوب / تيك توك / انستجرام)\nنشر فوري أو مجدول\nحذف الفيديو تلقائي بعد النشر', NULL, 0, 1),
    ('الاحترافية', 750.00, 50, 10, 30, '50 GB مساحة تخزين\nحسابات متعددة على كل منصة\nصورة مصغرة مخصصة (يوتيوب)\nدعم فني بأولوية', 'الأكثر طلبًا', 1, 2),
    ('الأعمال', 1500.00, 200, NULL, NULL, '200 GB مساحة تخزين\nعدد غير محدود من الحسابات\nلوحة تقارير موسعة\nمدير حساب مخصص', NULL, 0, 3)
ON DUPLICATE KEY UPDATE name = VALUES(name);
