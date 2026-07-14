<?php /** @var bool $loggedIn */ ?>
<?php if ($loggedIn): ?>
    </div>
    <div class="nav nav--footer">
        <a href="privacy.php" class="muted"><?= t('footer.privacy') ?></a>
        <a href="terms.php" class="muted"><?= t('footer.terms') ?></a>
        <a href="data-deletion.php" class="muted"><?= t('footer.data_deletion') ?></a>
    </div>
    </div>
</div>
<?php else: ?>
</div>
<div class="nav nav--footer">
    <a href="privacy.php" class="muted"><?= t('footer.privacy') ?></a>
    <a href="terms.php" class="muted"><?= t('footer.terms') ?></a>
    <a href="data-deletion.php" class="muted"><?= t('footer.data_deletion') ?></a>
</div>
<?php endif; ?>
<script src="assets/js/site.js"></script>
</body>
</html>
