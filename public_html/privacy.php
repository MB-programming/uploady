<?php
require __DIR__ . '/app/bootstrap.php';
$pageTitle = t('legal.privacy.title');
require __DIR__ . '/partials_header.php';
?>
<div class="card">
<h1><?= t('legal.privacy.title') ?></h1>
<p class="muted">
    <?= t('legal.privacy.notice') ?>
</p>

<h2 style="font-size:16px;"><?= t('legal.privacy.data_collected_h2') ?></h2>
<ul>
    <li><?= t('legal.privacy.data_item1') ?></li>
    <li><?= t('legal.privacy.data_item2') ?></li>
    <li><?= t('legal.privacy.data_item3') ?></li>
</ul>

<h2 style="font-size:16px;"><?= t('legal.privacy.usage_h2') ?></h2>
<p><?= t('legal.privacy.usage_body') ?></p>

<h2 style="font-size:16px;"><?= t('legal.privacy.retention_h2') ?></h2>
<p><?= t('legal.privacy.retention_body') ?></p>

<h2 style="font-size:16px;"><?= t('legal.privacy.deletion_h2') ?></h2>
<p><?= sprintf(t('legal.privacy.deletion_body'), t('legal.privacy.deletion_link_text')) ?></p>

<h2 style="font-size:16px;"><?= t('legal.privacy.contact_h2') ?></h2>
<p><?= t('legal.privacy.contact_body') ?></p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
