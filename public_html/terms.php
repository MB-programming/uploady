<?php
require __DIR__ . '/app/bootstrap.php';
$pageTitle = t('legal.terms.title');
require __DIR__ . '/partials_header.php';
?>
<div class="card">
<h1><?= t('legal.terms.title') ?></h1>
<p class="muted">
    <?= t('legal.terms.notice') ?>
</p>

<h2 style="font-size:16px;"><?= t('legal.terms.usage_h2') ?></h2>
<p><?= t('legal.terms.usage_body') ?></p>

<h2 style="font-size:16px;"><?= t('legal.terms.connecting_h2') ?></h2>
<p><?= t('legal.terms.connecting_body') ?></p>

<h2 style="font-size:16px;"><?= t('legal.terms.liability_h2') ?></h2>
<p><?= t('legal.terms.liability_body') ?></p>

<h2 style="font-size:16px;"><?= t('legal.terms.contact_h2') ?></h2>
<p><?= t('legal.terms.contact_body') ?></p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
