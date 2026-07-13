<?php
require __DIR__ . '/../app/bootstrap.php';
$pageTitle = 'حذف البيانات';
require __DIR__ . '/partials_header.php';
?>
<div class="card">
<h1>حذف الحساب والبيانات</h1>
<p>لو عايز تحذف حسابك وكل بياناتك من Uploady (بيانات الحساب، حسابات التواصل الاجتماعي المربوطة،
وسجل الفيديوهات اللي نشرتها) عندك طريقتين:</p>

<h2 style="font-size:16px;">1. من داخل حسابك (فوري)</h2>
<?php if (Auth::check()): ?>
    <form method="post" action="account_delete.php" data-confirm="متأكد إنك عايز تحذف حسابك؟ الإجراء ده نهائي ومش هيتراجع.">
        <?= Csrf::field() ?>
        <button type="submit" class="btn danger">احذف حسابي وكل بياناتي الآن</button>
    </form>
    <p class="muted">هيتم حذف حسابك فورًا: بيانات الدخول، توكنات حسابات التواصل الاجتماعي، وأي فيديو
    لسه موجود على السيرفر. الفيديوهات المنشورة فعلاً على يوتيوب/تيك توك/انستجرام مش بنتحكم فيها —
    لازم تتحذف من المنصة نفسها لو عايز كده.</p>
<?php else: ?>
    <p class="muted"><a href="login.php">سجّل دخولك</a> الأول عشان تقدر تحذف حسابك مباشرة.</p>
<?php endif; ?>

<h2 style="font-size:16px;">2. عن طريق التواصل معنا</h2>
<p>لو مش قادر تدخل حسابك، ابعت طلب حذف من نفس البريد الإلكتروني المسجل به الحساب إلى:
<strong>[ضيف إيميل التواصل هنا]</strong>. هنرد ونأكد الحذف خلال 7 أيام عمل.</p>
</div>
<?php require __DIR__ . '/partials_footer.php'; ?>
