<?php

namespace App\Services\Ai;

use App\Models\Lesson;
use App\Models\User;
use App\Traits\DetectsArabic;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Deterministic, bilingual replies used when no AI call is needed or every provider fails.
 */
class CannedResponses
{
    use DetectsArabic;

    public function __construct(
        private IntentDetector $intents,
    ) {}

    public function safetyResponse(string $message): string
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

    public function rateLimitResponse(string $message): string
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

    public function dailyLimitResponse(string $message, User $user): string
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

    public function smallTalkResponse(string $message, string $agentType, ?User $user): string
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

    /**
     * @return array{message: string, meta: array<string, mixed>}
     */
    public function agentRedirectResponse(string $message, string $currentAgent, string $targetAgent): array
    {
        $agents = [
            'navigation' => ['en' => 'Guide', 'ar' => 'المرشد', 'scope_en' => 'plans, roadmaps, schedules, learning resources, and lesson navigation', 'scope_ar' => 'الخطط، المسارات، الجداول، مصادر التعلم، والتنقل بين الدروس'],
            'explanation' => ['en' => 'Tutor', 'ar' => 'الشارح', 'scope_en' => 'cybersecurity concepts, definitions, comparisons, safe examples, and concept links', 'scope_ar' => 'شرح المفاهيم، التعاريف، المقارنات، الأمثلة الآمنة، وروابط المفاهيم'],
            'video' => ['en' => 'Video', 'ar' => 'الفيديو', 'scope_en' => 'approved videos embedded in this lesson', 'scope_ar' => 'الفيديوهات المعتمدة داخل هذا الدرس فقط'],
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

    public function resourceLinksResponse(string $message, string $agentType): string
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

    public function fallbackResponse(string $message, string $agentType, ?Lesson $lesson, ?User $user = null): string
    {
        if ($this->intents->isGreetingOnly($message)) {
            return $this->greetingResponse($message, $agentType, $user);
        }

        if (in_array($agentType, ['single_tutor', 'navigation'], true) && $this->intents->isStudyPlanRequest($message)) {
            $level = $user ? $this->intents->studentLevel($user, [], $message) : $this->intents->detectLevel($message);

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

        if ($arabic) {
            return "خلّنا نشرحها بشكل آمن ومبسّط.\n\n"
                ."حدّد المفهوم اللي تبغاه بدقة وأشرحه لك: تعريفه، سبب أهميته، ومثال دفاعي عملي.\n\n"
                .'أمثلة: CIA Triad، التصيّد (Phishing)، MFA، كلمة المرور القوية، DNS، جدار الحماية، HTTPS، VPN، حقن SQL، التشفير والهاش، برامج الفدية.';
        }

        return "I can explain any cybersecurity concept in a clear, defensive way.\n\n"
            ."Just name the concept and I will cover: its definition, why it matters, and a practical defensive example.\n\n"
            .'Examples: CIA Triad, Phishing, MFA, Strong Passwords, DNS, Firewall, HTTPS, VPN, SQL Injection, Encryption vs Hashing, Ransomware.';
    }

    private function knownConceptExplanation(string $message, bool $arabic): ?string
    {
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
                'ar' => "**MFA (المصادقة متعددة العوامل)** تضيف دليل هوية ثاني بعد كلمة المرور - شيء تملكه (هاتف/توكن) أو شيء أنت (بصمة).\n\n**ليش مهمة:** حتى لو سُرقت كلمة المرور، المهاجم ما يقدر يدخل بدون العامل الثاني. من أقوى وأبسط الدفاعات ضد الاستيلاء على الحساب.\n\nتبغى تعرف أقوى أنواع MFA؟",
            ],
            'dns' => [
                'match' => ['dns', 'domain name system', 'نظام أسماء النطاقات', 'دي ان اس'],
                'en' => "**DNS (Domain Name System)** translates human-readable domain names (like example.com) into IP addresses so devices can find each other.\n\n**Security angle:** attackers may try DNS spoofing or poisoning to redirect you to fake sites. Defenses include DNSSEC, trusted resolvers, and monitoring for unusual lookups.\n\nWant a simple analogy for how DNS works?",
                'ar' => "**DNS (نظام أسماء النطاقات)** يترجم أسماء النطاقات المفهومة للبشر (مثل example.com) إلى عناوين IP عشان الأجهزة تلقى بعضها.\n\n**الجانب الأمني:** المهاجمون ممكن يحاولوا DNS spoofing أو poisoning عشان يحوّلوك لمواقع مزيفة. الحماية تشمل DNSSEC، resolvers موثوقة، ومراقبة الاستعلامات الغريبة.\n\nتبغى تشبيه بسيط لطريقة عمل DNS؟",
            ],
            'sql_injection' => [
                'match' => ['sql injection', 'sqli', 'حقن sql', 'حقن اس كيو ال', 'حقن قواعد البيانات', 'حقن قاعدة البيانات'],
                'en' => "**SQL Injection (SQLi)** happens when an application builds a database query by gluing user input directly into the SQL text, so crafted input can change what the query does (read, modify, or delete data).\n\n**How developers prevent it:**\n1. **Parameterized queries / prepared statements** — the query structure is fixed and user input is sent separately as data. This is the main fix.\n2. **Use an ORM or query builder** (e.g., Laravel Eloquent) that parameterizes for you.\n3. **Validate input** — allow-list expected formats (numbers, emails, IDs).\n4. **Least privilege** — the app's database account should only have the permissions it needs.\n5. **Hide detailed errors** from users and add a WAF plus monitoring as extra layers.\n\nWant to see the difference between an unsafe and a safe query in a simple example?",
                'ar' => "**حقن SQL (SQL Injection)** يصير لما التطبيق يبني استعلام قاعدة البيانات بلصق مدخلات المستخدم مباشرة داخل نص الـ SQL، فمدخلات معيّنة ممكن تغيّر وظيفة الاستعلام (قراءة أو تعديل أو حذف بيانات).\n\n**كيف يمنعه المطورين:**\n1. **Parameterized Queries / Prepared Statements** — شكل الاستعلام ثابت والمدخلات تنرسل كبيانات منفصلة. هذا الحل الأساسي.\n2. **استخدم ORM** (مثل Eloquent في Laravel) لأنه يسوي الـ parameterization عنك.\n3. **تحقق من المدخلات** — اسمح بالصيغ المتوقعة فقط (أرقام، إيميلات، معرّفات).\n4. **أقل صلاحية** — حساب قاعدة البيانات للتطبيق ياخذ الصلاحيات اللي يحتاجها بس.\n5. **أخفِ رسائل الخطأ التفصيلية** عن المستخدم، وأضف WAF ومراقبة كطبقات إضافية.\n\nتبغى مثال بسيط يوضح الفرق بين استعلام غير آمن وآمن؟",
            ],
            'https' => [
                'match' => ['https', 'ssl', 'tls', 'شهادة ssl', 'بروتوكول https'],
                'en' => "**HTTPS** is HTTP protected by **TLS** encryption.\n\n**What it gives you:**\n1. **Confidentiality** — traffic between your browser and the site is encrypted, so others on the network cannot read it.\n2. **Integrity** — data cannot be silently modified in transit.\n3. **Authentication** — the site's certificate proves you are talking to the real domain.\n\n**Keep in mind:** the padlock only means the connection is encrypted — a phishing site can have HTTPS too, so still check the domain name.\n\nWant to know how certificates are verified?",
                'ar' => "**HTTPS** هو HTTP محمي بتشفير **TLS**.\n\n**وش يعطيك:**\n1. **السرية** — الاتصال بين المتصفح والموقع مشفّر، فاللي على نفس الشبكة ما يقدر يقرأه.\n2. **السلامة** — البيانات ما تتعدّل في الطريق بدون ما ينكشف.\n3. **التحقق من الهوية** — شهادة الموقع تثبت إنك تكلم النطاق الحقيقي.\n\n**انتبه:** القفل يعني إن الاتصال مشفّر بس — موقع التصيّد ممكن يكون عنده HTTPS برضه، فتأكد من اسم النطاق دايماً.\n\nتبغى تعرف كيف يتم التحقق من الشهادات؟",
            ],
            'vpn' => [
                'match' => ['vpn', 'virtual private network', 'شبكة افتراضية خاصة', 'في بي ان'],
                'en' => "**VPN (Virtual Private Network)** creates an encrypted tunnel between your device and a VPN server.\n\n**When it helps:**\n1. On **public Wi-Fi** — others on the network cannot read your traffic.\n2. **Remote work** — securely reach company systems as if you were in the office.\n3. Hides your traffic from the local network and your IP from the sites you visit.\n\n**Limits:** a VPN does not stop phishing or malware, and you are trusting the VPN provider with your traffic — choose a reputable one.\n\nWant to compare a VPN with HTTPS?",
                'ar' => "**VPN (الشبكة الافتراضية الخاصة)** تسوي نفق مشفّر بين جهازك وسيرفر الـ VPN.\n\n**متى يفيد:**\n1. على **الواي فاي العام** — اللي على نفس الشبكة ما يقدرون يقرون اتصالك.\n2. **العمل عن بُعد** — توصل لأنظمة الشركة بأمان كأنك في المكتب.\n3. يخفي اتصالك عن الشبكة المحلية ويخفي عنوان الـ IP عن المواقع.\n\n**حدوده:** الـ VPN ما يمنع التصيّد ولا البرمجيات الخبيثة، وأنت تأمّن مزوّد الـ VPN على بياناتك — فاختر مزوّد موثوق.\n\nتبغى مقارنة بين الـ VPN والـ HTTPS؟",
            ],
            'encryption_hashing' => [
                'match' => ['encryption', 'encrypt', 'hashing', 'hash', 'symmetric', 'asymmetric', 'تشفير', 'هاش', 'تجزئه', 'متماثل'],
                'en' => "**Encryption vs. Hashing**\n\n**Encryption** is two-way: data is scrambled with a key and can be decrypted back with the right key. Used to protect data you need to read later (files, HTTPS traffic).\n- **Symmetric** (e.g., AES): one shared key — fast, used for bulk data.\n- **Asymmetric** (e.g., RSA, ECC): a public key to encrypt and a private key to decrypt — used for key exchange and digital signatures.\n\n**Hashing** is one-way: it turns data into a fixed-length fingerprint (e.g., SHA-256) that cannot be reversed. Used to check integrity and to store passwords — with a slow, salted algorithm like **bcrypt** or **Argon2**.\n\n**Rule of thumb:** need the original back → encrypt. Only need to verify → hash.\n\nWant an example of how a website checks your password using a hash?",
                'ar' => "**الفرق بين التشفير والهاش**\n\n**التشفير (Encryption)** باتجاهين: البيانات تتشفّر بمفتاح وترجع لأصلها بالمفتاح الصحيح. نستخدمه لحماية بيانات نحتاج نقرأها بعدين (ملفات، اتصال HTTPS).\n- **متماثل (Symmetric)** مثل AES: مفتاح واحد مشترك — سريع ومناسب للبيانات الكبيرة.\n- **غير متماثل (Asymmetric)** مثل RSA وECC: مفتاح عام للتشفير ومفتاح خاص لفك التشفير — يُستخدم لتبادل المفاتيح والتوقيع الرقمي.\n\n**الهاش (Hashing)** باتجاه واحد: يحوّل البيانات لبصمة بطول ثابت (مثل SHA-256) ما ترجع لأصلها. نستخدمه للتحقق من سلامة البيانات ولتخزين كلمات المرور — بخوارزمية بطيئة مع salt مثل **bcrypt** أو **Argon2**.\n\n**القاعدة:** تحتاج ترجع الأصل ← شفّر. تحتاج تتحقق بس ← استخدم هاش.\n\nتبغى مثال كيف الموقع يتحقق من كلمة مرورك باستخدام الهاش؟",
            ],
            'ransomware' => [
                'match' => ['ransomware', 'برامج الفدية', 'برنامج فدية', 'فديه', 'رانسوم'],
                'en' => "**Ransomware** is malware that encrypts an organization's files and demands payment for the key — often also threatening to leak stolen data.\n\n**How organizations defend:**\n1. **Offline / immutable backups** that are tested regularly — the most important control.\n2. **Patch** systems and VPN/remote-access devices quickly.\n3. **MFA** on email, VPN and admin accounts.\n4. **Phishing awareness** — a common way in.\n5. **Least privilege and network segmentation** to limit spread.\n6. **EDR and monitoring** to catch it early, plus a practiced incident-response plan.\n\nWant to know what an incident-response plan for ransomware includes?",
                'ar' => "**برامج الفدية (Ransomware)** برمجيات خبيثة تشفّر ملفات الجهة وتطلب فلوس مقابل المفتاح — وأحياناً تهدد بنشر البيانات المسروقة.\n\n**كيف تحمي الجهات نفسها:**\n1. **نسخ احتياطية منفصلة (Offline/Immutable)** وتنجرب بانتظام — أهم ضابط.\n2. **التحديثات** للأنظمة وأجهزة الـ VPN والوصول عن بُعد بسرعة.\n3. **MFA** على الإيميل والـ VPN وحسابات المدراء.\n4. **التوعية بالتصيّد** — لأنه من أشهر طرق الدخول.\n5. **أقل صلاحية وتقسيم الشبكة** عشان ما ينتشر.\n6. **EDR ومراقبة** لاكتشافه بدري، مع خطة استجابة للحوادث متدرّب عليها.\n\nتبغى تعرف وش تتضمن خطة الاستجابة لحادثة فدية؟",
            ],
            'password' => [
                'match' => [
                    'password', 'strong password', 'make password', 'create password',
                    'password strength', 'good password', 'secure password', 'password policy',
                    'كلمة مرور', 'كلمه سر', 'باسورد', 'كلمة سر', 'مرور قوية', 'مرور قوي',
                    'تكوين باسورد', 'انشاء كلمه', 'كلمه مرور قوية', 'كيفيه تكوين',
                ],
                'en' => "**Strong Password Principles**\n\nA strong password follows these rules:\n\n1. **Length first** — use at least 12–16 characters. A long passphrase (e.g., `Coffee!Mountain\$River42`) is harder to crack than a short complex one.\n2. **Mix character types** — uppercase, lowercase, digits, and symbols (e.g., `@`, `!`, `#`).\n3. **Avoid predictable patterns** — no names, birthdays, \"password123\", or keyboard walks (\"qwerty\").\n4. **Unique per account** — never reuse the same password across sites.\n5. **Use a password manager** — tools like Bitwarden or 1Password generate and store strong passwords for you.\n6. **Add MFA** — even a strong password can be stolen; a second factor (app/SMS code) stops attackers who have it.\n\n**Why it matters:** weak or reused passwords are the #1 cause of account breaches.\n\nWould you like to know how password managers work, or how to evaluate your current password's strength?",
                'ar' => "**مبادئ كلمة المرور القوية**\n\nكلمة المرور القوية تتبع هذه القواعد:\n\n1. **الطول أولاً** — استخدم 12-16 حرفاً على الأقل. عبارة طويلة (مثل `قهوة!جبل\$نهر42`) أصعب في الاختراق من كلمة قصيرة معقدة.\n2. **خلط أنواع الأحرف** — أحرف كبيرة وصغيرة وأرقام ورموز (مثل `@`، `!`، `#`).\n3. **تجنب الأنماط المتوقعة** — لا أسماء، ولا تواريخ ميلاد، ولا \"password123\".\n4. **كلمة مرور مختلفة لكل حساب** — لا تعيد استخدام نفس الكلمة في أكثر من موقع.\n5. **استخدم مدير كلمات المرور** — أدوات مثل Bitwarden أو 1Password تولّد وتحفظ كلمات مرور قوية لك.\n6. **فعّل MFA** — حتى لو سُرقت كلمة المرور، العامل الثاني يوقف المهاجم.\n\n**ليش مهم:** كلمات المرور الضعيفة أو المعادة هي السبب الأول لاختراق الحسابات.\n\nتبغى تعرف كيف تشتغل مديرات كلمات المرور، أو كيف تقيّم قوة كلمة مرورك الحالية؟",
            ],
            'firewall' => [
                'match' => ['firewall', 'جدار الحماية', 'جدار حماية', 'فايروول'],
                'en' => "**Firewall** is a security control that monitors and filters network traffic based on defined rules.\n\n**Types:**\n1. **Packet filter** — checks source/destination IP and port; fast but limited context.\n2. **Stateful firewall** — tracks active connections; blocks unexpected inbound packets.\n3. **Application-layer (WAF)** — inspects HTTP content; blocks SQL injection, XSS, etc.\n4. **Next-gen firewall (NGFW)** — combines all above plus IPS and app awareness.\n\n**Defensive value:** firewalls reduce the attack surface by blocking traffic that has no legitimate reason to reach your system.\n\nWant to know how to decide which ports to allow or block?",
                'ar' => "**جدار الحماية (Firewall)** هو ضابط أمني يراقب ويصفي حركة الشبكة بناءً على قواعد محددة.\n\n**الأنواع:**\n1. **Packet Filter** — يفحص IP والبورت؛ سريع لكن محدود السياق.\n2. **Stateful Firewall** — يتابع الاتصالات النشطة؛ يحجب الحزم الواردة غير المتوقعة.\n3. **Application Layer (WAF)** — يفحص محتوى HTTP؛ يحجب SQL Injection وXSS.\n4. **Next-Gen Firewall (NGFW)** — يجمع كل ما سبق مع IPS ووعي التطبيقات.\n\n**الفائدة الدفاعية:** يقلص سطح الهجوم بحجب الحركة اللي ما لها سبب شرعي للوصول لنظامك.\n\nتبغى تعرف كيف تقرر أي بورتات تسمح بها أو تحجبها؟",
            ],
        ];

        foreach ($concepts as $concept) {
            if ($this->intents->containsAnyNormalized($message, $concept['match'])) {
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

    public function videoFallbackResponse(?Lesson $lesson, string $message): string
    {
        if (! $lesson) {
            return $this->containsArabic($message)
                ? 'لا توجد فيديوهات معتمدة مرتبطة بسؤال عام الآن. افتح درساً محدداً ثم اسألني داخل تبويب Video.'
                : 'There are no approved videos attached to a general question. Open a specific lesson, then ask me in the Video tab.';
        }

        $videos = $lesson->approvedVideoList();

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
}
