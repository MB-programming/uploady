<?php
require __DIR__ . '/app/bootstrap.php';
$pageTitle = t('legal.deletion.title');
require __DIR__ . '/partials_header.php';
?>
<div class="card">
<h1><?= t('legal.deletion.title') ?></h1>
<p><?= t('legal.deletion.intro') ?></p>

<h2 style="font-size:16px;"><?= t('legal.deletion.method1_h2') ?></h2>
<?php if (Auth::check()): ?>
    <form method="post" action="account_delete.php" data-confirm="<?= htmlspecialchars(t('legal.deletion.confirm_delete')) ?>">
        <?= Csrf::field() ?>
        <button type="submit" class="btn danger"><?= t('legal.deletion.delete_now_btn') ?></button>
    </form>
    <p class="muted"><?= t('legal.deletion.delete_note') ?></p>
<?php else: ?>
    <p class="muted"><a href="login.php"><?= t('legal.deletion.login_first_link') ?></a> <?= t('legal.deletion.login_first') ?></p>
<?php endif; ?>

<h2 style="font-size:16px;"><?= t('legal.deletion.method2_h2') ?></h2>
<p><?= t('legal.deletion.method2_body') ?></p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
