-- Generic key/value store for admin-configurable site settings: SEO meta tags,
-- logo/favicon paths, and SMTP credentials (smtp_password is stored encrypted
-- via Crypto::encrypt(), same as OAuth tokens). Run once on an existing DB.
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
