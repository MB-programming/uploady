-- Adds admin-manageable fields to plans: feature bullets, an optional ribbon badge,
-- a "featured" highlight flag, and an is_active flag (deactivated plans stay assignable
-- to existing users but drop off the public pricing page). Run once on an existing DB.
-- If you get a duplicate-column error, the column already exists — skip that line.
ALTER TABLE plans
    ADD COLUMN features TEXT NULL AFTER max_social_accounts,
    ADD COLUMN badge_text VARCHAR(60) NULL AFTER features,
    ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER badge_text,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_featured;

-- Backfill the existing seeded plans with the feature text that used to be hardcoded
-- in pricing.php, so nothing changes visually until you edit them from admin_plans.php.
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
