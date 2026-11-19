<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Traits\DetectsArabic;
use Illuminate\Support\Str;

/**
 * Classifies student messages (intent, safety, level, language) using keyword heuristics.
 */
class IntentDetector
{
    use DetectsArabic;

    public function isUnsafeRequest(string $message): bool
    {
        $text = Str::lower($message);

        // Always block — explicit harmful intent regardless of context.
        $directHarmfulIntent = [
            'steal password',
            'steal credentials',
            'credential theft',
            'phishing kit',
            'dump lsass',
            'hack account',
            'hack instagram',
            'hack facebook',
            'hack gmail',
            'payload to hack',
            'exploit this target',
            'سرقة كلمة',
            'سرقة حساب',
            'عدة تصيد',
            'اختراق حساب',
            'تفريغ lsass',
        ];

        // Gaining access to something that is not yours (someone else's Wi-Fi, phone, account).
        // Blocked unless the message is clearly about defending against it.
        $unauthorizedAccess = [
            'hack into',
            'hack wifi',
            'hack a wifi',
            'hack the wifi',
            'crack wifi',
            'crack a wifi',
            'crack the wifi',
            "neighbor's wifi",
            "neighbour's wifi",
            'neighbors wifi',
            'neighbours wifi',
            'hack a phone',
            'hack someone',
            'hack my ex',
            'hack whatsapp',
            'hack snapchat',
            'hack tiktok',
            'hack twitter',
            'spy on my',
            'اختراق واي فاي',
            'اختراق الواي فاي',
            'اختراق شبكة الجيران',
            'واي فاي الجيران',
            'اخترق',
            'أخترق',
            'اهكر',
            'أهكر',
            'تهكير',
            'اختراق جوال',
            'اختراق جهاز',
            'اختراق واتساب',
            'اختراق سناب',
            'تجسس على',
        ];

        // Dangerous capability keywords.
        $dangerousCapability = [
            'credential stuffing',
            'phishing page',
            'aitm phishing',
            'adversary in the middle',
            'mfa bypass',
            '2fa bypass',
            'session hijacking',
            'social engineering attack',
            'malware delivery',
            'malware delivery chain',
            'lsass dumping',
            'keylogger',
            'ransomware',
            'malware',
            'backdoor',
            'reverse shell',
            'bypass authentication',
            'bypass login',
            'privilege escalation',
            'persistence',
            'evade antivirus',
            'disable antivirus',
            'ddos',
            'botnet',
            'صفحة تصيد',
            'تجاوز المصادقة',
            'تجاوز التحقق الثنائي',
            'اختطاف الجلسة',
            'هجوم هندسة اجتماعية',
            'هجوم حجب الخدمة',
            'تصيد',
            'برمجية خبيثة',
            'رانسوم وير',
            'باب خلفي',
            'شل عكسي',
            'تصعيد الصلاحيات',
            'ثبات',
            'تعطيل مضاد',
            'تجاوز تسجيل الدخول',
        ];

        // Offensive action verbs — building/deploying attacks, NOT learning about them.
        // We deliberately exclude generic educational phrases like "what is", "explain",
        // "how does", "example" to preserve the platform's defensive teaching role.
        $offensiveVerbs = [
            'how to build',
            'how to make',
            'how to create',
            'how to deploy',
            'how to launch',
            'how to execute a',
            'steps to create',
            'steps to build',
            'steps to deploy',
            'write me a',
            'create for me',
            'build me a',
            'give me a payload',
            'give me code for',
            'generate a payload',
            'كيف أصنع',
            'كيف اصنع',
            'كيف أبني',
            'كيف ابني',
            'خطوات لبناء',
            'خطوات لإنشاء',
            'اكتب لي كود',
            'أنشئ لي',
            'انشئ لي',
            'اصنع لي',
            'ابني لي',
        ];

        // Defensive intent — if present, the request is safe even if dangerous terms appear.
        $defensiveIntent = [
            'how to detect',
            'how to prevent',
            'how to protect',
            'how to defend',
            'how to identify',
            'how to recognize',
            'what is',
            'what are',
            'explain',
            'definition',
            'understand',
            'awareness',
            'defense',
            'protection',
            'incident response',
            'كيف أكتشف',
            'كيف أحمي',
            'كيف أمنع',
            'كيف أتعرف',
            'ما هو',
            'ما هي',
            'اشرح',
            'تعريف',
            'حماية',
            'دفاع',
        ];

        if ($this->containsAny($text, $directHarmfulIntent)) {
            return true;
        }

        $protective = ['how to protect', 'how to prevent', 'how to defend', 'how to detect', 'protect my', 'secure my',
            'كيف أحمي', 'كيف احمي', 'كيف أمنع', 'كيف امنع', 'أحمي', 'احمي', 'حماية'];

        if ($this->containsAny($text, $unauthorizedAccess) && ! $this->containsAny($text, $protective)) {
            return true;
        }

        $hasDangerous = $this->containsAny($text, $dangerousCapability);
        $hasOffensiveVerb = $this->containsAny($text, $offensiveVerbs);
        $hasDefensiveIntent = $this->containsAny($text, $defensiveIntent);

        // Block only when a dangerous capability is paired with an explicit offensive build/deploy
        // intent AND the message does NOT carry defensive learning intent.
        return $hasDangerous && $hasOffensiveVerb && ! $hasDefensiveIntent;
    }

