<?php
require __DIR__ . '/app/bootstrap.php';
Auth::requireAdmin();

$errors = [];
$testEmailResult = null;
$brandingDir = __DIR__ . '/assets/uploads/branding';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    $section = $_POST['section'] ?? '';

    if ($section === 'seo') {
        Settings::setMany([
            'seo_site_title' => trim((string) ($_POST['seo_site_title'] ?? '')) ?: null,
            'seo_meta_description' => trim((string) ($_POST['seo_meta_description'] ?? '')) ?: null,
            'seo_meta_keywords' => trim((string) ($_POST['seo_meta_keywords'] ?? '')) ?: null,
        ]);
        header('Location: admin_settings.php?saved=1#seo');
        exit;
    }

    if ($section === 'branding') {
        if (!empty($_POST['remove_logo'])) {
            $existing = Settings::get('site_logo_path');
            if ($existing && is_file(__DIR__ . '/' . $existing)) {
                unlink(__DIR__ . '/' . $existing);
            }
            Settings::set('site_logo_path', null);
        }
        if (!empty($_POST['remove_favicon'])) {
            $existing = Settings::get('site_favicon_path');
            if ($existing && is_file(__DIR__ . '/' . $existing)) {
                unlink(__DIR__ . '/' . $existing);
            }
            Settings::set('site_favicon_path', null);
        }

        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['png', 'jpg', 'jpeg', 'svg'];
            if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                $errors[] = t('admin.err_file_too_large');
            } elseif (!in_array($ext, $allowedExt, true)) {
                $errors[] = t('admin.err_logo_format');
            } else {
                if (!is_dir($brandingDir)) mkdir($brandingDir, 0755, true);
                $filename = 'logo-' . bin2hex(random_bytes(6)) . '.' . $ext;
                $previous = Settings::get('site_logo_path');
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $brandingDir . '/' . $filename)) {
                    Settings::set('site_logo_path', 'assets/uploads/branding/' . $filename);
                    if ($previous && is_file(__DIR__ . '/' . $previous)) {
                        unlink(__DIR__ . '/' . $previous);
                    }
                }
            }
        }

        if (!empty($_FILES['favicon']['name']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            $allowedExt = ['ico', 'png'];
            if ($_FILES['favicon']['size'] > 2 * 1024 * 1024) {
                $errors[] = t('admin.err_file_too_large');
            } elseif (!in_array($ext, $allowedExt, true)) {
                $errors[] = t('admin.err_favicon_format');
            } else {
                if (!is_dir($brandingDir)) mkdir($brandingDir, 0755, true);
                $filename = 'favicon-' . bin2hex(random_bytes(6)) . '.' . $ext;
                $previous = Settings::get('site_favicon_path');
                if (move_uploaded_file($_FILES['favicon']['tmp_name'], $brandingDir . '/' . $filename)) {
                    Settings::set('site_favicon_path', 'assets/uploads/branding/' . $filename);
                    if ($previous && is_file(__DIR__ . '/' . $previous)) {
                        unlink(__DIR__ . '/' . $previous);
                    }
                }
            }
        }

        if (!$errors) {
            header('Location: admin_settings.php?saved=1#branding');
            exit;
        }
    }

    if ($section === 'smtp') {
        $newPassword = (string) ($_POST['smtp_password'] ?? '');
        $data = [
            'smtp_host' => trim((string) ($_POST['smtp_host'] ?? '')) ?: null,
            'smtp_port' => trim((string) ($_POST['smtp_port'] ?? '')) ?: null,
            'smtp_username' => trim((string) ($_POST['smtp_username'] ?? '')) ?: null,
            'smtp_encryption' => in_array($_POST['smtp_encryption'] ?? '', ['none', 'tls', 'ssl'], true) ? $_POST['smtp_encryption'] : 'tls',
            'smtp_from_email' => trim((string) ($_POST['smtp_from_email'] ?? '')) ?: null,
            'smtp_from_name' => trim((string) ($_POST['smtp_from_name'] ?? '')) ?: null,
        ];
        if ($newPassword !== '') {
            $data['smtp_password'] = Crypto::encrypt($newPassword);
        }
        Settings::setMany($data);
        header('Location: admin_settings.php?saved=1#smtp');
        exit;
    }

    if ($section === 'test_email') {
        $testTo = trim((string) ($_POST['test_email'] ?? ''));
        try {
            Mailer::send($testTo, $testTo, t('admin.smtp_test_subject'), '<p>' . htmlspecialchars(t('admin.smtp_test_body')) . '</p>');
            $testEmailResult = ['ok' => true];
        } catch (Throwable $e) {
            $testEmailResult = ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}

$pageTitle = t('admin.settings_title');
require __DIR__ . '/partials_header.php';
?>
<h1><?= t('admin.settings_title') ?></h1>

<?php if (!empty($_GET['saved'])): ?>
    <div class="alert success"><?= t('admin.settings_saved') ?></div>
<?php endif; ?>
<?php foreach ($errors as $error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endforeach; ?>

<h2 id="seo" style="font-size:16px;"><?= t('admin.settings_seo_h2') ?></h2>
<form method="post" class="card" style="max-width:600px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="section" value="seo">

    <label><?= t('admin.seo_site_title_label') ?></label>
    <input type="text" name="seo_site_title" value="<?= htmlspecialchars((string) Settings::get('seo_site_title')) ?>">

    <label><?= t('admin.seo_meta_description_label') ?></label>
    <textarea name="seo_meta_description" rows="3"><?= htmlspecialchars((string) Settings::get('seo_meta_description')) ?></textarea>

    <label><?= t('admin.seo_meta_keywords_label') ?></label>
    <input type="text" name="seo_meta_keywords" value="<?= htmlspecialchars((string) Settings::get('seo_meta_keywords')) ?>">

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= t('common.save') ?></button></p>
</form>

<h2 id="branding" style="font-size:16px;"><?= t('admin.settings_branding_h2') ?></h2>
<form method="post" enctype="multipart/form-data" class="card" style="max-width:600px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="section" value="branding">

    <label><?= t('admin.logo_label') ?></label>
    <?php if (Settings::get('site_logo_path')): ?>
        <div style="margin-bottom:8px;">
            <span class="muted"><?= t('admin.current_logo') ?>:</span>
            <img src="<?= htmlspecialchars(Settings::get('site_logo_path')) ?>" alt="" style="height:32px;vertical-align:middle;margin-inline-start:8px;">
            <label style="display:inline-flex;align-items:center;gap:6px;font-weight:normal;margin-inline-start:14px;">
                <input type="checkbox" name="remove_logo" value="1" style="width:auto;"> <?= t('admin.remove_logo') ?>
            </label>
        </div>
    <?php endif; ?>
    <input type="file" name="logo" accept="image/png,image/jpeg,image/svg+xml">

    <label style="margin-top:20px;"><?= t('admin.favicon_label') ?></label>
    <?php if (Settings::get('site_favicon_path')): ?>
        <div style="margin-bottom:8px;">
            <span class="muted"><?= t('admin.current_favicon') ?>:</span>
            <img src="<?= htmlspecialchars(Settings::get('site_favicon_path')) ?>" alt="" style="height:24px;vertical-align:middle;margin-inline-start:8px;">
            <label style="display:inline-flex;align-items:center;gap:6px;font-weight:normal;margin-inline-start:14px;">
                <input type="checkbox" name="remove_favicon" value="1" style="width:auto;"> <?= t('admin.remove_favicon') ?>
            </label>
        </div>
    <?php endif; ?>
    <input type="file" name="favicon" accept="image/x-icon,image/png">

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= t('common.save') ?></button></p>
</form>

<h2 id="smtp" style="font-size:16px;"><?= t('admin.settings_smtp_h2') ?></h2>
<form method="post" class="card" style="max-width:600px;">
    <?= Csrf::field() ?>
    <input type="hidden" name="section" value="smtp">

    <label><?= t('admin.smtp_host_label') ?></label>
    <input type="text" name="smtp_host" value="<?= htmlspecialchars((string) Settings::get('smtp_host')) ?>">

    <label><?= t('admin.smtp_port_label') ?></label>
    <input type="text" name="smtp_port" value="<?= htmlspecialchars((string) Settings::get('smtp_port', '587')) ?>">

    <label><?= t('admin.smtp_username_label') ?></label>
    <input type="text" name="smtp_username" value="<?= htmlspecialchars((string) Settings::get('smtp_username')) ?>">

    <label><?= t('admin.smtp_password_label') ?></label>
    <input type="password" name="smtp_password">

    <label><?= t('admin.smtp_encryption_label') ?></label>
    <?php $currentEnc = Settings::get('smtp_encryption', 'tls'); ?>
    <select name="smtp_encryption">
        <option value="tls" <?= $currentEnc === 'tls' ? 'selected' : '' ?>><?= t('admin.smtp_enc_tls') ?></option>
        <option value="ssl" <?= $currentEnc === 'ssl' ? 'selected' : '' ?>><?= t('admin.smtp_enc_ssl') ?></option>
        <option value="none" <?= $currentEnc === 'none' ? 'selected' : '' ?>><?= t('admin.smtp_enc_none') ?></option>
    </select>

    <label><?= t('admin.smtp_from_email_label') ?></label>
    <input type="email" name="smtp_from_email" value="<?= htmlspecialchars((string) Settings::get('smtp_from_email')) ?>">

    <label><?= t('admin.smtp_from_name_label') ?></label>
    <input type="text" name="smtp_from_name" value="<?= htmlspecialchars((string) Settings::get('smtp_from_name')) ?>">

    <p><button type="submit" class="btn" style="margin-top:20px;"><?= t('common.save') ?></button></p>
</form>

<div class="card" style="max-width:600px;">
    <?php if ($testEmailResult): ?>
        <?php if ($testEmailResult['ok']): ?>
            <div class="alert success"><?= t('admin.smtp_test_success') ?></div>
        <?php else: ?>
            <div class="alert error"><?= sprintf(htmlspecialchars(t('admin.smtp_test_failed')), htmlspecialchars($testEmailResult['message'])) ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <form method="post" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
        <?= Csrf::field() ?>
        <input type="hidden" name="section" value="test_email">
        <div style="flex:1;min-width:220px;">
            <label><?= t('admin.smtp_test_email_label') ?></label>
            <input type="email" name="test_email" required>
        </div>
        <button type="submit" class="btn secondary" style="margin-top:14px;"><?= t('admin.smtp_send_test') ?></button>
    </form>
</div>

<?php require __DIR__ . '/partials_footer.php'; ?>
