<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\Lesson;
use App\Models\User;
use App\Traits\DetectsArabic;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AiAgentService
{
    use DetectsArabic;
    private const AI_RATE_LIMIT_ATTEMPTS = 20;

    private const AI_RATE_LIMIT_DECAY_SECONDS = 60;

    private const AI_DAILY_DECAY_SECONDS = 86400; // 24 hours

    public const AGENTS = [
        'single_tutor' => [
            'name' => 'Cyber Mentor',
            'role' => 'Single AI Agent',
            'prompt_key' => 'agent.single_tutor',
        ],
        'navigation' => [
            'name' => 'Navigation Agent',
            'role' => 'Learning Guide',
            'prompt_key' => 'agent.navigation',
        ],
        'explanation' => [
            'name' => 'Explanation Agent',
            'role' => 'Concept Tutor',
            'prompt_key' => 'agent.explanation',
        ],
        'video' => [
            'name' => 'Video Agent',
            'role' => 'Video Curator',
            'prompt_key' => 'agent.video',
        ],
    ];

    public function __construct(private PromptLoader $promptLoader) {}

    public function respond(User $user, ?Lesson $lesson, string $message, string $agentType, string $version, array $history = []): array
    {
        $agent = self::AGENTS[$agentType] ?? self::AGENTS['single_tutor'];
        $started = microtime(true);
        $content = $this->fallbackResponse($message, $agentType, $lesson, $user);
        $tokens = 0;
        $meta = [];
        $unsafe = $this->isUnsafeRequest($message);
        $messageHash = null;
        $cacheHit = false;

        if (! $this->withinRateLimit($user)) {
            $content = $this->rateLimitResponse($message);
        } elseif (! $this->withinDailyLimit($user)) {
            $content = $this->dailyLimitResponse($message, $user);
        } elseif ($unsafe) {
            $content = $this->safetyResponse($message);
        } elseif ($deterministicResponse = $this->deterministicResponse($message, $agentType, $history, $lesson, $user)) {
            $content = $deterministicResponse;
        } elseif ($scopeResponse = $this->scopeResponse($message, $agentType)) {
            $content = $scopeResponse['message'];
            $meta = $scopeResponse['meta'];
        } else {
            $messageHash = $this->messageHash($message, $agentType, $lesson?->id);
            $cachedResponse = $this->findCachedResponse($messageHash);

            if ($cachedResponse !== null) {
                $content = $cachedResponse;
                $cacheHit = true;
            } else {
                $result = $this->resolveAiResponse(
                    $this->messages($user, $agent['prompt_key'], $lesson, $history, $message),
                    $content,
                    $version,
                );

                $content = $result['content'];
                $tokens = $result['tokens'];
            }
        }

        // Store raw response before personalization so it can be reused as a cache hit
        $rawContent = $content;
        $content = $this->personalizeResponse($content, $message, $user);

        // Persist learning level if detected in this conversation turn.
        $this->maybePersistLevel($user, $message, $history);

        $interaction = AiInteraction::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson?->id,
            'platform_version' => $version,
            'agent_type' => $agentType,
            'message_hash' => $messageHash,
            'prompt' => $message,
            'response' => $rawContent,
            'tokens_used' => $tokens,
            'latency_ms' => (int) ((microtime(true) - $started) * 1000),
            'cache_hit' => $cacheHit,
        ]);

        return [
            'message' => $content,
            'agent' => $agent,
            'interaction_id' => $interaction->id,
            'meta' => $meta,
        ];
    }

    private function personalizeResponse(string $content, string $message, User $user): string
    {
        $name = trim((string) $user->name);

        if ($name === '') {
            return $content;
        }

        if (str_contains(Str::lower($content), Str::lower($name))) {
            return $content;
        }

        $prefix = ($this->containsArabic($message) || $this->containsArabic($content))
            ? "{$name}،"
            : "{$name},";

        return "{$prefix}\n\n{$content}";
    }

    private function isUnsafeRequest(string $message): bool
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

        $hasDangerous      = $this->containsAny($text, $dangerousCapability);
        $hasOffensiveVerb  = $this->containsAny($text, $offensiveVerbs);
        $hasDefensiveIntent = $this->containsAny($text, $defensiveIntent);

        // Block only when a dangerous capability is paired with an explicit offensive build/deploy
        // intent AND the message does NOT carry defensive learning intent.
        return $hasDangerous && $hasOffensiveVerb && ! $hasDefensiveIntent;
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function containsAny(string $text, array $terms): bool
    {
        return collect($terms)->contains(fn ($term) => str_contains($text, $term));
    }

    private function safetyResponse(string $message): string
    {
        if ($this->containsArabic($message)) {
            return 'مرحباً! أنا Cyber Mentor، مساعدك المتخصص في الأمن السيبراني.

لا أستطيع المساعدة في خطوات أو أكواد قد تُستخدم للاختراق أو سرقة الحسابات أو التصيد أو البرمجيات الخبيثة.

**بديل آمن:**
1. أشرح لك الفكرة بشكل دفاعي.
2. أو أوضح طرق الحماية والكشف.
3. أو أقترح تدريباً قانونياً داخل Lab مثل TryHackMe أو Hack The Box Academy.

ما الجانب الآمن الذي تريد أن نركز عليه: الفهم، الحماية، أم التدريب داخل مختبر قانوني؟';
        }

        return 'Hello! I am Cyber Mentor, your specialized cybersecurity study assistant.

I cannot help with steps, code, or instructions that could enable unauthorized access, credential theft, phishing, malware, or real-world harm.

**Safe alternative:**
1. I can explain the concept defensively.
2. I can show prevention and detection methods.
3. I can suggest legal lab practice on platforms like TryHackMe or Hack The Box Academy.

Which safe angle would you like to focus on: understanding, defense, or legal lab practice?';
    }

    private function rateLimitResponse(string $message): string
    {
        if ($this->containsArabic($message)) {
            return 'مرحباً! أنا Cyber Mentor، مساعدك المتخصص في الأمن السيبراني.

وصلنا للحد المؤقت لرسائل الذكاء الاصطناعي. انتظر دقيقة قصيرة ثم أرسل سؤالك مرة أخرى.

لو كان سؤالك عاجلاً، اختصره في نقطة واحدة وسأساعدك فور توفر المحاولة التالية.';
        }

        return 'Hello! I am Cyber Mentor, your specialized cybersecurity study assistant.

You have reached the temporary AI message limit. Please wait about a minute, then send your question again.

If it is urgent, make the next message one focused question and I will help as soon as the limit resets.';
    }

    private function withinRateLimit(User $user): bool
    {
        return RateLimiter::attempt(
            "ai:{$user->id}",
            self::AI_RATE_LIMIT_ATTEMPTS,
            fn () => true,
            self::AI_RATE_LIMIT_DECAY_SECONDS,
        );
    }

    private function withinDailyLimit(User $user): bool
    {
        $limit = config('services.ai.daily_limit', 50);

        return RateLimiter::attempt(
            "ai:daily:{$user->id}",
            $limit,
            fn () => true,
            self::AI_DAILY_DECAY_SECONDS,
        );
    }

    private function dailyLimitResponse(string $message, User $user): string
    {
        $limit = config('services.ai.daily_limit', 50);
        $seconds = RateLimiter::availableIn("ai:daily:{$user->id}");
        $hours = (int) ceil($seconds / 3600);

        if ($this->containsArabic($message)) {
            $hoursLabel = $hours === 1 ? 'ساعة' : 'ساعات';

            return "مرحباً! وصلت للحد اليومي لرسائل الذكاء الاصطناعي ({$limit} رسالة في اليوم).

الحد يُعاد خلال {$hours} {$hoursLabel}.

نصيحة: حاول تدمج أسئلتك في رسالة واحدة عشان تستفيد أكثر من رصيدك اليومي.";
        }

        $hoursLabel = $hours === 1 ? 'hour' : 'hours';

        return "You have reached your daily AI message limit ({$limit} messages per day).

Your limit resets in {$hours} {$hoursLabel}.

Tip: combine related questions into one message to get more out of your daily quota.";
    }

    /**
     * Build a stable hash for a question so identical questions can share a cached response.
     * Hash includes: normalized message + agent type + lesson id.
     */
    private function messageHash(string $message, string $agentType, ?int $lessonId): string
    {
        $normalized = Str::lower(trim($message));
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/[،,؟?!.؛;:]+/u', '', $normalized) ?? $normalized;

        return hash('sha256', $normalized.'|'.$agentType.'|'.($lessonId ?? ''));
    }

    /**
     * Return a previously stored AI response for the same question, or null on miss.
     * Only returns responses that cost tokens (real AI answers) and are not themselves cache hits.
     */
    private function findCachedResponse(string $hash): ?string
    {
        $ttlDays = config('services.ai.cache_ttl_days', 30);

        $response = AiInteraction::where('message_hash', $hash)
            ->where('tokens_used', '>', 0)
            ->where('cache_hit', false)
            ->whereNotNull('response')
            ->where('created_at', '>=', now()->subDays($ttlDays))
            ->latest()
            ->value('response');

        return $response ?: null;
    }

    // containsArabic() is provided by the DetectsArabic trait.

    private function prefersArabicReply(string $message, array $history): bool
    {
        if ($this->containsArabic($message)) {
            return true;
        }

        if (preg_match('/[a-z]/i', $message) === 1) {
            return false;
        }

        return $this->containsArabic($this->lastAssistantMessage($history) ?? '');
    }

    private function isGreetingOnly(string $message): bool
    {
        $text = trim(Str::lower($message));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return preg_match('/^(h+i+|hello+|hey+|salam|السلام( عليكم( ورحمة الله( وبركاته)?)?)?|اهلا|أهلا|أهلاً|اهلين|أهلين|مرحبا|مرحباً|هاي|هاى|هلا|يا هلا|ياهلا|هلا والله)$/u', $text) === 1;
    }

    private function isSmallTalk(string $message): bool
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

    private function hasLearningIntent(string $message): bool
    {
        return $this->isStudyPlanRequest($message)
            || $this->isConceptRequest($message)
            || $this->isResourceRequest($message)
            || $this->isQuizRequest($message)
            || $this->isVideoRequest($message);
    }

    private function smallTalkResponse(string $message, string $agentType, ?User $user): string
    {
        $arabic = $this->containsArabic($message);
        $name = trim((string) $user?->name);
        $displayName = $name !== '' ? $name : ($arabic ? 'صديقي' : 'there');

        if ($arabic) {
            $scope = match ($agentType) {
                'navigation' => 'تحب نبدأ بخطة تعلم، ترتيب الدروس، أو مصادر مفيدة؟',
                'explanation' => 'تحب أشرح لك مفهوماً في الأمن السيبراني أو نراجع فكرة من الدرس؟',
                'video' => 'تحب أرشّح لك فيديو معتمد من هذا الدرس؟',
                default => 'تحب نبدأ بشرح مفهوم، خطة تعلم، مصادر، أو اختبار قصير؟',
            };

            return "الحمد لله تمام، {$displayName}! جاهز أساعدك في الأمن السيبراني.\n\n{$scope}";
        }

        $scope = match ($agentType) {
            'navigation' => 'Want to start with a study plan, lesson order, or useful resources?',
            'explanation' => 'Would you like me to explain a cybersecurity concept or review an idea from the lesson?',
            'video' => 'Would you like me to recommend an approved video from this lesson?',
            default => 'Want to start with a concept explanation, a study plan, resources, or a quick quiz?',
        };

        return "Doing well, thanks {$displayName}! I am ready to help you with cybersecurity.\n\n{$scope}";
    }

    private function deterministicResponse(string $message, string $agentType, array $history, ?Lesson $lesson, User $user): ?string
    {
        if ($this->isGreetingOnly($message)) {
            return $this->fallbackResponse($message, $agentType, $lesson, $user);
        }

        if ($this->isSmallTalk($message) && ! $this->hasLearningIntent($message)) {
            return $this->smallTalkResponse($message, $agentType, $user);
        }

        if (
            in_array($agentType, ['single_tutor', 'navigation'], true)
            && $this->isStudyPlanRequest($message)
            && ! $this->studentLevel($user, $history, $message)
        ) {
            return $this->fallbackResponse($message, $agentType, $lesson, $user);
        }

        if (in_array($agentType, ['single_tutor', 'explanation'], true)) {
            if ($this->isAnotherQuizRequest($message, $history)) {
                return $this->quizQuestionResponse($message, $lesson, $history);
            }

            if ($this->isQuizDeclineRequest($message, $history)) {
                return $this->quizDeclineResponse($message, $history);
            }

            if ($response = $this->quizEvaluationResponse($message, $history)) {
                return $response;
            }

            if ($this->isQuizRequest($message)) {
                return $this->quizQuestionResponse($message, $lesson, $history);
            }
        }

        if (in_array($agentType, ['single_tutor', 'navigation', 'explanation'], true) && $this->isResourceRequest($message)) {
            return $this->resourceLinksResponse($message, $agentType);
        }

        if ($agentType === 'video' && $this->isResourceRequest($message)) {
            return $this->videoFallbackResponse($lesson, $message);
        }

        return null;
    }

    private function isStudyPlanRequest(string $message): bool
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

    private function isGuideRequest(string $message): bool
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

    private function isAnotherQuizRequest(string $message, array $history): bool
    {
        $assistantMessage = $this->lastAssistantMessage($history);

        if (! $assistantMessage) {
            return false;
        }

        $offeredAnotherQuestion = str_contains($assistantMessage, 'Would you like another question?')
            || str_contains($assistantMessage, 'هل تريد سؤالاً آخر؟');

        if (! $offeredAnotherQuestion) {
            return false;
        }

        $text = trim(Str::lower($message));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return preg_match('/\b(yes|yeah|yep|sure|ok|okay|another|next)\b/u', $text) === 1
            || $this->containsAny($text, [
                'another question',
                'next question',
                'نعم',
                'ايوه',
                'أيوه',
                'اه',
                'تمام',
                'سؤال تاني',
                'سؤال ثاني',
                'سؤال آخر',
                'السؤال التالي',
            ]);
    }

    private function lastAssistantOfferedAnotherQuestion(array $history): bool
    {
        $assistantMessage = $this->lastAssistantMessage($history);

        if (! $assistantMessage) {
            return false;
        }

        return str_contains($assistantMessage, 'Would you like another question?')
            || str_contains($assistantMessage, 'هل تريد سؤالاً آخر؟');
    }

    private function isQuizDeclineRequest(string $message, array $history): bool
    {
        if (! $this->lastAssistantOfferedAnotherQuestion($history)) {
            return false;
        }

        $text = trim(Str::lower($message));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        if ($text === '') {
            return false;
        }

        return preg_match('/\b(no|nope|nah|stop|done|later|not now|no thanks|enough)\b/u', $text) === 1
            || $this->containsAny($text, [
                'لا',
                'لأ',
                'مش دلوقتي',
                'مش دلوقت',
                'خلاص',
                'كفاية',
                'كفايه',
                'يكفي',
                'بعدين',
                'لاحقا',
                'لاحقاً',
                'مش عايز',
                'ما ابغى',
                'ما أبغى',
                'ما ابي',
                'لا شكرا',
                'لا شكراً',
            ]);
    }

    private function quizDeclineResponse(string $message, array $history): string
    {
        if ($this->prefersArabicReply($message, $history)) {
            return 'تمام، خلصنا الاختبار. لو حابب نكمل، أقدر أشرح لك مفهوماً، أرشّح مصادر، أو أبني لك خطة تعلم. وش تحب نسوي؟';
        }

        return 'No problem, we will stop the quiz here. Whenever you are ready, I can explain a concept, suggest resources, or build a study plan. What would you like to do next?';
    }

    private function isConceptRequest(string $message): bool
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

    private function isResourceRequest(string $message): bool
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

    private function isQuizRequest(string $message): bool
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

    private function isVideoRequest(string $message): bool
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

    /**
     * @return array{message: string, meta: array<string, mixed>}|null
     */
    private function scopeResponse(string $message, string $agentType): ?array
    {
        if ($agentType === 'single_tutor' || $this->isGreetingOnly($message)) {
            return null;
        }

        return match ($agentType) {
            'navigation' => ($this->isGuideRequest($message) || $this->isResourceRequest($message))
                ? null
                : $this->agentRedirectResponse($message, 'navigation', $this->targetAgentFor($message)),
            'explanation' => ($this->isConceptRequest($message) || $this->isResourceRequest($message))
                ? null
                : $this->agentRedirectResponse($message, 'explanation', $this->targetAgentFor($message)),
            'video' => $this->isVideoRequest($message)
                ? null
                : $this->agentRedirectResponse($message, 'video', $this->targetAgentFor($message)),
            default => null,
        };
    }

    private function targetAgentFor(string $message): string
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

    /**
     * @return array{message: string, meta: array<string, mixed>}
     */
    private function agentRedirectResponse(string $message, string $currentAgent, string $targetAgent): array
    {
        $agents = [
            'navigation' => ['en' => 'Guide', 'ar' => 'Guide', 'scope_en' => 'plans, roadmaps, schedules, learning resources, and lesson navigation', 'scope_ar' => 'الخطط، المسارات، الجداول، مصادر التعلم، والتنقل بين الدروس'],
            'explanation' => ['en' => 'Tutor', 'ar' => 'Tutor', 'scope_en' => 'cybersecurity concepts, definitions, comparisons, safe examples, and concept links', 'scope_ar' => 'شرح المفاهيم، التعاريف، المقارنات، الأمثلة الآمنة، وروابط المفاهيم'],
            'video' => ['en' => 'Video', 'ar' => 'Video', 'scope_en' => 'approved videos embedded in this lesson', 'scope_ar' => 'الفيديوهات المعتمدة داخل هذا الدرس فقط'],
        ];

        $target = $agents[$targetAgent] ?? ['en' => 'the matching tab', 'ar' => 'التبويب المناسب'];
        $current = $agents[$currentAgent] ?? $agents['navigation'];

        if ($this->containsArabic($message)) {
            $content = "أنا تبويب {$current['ar']}، ودوري هنا محدود بـ {$current['scope_ar']}.\n\nافتح تبويب {$target['ar']} لهذا النوع من الطلبات، أو أعد صياغة سؤالك داخل نطاق {$current['ar']}.";
        } else {
            $content = "I am the {$current['en']} agent, so I only handle {$current['scope_en']}.\n\nSwitch to {$target['en']} for this request, or rephrase it as a {$current['en']} task.";
        }

        return [
            'message' => $content,
            'meta' => [
                'redirect' => true,
                'current_agent' => $currentAgent,
                'target_agent' => $targetAgent ?: null,
                'target_label' => $target['en'],
                'target_label_ar' => $target['ar'],
            ],
        ];
    }

    private function resourceLinksResponse(string $message, string $agentType): string
    {
        if ($this->containsArabic($message)) {
            $intro = match ($agentType) {
                'navigation' => 'أكيد. هذه روابط مناسبة تبني عليها مسارك في الأمن السيبراني:',
                'explanation' => 'أكيد. هذه روابط تساعدك تفهم مفاهيم الأمن السيبراني بشكل عملي وآمن:',
                default => 'أكيد. هذه روابط مفيدة وموثوقة لتعلم الأمن السيبراني:',
            };

            return "{$intro}\n\n"
                ."1. **OWASP Top 10** - أساسيات مخاطر تطبيقات الويب.\nhttps://owasp.org/www-project-top-ten/\n\n"
                ."2. **PortSwigger Web Security Academy** - تدريبات قانونية وآمنة على أمن الويب.\nhttps://portswigger.net/web-security\n\n"
                ."3. **MDN Web Security** - مفاهيم أمن الويب من الأساسيات.\nhttps://developer.mozilla.org/en-US/docs/Web/Security\n\n"
                ."4. **TryHackMe** - مسارات عملية للمبتدئين والمتوسطين.\nhttps://tryhackme.com/\n\n"
                ."5. **Hack The Box Academy** - دروس عملية منظمة في الأمن السيبراني.\nhttps://academy.hackthebox.com/\n\n"
                .'خلّنا نبدأ صح: تبغى روابط للمبتدئين، أمن الويب، الشبكات، ولا Linux؟';
        }

        $intro = match ($agentType) {
            'navigation' => 'Sure. Here are reliable links you can use to structure your cybersecurity path:',
            'explanation' => 'Sure. Here are reliable links for understanding cybersecurity concepts safely:',
            default => 'Sure. Here are reliable cybersecurity learning links:',
        };

        return "{$intro}\n\n"
            ."1. **OWASP Top 10** - core web application risks.\nhttps://owasp.org/www-project-top-ten/\n\n"
            ."2. **PortSwigger Web Security Academy** - legal, hands-on web security labs.\nhttps://portswigger.net/web-security\n\n"
            ."3. **MDN Web Security** - web security fundamentals.\nhttps://developer.mozilla.org/en-US/docs/Web/Security\n\n"
            ."4. **TryHackMe** - beginner and intermediate guided paths.\nhttps://tryhackme.com/\n\n"
            ."5. **Hack The Box Academy** - structured practical cybersecurity modules.\nhttps://academy.hackthebox.com/\n\n"
            .'Which area do you want links for next: beginner basics, web security, networking, or Linux?';
    }

    private function quizQuestionResponse(string $message, ?Lesson $lesson, array $history = []): string
    {
        $quiz = $this->quizForLesson($lesson, $history);
        $useArabic = $this->prefersArabicReply($message, $history);

        if ($useArabic) {
            return "**اختبار سريع**\n\n{$quiz['question_ar']}\n{$quiz['options_ar'][0]}\n{$quiz['options_ar'][1]}\n{$quiz['options_ar'][2]}\n{$quiz['options_ar'][3]}\n\nاكتب أ، ب، ج، أو د وسأخبرك هل إجابتك صحيحة أم لا.";
        }

        return "**Quick quiz**\n\n{$quiz['question_en']}\n{$quiz['options_en'][0]}\n{$quiz['options_en'][1]}\n{$quiz['options_en'][2]}\n{$quiz['options_en'][3]}\n\nReply with A, B, C, or D and I will tell you whether it is correct.";
    }

    private function quizEvaluationResponse(string $message, array $history): ?string
    {
        if ($this->lastAssistantOfferedAnotherQuestion($history)) {
            return null;
        }

        $quiz = $this->lastQuizFromHistory($history);

        if (! $quiz) {
            return null;
        }

        $answer = $this->normalizeQuizAnswer($message, $quiz);
        $useArabic = $this->prefersArabicReply($message, $history);

        if (! $answer) {
            return $useArabic
                ? 'اكتب إجابتك بصيغة أ، ب، ج، أو د عشان أقدر أصححها لك.'
                : 'Please answer with A, B, C, or D so I can grade it clearly.';
        }

        $isCorrect = $answer === $quiz['correct'];

        if ($useArabic) {
            if ($isCorrect) {
                return "صحيح. الإجابة {$quiz['correct_ar']}.\n\n{$quiz['explanation_ar']}\n\nهل تريد سؤالاً آخر؟";
            }

            return "ليست صحيحة. الإجابة الصحيحة هي {$quiz['correct_ar']}.\n\n{$quiz['explanation_ar']}\n\nهل تريد سؤالاً آخر؟";
        }

        if ($isCorrect) {
            return "Correct. The answer is {$quiz['correct']}.\n\n{$quiz['explanation_en']}\n\nWould you like another question?";
        }

        return "Not quite. The correct answer is {$quiz['correct']}.\n\n{$quiz['explanation_en']}\n\nWould you like another question?";
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastQuizFromHistory(array $history): ?array
    {
        foreach (array_reverse($history) as $item) {
            if (($item['role'] ?? null) !== 'assistant') {
                continue;
            }

            $content = trim((string) ($item['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            foreach ($this->quizBank() as $quiz) {
                if (
                    str_contains($content, $quiz['question_en'])
                    || str_contains($content, $quiz['question_ar'])
                ) {
                    return $quiz;
                }
            }
        }

        return null;
    }

    private function lastAssistantMessage(array $history): ?string
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

    /**
     * @param  array<string, mixed>  $quiz
     */
    private function normalizeQuizAnswer(string $message, array $quiz): ?string
    {
        $text = trim(Str::lower($message));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        $letterMap = [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C',
            'd' => 'D',
            'أ' => 'A',
            'ا' => 'A',
            'ب' => 'B',
            'ج' => 'C',
            'د' => 'D',
        ];

        $firstToken = Str::before($text, ' ');

        if (isset($letterMap[$firstToken])) {
            return $letterMap[$firstToken];
        }

        foreach ($quiz['acceptable'] as $acceptable) {
            if (str_contains($text, Str::lower($acceptable))) {
                return $quiz['correct'];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Pick the most relevant quiz question for the given lesson context.
     * Falls back gracefully when no topic match is found.
     */
    private function quizForLesson(?Lesson $lesson, array $history = []): array
    {
        $context = Str::lower(
            ($lesson?->title ?? '').' '.
            strip_tags($lesson?->summary ?? '').' '.
            strip_tags($lesson?->content ?? '')
        );

        $bank      = $this->quizBank();
        $askedKeys = $this->askedQuizKeysFromHistory($history);

        // Topic → quiz key mapping (ordered by specificity).
        $topicMap = [
            'dns'               => 'dns-purpose',
            'domain name'       => 'dns-purpose',
            'cia'               => 'cia-triad',
            'confidentiality'   => 'cia-triad',
            'integrity'         => 'cia-triad',
            'availability'      => 'cia-triad',
            'mfa'               => 'mfa-purpose',
            'multi-factor'      => 'mfa-purpose',
            'two-factor'        => 'mfa-purpose',
            'phishing'          => 'phishing-definition',
            'social engineering' => 'social-engineering',
            'pretexting'        => 'social-engineering',
            'https'             => 'https-purpose',
            'tls'               => 'https-purpose',
            'ssl'               => 'https-purpose',
            'firewall'          => 'firewall-function',
            'sql'               => 'owasp-sqli',
            'injection'         => 'owasp-sqli',
            'owasp'             => 'owasp-sqli',
            'vpn'               => 'vpn-purpose',
            'privilege'         => 'least-privilege',
            'least privilege'   => 'least-privilege',
            'incident response' => 'incident-response',
            'zero trust'        => 'zero-trust',
            'zero-trust'        => 'zero-trust',
            'password'          => 'password-policy',
            'patch'             => 'patch-management',
            'update'            => 'patch-management',
            'vulnerability'     => 'patch-management',
            'encrypt'           => 'encryption-types',
            'asymmetric'        => 'encryption-types',
            'symmetric'         => 'encryption-types',
            'rsa'               => 'encryption-types',
            'aes'               => 'encryption-types',
        ];

        // Find the first matching quiz that has not been asked yet.
        foreach ($topicMap as $keyword => $quizKey) {
            if (str_contains($context, $keyword) && ! in_array($quizKey, $askedKeys, true) && isset($bank[$quizKey])) {
                return $bank[$quizKey];
            }
        }

        // Round-robin through the full bank, skipping already-asked questions.
        foreach ($bank as $key => $quiz) {
            if (! in_array($key, $askedKeys, true)) {
                return $quiz;
            }
        }

        // All questions have been asked — restart from the beginning.
        return reset($bank);
    }

    /**
     * @return array<int, string>
     */
    private function askedQuizKeysFromHistory(array $history): array
    {
        $asked = [];

        foreach ($history as $item) {
            if (($item['role'] ?? null) !== 'assistant') {
                continue;
            }

            $content = trim((string) ($item['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            foreach ($this->quizBank() as $key => $quiz) {
                if (
                    str_contains($content, $quiz['question_en'])
                    || str_contains($content, $quiz['question_ar'])
                ) {
                    $asked[] = $key;
                }
            }
        }

        return array_values(array_unique($asked));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function quizBank(): array
    {
        return [
            'dns-purpose' => [
                'question_en' => 'What does DNS primarily do?',
                'question_ar' => 'ما الوظيفة الأساسية لـ DNS؟',
                'options_en'  => ['A. Encrypts files on disk', 'B. Translates domain names to IP addresses', 'C. Blocks all phishing emails automatically', 'D. Stores user passwords'],
                'options_ar'  => ['أ. تشفير الملفات على القرص', 'ب. ترجمة أسماء النطاقات إلى عناوين IP', 'ج. منع كل رسائل التصيد تلقائياً', 'د. تخزين كلمات مرور المستخدمين'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['domain names', 'ip address', 'ip addresses', 'dns names', 'أسماء النطاقات', 'عناوين ip'],
                'explanation_en' => 'DNS maps human-readable domain names to IP addresses so browsers and apps can find the right server.',
                'explanation_ar' => 'DNS يربط أسماء النطاقات المفهومة للبشر بعناوين IP حتى يعرف المتصفح أو التطبيق الخادم الصحيح.',
            ],
            'cia-triad' => [
                'question_en' => 'Which three properties make up the CIA triad?',
                'question_ar' => 'ما العناصر الثلاثة التي تكوّن نموذج CIA Triad؟',
                'options_en'  => ['A. Code, Identity, Access', 'B. Confidentiality, Integrity, Availability', 'C. Capture, Inspect, Alert', 'D. Cloud, Internet, Authentication'],
                'options_ar'  => ['أ. الكود، الهوية، الوصول', 'ب. السرية، السلامة، التوافر', 'ج. الالتقاط، الفحص، التنبيه', 'د. السحابة، الإنترنت، المصادقة'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['confidentiality', 'integrity', 'availability', 'سرية', 'سلامة', 'توافر'],
                'explanation_en' => 'The CIA triad covers Confidentiality (protect data from unauthorized access), Integrity (keep data accurate), and Availability (keep systems accessible).',
                'explanation_ar' => 'CIA Triad يركز على السرية (حماية البيانات من الوصول غير المصرح)، السلامة (الحفاظ على دقة البيانات)، والتوافر (ضمان الوصول للأنظمة).',
            ],
            'mfa-purpose' => [
                'question_en' => 'What is the main purpose of MFA?',
                'question_ar' => 'ما الهدف الأساسي من MFA؟',
                'options_en'  => ['A. Make passwords public', 'B. Add another verification factor beyond the password', 'C. Disable account monitoring', 'D. Replace all security training'],
                'options_ar'  => ['أ. جعل كلمات المرور عامة', 'ب. إضافة عامل تحقق آخر بجانب كلمة المرور', 'ج. تعطيل مراقبة الحسابات', 'د. استبدال كل التدريب الأمني'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['another verification factor', 'second factor', 'multi factor', 'عامل تحقق', 'عامل ثاني'],
                'explanation_en' => 'MFA reduces account takeover risk by requiring a second proof of identity beyond just the password.',
                'explanation_ar' => 'MFA يقلل خطر الاستيلاء على الحساب لأنه يطلب دليلاً إضافياً على الهوية بجانب كلمة المرور.',
            ],
            'phishing-definition' => [
                'question_en' => 'Which best describes a phishing attack?',
                'question_ar' => 'أيٌّ من التالي يصف هجوم التصيد (Phishing) بشكل أدق؟',
                'options_en'  => ['A. Overloading a server with traffic', 'B. Tricking users into revealing credentials via fake messages', 'C. Scanning for open ports', 'D. Encrypting files for ransom'],
                'options_ar'  => ['أ. إغراق خادم بحركة المرور', 'ب. خداع المستخدمين للكشف عن بياناتهم عبر رسائل مزيفة', 'ج. فحص المنافذ المفتوحة', 'د. تشفير الملفات طلباً للفدية'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['tricking', 'fake', 'credentials', 'خداع', 'مزيفة', 'بيانات'],
                'explanation_en' => 'Phishing is a social engineering attack where attackers impersonate trusted entities to steal credentials or install malware.',
                'explanation_ar' => 'التصيد هجوم هندسة اجتماعية يقوم فيه المهاجم بانتحال هوية جهة موثوقة لسرقة بيانات الدخول أو تثبيت برمجيات خبيثة.',
            ],
            'https-purpose' => [
                'question_en' => 'What does HTTPS protect compared to HTTP?',
                'question_ar' => 'ما الذي يضيفه HTTPS مقارنةً بـ HTTP؟',
                'options_en'  => ['A. Faster page loads', 'B. Encrypted and authenticated connection between client and server', 'C. Larger file uploads', 'D. Automatic login'],
                'options_ar'  => ['أ. تحميل أسرع للصفحات', 'ب. اتصال مشفر وموثّق بين العميل والخادم', 'ج. رفع ملفات أكبر', 'د. تسجيل دخول تلقائي'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['encrypted', 'authentication', 'tls', 'ssl', 'مشفر', 'تشفير'],
                'explanation_en' => 'HTTPS uses TLS to encrypt data in transit and verify the server\'s identity, preventing eavesdropping and tampering.',
                'explanation_ar' => 'HTTPS يستخدم TLS لتشفير البيانات أثناء النقل والتحقق من هوية الخادم، مما يمنع التنصت والتلاعب بالبيانات.',
            ],
            'firewall-function' => [
                'question_en' => 'What is the primary job of a firewall?',
                'question_ar' => 'ما الوظيفة الأساسية لجدار الحماية (Firewall)؟',
                'options_en'  => ['A. Encrypt hard drive data', 'B. Filter network traffic based on rules', 'C. Back up databases automatically', 'D. Generate strong passwords'],
                'options_ar'  => ['أ. تشفير بيانات القرص الصلب', 'ب. تصفية حركة الشبكة بناءً على قواعد', 'ج. النسخ الاحتياطي للقواعد تلقائياً', 'د. توليد كلمات مرور قوية'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['filter', 'traffic', 'rules', 'block', 'تصفية', 'حركة', 'قواعد'],
                'explanation_en' => 'A firewall inspects incoming and outgoing network traffic and blocks packets that violate defined security rules.',
                'explanation_ar' => 'جدار الحماية يفحص حركة الشبكة الواردة والصادرة ويحجب الحزم التي تنتهك قواعد الأمان المحددة.',
            ],
            'owasp-sqli' => [
                'question_en' => 'SQL Injection attacks target which layer of an application?',
                'question_ar' => 'هجمات SQL Injection تستهدف أي طبقة من التطبيق؟',
                'options_en'  => ['A. The CSS styling layer', 'B. The database query layer', 'C. The DNS resolver', 'D. The TLS certificate'],
                'options_ar'  => ['أ. طبقة CSS', 'ب. طبقة استعلامات قاعدة البيانات', 'ج. محلل DNS', 'د. شهادة TLS'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['database', 'query', 'sql', 'قاعدة البيانات', 'استعلام'],
                'explanation_en' => 'SQL Injection inserts malicious SQL into user input to manipulate the database. Prevention: use parameterized queries and prepared statements.',
                'explanation_ar' => 'SQL Injection تحقن كوداً SQL خبيثاً في مدخلات المستخدم للتلاعب بقاعدة البيانات. الحماية: استخدم Parameterized Queries وPrepared Statements.',
            ],
            'vpn-purpose' => [
                'question_en' => 'What is the primary security benefit of a VPN?',
                'question_ar' => 'ما الفائدة الأمنية الأساسية لـ VPN؟',
                'options_en'  => ['A. Speeds up your internet connection', 'B. Encrypts traffic between your device and the VPN server', 'C. Permanently blocks all ads', 'D. Gives admin access to remote systems'],
                'options_ar'  => ['أ. يسرّع اتصالك بالإنترنت', 'ب. يشفر حركة البيانات بين جهازك وخادم VPN', 'ج. يمنع الإعلانات بشكل دائم', 'د. يمنح وصولاً إدارياً للأنظمة البعيدة'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['encrypt', 'tunnel', 'privacy', 'يشفر', 'تشفير', 'خصوصية'],
                'explanation_en' => 'A VPN creates an encrypted tunnel for your traffic, protecting it from interception on untrusted networks like public Wi-Fi.',
                'explanation_ar' => 'الـ VPN ينشئ نفقاً مشفراً لحركة بياناتك، يحميها من الاعتراض على الشبكات غير الموثوقة كشبكات Wi-Fi العامة.',
            ],
            'least-privilege' => [
                'question_en' => 'Which principle states that users and systems should have only the minimum access they need?',
                'question_ar' => 'أي مبدأ ينص على أن المستخدمين والأنظمة يجب أن يمتلكوا الحد الأدنى من الصلاحيات اللازمة فقط؟',
                'options_en'  => ['A. Defense in Depth', 'B. Principle of Least Privilege', 'C. Zero Trust', 'D. Need to Share'],
                'options_ar'  => ['أ. الدفاع المتعمق', 'ب. مبدأ الامتياز الأدنى', 'ج. الثقة الصفرية', 'د. مبدأ المشاركة'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['least privilege', 'minimum access', 'الامتياز الأدنى', 'الحد الأدنى'],
                'explanation_en' => 'Least Privilege limits the damage a compromised account or system can do by ensuring it only has access to what it truly needs.',
                'explanation_ar' => 'مبدأ الامتياز الأدنى يقلل الضرر الناجم عن اختراق حساب أو نظام بضمان أن له صلاحيات ما يحتاجه فعلاً فقط.',
            ],
            'incident-response' => [
                'question_en' => 'What is the correct first step of an incident response plan?',
                'question_ar' => 'ما الخطوة الأولى الصحيحة في خطة الاستجابة للحوادث؟',
                'options_en'  => ['A. Eradication', 'B. Recovery', 'C. Preparation', 'D. Containment'],
                'options_ar'  => ['أ. الاستئصال', 'ب. الاسترداد', 'ج. التحضير', 'د. الاحتواء'],
                'correct'     => 'C',
                'correct_ar'  => 'ج',
                'acceptable'  => ['preparation', 'prepare', 'planning', 'التحضير', 'الاستعداد'],
                'explanation_en' => 'The six NIST incident response phases are: Preparation → Identification → Containment → Eradication → Recovery → Lessons Learned. Preparation comes first.',
                'explanation_ar' => 'مراحل الاستجابة للحوادث الستة وفق NIST: التحضير ← التعرف ← الاحتواء ← الاستئصال ← الاسترداد ← الدروس المستفادة. التحضير يأتي أولاً.',
            ],
            'zero-trust' => [
                'question_en' => 'What is the core principle of Zero Trust security?',
                'question_ar' => 'ما المبدأ الجوهري لنموذج الأمان Zero Trust؟',
                'options_en'  => ['A. Trust everyone inside the network perimeter', 'B. Never trust, always verify — every request must be authenticated', 'C. Disable all firewalls to improve speed', 'D. Share credentials across teams for efficiency'],
                'options_ar'  => ['أ. ثق بكل من هو داخل محيط الشبكة', 'ب. لا تثق أبداً، تحقق دائماً — كل طلب يجب مصادقته', 'ج. تعطيل جدران الحماية لتحسين السرعة', 'د. مشاركة بيانات الاعتماد عبر الفرق لزيادة الكفاءة'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['never trust', 'always verify', 'لا تثق', 'تحقق دائماً'],
                'explanation_en' => 'Zero Trust assumes breach and requires continuous verification of every user and device, inside or outside the network perimeter.',
                'explanation_ar' => 'Zero Trust يفترض أن الاختراق حادث ويتطلب التحقق المستمر من كل مستخدم وجهاز سواء كانوا داخل أو خارج الشبكة.',
            ],
            'password-policy' => [
                'question_en' => 'Which password policy practice provides the MOST security benefit?',
                'question_ar' => 'أي ممارسة في سياسة كلمات المرور توفر أكبر فائدة أمنية؟',
                'options_en'  => ['A. Require changing passwords every 30 days', 'B. Use long passphrases (16+ characters) with no forced rotation', 'C. Allow reusing the last 3 passwords', 'D. Store passwords in a shared spreadsheet'],
                'options_ar'  => ['أ. إلزام تغيير كلمات المرور كل 30 يوماً', 'ب. استخدام عبارات مرور طويلة (16+ حرف) دون تدوير قسري', 'ج. السماح بإعادة استخدام آخر 3 كلمات مرور', 'د. تخزين كلمات المرور في ملف مشترك'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['long', 'passphrase', '16', 'طويلة', 'عبارة مرور'],
                'explanation_en' => 'Long passphrases are harder to crack than short complex passwords. Frequent forced rotation often leads to weaker, predictable passwords.',
                'explanation_ar' => 'العبارات الطويلة أصعب في الاختراق من كلمات المرور القصيرة المعقدة. التدوير القسري المتكرر يؤدي غالباً لكلمات مرور أضعف وأكثر توقعاً.',
            ],
            'social-engineering' => [
                'question_en' => 'A caller claims to be IT support and asks for your password to "fix an issue." This is an example of:',
                'question_ar' => 'يدّعي متصل أنه دعم تقني ويطلب كلمة مرورك لـ "إصلاح مشكلة". هذا مثال على:',
                'options_en'  => ['A. Brute force attack', 'B. Pretexting (a social engineering technique)', 'C. SQL Injection', 'D. Man-in-the-middle attack'],
                'options_ar'  => ['أ. هجوم القوة الغاشمة', 'ب. التذرع (أسلوب من أساليب الهندسة الاجتماعية)', 'ج. SQL Injection', 'د. هجوم الوسيط'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['pretexting', 'social engineering', 'تذرع', 'هندسة اجتماعية'],
                'explanation_en' => 'Pretexting is creating a fabricated scenario to manipulate someone into revealing information. Legitimate IT support never asks for passwords.',
                'explanation_ar' => 'التذرع هو اختلاق سيناريو مزيف للتلاعب بشخص ما وإقناعه بالكشف عن معلومات. الدعم التقني الشرعي لا يطلب كلمات المرور أبداً.',
            ],
            'patch-management' => [
                'question_en' => 'Why is timely patch management critical for security?',
                'question_ar' => 'لماذا تُعدّ إدارة التحديثات والتصحيحات في الوقت المناسب أمراً حيوياً للأمن؟',
                'options_en'  => ['A. Patches add new features users want', 'B. Unpatched systems expose known vulnerabilities that attackers actively exploit', 'C. Patching only improves performance, not security', 'D. Patches are optional and only for enterprise systems'],
                'options_ar'  => ['أ. التحديثات تضيف ميزات يريدها المستخدمون', 'ب. الأنظمة غير المُحدَّثة تكشف ثغرات معروفة يستغلها المهاجمون بنشاط', 'ج. التحديثات تحسّن الأداء فقط لا الأمان', 'د. التحديثات اختيارية للأنظمة المؤسسية فقط'],
                'correct'     => 'B',
                'correct_ar'  => 'ب',
                'acceptable'  => ['unpatched', 'vulnerabilities', 'exploit', 'ثغرات', 'غير محدّثة'],
                'explanation_en' => 'Most breaches exploit known, already-patched vulnerabilities. Keeping systems updated closes those doors before attackers walk through them.',
                'explanation_ar' => 'معظم الاختراقات تستغل ثغرات معروفة وتم تصحيحها بالفعل. إبقاء الأنظمة محدّثة يغلق تلك الأبواب قبل أن يلجها المهاجمون.',
            ],
            'encryption-types' => [
                'question_en' => 'Which statement correctly describes the difference between symmetric and asymmetric encryption?',
                'question_ar' => 'أي عبارة تصف الفرق بشكل صحيح بين التشفير المتماثل وغير المتماثل؟',
                'options_en'  => ['A. Symmetric uses one shared key; asymmetric uses a public/private key pair', 'B. Symmetric is slower than asymmetric for all use cases', 'C. Asymmetric uses one shared key; symmetric uses a key pair', 'D. Both use the same algorithm but different key sizes'],
                'options_ar'  => ['أ. المتماثل يستخدم مفتاحاً مشتركاً واحداً؛ غير المتماثل يستخدم زوج مفاتيح عام/خاص', 'ب. المتماثل أبطأ من غير المتماثل في جميع الحالات', 'ج. غير المتماثل يستخدم مفتاحاً مشتركاً؛ المتماثل يستخدم زوج مفاتيح', 'د. كلاهما يستخدم نفس الخوارزمية بأحجام مفاتيح مختلفة'],
                'correct'     => 'A',
                'correct_ar'  => 'أ',
                'acceptable'  => ['symmetric', 'shared key', 'public', 'private', 'متماثل', 'مفتاح مشترك', 'عام', 'خاص'],
                'explanation_en' => 'Symmetric encryption (AES) uses one shared secret key — fast but key distribution is hard. Asymmetric (RSA) uses a public key to encrypt and a private key to decrypt — great for key exchange.',
                'explanation_ar' => 'التشفير المتماثل (AES) يستخدم مفتاحاً سرياً مشتركاً — سريع لكن توزيع المفتاح صعب. غير المتماثل (RSA) يستخدم مفتاحاً عاماً للتشفير وخاصاً لفك التشفير — ممتاز لتبادل المفاتيح.',
            ],
        ];
    }

    private function messages(User $user, string $promptKey, ?Lesson $lesson, array $history, string $message): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->promptLoader->get('core.policy')],
            ['role' => 'system', 'content' => $this->promptLoader->get('core.behavior')],
            ['role' => 'system', 'content' => $this->studentContext($user, $history, $message)],
            ['role' => 'system', 'content' => $this->promptLoader->get($promptKey)],
            ['role' => 'system', 'content' => $this->lessonContext($lesson)],
        ];

        foreach (array_slice($history, -8) as $item) {
            $role = $item['role'] ?? null;
            $content = trim((string) ($item['content'] ?? ''));

            if (in_array($role, ['user', 'assistant'], true) && $content !== '') {
                $messages[] = ['role' => $role, 'content' => Str::limit($content, 700)];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }

    private function studentContext(User $user, array $history, string $message): string
    {
        $name  = trim($user->name) !== '' ? $user->name : 'Student';
        $level = $this->studentLevel($user, $history, $message);

        // Pull cross-agent context from the session (set by maybePersistLevel).
        $sessionLevel = null;
        if (function_exists('session')) {
            $sessionLevel = session('cyber_context.level');
        }

        $effectiveLevel = $level ?? $sessionLevel;
        $chatMemory     = $this->chatMemoryContext($history, $message);
        $recent         = $this->recentInteractionContext($user);

        return "Student profile:\n"
            ."- Name: {$name}\n"
            .'- Current level: '.($effectiveLevel ?? 'unknown; ask only when needed for a plan')."\n"
            ."- Personalization: address the student by name once near the start of each reply when natural. Do not overuse the name.\n"
            ."- Continuity: use the current chat history and recent interaction summary to avoid repeating questions and maintain context across all agent tabs.\n"
            ."Current chat memory:\n{$chatMemory}\n"
            ."Recent interaction summary:\n{$recent}";
    }

    private function chatMemoryContext(array $history, string $message): string
    {
        $lines = [];
        $lastAssistant = $this->lastAssistantMessage($history);

        if ($lastAssistant && $this->lastQuizFromHistory($history)) {
            $lines[] = '- Pending quiz state: if the latest student message is an answer or asks for another question, grade or continue the quiz before changing topics.';
        }

        if ($lastAssistant && (str_contains($lastAssistant, 'Which level are you?') || str_contains($lastAssistant, 'وش مستواك؟'))) {
            $lines[] = '- Pending plan state: the assistant asked for Beginner, Intermediate, or Expert; use the student reply as the level and continue the plan.';
        }

        $lines[] = '- current student message: '.Str::limit(preg_replace('/\s+/u', ' ', trim($message)) ?? trim($message), 180);

        return implode("\n", $lines);
    }

    private function studentLevel(User $user, array $history, string $message): ?string
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

    private function detectLevel(string $text): ?string
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

    private function recentInteractionContext(User $user): string
    {
        $interactions = $user->aiInteractions()
            ->latest()
            ->take(5)
            ->get(['agent_type', 'platform_version', 'prompt', 'response']);

        if ($interactions->isEmpty()) {
            return '- No previous AI interactions found.';
        }

        return $interactions
            ->map(function ($interaction) {
                $prompt = Str::limit(trim((string) $interaction->prompt), 140);
                $response = Str::limit(trim(strip_tags((string) $interaction->response)), 180);

                return "- {$interaction->platform_version}/{$interaction->agent_type}: student asked \"{$prompt}\"; assistant replied \"{$response}\".";
            })
            ->implode("\n");
    }

    private function lessonContext(?Lesson $lesson): string
    {
        if (! $lesson) {
            return 'Lesson context: General course support.';
        }

        $context = 'Lesson context: '.$lesson->title.' - '.Str::limit(strip_tags($lesson->content), 1600);
        $videos = $this->approvedVideos($lesson);

        if ($videos->isEmpty()) {
            return $context."\nApproved videos: none available for this lesson.";
        }

        $videoLines = $videos
            ->map(fn ($video) => '- '.$video->title.' | Channel: '.$video->channel_name.' | Embed: https://www.youtube.com/embed/'.$video->youtube_id)
            ->implode("\n");

        return $context."\nApproved videos:\n".$videoLines;
    }

    private function approvedVideos(Lesson $lesson)
    {
        if ($lesson->relationLoaded('videos')) {
            return $lesson->videos->where('approved', true)->values();
        }

        return $lesson->videos()->where('approved', true)->get();
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{content: string, tokens: int}
     */
    private function resolveAiResponse(array $messages, string $fallbackContent, string $version): array
    {
        if ($groqApiKey = $this->groqApiKey($version)) {
            $groq = $this->callGroq($messages, $groqApiKey, $version);

            if ($groq['ok']) {
                return ['content' => $groq['content'], 'tokens' => $groq['tokens']];
            }

            $this->logProviderFailure('Groq failed; trying the next AI provider.', $groq);
        }

        if (config('services.openai.api_key')) {
            $openai = $this->callOpenAI($messages);

            if ($openai['ok']) {
                return ['content' => $openai['content'], 'tokens' => $openai['tokens']];
            }

            $this->logProviderFailure('OpenAI failed; using the local AI fallback response.', $openai);
        }

        return ['content' => $fallbackContent, 'tokens' => 0];
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{ok: bool, provider: string, content: string, tokens: int, status: int|null, error: string|null, body: string|null}
     */
    private function callGroq(array $messages, string $apiKey, string $version): array
    {
        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => config('services.groq.model'),
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1200,
                    'top_p' => 0.9,
                ]);
        } catch (\Throwable $exception) {
            return $this->failedProviderResult('groq-'.$version, null, $exception->getMessage());
        }

        return $this->providerResult('groq-'.$version, $response);
    }

    private function groqApiKey(string $version): ?string
    {
        $key = $version === 'multi'
            ? config('services.groq.multi_api_key')
            : config('services.groq.single_api_key');

        $key = trim((string) $key);

        if ($key === '') {
            $key = trim((string) config('services.groq.api_key'));
        }

        return $key !== '' ? $key : null;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{ok: bool, provider: string, content: string, tokens: int, status: int|null, error: string|null, body: string|null}
     */
    private function callOpenAI(array $messages): array
    {
        try {
            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(25)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model'),
                    'messages' => $messages,
                    'temperature' => 0.4,
                    'max_tokens' => 700,
                ]);
        } catch (\Throwable $exception) {
            return $this->failedProviderResult('openai', null, $exception->getMessage());
        }

        return $this->providerResult('openai', $response);
    }

    /**
     * @return array{ok: bool, provider: string, content: string, tokens: int, status: int|null, error: string|null, body: string|null}
     */
    private function providerResult(string $provider, Response $response): array
    {
        if (! $response->successful()) {
            return $this->failedProviderResult(
                $provider,
                $response->status(),
                'Provider returned a non-success status.',
                Str::limit($response->body(), 500),
            );
        }

        $payload = $response->json();
        $content = trim((string) data_get($payload, 'choices.0.message.content', ''));

        if ($content === '') {
            return $this->failedProviderResult($provider, $response->status(), 'Provider returned empty content.');
        }

        return [
            'ok' => true,
            'provider' => $provider,
            'content' => $content,
            'tokens' => (int) data_get($payload, 'usage.total_tokens', 0),
            'status' => $response->status(),
            'error' => null,
            'body' => null,
        ];
    }

    /**
     * @return array{ok: bool, provider: string, content: string, tokens: int, status: int|null, error: string|null, body: string|null}
     */
    private function failedProviderResult(string $provider, ?int $status, string $error, ?string $body = null): array
    {
        return [
            'ok' => false,
            'provider' => $provider,
            'content' => '',
            'tokens' => 0,
            'status' => $status,
            'error' => $error,
            'body' => $body,
        ];
    }

    /**
     * @param  array{provider: string, status: int|null, error: string|null, body: string|null}  $result
     */
    private function logProviderFailure(string $message, array $result): void
    {
        Log::warning($message, [
            'provider' => $result['provider'],
            'status' => $result['status'],
            'error' => $result['error'],
            'body' => $result['body'],
        ]);
    }

    private function fallbackResponse(string $message, string $agentType, ?Lesson $lesson, ?User $user = null): string
    {
        if ($this->isGreetingOnly($message)) {
            return $this->greetingResponse($message, $agentType, $user);
        }

        if (in_array($agentType, ['single_tutor', 'navigation'], true) && $this->isStudyPlanRequest($message)) {
            $level = $user ? $this->studentLevel($user, [], $message) : $this->detectLevel($message);

            if ($level) {
                return $this->studyPlanFallbackResponse($message, $level);
            }

            return $this->levelClarificationResponse($message, $user);
        }

        return match ($agentType) {
            'navigation' => $this->navigationFallbackResponse($message, $lesson),
            'video' => $this->videoFallbackResponse($lesson, $message),
            default => $this->explanationFallbackResponse($message, $lesson),
        };
    }

    private function levelClarificationResponse(string $message, ?User $user): string
    {
        $name = trim((string) $user?->name);
        $displayName = $name !== '' ? $name : ($this->containsArabic($message) ? 'صديقي' : 'there');

        if ($this->containsArabic($message)) {
            return "تمام {$displayName}، قبل ما أبني لك الخطة اختر مستواك الحالي:\n\n"
                ."1. Beginner - مبتدئ أو توك تبدأ في الأمن السيبراني.\n"
                ."2. Intermediate - عندك أساسيات شبكات/Linux/أمن وتبغى ترتيب.\n"
                ."3. Expert - عندك خبرة عملية وتبغى تخصص متقدم.\n\n"
                .'وش مستواك؟';
        }

        return "Hello {$displayName}! Before I build your plan, please choose your current level:\n\n"
            ."1. Beginner - new to cybersecurity or still learning basics.\n"
            ."2. Intermediate - you understand networking/Linux/security basics and want structure.\n"
            ."3. Expert - you already have hands-on experience and want advanced specialization.\n\n"
            .'Which level are you?';
    }

    private function studyPlanFallbackResponse(string $message, string $level): string
    {
        $arabic = $this->containsArabic($message);

        return match ($level) {
            'Intermediate' => $arabic
                ? "**خطة تعلم الأمن السيبراني — مستوى Intermediate**\n\n"
                    ."**Module 1 (أسبوع 1–2): أمن تطبيقات الويب**\n"
                    ."- OWASP Top 10 بعمق: XSS، SQLi، CSRF، IDOR، SSRF.\n"
                    ."- تدرّب على PortSwigger Web Security Academy (Labs مجانية).\n\n"
                    ."**Module 2 (أسبوع 3–4): أمن الشبكات والأدوات**\n"
                    ."- تحليل حزم الشبكة بـ Wireshark، أنواع جدران الحماية، IDS/IPS.\n"
                    ."- تدرّب على TryHackMe: Jr Penetration Tester Path.\n\n"
                    ."**Module 3 (أسبوع 5–6): التشفير والشهادات**\n"
                    ."- التشفير المتماثل وغير المتماثل، TLS، PKI، هاش وكيف تكشف التلاعب.\n"
                    ."- اقرأ: Cryptography I - Coursera (أول فصلين مجانيان).\n\n"
                    ."**مصادر الأولوية:** PortSwigger Academy، TryHackMe، OWASP Testing Guide.\n\n"
                    .'خلّنا نبدأ بموضوع محدد: أمن الويب، الشبكات، أم التشفير؟'
                : "**Cybersecurity study plan — Intermediate level**\n\n"
                    ."**Module 1 (Week 1–2): Web application security**\n"
                    ."- OWASP Top 10 in depth: XSS, SQLi, CSRF, IDOR, SSRF.\n"
                    ."- Practice on PortSwigger Web Security Academy (free labs).\n\n"
                    ."**Module 2 (Week 3–4): Network security and tools**\n"
                    ."- Wireshark packet analysis, firewall types, IDS/IPS concepts.\n"
                    ."- TryHackMe: Jr Penetration Tester path.\n\n"
                    ."**Module 3 (Week 5–6): Cryptography and certificates**\n"
                    ."- Symmetric vs asymmetric encryption, TLS, PKI, hashing and tamper detection.\n"
                    ."- Read: Coursera Cryptography I (first two weeks are free).\n\n"
                    ."**Priority resources:** PortSwigger Academy, TryHackMe, OWASP Testing Guide.\n\n"
                    .'Where do you want to start: web security, networking, or cryptography?',

            'Expert' => $arabic
                ? "**خطة تعلم الأمن السيبراني — مستوى Expert**\n\n"
                    ."**Module 1 (أسبوع 1–2): صيد التهديدات والكشف المتقدم**\n"
                    ."- إطار MITRE ATT&CK، تحليل SIEM وSplunk، تحليل الـ Logs، بناء Detection Rules.\n"
                    ."- تدرّب على Hack The Box Pro Labs (RastaLabs أو Offshore).\n\n"
                    ."**Module 2 (أسبوع 3–4): أمن السحابة والحاويات**\n"
                    ."- AWS/Azure IAM Misconfigurations، تصليب الحاويات Docker/Kubernetes، أمان Serverless.\n"
                    ."- أدوات: Prowler، ScoutSuite، Trivy.\n\n"
                    ."**Module 3 (أسبوع 5–6): البنية الأمنية الآمنة وDevSecOps**\n"
                    ."- Threat Modeling (STRIDE/PASTA)، SAST/DAST في CI/CD، تأمين Secrets Management.\n"
                    ."- اقرأ: NIST SP 800-53، CIS Benchmarks لبيئتك.\n\n"
                    ."**مصادر الأولوية:** Hack The Box Pro Labs، SANS courses، OSCP (للـ Red Team).\n\n"
                    .'اختر اتجاهك: صيد التهديدات، أمن السحابة، أم DevSecOps؟'
                : "**Cybersecurity study plan — Expert level**\n\n"
                    ."**Module 1 (Week 1–2): Threat hunting and advanced detection**\n"
                    ."- MITRE ATT&CK framework, SIEM/Splunk log analysis, building detection rules.\n"
                    ."- Practice on Hack The Box Pro Labs (RastaLabs or Offshore).\n\n"
                    ."**Module 2 (Week 3–4): Cloud security and containers**\n"
                    ."- AWS/Azure IAM misconfigurations, Docker/Kubernetes hardening, Serverless security.\n"
                    ."- Tools: Prowler, ScoutSuite, Trivy.\n\n"
                    ."**Module 3 (Week 5–6): Secure architecture and DevSecOps**\n"
                    ."- Threat modeling (STRIDE/PASTA), SAST/DAST in CI/CD pipelines, Secrets Management.\n"
                    ."- Read: NIST SP 800-53, CIS Benchmarks for your environment.\n\n"
                    ."**Priority resources:** Hack The Box Pro Labs, SANS courses, OSCP prep.\n\n"
                    .'Pick your focus: threat hunting, cloud security, or DevSecOps?',

            // Beginner (default)
            default => $arabic
                ? "**خطة تعلم الأمن السيبراني — مستوى Beginner**\n\n"
                    ."**Module 1 (أسبوع 1–2): أساسيات الشبكات**\n"
                    ."- IP، DNS، HTTP/HTTPS، TCP/UDP، المنافذ، نموذج OSI.\n"
                    ."- خصص 30-45 دقيقة يومياً. المصدر: TryHackMe Pre-Security Path.\n\n"
                    ."**Module 2 (أسبوع 3–4): أساسيات Linux**\n"
                    ."- أوامر CLI الأساسية، الملفات والمجلدات، الصلاحيات، إدارة المستخدمين.\n"
                    ."- تدرّب داخل: TryHackMe Linux Fundamentals (3 parts).\n\n"
                    ."**Module 3 (أسبوع 5–6): مفاهيم أمن الويب**\n"
                    ."- OWASP Top 10 بشكل مبسط: فهم كل ثغرة وكيف تُكتشف وكيف تتحمى.\n"
                    ."- تدرّب على: PortSwigger Web Security Academy (مستوى Apprentice).\n\n"
                    ."**مصادر الأولوية:** TryHackMe، Cybrary، Google Cybersecurity Certificate.\n\n"
                    .'نبدأ بأي موضوع: الشبكات، Linux، أم أمن الويب؟'
                : "**Cybersecurity study plan — Beginner level**\n\n"
                    ."**Module 1 (Week 1–2): Networking fundamentals**\n"
                    ."- IP addressing, DNS, HTTP/HTTPS, TCP/UDP, ports, and the OSI model.\n"
                    ."- Dedicate 30-45 minutes daily. Resource: TryHackMe Pre-Security Path.\n\n"
                    ."**Module 2 (Week 3–4): Linux basics**\n"
                    ."- Core CLI commands, directories, file permissions, and user management.\n"
                    ."- Practice inside: TryHackMe Linux Fundamentals (3 parts).\n\n"
                    ."**Module 3 (Week 5–6): Web security concepts**\n"
                    ."- OWASP Top 10 simplified: understand each vulnerability, detection, and defense.\n"
                    ."- Practice on: PortSwigger Web Security Academy (Apprentice tier, free).\n\n"
                    ."**Priority resources:** TryHackMe, Cybrary, Google Cybersecurity Certificate.\n\n"
                    .'Where do you want to start: networking, Linux, or web security?',
        };
    }

    private function navigationFallbackResponse(string $message, ?Lesson $lesson): string
    {
        $summary = $lesson?->summary ?? 'أساسيات الأمن السيبراني';

        if ($this->containsArabic($message)) {
            return "خلّنا نمشيها خطوة بخطوة:\n\n"
                ."1. ابدأ بهدف الدرس الحالي: {$summary}.\n"
                ."2. خلّص القراءة أو الفيديوهات المعتمدة.\n"
                ."3. اختبر نفسك بسؤال أو Quiz قصير.\n"
                ."4. بعدها انتقل للدرس التالي في المسار.\n\n"
                .'إذا تبغى خطة كاملة، اكتب: "أبغى خطة".';
        }

        return 'Guide focus: start with the current lesson goals, complete the quiz, then continue to the next lesson in the sidebar. For this topic, focus on: '.$summary.'.';
    }

    private function explanationFallbackResponse(string $message, ?Lesson $lesson): string
    {
        $arabic = $this->containsArabic($message);

        if ($concept = $this->knownConceptExplanation($message, $arabic)) {
            return $concept;
        }

        $summary = $lesson?->summary;

        if ($arabic) {
            $idea = $summary
                ? strip_tags($summary)
                : 'حدّد المفهوم اللي تبغاه بدقة وأشرحه لك: تعريفه، سبب أهميته، ومثال دفاعي عملي';

            return "خلّنا نشرحها بشكل آمن ومبسّط:\n\n"
                ."**الفكرة:** {$idea}.\n\n"
                ."**مثال دفاعي:** اربط المفهوم بطرق الحماية أو الكشف أو تقليل المخاطر، بدون أي خطوات هجومية.\n\n"
                .'تبغى مثال أبسط، مقارنة، ولا روابط للتعلم؟';
        }

        $idea = $summary
            ? strip_tags($summary)
            : 'tell me the exact concept and I will cover its definition, why it matters, and a practical defensive example';

        return "Let's break it down in a study-safe way:\n\n"
            ."**Idea:** {$idea}.\n\n"
            ."**Defensive example:** connect the concept to how you protect, detect, or reduce risk, with no offensive steps.\n\n"
            .'Would you like a simpler example, a comparison, or some learning links?';
    }

    private function knownConceptExplanation(string $message, bool $arabic): ?string
    {
        $text = Str::lower($message);

        $concepts = [
            'cia' => [
                'match' => ['cia triad', 'cia', 'confidentiality integrity', 'السرية والسلامة', 'سي اي ايه', 'مثلث', 'cia triad and risk'],
                'en' => "**CIA Triad** is the core model for security goals:\n\n1. **Confidentiality** - keep data private and accessible only to authorized people (encryption, access control).\n2. **Integrity** - keep data accurate and unaltered (hashing, change control).\n3. **Availability** - keep systems and data reachable when needed (backups, redundancy).\n\n**Risk** is the chance that a threat exploits a weakness and harms one of these three. You manage it by reducing likelihood or impact (controls, patching, monitoring).\n\nWant an example of a control for each part?",
                'ar' => "**نموذج CIA Triad** هو الأساس لأهداف الأمن:\n\n1. **السرية (Confidentiality)** - تبقى البيانات خاصة ومتاحة للمصرّح لهم فقط (تشفير، صلاحيات وصول).\n2. **السلامة (Integrity)** - تبقى البيانات دقيقة وغير معدّلة (hashing، التحكم في التغييرات).\n3. **التوافر (Availability)** - تبقى الأنظمة والبيانات متاحة وقت الحاجة (نسخ احتياطي، تكرار).\n\n**المخاطرة (Risk)** هي احتمال إن تهديد يستغل ثغرة ويضر واحد من الثلاثة. تتعامل معها بتقليل الاحتمال أو الأثر (ضوابط، تحديثات، مراقبة).\n\nتبغى مثال لضابط حماية لكل عنصر؟",
            ],
            'phishing' => [
                'match' => ['phishing', 'تصيد', 'فيشينج'],
                'en' => "**Phishing** is a social-engineering attack where an attacker impersonates a trusted party to trick you into revealing credentials or clicking a malicious link.\n\n**How to defend:**\n1. Check the sender address and the real link before clicking.\n2. Never enter passwords from email links - go to the site directly.\n3. Enable MFA so a stolen password alone is not enough.\n4. Report suspicious emails to your security team.\n\nWant the common red flags to watch for?",
                'ar' => "**التصيّد (Phishing)** هجوم هندسة اجتماعية، المهاجم بينتحل جهة موثوقة عشان يخدعك وتكشف بيانات دخولك أو تضغط رابط خبيث.\n\n**طرق الحماية:**\n1. افحص عنوان المُرسل والرابط الحقيقي قبل الضغط.\n2. ما تدخل كلمة المرور من روابط الإيميل - ادخل الموقع مباشرة.\n3. فعّل MFA عشان كلمة المرور المسروقة وحدها ما تكفي.\n4. بلّغ فريق الأمن عن الرسائل المشبوهة.\n\nتبغى أهم العلامات التحذيرية؟",
            ],
            'mfa' => [
                'match' => ['mfa', '2fa', 'multi factor', 'multi-factor', 'two factor', 'تحقق ثنائي', 'المصادقة الثنائية', 'عامل ثاني'],
                'en' => "**MFA (Multi-Factor Authentication)** adds a second proof of identity beyond your password - something you have (phone/token) or are (fingerprint).\n\n**Why it matters:** even if a password is stolen, the attacker still cannot log in without the second factor. It is one of the strongest, simplest defenses against account takeover.\n\nWant to know which MFA types are strongest?",
                'ar' => "**MFA (المصادقة متعددة العوامل)** تضيف دليل هوية ثاني بعد كلمة المرور - شيء تملكه (هاتف/توكن) أو شيء أنت (بصمة).\n\n**ليه مهمة:** حتى لو سُرقت كلمة المرور، المهاجم ما يقدر يدخل بدون العامل الثاني. من أقوى وأبسط الدفاعات ضد الاستيلاء على الحساب.\n\nتبغى تعرف أقوى أنواع MFA؟",
            ],
            'dns' => [
                'match' => ['dns', 'domain name system', 'نظام أسماء النطاقات', 'دي ان اس'],
                'en' => "**DNS (Domain Name System)** translates human-readable domain names (like example.com) into IP addresses so devices can find each other.\n\n**Security angle:** attackers may try DNS spoofing or poisoning to redirect you to fake sites. Defenses include DNSSEC, trusted resolvers, and monitoring for unusual lookups.\n\nWant a simple analogy for how DNS works?",
                'ar' => "**DNS (نظام أسماء النطاقات)** يترجم أسماء النطاقات المفهومة للبشر (زي example.com) إلى عناوين IP عشان الأجهزة تلاقي بعض.\n\n**الجانب الأمني:** المهاجمون ممكن يحاولوا DNS spoofing أو poisoning عشان يحوّلوك لمواقع مزيفة. الحماية تشمل DNSSEC، resolvers موثوقة، ومراقبة الاستعلامات الغريبة.\n\nتبغى تشبيه بسيط لطريقة عمل DNS؟",
            ],
        ];

        foreach ($concepts as $concept) {
            if ($this->containsAny($text, $concept['match'])) {
                return $arabic ? $concept['ar'] : $concept['en'];
            }
        }

        return null;
    }

    private function greetingResponse(string $message, string $agentType, ?User $user): string
    {
        $arabic = $this->containsArabic($message);
        $name = trim((string) $user?->name);
        $displayName = $name !== '' ? $name : ($arabic ? 'صديقي' : 'there');

        return match ($agentType) {
            'navigation' => $arabic
                ? "مرحباً {$displayName}! تبويب Guide مفعل.\n\nأساعدك في الخطط، المسارات، الجداول، واختيار الدرس التالي. هل تريد خطة أم توجيهاً للخطوة القادمة؟"
                : "Hello {$displayName}! Guide is active.\n\nI can help with plans, roadmaps, schedules, and choosing the next lesson. Do you want a plan or guidance on the next step?",
            'explanation' => $arabic
                ? "مرحباً {$displayName}! تبويب Tutor مفعل.\n\nأساعدك في شرح مفاهيم الأمن السيبراني وأمثلة دفاعية آمنة. ما المفهوم الذي تريد شرحه؟"
                : "Hello {$displayName}! Tutor is active.\n\nI can explain cybersecurity concepts with safe defensive examples. What concept would you like to understand?",
            'video' => $arabic
                ? "مرحباً {$displayName}! تبويب Video مفعل.\n\nأرشح فقط الفيديوهات المعتمدة داخل هذا الدرس. هل تريد معرفة أي فيديو تبدأ به؟"
                : "Hello {$displayName}! Video is active.\n\nI only recommend approved videos embedded in this lesson. Would you like to know which one to watch first?",
            default => $arabic
                ? "مرحباً {$displayName}! أنا Cyber Mentor، مساعدك المتخصص في الأمن السيبراني.\n\nكيف أقدر أساعدك اليوم؟ يمكنني شرح مفهوم، اقتراح مصادر، عمل اختبار قصير، أو بناء خطة إذا طلبت ذلك."
                : "Hello {$displayName}! I am Cyber Mentor, your specialized cybersecurity study assistant.\n\nHow can I help you today? I can explain a concept, suggest resources, quiz you, or build a plan if you ask for one.",
        };
    }

    private function videoFallbackResponse(?Lesson $lesson, string $message): string
    {
        if (! $lesson) {
            return $this->containsArabic($message)
                ? 'لا توجد فيديوهات معتمدة مرتبطة بسؤال عام الآن. افتح درساً محدداً ثم اسألني داخل تبويب Video.'
                : 'There are no approved videos attached to a general question. Open a specific lesson, then ask me in the Video tab.';
        }

        $videos = $this->approvedVideos($lesson);

        if ($videos->isEmpty()) {
            return $this->containsArabic($message)
                ? 'لا توجد فيديوهات معتمدة مضافة لهذا الدرس حالياً. استخدم Tutor لشرح المفهوم أو Guide لترتيب الخطة.'
                : 'There are no approved videos attached to this lesson yet. Use Tutor for concept help or Guide for planning.';
        }

        $lines = $videos
            ->take(3)
            ->map(fn ($video, $index) => ($index + 1).'. '.$video->title.' - '.$video->channel_name."\n".'https://www.youtube.com/embed/'.$video->youtube_id)
            ->implode("\n\n");

        if ($this->containsArabic($message)) {
            return "هذه أفضل الفيديوهات المعتمدة لهذا الدرس:\n\n{$lines}\n\nشاهد أول فيديو، وركز على الفكرة الرئيسية ثم ارجع للاختبار.";
        }

        return "Here are the approved videos for this lesson:\n\n{$lines}\n\nStart with the first video, note the core idea, then return to the quiz.";
    }

    // -------------------------------------------------------------------------
    // Level persistence
    // -------------------------------------------------------------------------

    /**
     * Persist a newly detected learning level to the user record and the session
     * so every subsequent agent can use it without re-asking.
     */
    private function maybePersistLevel(User $user, string $message, array $history): void
    {
        // Already stored — nothing to do.
        if ($user->learning_level && $this->detectLevel((string) $user->learning_level)) {
            return;
        }

        $sources = array_merge([$message], array_column($history, 'content'));

        foreach ($sources as $source) {
            $level = $this->detectLevel((string) $source);

            if ($level) {
                // Persist to the database for cross-session memory.
                $user->update(['learning_level' => $level]);

                // Also store in the current session for immediate cross-agent availability.
                if (app()->bound('session.store') || function_exists('session')) {
                    session(['cyber_context.level' => $level]);
                }

                return;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Real-time streaming
    // -------------------------------------------------------------------------

    /**
     * Streaming counterpart to respond().
     * Calls $onChunk for every token/chunk as it arrives, then calls $onDone once
     * with the final metadata. Uses Groq's native SSE stream when available;
     * falls back to OpenAI (non-streaming) and then to the hardcoded fallback,
     * both of which are chunked and emitted with the same callback signature.
     */
    public function respondStreaming(
        User $user,
        ?Lesson $lesson,
        string $message,
        string $agentType,
        string $version,
        array $history,
        callable $onChunk,
        callable $onDone,
    ): void {
        $agent   = self::AGENTS[$agentType] ?? self::AGENTS['single_tutor'];
        $started = microtime(true);
        $tokens  = 0;
        $meta    = [];
        $cacheHit     = false;
        $messageHash  = null;
        $prebuiltContent = null; // non-null means we will chunk it manually
        $streamedContent = '';   // accumulated from real Groq streaming

        // ── Pre-flight checks (same order as respond()) ──────────────────────
        if (! $this->withinRateLimit($user)) {
            $prebuiltContent = $this->rateLimitResponse($message);
        } elseif (! $this->withinDailyLimit($user)) {
            $prebuiltContent = $this->dailyLimitResponse($message, $user);
        } elseif ($this->isUnsafeRequest($message)) {
            $prebuiltContent = $this->safetyResponse($message);
        } elseif ($deterministicResponse = $this->deterministicResponse($message, $agentType, $history, $lesson, $user)) {
            $prebuiltContent = $deterministicResponse;
        } elseif ($scopeResponse = $this->scopeResponse($message, $agentType)) {
            $prebuiltContent = $scopeResponse['message'];
            $meta            = $scopeResponse['meta'];
        } else {
            // ── AI path ──────────────────────────────────────────────────────
            $messageHash    = $this->messageHash($message, $agentType, $lesson?->id);
            $cachedResponse = $this->findCachedResponse($messageHash);

            if ($cachedResponse !== null) {
                $prebuiltContent = $cachedResponse;
                $cacheHit        = true;
            } else {
                $messages   = $this->messages($user, $agent['prompt_key'], $lesson, $history, $message);
                $groqKey    = $this->groqApiKey($version);
                $groqSuccess = false;

                if ($groqKey) {
                    $groqResult = $this->streamFromGroq(
                        $messages,
                        $groqKey,
                        function (string $token) use ($onChunk, &$streamedContent): void {
                            $streamedContent .= $token;
                            $onChunk($token);
                        }
                    );

                    if ($groqResult['ok']) {
                        $groqSuccess = true;
                        $tokens      = $groqResult['tokens'];
                    } else {
                        $this->logProviderFailure('Groq streaming failed; trying OpenAI.', $groqResult);
                    }
                }

                if (! $groqSuccess) {
                    if (config('services.openai.api_key')) {
                        $openai = $this->callOpenAI($messages);

                        if ($openai['ok']) {
                            $prebuiltContent = $openai['content'];
                            $tokens          = $openai['tokens'];
                        } else {
                            $this->logProviderFailure('OpenAI failed; using fallback response.', $openai);
                        }
                    }

                    if ($prebuiltContent === null) {
                        $prebuiltContent = $this->fallbackResponse($message, $agentType, $lesson, $user);
                    }
                }
            }
        }

        // ── Personalize & emit pre-built content in small chunks ─────────────
        if ($prebuiltContent !== null) {
            $prebuiltContent = $this->personalizeResponse($prebuiltContent, $message, $user);

            foreach (mb_str_split($prebuiltContent, 6) as $chunk) {
                $onChunk($chunk);
            }
        }

        // ── Determine what to log ─────────────────────────────────────────────
        $rawContent  = $prebuiltContent ?? $streamedContent;
        $finalContent = $rawContent;

        // ── Persist level if detected in this turn ────────────────────────────
        $this->maybePersistLevel($user, $message, $history);

        $interaction = AiInteraction::create([
            'user_id'          => $user->id,
            'lesson_id'        => $lesson?->id,
            'platform_version' => $version,
            'agent_type'       => $agentType,
            'message_hash'     => $messageHash,
            'prompt'           => $message,
            'response'         => $rawContent,
            'tokens_used'      => $tokens,
            'latency_ms'       => (int) ((microtime(true) - $started) * 1000),
            'cache_hit'        => $cacheHit,
        ]);

        $onDone([
            'message'        => $finalContent,
            'agent'          => $agent,
            'interaction_id' => $interaction->id,
            'meta'           => $meta,
        ]);
    }

    /**
     * Connect to Groq's chat-completions endpoint with stream=true and call
     * $onToken for every text token received over the SSE connection.
     *
     * Returns ['ok' => bool, 'provider' => string, 'tokens' => int, 'error' => string|null].
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{ok: bool, provider: string, tokens: int, content: string, status: int|null, error: string|null, body: string|null}
     */
    private function streamFromGroq(array $messages, string $apiKey, callable $onToken): array
    {
        $fullContent = '';
        $totalTokens = 0;

        try {
            $client   = new GuzzleClient(['timeout' => 60]);
            $response = $client->post('https://api.groq.com/openai/v1/chat/completions', [
                'stream'  => true,
                'headers' => [
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => config('services.groq.model', 'llama-3.3-70b-versatile'),
                    'messages'    => $messages,
                    'temperature' => 0.7,
                    'max_tokens'  => 1200,
                    'top_p'       => 0.9,
                    'stream'      => true,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->failedProviderResult('groq-stream', null, $e->getMessage());
        }

        $body   = $response->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $chunk  = $body->read(512);
            $buffer .= $chunk;

            // Process every complete SSE line.
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line   = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if (! str_starts_with($line, 'data: ')) {
                    continue;
                }

                $json = substr($line, 6);

                if ($json === '[DONE]') {
                    break 2;
                }

                $parsed = json_decode($json, true);

                if (! is_array($parsed)) {
                    continue;
                }

                $token = (string) data_get($parsed, 'choices.0.delta.content', '');

                if ($token !== '') {
                    $fullContent .= $token;
                    $onToken($token);
                }

                if (isset($parsed['usage']['total_tokens'])) {
                    $totalTokens = (int) $parsed['usage']['total_tokens'];
                }
            }
        }

        if ($fullContent === '') {
            return $this->failedProviderResult('groq-stream', $response->getStatusCode(), 'Groq stream returned empty content.');
        }

        return [
            'ok'       => true,
            'provider' => 'groq-stream',
            'content'  => $fullContent,
            'tokens'   => $totalTokens,
            'status'   => $response->getStatusCode(),
            'error'    => null,
            'body'     => null,
        ];
    }
}
