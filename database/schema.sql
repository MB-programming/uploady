-- Uploady schema (MySQL 5.7+/MariaDB 10.3+, compatible with Hostinger shared hosting)
SET NAMES utf8mb4;

-- Subscription tiers shown on pricing.php. Seeded below; edit rows directly via phpMyAdmin
-- if you need to change prices/quotas later (there's no admin UI for editing plans yet).
CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    price_egp DECIMAL(10,2) NOT NULL,
    storage_quota_gb INT UNSIGNED NOT NULL,
    max_social_accounts INT UNSIGNED NULL, -- NULL = unlimited; informational only, not enforced yet
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

-- One row per (post, platform) publish attempt
CREATE TABLE IF NOT EXISTS post_targets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    social_account_id INT UNSIGNED NOT NULL,
    platform ENUM('youtube','youtube_shorts','tiktok','instagram') NOT NULL,
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
    KEY idx_targets_pending (status),
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

INSERT INTO plans (name, price_egp, storage_quota_gb, max_social_accounts, sort_order) VALUES
    ('الأساسية', 299.00, 10, 3, 1),
    ('الاحترافية', 750.00, 50, 10, 2),
    ('الأعمال', 1500.00, 200, NULL, 3)
ON DUPLICATE KEY UPDATE name = VALUES(name);
