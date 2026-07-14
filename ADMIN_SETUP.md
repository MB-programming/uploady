# دليل الإعداد الخارجي (للأدمن)

الملف ده مخصص ليك انت (صاحب الموقع) — فيه بالظبط اللي لازم تعمله **خارج الكود** على كل منصة عشان
الموقع يقدر يربط حسابات عملائك وينشر نيابة عنهم. مفيش حاجة من دي ممكن تتعمل من الكود — كلها إجراءات
لازم تتم يدويًا على مواقع المنصات نفسها.

## قبل ما تبدأ (متطلبات مشتركة لكل المنصات)

1. **دومين شغال بـ HTTPS** — Hostinger بيدّي SSL مجاني، تأكد إنه مفعّل.
2. **صفحات قانونية لازم تكون شغالة على الدومين قبل أي تسجيل:**
   - `https://your-domain.com/privacy.php` — سياسة الخصوصية (موجودة جاهزة كنموذج، راجعها وعدّلها)
   - `https://your-domain.com/terms.php` — الشروط والأحكام (نفس الكلام)
   - `https://your-domain.com/data-deletion.php` — صفحة حذف البيانات (فيها زرار حذف حساب فعلي شغال)
3. **شعار/أيقونة للتطبيق** — جوجل وميتا بيطلبوا صورة مربعة (512×512 غالبًا) لاسم التطبيق.
4. **إيميل دعم فني** — استبدل `[ضيف إيميل التواصل هنا]` في `privacy.php` و`terms.php` و`data-deletion.php` بإيميلك الحقيقي قبل ما تقدّم أي طلب مراجعة.

بعد ما ترفع الموقع فعليًا على Hostinger، اجمع الروابط دي واحفظها — هتحتاجها في كل خطوة:

| الغرض | الرابط |
|---|---|
| Redirect URI ليوتيوب | `https://your-domain.com/oauth/youtube_callback.php` |
| Redirect URI لتيك توك | `https://your-domain.com/oauth/tiktok_callback.php` |
| Redirect URI لانستجرام | `https://your-domain.com/oauth/instagram_callback.php` |
| Redirect URI لتسجيل الدخول بجوجل (اختياري) | `https://your-domain.com/oauth/google_login_callback.php` |
| Privacy Policy URL | `https://your-domain.com/privacy.php` |
| Terms of Service URL | `https://your-domain.com/terms.php` |
| Data Deletion URL | `https://your-domain.com/data-deletion.php` |

---

## 1. يوتيوب (Google Cloud Console)

