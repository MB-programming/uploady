-- Forgot-password tokens: one active token per user (older ones are deleted when a new
-- one is requested), expiring after 1 hour. Sending the actual email requires SMTP to be
-- configured first (admin_settings.php).
CREATE TABLE IF NOT EXISTS password_resets (
    token VARCHAR(64) NOT NULL PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
