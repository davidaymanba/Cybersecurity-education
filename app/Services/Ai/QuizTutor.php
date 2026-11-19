<?php

namespace App\Services\Ai;

use App\Models\Lesson;
use Illuminate\Support\Str;

/**
 * Runs the in-chat practice quiz: picks questions, grades answers and handles follow-ups.
 */
class QuizTutor
{
    private const REPROMPT_EN = 'Please answer with A, B, C, or D so I can grade it clearly.';

    private const REPROMPT_AR = 'اكتب إجابتك بصيغة أ، ب، ج، أو د عشان أقدر أصححها لك.';

    public function __construct(
        private IntentDetector $intents,
    ) {}

    public function isAnotherQuizRequest(string $message, array $history): bool
    {
        $assistantMessage = $this->intents->lastAssistantMessage($history);

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
            || $this->containsWord($text, ['نعم', 'ايوه', 'أيوه', 'ايه', 'اه', 'إي', 'اي', 'تمام', 'اكيد', 'أكيد', 'يلا', 'هات', 'كمل'])
            || $this->intents->containsAny($text, [
                'another question',
                'next question',
                'سؤال تاني',
                'سؤال ثاني',
                'سؤال آخر',
                'السؤال التالي',
            ]);
    }

    private function lastAssistantOfferedAnotherQuestion(array $history): bool
    {
        $assistantMessage = $this->intents->lastAssistantMessage($history);

        if (! $assistantMessage) {
            return false;
        }

        return str_contains($assistantMessage, 'Would you like another question?')
            || str_contains($assistantMessage, 'هل تريد سؤالاً آخر؟');
    }

