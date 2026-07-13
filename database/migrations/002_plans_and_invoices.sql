-- Run this only if your database was created before plans/invoices existed.
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
-- MariaDB doesn't support "ADD CONSTRAINT IF NOT EXISTS" — if this line errors with
-- "Duplicate key/constraint name", the constraint already exists; skip it and continue.
ALTER TABLE users ADD CONSTRAINT fk_users_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE SET NULL;

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
