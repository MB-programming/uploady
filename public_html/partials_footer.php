<?php /** @var bool $loggedIn */ ?>
<?php if ($loggedIn): ?>
    </div>
    <div class="nav nav--footer">
        <a href="privacy.php" class="muted">سياسة الخصوصية</a>
        <a href="terms.php" class="muted">الشروط والأحكام</a>
        <a href="data-deletion.php" class="muted">حذف البيانات</a>
    </div>
    </div>
</div>
<?php else: ?>
</div>
<div class="nav nav--footer">
    <a href="privacy.php" class="muted">سياسة الخصوصية</a>
    <a href="terms.php" class="muted">الشروط والأحكام</a>
    <a href="data-deletion.php" class="muted">حذف البيانات</a>
</div>
<?php endif; ?>
<script src="assets/js/site.js"></script>
</body>
</html>