    public function isQuizDeclineRequest(string $message, array $history): bool
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
            || $this->containsWord($text, ['لا', 'لأ', 'لاء'])
            || $this->intents->containsAny($text, [
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

    public function quizDeclineResponse(string $message, array $history): string
    {
        if ($this->intents->prefersArabicReply($message, $history)) {
            return 'تمام، خلصنا الاختبار. لو حابب نكمل، أقدر أشرح لك مفهوماً، أرشّح مصادر، أو أبني لك خطة تعلم. وش تحب نسوي؟';
        }

        return 'No problem, we will stop the quiz here. Whenever you are ready, I can explain a concept, suggest resources, or build a study plan. What would you like to do next?';
    }

    public function quizQuestionResponse(string $message, ?Lesson $lesson, array $history = []): string
    {
        $quiz = $this->quizForLesson($lesson, $history);
        $useArabic = $this->intents->prefersArabicReply($message, $history);

        if ($useArabic) {
            return "**اختبار سريع**\n\n{$quiz['question_ar']}\n{$quiz['options_ar'][0]}\n{$quiz['options_ar'][1]}\n{$quiz['options_ar'][2]}\n{$quiz['options_ar'][3]}\n\nاكتب أ، ب، ج، أو د وسأخبرك هل إجابتك صحيحة أم لا.";
        }

        return "**Quick quiz**\n\n{$quiz['question_en']}\n{$quiz['options_en'][0]}\n{$quiz['options_en'][1]}\n{$quiz['options_en'][2]}\n{$quiz['options_en'][3]}\n\nReply with A, B, C, or D and I will tell you whether it is correct.";
    }

    public function quizEvaluationResponse(string $message, array $history): ?string
    {
        $lastAssistant = (string) $this->intents->lastAssistantMessage($history);

        if ($lastAssistant === '' || $this->lastAssistantOfferedAnotherQuestion($history)) {
            return null;
        }

        $alreadyReprompted = str_contains($lastAssistant, self::REPROMPT_EN) || str_contains($lastAssistant, self::REPROMPT_AR);
        $quiz = $this->lastQuizFromHistory($history);

        // Only grade while the quiz question (or our single re-prompt) is the latest assistant turn.
        // Otherwise an old question in the history would capture every later message.
        if (! $quiz || (! $alreadyReprompted && $this->quizIn($lastAssistant) === null)) {
            return null;
        }

        $answer = $this->normalizeQuizAnswer($message, $quiz);
        $useArabic = $this->intents->prefersArabicReply($message, $history);

        if (! $answer) {
            // Re-prompt once for short, answer-like messages; anything else (a new question, a
            // request, or a second non-answer) leaves the quiz so the student is never stuck.
            $wordCount = count(preg_split('/\s+/u', trim($message)) ?: []);

            if ($alreadyReprompted || $wordCount > 4 || str_contains($message, '?') || str_contains($message, '؟')) {
                return null;
            }

            return $useArabic ? self::REPROMPT_AR : self::REPROMPT_EN;
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
    public function lastQuizFromHistory(array $history): ?array
    {
        foreach (array_reverse($history) as $item) {
            if (($item['role'] ?? null) !== 'assistant') {
                continue;
            }

            $content = trim((string) ($item['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            if ($quiz = $this->quizIn($content)) {
                return $quiz;
            }
        }

        return null;
    }

    /**
     * The bank question contained in an assistant message, if any.
     *
     * @return array<string, mixed>|null
     */
    private function quizIn(string $content): ?array
    {
        foreach ($this->quizBank() as $quiz) {
            if (str_contains($content, $quiz['question_en']) || str_contains($content, $quiz['question_ar'])) {
                return $quiz;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $words
     */
    private function containsWord(string $text, array $words): bool
    {
        foreach ($words as $word) {
            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($word, '/').'(?![\p{L}\p{N}])/u', $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $quiz
     */
    private function normalizeQuizAnswer(string $message, array $quiz): ?string
    {
        $text = trim(Str::lower($message));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        $letterMap = ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'أ' => 'A', 'ا' => 'A', 'إ' => 'A', 'ب' => 'B', 'ج' => 'C', 'د' => 'D'];
        $tokens = $text === '' ? [] : explode(' ', $text);

        // "a or b?" is a hesitation, not an answer.
        if (preg_match('/(?<![\p{L}])(a|b|c|d|أ|ا|ب|ج|د)\s+(or|او|أو)\s+(a|b|c|d|أ|ا|ب|ج|د)(?![\p{L}])/u', $text)) {
            return null;
        }

        // 1) "B", "b)", "ب. ..." — the letter comes first.
        if ($tokens && isset($letterMap[$tokens[0]])) {
            return $letterMap[$tokens[0]];
        }

        // 2) "I think it's B", "the answer is b", "الإجابة ب", "أظن أ", "option C".
        $cue = '(?:answer|option|choice|choose|pick|go with|think|guess|its|it s|is|الاجابه|الإجابة|الاجابة|اجابتي|إجابتي|الجواب|جوابي|اظن|أظن|اعتقد|أعتقد|اختار|أختار|الخيار|خيار|هي|هو)';
        if (preg_match('/(?<![\p{L}])'.$cue.'\s+(a|b|c|d|أ|ا|إ|ب|ج|د)(?![\p{L}\p{N}])/u', $text, $m)) {
            return $letterMap[$m[1]];
        }

        // 3) Exactly one standalone option letter anywhere (English "a" is skipped: it is usually an article).
        $letters = array_values(array_unique(array_map(
            fn ($t) => $letterMap[$t],
            array_filter($tokens, fn ($t) => isset($letterMap[$t]) && ! in_array($t, ['a', 'ا'], true))
        )));
        if (count($letters) === 1) {
            return $letters[0];
        }

        // 4) The student typed an option's text (right or wrong one).
        foreach (['options_en', 'options_ar'] as $key) {
            foreach ($quiz[$key] as $index => $option) {
                $optionText = trim(preg_replace('/^\s*\S+[.)]\s*/u', '', Str::lower($option)) ?? '');
                $optionText = trim(preg_replace('/\s+/u', ' ', preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $optionText) ?? '') ?? '');

                if ($optionText !== '' && (str_contains($text, $optionText) || (mb_strlen($text) >= 8 && str_contains($optionText, $text)))) {
                    return 'ABCD'[$index];
                }
            }
        }

        // 5) Key words of the correct answer ("domain names", "سرية").
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

        $bank = $this->quizBank();
        $askedKeys = $this->askedQuizKeysFromHistory($history);

        // Topic → quiz key mapping (ordered by specificity).
        $topicMap = [
            'dns' => 'dns-purpose',
            'domain name' => 'dns-purpose',
            'cia' => 'cia-triad',
            'confidentiality' => 'cia-triad',
            'integrity' => 'cia-triad',
            'availability' => 'cia-triad',
            'mfa' => 'mfa-purpose',
            'multi-factor' => 'mfa-purpose',
            'two-factor' => 'mfa-purpose',
            'phishing' => 'phishing-definition',
            'social engineering' => 'social-engineering',
            'pretexting' => 'social-engineering',
            'https' => 'https-purpose',
            'tls' => 'https-purpose',
            'ssl' => 'https-purpose',
            'firewall' => 'firewall-function',
            'sql' => 'owasp-sqli',
            'injection' => 'owasp-sqli',
            'owasp' => 'owasp-sqli',
            'vpn' => 'vpn-purpose',
            'privilege' => 'least-privilege',
            'least privilege' => 'least-privilege',
            'incident response' => 'incident-response',
            'zero trust' => 'zero-trust',
            'zero-trust' => 'zero-trust',
            'password' => 'password-policy',
            'patch' => 'patch-management',
            'update' => 'patch-management',
            'vulnerability' => 'patch-management',
            'encrypt' => 'encryption-types',
            'asymmetric' => 'encryption-types',
            'symmetric' => 'encryption-types',
            'rsa' => 'encryption-types',
            'aes' => 'encryption-types',
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
                'options_en' => ['A. Encrypts files on disk', 'B. Translates domain names to IP addresses', 'C. Blocks all phishing emails automatically', 'D. Stores user passwords'],
                'options_ar' => ['أ. تشفير الملفات على القرص', 'ب. ترجمة أسماء النطاقات إلى عناوين IP', 'ج. منع كل رسائل التصيد تلقائياً', 'د. تخزين كلمات مرور المستخدمين'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['domain names', 'ip address', 'ip addresses', 'dns names', 'أسماء النطاقات', 'عناوين ip'],
                'explanation_en' => 'DNS maps human-readable domain names to IP addresses so browsers and apps can find the right server.',
                'explanation_ar' => 'DNS يربط أسماء النطاقات المفهومة للبشر بعناوين IP حتى يعرف المتصفح أو التطبيق الخادم الصحيح.',
            ],
            'cia-triad' => [
                'question_en' => 'Which three properties make up the CIA triad?',
                'question_ar' => 'ما العناصر الثلاثة التي تكوّن نموذج CIA Triad؟',
                'options_en' => ['A. Code, Identity, Access', 'B. Confidentiality, Integrity, Availability', 'C. Capture, Inspect, Alert', 'D. Cloud, Internet, Authentication'],
                'options_ar' => ['أ. الكود، الهوية، الوصول', 'ب. السرية، السلامة، التوافر', 'ج. الالتقاط، الفحص، التنبيه', 'د. السحابة، الإنترنت، المصادقة'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['confidentiality', 'integrity', 'availability', 'سرية', 'سلامة', 'توافر'],
                'explanation_en' => 'The CIA triad covers Confidentiality (protect data from unauthorized access), Integrity (keep data accurate), and Availability (keep systems accessible).',
                'explanation_ar' => 'CIA Triad يركز على السرية (حماية البيانات من الوصول غير المصرح)، السلامة (الحفاظ على دقة البيانات)، والتوافر (ضمان الوصول للأنظمة).',
            ],
            'mfa-purpose' => [
                'question_en' => 'What is the main purpose of MFA?',
                'question_ar' => 'ما الهدف الأساسي من MFA؟',
                'options_en' => ['A. Make passwords public', 'B. Add another verification factor beyond the password', 'C. Disable account monitoring', 'D. Replace all security training'],
                'options_ar' => ['أ. جعل كلمات المرور عامة', 'ب. إضافة عامل تحقق آخر بجانب كلمة المرور', 'ج. تعطيل مراقبة الحسابات', 'د. استبدال كل التدريب الأمني'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['another verification factor', 'second factor', 'multi factor', 'عامل تحقق', 'عامل ثاني'],
                'explanation_en' => 'MFA reduces account takeover risk by requiring a second proof of identity beyond just the password.',
                'explanation_ar' => 'MFA يقلل خطر الاستيلاء على الحساب لأنه يطلب دليلاً إضافياً على الهوية بجانب كلمة المرور.',
            ],
            'phishing-definition' => [
                'question_en' => 'Which best describes a phishing attack?',
                'question_ar' => 'أيٌّ من التالي يصف هجوم التصيد (Phishing) بشكل أدق؟',
                'options_en' => ['A. Overloading a server with traffic', 'B. Tricking users into revealing credentials via fake messages', 'C. Scanning for open ports', 'D. Encrypting files for ransom'],
                'options_ar' => ['أ. إغراق خادم بحركة المرور', 'ب. خداع المستخدمين للكشف عن بياناتهم عبر رسائل مزيفة', 'ج. فحص المنافذ المفتوحة', 'د. تشفير الملفات طلباً للفدية'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['tricking', 'fake', 'credentials', 'خداع', 'مزيفة', 'بيانات'],
                'explanation_en' => 'Phishing is a social engineering attack where attackers impersonate trusted entities to steal credentials or install malware.',
                'explanation_ar' => 'التصيد هجوم هندسة اجتماعية يقوم فيه المهاجم بانتحال هوية جهة موثوقة لسرقة بيانات الدخول أو تثبيت برمجيات خبيثة.',
            ],
            'https-purpose' => [
                'question_en' => 'What does HTTPS protect compared to HTTP?',
                'question_ar' => 'ما الذي يضيفه HTTPS مقارنةً بـ HTTP؟',
                'options_en' => ['A. Faster page loads', 'B. Encrypted and authenticated connection between client and server', 'C. Larger file uploads', 'D. Automatic login'],
                'options_ar' => ['أ. تحميل أسرع للصفحات', 'ب. اتصال مشفر وموثّق بين العميل والخادم', 'ج. رفع ملفات أكبر', 'د. تسجيل دخول تلقائي'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['encrypted', 'authentication', 'tls', 'ssl', 'مشفر', 'تشفير'],
                'explanation_en' => 'HTTPS uses TLS to encrypt data in transit and verify the server\'s identity, preventing eavesdropping and tampering.',
                'explanation_ar' => 'HTTPS يستخدم TLS لتشفير البيانات أثناء النقل والتحقق من هوية الخادم، مما يمنع التنصت والتلاعب بالبيانات.',
            ],
            'firewall-function' => [
                'question_en' => 'What is the primary job of a firewall?',
                'question_ar' => 'ما الوظيفة الأساسية لجدار الحماية (Firewall)؟',
                'options_en' => ['A. Encrypt hard drive data', 'B. Filter network traffic based on rules', 'C. Back up databases automatically', 'D. Generate strong passwords'],
                'options_ar' => ['أ. تشفير بيانات القرص الصلب', 'ب. تصفية حركة الشبكة بناءً على قواعد', 'ج. النسخ الاحتياطي للقواعد تلقائياً', 'د. توليد كلمات مرور قوية'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['filter', 'traffic', 'rules', 'block', 'تصفية', 'حركة', 'قواعد'],
                'explanation_en' => 'A firewall inspects incoming and outgoing network traffic and blocks packets that violate defined security rules.',
                'explanation_ar' => 'جدار الحماية يفحص حركة الشبكة الواردة والصادرة ويحجب الحزم التي تنتهك قواعد الأمان المحددة.',
            ],
            'owasp-sqli' => [
                'question_en' => 'SQL Injection attacks target which layer of an application?',
                'question_ar' => 'هجمات SQL Injection تستهدف أي طبقة من التطبيق؟',
                'options_en' => ['A. The CSS styling layer', 'B. The database query layer', 'C. The DNS resolver', 'D. The TLS certificate'],
                'options_ar' => ['أ. طبقة CSS', 'ب. طبقة استعلامات قاعدة البيانات', 'ج. محلل DNS', 'د. شهادة TLS'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['database', 'query', 'sql', 'قاعدة البيانات', 'استعلام'],
                'explanation_en' => 'SQL Injection inserts malicious SQL into user input to manipulate the database. Prevention: use parameterized queries and prepared statements.',
                'explanation_ar' => 'SQL Injection تحقن كوداً SQL خبيثاً في مدخلات المستخدم للتلاعب بقاعدة البيانات. الحماية: استخدم Parameterized Queries وPrepared Statements.',
            ],
            'vpn-purpose' => [
                'question_en' => 'What is the primary security benefit of a VPN?',
                'question_ar' => 'ما الفائدة الأمنية الأساسية لـ VPN؟',
                'options_en' => ['A. Speeds up your internet connection', 'B. Encrypts traffic between your device and the VPN server', 'C. Permanently blocks all ads', 'D. Gives admin access to remote systems'],
                'options_ar' => ['أ. يسرّع اتصالك بالإنترنت', 'ب. يشفر حركة البيانات بين جهازك وخادم VPN', 'ج. يمنع الإعلانات بشكل دائم', 'د. يمنح وصولاً إدارياً للأنظمة البعيدة'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['encrypt', 'tunnel', 'privacy', 'يشفر', 'تشفير', 'خصوصية'],
                'explanation_en' => 'A VPN creates an encrypted tunnel for your traffic, protecting it from interception on untrusted networks like public Wi-Fi.',
                'explanation_ar' => 'الـ VPN ينشئ نفقاً مشفراً لحركة بياناتك، يحميها من الاعتراض على الشبكات غير الموثوقة كشبكات Wi-Fi العامة.',
            ],
            'least-privilege' => [
                'question_en' => 'Which principle states that users and systems should have only the minimum access they need?',
                'question_ar' => 'أي مبدأ ينص على أن المستخدمين والأنظمة يجب أن يمتلكوا الحد الأدنى من الصلاحيات اللازمة فقط؟',
                'options_en' => ['A. Defense in Depth', 'B. Principle of Least Privilege', 'C. Zero Trust', 'D. Need to Share'],
                'options_ar' => ['أ. الدفاع المتعمق', 'ب. مبدأ الامتياز الأدنى', 'ج. الثقة الصفرية', 'د. مبدأ المشاركة'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['least privilege', 'minimum access', 'الامتياز الأدنى', 'الحد الأدنى'],
                'explanation_en' => 'Least Privilege limits the damage a compromised account or system can do by ensuring it only has access to what it truly needs.',
                'explanation_ar' => 'مبدأ الامتياز الأدنى يقلل الضرر الناجم عن اختراق حساب أو نظام بضمان أن له صلاحيات ما يحتاجه فعلاً فقط.',
            ],
            'incident-response' => [
                'question_en' => 'What is the correct first step of an incident response plan?',
                'question_ar' => 'ما الخطوة الأولى الصحيحة في خطة الاستجابة للحوادث؟',
                'options_en' => ['A. Eradication', 'B. Recovery', 'C. Preparation', 'D. Containment'],
                'options_ar' => ['أ. الاستئصال', 'ب. الاسترداد', 'ج. التحضير', 'د. الاحتواء'],
                'correct' => 'C',
                'correct_ar' => 'ج',
                'acceptable' => ['preparation', 'prepare', 'planning', 'التحضير', 'الاستعداد'],
                'explanation_en' => 'The six NIST incident response phases are: Preparation → Identification → Containment → Eradication → Recovery → Lessons Learned. Preparation comes first.',
                'explanation_ar' => 'مراحل الاستجابة للحوادث الستة وفق NIST: التحضير ← التعرف ← الاحتواء ← الاستئصال ← الاسترداد ← الدروس المستفادة. التحضير يأتي أولاً.',
            ],
            'zero-trust' => [
                'question_en' => 'What is the core principle of Zero Trust security?',
                'question_ar' => 'ما المبدأ الجوهري لنموذج الأمان Zero Trust؟',
                'options_en' => ['A. Trust everyone inside the network perimeter', 'B. Never trust, always verify — every request must be authenticated', 'C. Disable all firewalls to improve speed', 'D. Share credentials across teams for efficiency'],
                'options_ar' => ['أ. ثق بكل من هو داخل محيط الشبكة', 'ب. لا تثق أبداً، تحقق دائماً — كل طلب يجب مصادقته', 'ج. تعطيل جدران الحماية لتحسين السرعة', 'د. مشاركة بيانات الاعتماد عبر الفرق لزيادة الكفاءة'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['never trust', 'always verify', 'لا تثق', 'تحقق دائماً'],
                'explanation_en' => 'Zero Trust assumes breach and requires continuous verification of every user and device, inside or outside the network perimeter.',
                'explanation_ar' => 'Zero Trust يفترض أن الاختراق حادث ويتطلب التحقق المستمر من كل مستخدم وجهاز سواء كانوا داخل أو خارج الشبكة.',
            ],
            'password-policy' => [
                'question_en' => 'Which password policy practice provides the MOST security benefit?',
                'question_ar' => 'أي ممارسة في سياسة كلمات المرور توفر أكبر فائدة أمنية؟',
                'options_en' => ['A. Require changing passwords every 30 days', 'B. Use long passphrases (16+ characters) with no forced rotation', 'C. Allow reusing the last 3 passwords', 'D. Store passwords in a shared spreadsheet'],
                'options_ar' => ['أ. إلزام تغيير كلمات المرور كل 30 يوماً', 'ب. استخدام عبارات مرور طويلة (16+ حرف) دون تدوير قسري', 'ج. السماح بإعادة استخدام آخر 3 كلمات مرور', 'د. تخزين كلمات المرور في ملف مشترك'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['long', 'passphrase', '16', 'طويلة', 'عبارة مرور'],
                'explanation_en' => 'Long passphrases are harder to crack than short complex passwords. Frequent forced rotation often leads to weaker, predictable passwords.',
                'explanation_ar' => 'العبارات الطويلة أصعب في الاختراق من كلمات المرور القصيرة المعقدة. التدوير القسري المتكرر يؤدي غالباً لكلمات مرور أضعف وأكثر توقعاً.',
            ],
            'social-engineering' => [
                'question_en' => 'A caller claims to be IT support and asks for your password to "fix an issue." This is an example of:',
                'question_ar' => 'يدّعي متصل أنه دعم تقني ويطلب كلمة مرورك لـ "إصلاح مشكلة". هذا مثال على:',
                'options_en' => ['A. Brute force attack', 'B. Pretexting (a social engineering technique)', 'C. SQL Injection', 'D. Man-in-the-middle attack'],
                'options_ar' => ['أ. هجوم القوة الغاشمة', 'ب. التذرع (أسلوب من أساليب الهندسة الاجتماعية)', 'ج. SQL Injection', 'د. هجوم الوسيط'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['pretexting', 'social engineering', 'تذرع', 'هندسة اجتماعية'],
                'explanation_en' => 'Pretexting is creating a fabricated scenario to manipulate someone into revealing information. Legitimate IT support never asks for passwords.',
                'explanation_ar' => 'التذرع هو اختلاق سيناريو مزيف للتلاعب بشخص ما وإقناعه بالكشف عن معلومات. الدعم التقني الشرعي لا يطلب كلمات المرور أبداً.',
            ],
            'patch-management' => [
                'question_en' => 'Why is timely patch management critical for security?',
                'question_ar' => 'لماذا تُعدّ إدارة التحديثات والتصحيحات في الوقت المناسب أمراً حيوياً للأمن؟',
                'options_en' => ['A. Patches add new features users want', 'B. Unpatched systems expose known vulnerabilities that attackers actively exploit', 'C. Patching only improves performance, not security', 'D. Patches are optional and only for enterprise systems'],
                'options_ar' => ['أ. التحديثات تضيف ميزات يريدها المستخدمون', 'ب. الأنظمة غير المُحدَّثة تكشف ثغرات معروفة يستغلها المهاجمون بنشاط', 'ج. التحديثات تحسّن الأداء فقط لا الأمان', 'د. التحديثات اختيارية للأنظمة المؤسسية فقط'],
                'correct' => 'B',
                'correct_ar' => 'ب',
                'acceptable' => ['unpatched', 'vulnerabilities', 'exploit', 'ثغرات', 'غير محدّثة'],
                'explanation_en' => 'Most breaches exploit known, already-patched vulnerabilities. Keeping systems updated closes those doors before attackers walk through them.',
                'explanation_ar' => 'معظم الاختراقات تستغل ثغرات معروفة وتم تصحيحها بالفعل. إبقاء الأنظمة محدّثة يغلق تلك الأبواب قبل أن يلجها المهاجمون.',
            ],
            'encryption-types' => [
                'question_en' => 'Which statement correctly describes the difference between symmetric and asymmetric encryption?',
                'question_ar' => 'أي عبارة تصف الفرق بشكل صحيح بين التشفير المتماثل وغير المتماثل؟',
                'options_en' => ['A. Symmetric uses one shared key; asymmetric uses a public/private key pair', 'B. Symmetric is slower than asymmetric for all use cases', 'C. Asymmetric uses one shared key; symmetric uses a key pair', 'D. Both use the same algorithm but different key sizes'],
                'options_ar' => ['أ. المتماثل يستخدم مفتاحاً مشتركاً واحداً؛ غير المتماثل يستخدم زوج مفاتيح عام/خاص', 'ب. المتماثل أبطأ من غير المتماثل في جميع الحالات', 'ج. غير المتماثل يستخدم مفتاحاً مشتركاً؛ المتماثل يستخدم زوج مفاتيح', 'د. كلاهما يستخدم نفس الخوارزمية بأحجام مفاتيح مختلفة'],
                'correct' => 'A',
                'correct_ar' => 'أ',
                'acceptable' => ['symmetric', 'shared key', 'public', 'private', 'متماثل', 'مفتاح مشترك', 'عام', 'خاص'],
                'explanation_en' => 'Symmetric encryption (AES) uses one shared secret key — fast but key distribution is hard. Asymmetric (RSA) uses a public key to encrypt and a private key to decrypt — great for key exchange.',
                'explanation_ar' => 'التشفير المتماثل (AES) يستخدم مفتاحاً سرياً مشتركاً — سريع لكن توزيع المفتاح صعب. غير المتماثل (RSA) يستخدم مفتاحاً عاماً للتشفير وخاصاً لفك التشفير — ممتاز لتبادل المفاتيح.',
            ],
        ];
    }
}
