-- ============================================================================
-- Uploady — ملف الترقية المجمّع (كل الـ migrations في ملف واحد)
--
-- شغّل الملف ده مرة واحدة من phpMyAdmin على أي قاعدة بيانات Uploady قديمة
-- وهيوصلها لآخر إصدار — بدل ما ترفع ملفات database/migrations/ واحد واحد.
-- كل الأوامر هنا آمنة التكرار (IF NOT EXISTS) على MariaDB (استضافة Hostinger).
--
-- ⚠️ لو بتنشئ قاعدة جديدة من الصفر: استخدم schema.sql بس، ومتشغلش الملف ده.
-- ⚠️ لو ظهر خطأ "Duplicate key/constraint name" عند سطر معين: معناه إن الحاجة دي
--    موجودة أصلاً — احذف السطر ده وشغّل الباقي.
-- ============================================================================
SET NAMES utf8mb4;

-- ---- 001: thumbnail + admin flag -------------------------------------------
ALTER TABLE posts ADD COLUMN IF NOT EXISTS thumbnail_path VARCHAR(255) NULL AFTER video_size_bytes;
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0;

-- ---- 002: plans + invoices --------------------------------------------------
CREATE TABLE IF NOT EXISTS plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    price_egp DECIMAL(10,2) NOT NULL,
    storage_quota_gb INT UNSIGNED NOT NULL,
    max_social_accounts INT UNSIGNED NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users ADD COLUMN IF NOT EXISTS plan_id INT UNSIGNED NULL AFTER is_admin;
ALTER TABLE users ADD CONSTRAINT IF NOT EXISTS fk_users_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL;

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

-- ---- 003: per-target scheduling ---------------------------------------------
ALTER TABLE post_targets
    ADD COLUMN IF NOT EXISTS title VARCHAR(200) NOT NULL DEFAULT '' AFTER platform,
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER title,
    ADD COLUMN IF NOT EXISTS tags VARCHAR(500) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS visibility ENUM('public','unlisted','private') NOT NULL DEFAULT 'public' AFTER tags,
    ADD COLUMN IF NOT EXISTS scheduled_at DATETIME NULL AFTER visibility;

UPDATE post_targets pt
JOIN posts p ON p.id = pt.post_id
SET pt.title = p.title,
    pt.description = p.description,
    pt.tags = p.tags,
    pt.visibility = p.visibility,
    pt.scheduled_at = p.scheduled_at
WHERE pt.scheduled_at IS NULL;

ALTER TABLE post_targets MODIFY COLUMN scheduled_at DATETIME NOT NULL;
ALTER TABLE post_targets ADD KEY IF NOT EXISTS idx_targets_due (status, scheduled_at);

-- ---- 004: plan management fields --------------------------------------------
ALTER TABLE plans
    ADD COLUMN IF NOT EXISTS features TEXT NULL AFTER max_social_accounts,
    ADD COLUMN IF NOT EXISTS badge_text VARCHAR(60) NULL AFTER features,
    ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER badge_text,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_featured;

UPDATE plans SET
    features = '10 GB مساحة تخزين\nحساب واحد لكل منصة (يوتيوب / تيك توك / انستجرام)\nنشر فوري أو مجدول\nحذف الفيديو تلقائي بعد النشر'
    WHERE name = 'الأساسية' AND features IS NULL;
UPDATE plans SET
    features = '50 GB مساحة تخزين\nحسابات متعددة على كل منصة\nصورة مصغرة مخصصة (يوتيوب)\nدعم فني بأولوية',
    is_featured = 1
    WHERE name = 'الاحترافية' AND features IS NULL;
UPDATE plans SET
    features = '200 GB مساحة تخزين\nعدد غير محدود من الحسابات\nلوحة تقارير موسعة\nمدير حساب مخصص'
    WHERE name = 'الأعمال' AND features IS NULL;

-- ---- 005: settings ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 006: notifications -------------------------------------------------------
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

-- ---- 007: password resets -----------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    token VARCHAR(64) NOT NULL PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---- 008: auto replies (comment-to-DM) ----------------------------------------
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

-- ---- 009: keyword tool limits ---------------------------------------------------
ALTER TABLE plans ADD COLUMN IF NOT EXISTS keyword_searches_per_day INT UNSIGNED NULL AFTER max_social_accounts;

CREATE TABLE IF NOT EXISTS keyword_search_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    seed VARCHAR(190) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_kw_logs_user (user_id, created_at),
    KEY idx_kw_logs_ip (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE plans SET keyword_searches_per_day = 10 WHERE name = 'الأساسية' AND keyword_searches_per_day IS NULL;
UPDATE plans SET keyword_searches_per_day = 30 WHERE name = 'الاحترافية' AND keyword_searches_per_day IS NULL;