    /**
     * Like containsAny(), but tolerant of Arabic spelling variants and the definite article
     * ("كلمة المرور" matches "كلمة مرور"), and matches short Latin terms as whole words only
     * (so "cia" does not match inside "especially").
     *
     * @param  array<int, string>  $terms
     */
    public function containsAnyNormalized(string $text, array $terms): bool
    {
        $haystack = $this->normalizeForMatching($text);

        foreach ($terms as $term) {
            $needle = $this->normalizeForMatching($term);

            if ($needle === '') {
                continue;
            }

            if (preg_match('/^[a-z0-9 \'\-]+$/', $needle)) {
                if (preg_match('/(?<![a-z0-9])'.preg_quote($needle, '/').'(?![a-z0-9])/', $haystack)) {
                    return true;
                }
            } elseif (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public function normalizeForMatching(string $text): string
    {
        $text = Str::lower($text);
        $text = (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text); // tashkeel + tatweel
        $text = strtr($text, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه', 'ى' => 'ي']);
        // Drop common attached prefixes: و/ب/ف/ك + ال, لل, ال.
        $text = (string) preg_replace('/(?<![\p{L}])(?:[وبفك]?ال|لل)(?=\p{L}{2,})/u', '', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param  array<int, string>  $terms
     */
    public function containsAny(string $text, array $terms): bool
    {
        return collect($terms)->contains(fn ($term) => str_contains($text, $term));
    }

    public function prefersArabicReply(string $message, array $history): bool
    {
        if ($this->containsArabic($message)) {
            return true;
        }

        // "A", "b)", "ok": too short to tell, so keep the conversation's language.
        if (preg_match_all('/[a-z]/i', $message) >= 3) {
            return false;
        }

        // Prefer the student's own latest message that has a clear language, then the assistant's.
        foreach (array_reverse($history) as $turn) {
            $content = (string) ($turn['content'] ?? '');

            if (($turn['role'] ?? null) === 'user' && ($this->containsArabic($content) || preg_match_all('/[a-z]/i', $content) >= 3)) {
                return $this->containsArabic($content);
            }
        }

        return $this->containsArabic($this->lastAssistantMessage($history) ?? '');
    }

    public function lastAssistantMessage(array $history): ?string
    {
        foreach (array_reverse($history) as $item) {
            if (($item['role'] ?? null) === 'assistant') {
                $content = trim((string) ($item['content'] ?? ''));

                if ($content !== '') {
                    return $content;
                }
            }
        }

        return null;
    }

    public function isGreetingOnly(string $message): bool
    {
        $text = trim(Str::lower($message));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return preg_match('/^(h+i+|hello+|hey+|salam|السلام( عليكم( ورحمة الله( وبركاته)?)?)?|اهلا|أهلا|أهلاً|اهلين|أهلين|مرحبا|مرحباً|هاي|هاى|هلا|يا هلا|ياهلا|هلا والله)$/u', $text) === 1;
    }

    public function isSmallTalk(string $message): bool
    {
        $text = trim(Str::lower($message));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        if ($text === '') {
            return false;
        }

        return $this->containsAny($text, [
            'how are you',
            'how r u',
            'how are u',
            'hows it going',
            'how is it going',
            'whats up',
            'what is up',
            'sup',
            'how do you do',
            'thank you',
            'thanks',
            'thx',
            'good morning',
            'good evening',
            'كيف حالك',
            'كيف الحال',
            'كيفك',
            'كيف الأحوال',
            'كيف الاحوال',
            'شخبارك',
            'شلونك',
            'وش اخبارك',
            'وش أخبارك',
            'ايش اخبارك',
            'اخبارك',
            'أخبارك',
            'ايه الاخبار',
            'ايه الأخبار',
            'إيه الأخبار',
            'عامل ايه',
            'عامل إيه',
            'عاملة ايه',
            'ازيك',
            'إزيك',
            'ازيّك',
            'شكرا',
            'شكراً',
            'مشكور',
            'تسلم',
            'يعطيك العافية',
            'صباح الخير',
            'مساء الخير',
        ]);
    }

    public function hasLearningIntent(string $message): bool
    {
        return $this->isStudyPlanRequest($message)
            || $this->isConceptRequest($message)
            || $this->isResourceRequest($message)
            || $this->isQuizRequest($message)
            || $this->isVideoRequest($message);
    }

    public function isStudyPlanRequest(string $message): bool
    {
        $text = trim(Str::lower($message));

        return collect([
            'plan',
            'roadmap',
            'schedule',
            'learning path',
            'curriculum',
            'study plan',
            'path',
            'خطة',
            'خطه',
            'مسار',
            'جدول',
            'خارطة طريق',
            'خريطه طريق',
            'خريطة طريق',
            'ابغى خطة',
            'أبغى خطة',
            'ابي خطة',
            'أبي خطة',
            'عطني خطة',
            'اعطني خطة',
            'اديني خطة',
            'اديني خطه',
            'ابغى خطه',
            'ابي خطه',
            'عطني خطه',
            'وش الخطة',
            'وش الخطه',
        ])->contains(fn ($term) => str_contains($text, $term));
    }

    public function isGuideRequest(string $message): bool
    {
        if ($this->isStudyPlanRequest($message)) {
            return true;
        }

        $text = trim(Str::lower($message));

        return $this->containsAny($text, [
            'guide',
            'next lesson',
            'where should i start',
            'where do i start',
            'what should i learn next',
            'what should i study',
            'study next',
            'learn next',
            "what's next",
            'what next',
            'after this lesson',
            'next step',
            'وش أدرس',
            'وش ادرس',
            'ايش ادرس',
            'إيش أدرس',
            'بعد هذا الدرس',
            'الدرس الجاي',
            'الخطوة الجاية',
            'الخطوة التالية',
            'learning goal',
            'resources',
            'links',
            'courses',
            'course path',
            'lesson order',
            'beginner',
            'intermediate',
            'expert',
            'مرشد',
            'ابدأ منين',
            'ابدأ من أين',
            'اتعلم ايه بعد كده',
            'أتعلم ايه بعد كده',
            'ايش اتعلم',
            'وش اتعلم',
            'الدرس التالي',
            'ترتيب الدروس',
            'هدفي',
            'روابط',
            'لينكات',
            'مصادر',
            'مراجع',
            'كورسات',
            'مواقع',
            'مبتدئ',
            'متوسط',
            'خبير',
        ]);
    }

    public function isConceptRequest(string $message): bool
    {
        if ($this->isQuizRequest($message)) {
            return true;
        }

        $text = trim(Str::lower($message));

        return $this->containsAny($text, [
            'explain',
            'what is',
            'what are',
            'how does',
            'why does',
            'how do',
            'concept',
            'definition',
            'meaning',
            'compare',
            'difference between',
            'example',
            'simple terms',
            'quiz me',
            'check my understanding',
            'اشرح',
            'ما هو',
            'ما هي',
            'وش يعني',
            'وش معنى',
            'وش هو',
            'وش هي',
            'ايش يعني',
            'إيش يعني',
            'ايش هو',
            'إيش هو',
            'ايش هي',
            'إيش هي',
            'يعني ايه',
            'يعني إيه',
            'يعني وش',
            'مفهوم',
            'تعريف',
            'مثال',
            'الفرق بين',
            'اختبرني',
        ]);
    }

    public function isResourceRequest(string $message): bool
    {
        $text = trim(Str::lower($message));

        return $this->containsAny($text, [
            'links',
            'resources',
            'references',
            'courses',
            'websites',
            'materials',
            'learning materials',
            'روابط',
            'رابط',
            'لينكات',
            'لينك',
            'مصادر',
            'مصدر',
            'مراجع',
            'مرجع',
            'كورسات',
            'كورس',
            'دورات',
            'دورة',
            'مواقع',
            'موقع',
            'اعطني روابط',
            'عطني روابط',
            'اديني لينكات',
            'ابغى مصادر',
            'أبغى مصادر',
            'ابي مصادر',
            'أبي مصادر',
        ]);
    }

    public function isQuizRequest(string $message): bool
    {
        $text = trim(Str::lower($message));

        return $this->containsAny($text, [
            'quiz me',
            'quiz',
            'test me',
            'ask me a question',
            'practice question',
            'اختبرني',
            'اختبار',
            'اسألني سؤال',
            'سؤال تدريب',
        ]);
    }

    public function isVideoRequest(string $message): bool
    {
        $text = trim(Str::lower($message));

        return $this->containsAny($text, [
            'video',
            'videos',
            'watch',
            'youtube',
            'recommend a video',
            'recommend videos',
            'approved video',
            'video links',
            'فيديو',
            'فيديوهات',
            'يوتيوب',
            'أشاهد',
            'اشاهد',
            'رشح فيديو',
            'اقترح فيديو',
            'روابط فيديو',
            'لينكات فيديو',
        ]);
    }

    public function targetAgentFor(string $message): string
    {
        if ($this->isVideoRequest($message)) {
            return 'video';
        }

        if ($this->isGuideRequest($message)) {
            return 'navigation';
        }

        if ($this->isResourceRequest($message)) {
            return 'navigation';
        }

        if ($this->isConceptRequest($message)) {
            return 'explanation';
        }

        return '';
    }

    public function detectLevel(string $text): ?string
    {
        $normalized = Str::lower($text);

        if (preg_match('/\b(beginner|newbie|starter)\b/u', $normalized) === 1 || $this->containsAny($normalized, ['مبتدئ', 'مبتدئة', 'جديد', 'جديدة'])) {
            return 'Beginner';
        }

        if (preg_match('/\b(intermediate)\b/u', $normalized) === 1 || $this->containsAny($normalized, ['متوسط', 'متوسطة'])) {
            return 'Intermediate';
        }

        if (preg_match('/\b(expert|advanced)\b/u', $normalized) === 1 || $this->containsAny($normalized, ['خبير', 'خبيرة', 'متقدم', 'متقدمة'])) {
            return 'Expert';
        }

        return null;
    }

    public function studentLevel(User $user, array $history, string $message): ?string
    {
        $storedLevel = $this->detectLevel((string) $user->learning_level);

        if ($storedLevel) {
            return $storedLevel;
        }

        $sources = [$message];

        foreach (array_reverse($history) as $item) {
            $sources[] = (string) ($item['content'] ?? '');
        }

        $recentPrompts = $user->aiInteractions()
            ->latest()
            ->take(8)
            ->pluck('prompt')
            ->all();

        $sources = array_merge($sources, $recentPrompts);

        foreach ($sources as $source) {
            $level = $this->detectLevel($source);

            if ($level) {
                return $level;
            }
        }

        return null;
    }
}