1. روح على [console.cloud.google.com](https://console.cloud.google.com) وسجّل دخول بحساب جوجل بتاع الشغل.
2. أنشئ **مشروع جديد** (Project) — اسمه مثلاً "Uploady".
3. من القائمة الجانبية: **APIs & Services → Library**، ابحث عن **YouTube Data API v3** ودوس **Enable**.
4. **APIs & Services → OAuth consent screen**:
   - النوع: **External**
   - اسم التطبيق، شعار، إيميل الدعم
   - Authorized domains: الدومين بتاعك
   - روابط Privacy Policy و Terms of Service (من الجدول فوق)
   - Scopes: أضف
     - `https://www.googleapis.com/auth/youtube.upload`
     - `https://www.googleapis.com/auth/youtube.readonly`
   - Test users: أضف إيميلات جوجل بتاعة عملائك (لحد ما التطبيق ياخد موافقة، أي حد مش مضاف هنا مش هيقدر يربط حسابه)
5. **APIs & Services → Credentials → Create Credentials → OAuth Client ID**:
   - النوع: **Web application**
   - Authorized redirect URIs: حط رابط `oauth/youtube_callback.php` من الجدول فوق — ولو عايز تفعّل
     "تسجيل الدخول بجوجل" في `login.php`/`register.php` (بيستخدم نفس الـ Client ده)، ضيف كمان رابط
     `oauth/google_login_callback.php` كـ Authorized redirect URI تاني لنفس الـ Client
   - انسخ **Client ID** و **Client Secret** — دول اللي هتحطهم في `app/config/config.local.php` تحت `youtube`

### النشر لعملاء حقيقيين (مش بس Test Users)
التطبيق وهو في وضع **Testing** بيشتغل بس مع الإيميلات اللي ضفتها كـ Test Users. عشان أي عميل يقدر
يربط حسابه من غير ما تضيفه يدوي، لازم تقدّم التطبيق لـ **Verification** (زرار "Publish App" ثم طلب
مراجعة للـ Sensitive Scopes). جوجل هتطلب منك فيديو Screencast قصير يوضح إزاي المستخدم بيربط حسابه
وبيستخدم بياناته. المراجعة عادة بتاخد من كام يوم لأسبوعين.

---

## 2. تيك توك (TikTok for Developers)

1. روح على [developers.tiktok.com](https://developers.tiktok.com) وسجّل حساب مطور.
2. **Manage apps → Create an app**.
3. من صفحة التطبيق، أضف المنتجات (Products):
   - **Login Kit**
   - **Content Posting API**
4. اطلب الـ Scopes: `user.info.basic`, `video.publish`, `video.upload`
5. في إعدادات التطبيق، حط الـ **Redirect URI** بتاع تيك توك من الجدول فوق.
6. انسخ **Client Key** و **Client Secret** لملف الإعدادات تحت `tiktok`.

### قيد مهم لازم تعرفه
أي تطبيق جديد على تيك توك بيبدأ **Unaudited** — في الوضع ده، أي فيديو بينشر بيتحط **Private
(يشوفه صاحب الحساب بس)** إجباريًا، مهما كانت إعدادات الخصوصية اللي اخترتها في الموقع. عشان تقدر
تنشر فيديوهات عامة (Public) لعملائك، لازم تقدّم طلب **Audit** من نفس صفحة التطبيق، وتوضح فيه
استخدامك الفعلي للـ API. المراجعة دي بتاخد وقت وممكن تترفض لو الاستخدام مش واضح — جهّز وصف دقيق
لطريقة استخدام الموقع.

---

## 3. انستجرام (عبر Meta / فيسبوك)

انستجرام مالوش تسجيل منفصل — بيتم عن طريق تطبيق فيسبوك (Meta for Developers).

1. روح على [developers.facebook.com](https://developers.facebook.com) → **My Apps → Create App**.
2. اختار نوع التطبيق **Business**.
3. من **Add Products**، أضف **Instagram Graph API** (أو Facebook Login for Business لو مش ظاهر).
4. **App Settings → Basic**: املأ
   - Privacy Policy URL
   - Terms of Service URL
   - App Icon
   - Data Deletion Instructions URL (حط رابط `data-deletion.php` من الجدول فوق)
5. **Facebook Login → Settings**: أضف الـ Redirect URI بتاع انستجرام من الجدول فوق.
6. **App Review → Permissions and Features**: اطلب Advanced Access للصلاحيات:
   - `instagram_content_publish`
   - `pages_show_list`
   - `pages_read_engagement`
   - `business_management`
   لكل صلاحية هتحتاج تسجل **فيديو Screencast** يوضح بالظبط إزاي بتُستخدم في الموقع (رفع فيديو → نشر
   على انستجرام). ميتا بتاخد الفيديوهات دي بجدية — وضّح الخطوات كاملة وميتفوتكش أي جزء.
7. **Meta Business Suite → Business Verification**: هتحتاج توثّق هوية شركتك (مستندات رسمية زي
   السجل التجاري) قبل ما توافق ميتا على بعض الصلاحيات المتقدمة.

### متطلب على كل عميل (مش حاجة تعملها إنت)
لازم كل عميل يعمل الآتي **قبل ما يقدر يربط حسابه بالموقع**:
1. يحوّل حساب الانستجرام بتاعه لـ **Business** أو **Creator** (من إعدادات انستجرام).
2. يربطه بصفحة فيسبوك خاصة بيه (Settings → Linked Accounts → Instagram).
3. يكون هو Admin على الصفحة دي وقت ما بيعمل تسجيل الدخول بفيسبوك في موقعك.

من غير الخطوات دي، خطوة الربط في `connect/instagram.php` هترجع "مفيش حساب انستجرام Business مربوط".

---

## ملخص الجدول الزمني المتوقع

| المنصة | استخدام فوري (تجريبي) | نشر عام لكل العملاء |
|---|---|---|
| يوتيوب | فوري (Test Users بس) | مراجعة جوجل: أيام - أسبوعين |
| تيك توك | فوري (Private فقط) | مراجعة Audit: أسبوع - أسبوعين |
| انستجرام | يحتاج App Review حتى للتجربة الجادة | + Business Verification: أسبوع - شهر |

لحد ما الموافقات دي تخلص، تقدر تجرب الموقع بحسابك الشخصي (كـ Test User / Admin / صاحب التطبيق) على
كل المنصات من غير أي قيود.
