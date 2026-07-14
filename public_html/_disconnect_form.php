<?php /** @var array $acc expected in scope from includer */ ?>
<form method="post" data-confirm="<?= htmlspecialchars(t('accounts.disconnect_confirm')) ?>" style="display:inline;">
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="disconnect">
    <input type="hidden" name="id" value="<?= (int) $acc['id'] ?>">
    <button type="submit" class="btn danger" style="padding:4px 10px;font-size:12px;"><?= t('accounts.disconnect') ?></button>
</form>
