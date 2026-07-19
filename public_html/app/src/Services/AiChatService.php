<?php

/**
 * Backend for the AI script-writing chat (script_chat.php).
 *
 * Provider-agnostic: the admin picks the provider + model + API key in admin_settings.php.
 *  - gemini:     Google AI Studio — has a genuinely free tier (aistudio.google.com/apikey).
 *  - openrouter: openrouter.ai — exposes many free community models (ids ending in ":free").
 * "Free" tiers still carry provider rate limits (requests/minute/day) — that's a provider
 * policy, not something this code can lift.
 *
 * The system prompt specializes the assistant in video scripts and injects the user's saved
 * memories, so answers stay personalized across conversations.
 */
class AiChatService
{
    public static function isConfigured(): bool
    {
        return Settings::get('ai_api_key') !== null;
    }

    public static function provider(): string
    {
        return Settings::get('ai_provider', 'gemini');
    }

    public static function model(): string
    {
        $default = self::provider() === 'openrouter' ? 'deepseek/deepseek-chat-v3-0324:free' : 'gemini-2.0-flash';
        return Settings::get('ai_model', $default);
    }

    /**
     * @param array $history [['role' => 'user'|'assistant', 'content' => ...], ...] oldest first
     * @return string assistant reply text
     */
    public static function reply(array $history, array $memories): string
    {
        $apiKey = Crypto::decrypt((string) Settings::get('ai_api_key'));
        $system = self::systemPrompt($memories);

        // Keep the context bounded: long pasted scripts shouldn't blow the request size.
        $history = array_map(function ($m) {
            $m['content'] = mb_substr($m['content'], 0, 12000);
            return $m;
        }, $history);

        return self::provider() === 'openrouter'
            ? self::openRouterReply($apiKey, $system, $history)
            : self::geminiReply($apiKey, $system, $history);
    }

    private static function geminiReply(string $apiKey, string $system, array $history): string
    {
        $contents = array_map(fn ($m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], $history);

        $model = rawurlencode(self::model());
        $response = Http::request(
            'POST',
            "https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=" . rawurlencode($apiKey),
            [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => [
                    'system_instruction' => ['parts' => [['text' => $system]]],
                    'contents' => $contents,
                    'generationConfig' => ['temperature' => 0.8, 'maxOutputTokens' => 8192],
                ],
                'timeout' => 120,
            ]
        );

        if ($response['status'] !== 200) {
            throw new RuntimeException(self::friendlyApiError($response));
        }

        $parts = $response['json']['candidates'][0]['content']['parts'] ?? [];
        $text = trim(implode('', array_column($parts, 'text')));
        if ($text === '') {
            throw new RuntimeException(t('chat.err_empty_reply'));
        }
        return $text;
    }

    private static function openRouterReply(string $apiKey, string $system, array $history): string
    {
        $messages = [['role' => 'system', 'content' => $system]];
        foreach ($history as $m) {
            $messages[] = ['role' => $m['role'], 'content' => $m['content']];
        }

        $response = Http::request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer $apiKey",
                'HTTP-Referer' => App::url(),
                'X-Title' => 'Uploady Script Chat',
            ],
            'json' => [
                'model' => self::model(),
                'messages' => $messages,
                'temperature' => 0.8,
            ],
            'timeout' => 120,
        ]);

        if ($response['status'] !== 200) {
            throw new RuntimeException(self::friendlyApiError($response));
        }

        $text = trim((string) ($response['json']['choices'][0]['message']['content'] ?? ''));
        if ($text === '') {
            throw new RuntimeException(t('chat.err_empty_reply'));
        }
        return $text;
    }

    /** Rate limits and auth failures get a readable Arabic message instead of a raw API dump. */
    private static function friendlyApiError(array $response): string
    {
        if ($response['status'] === 429) {
            return t('chat.err_rate_limited');
        }
        if (in_array($response['status'], [401, 403], true)) {
            return t('chat.err_bad_key');
        }
        $detail = $response['json']['error']['message'] ?? mb_substr($response['body'], 0, 300);
        return sprintf(t('chat.err_api'), $response['status'], $detail);
    }

    private static function systemPrompt(array $memories): string
    {
        $memoryBlock = '';
        if ($memories !== []) {
            $lines = array_map(fn ($m) => '- ' . $m['content'], $memories);
            $memoryBlock = "\n\nمعلومات محفوظة عن المستخدم (استخدمها دائمًا لتخصيص ردودك):\n" . implode("\n", $lines);
        }

        return <<<PROMPT
أنت "كاتب السكربتات" — مساعد متخصص حصريًا في كتابة سكربتات الفيديو للمنصات (يوتيوب، تيك توك، انستجرام ريلز، يوتيوب شورتس). تتحدث بالعربية (وباللهجة المصرية لو المستخدم كتب بها) وتكتب سكربتات جاهزة للتصوير مباشرة.

عندما يطلب المستخدم سكربت فيديو، قدّم دائمًا هذا الهيكل الكامل بعناوين واضحة:

## 🎣 الهوك (أول 3-5 ثواني)
جملة افتتاحية تمنع المشاهد من التمرير — قدّم 2-3 بدائل.

## 🎬 المقدمة
تمهيد سريع يعد المشاهد بقيمة واضحة.

## 📖 المحتوى الكامل
السكربت كاملًا من البداية للنهاية، مقسمًا لمشاهد/فقرات مرقمة، بكلام جاهز للإلقاء حرفيًا (مش نقاط تلخيص)، مع ملاحظات إخراجية بين قوسين [لقطة قريبة / نص على الشاشة / ...] عند الحاجة.

## 🏁 الخاتمة + CTA
إنهاء القصة + دعوة واضحة للتفاعل (متابعة/كومنت/مشاركة).

## 🔗 المصادر
لو السكربت يعتمد على معلومات/أرقام/قصص حقيقية: اذكر مصادرها بروابط. **مهم جدًا: لا تخترع روابط** — لو مش متأكد من الرابط الدقيق اكتب اسم المصدر وقل صراحة "ابحث عنه بهذه الكلمات". الأمانة في المصادر أهم من شكل الرد.

## 📱 تفاصيل النشر لكل منصة
لكل منصة مناسبة (يوتيوب / تيك توك / انستجرام): العنوان المقترح، الوصف، والتاجز — مع مراعاة اختلاف طول وأسلوب كل منصة.

## #️⃣ الهاشتاجات
في آخر الرد دائمًا: قائمة هاشتاجات مناسبة، كل هاشتاج يبدأ بعلامة # (بدون مسافات داخل الهاشتاج — استخدم _ بين الكلمات). لا تقدّر أرقام منافسة من عندك — أداة "الكلمات المفتاحية" في الموقع تعطي أرقام المنافسة الحقيقية.

قواعد عامة:
- لو طلب المستخدم شيئًا غير كتابة/تطوير سكربتات أو أفكار فيديوهات، ساعده باختصار ثم ذكّره بتخصصك.
- لو التفاصيل ناقصة (المنصة؟ المدة؟ الجمهور؟) اسأل سؤالًا أو اثنين قبل الكتابة، أو اكتب نسخة أولى بافتراضات معلنة.
- اقترح تحسينات على أفكار المستخدم بدل مجرد التنفيذ الحرفي.{$memoryBlock}
PROMPT;
    }
}
