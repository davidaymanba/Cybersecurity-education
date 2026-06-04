<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiAgentService
{
    public const AGENTS = [
        'single_tutor' => [
            'name' => 'Cyber Mentor',
            'role' => 'Single AI Agent',
            'prompt' => <<<'PROMPT'
You are "Cyber Mentor" - a friendly, professional, and encouraging Cybersecurity Study Mentor.

## Important Rules for Every Response:
1. Match the student's language:
   - If the student writes in Arabic, reply in Arabic.
   - If the student writes in English, reply in English.
   - If the message mixes Arabic and English, use the dominant language.
   - Do not apologize for changing languages. Just answer naturally in the same language.
   - When replying in Arabic, use Arabic only except for standard technical terms such as TCP/IP, DNS, Linux, Nmap, OWASP, and URLs. Never insert unrelated languages or non-Arabic filler words.

2. Always start every response with a short introduction in the student's language before any educational content:
   - English: "Hello! I am Cyber Mentor, your specialized cybersecurity study assistant."
   - Arabic: "مرحباً! أنا Cyber Mentor، مساعدك المتخصص في الأمن السيبراني."

3. Response length:
   Keep replies concise and useful. Do not exceed 300-400 words unless the student explicitly asks for more details.

4. Formatting and organization:
   - Use bold subheadings when helpful.
   - Use numbered lists or bullet points for steps.
   - Use clear examples.
   - Separate ideas with short spacing.
   - For long plans, use short sections, clear spacing, and no more than 3-5 bullets per section.
   - Keep links on their own lines when possible.

5. Style:
   - Be encouraging and friendly.
   - Use clear, simple language.
   - Focus only on cybersecurity.
   - End with a question that helps identify what the student wants next.
   - Keep all guidance educational and defensive. Do not provide harmful operational instructions.

6. Intent handling:
   - Treat short greetings and casual openers as greetings only. Examples: "hi", "hii", "hiii", "hello", "hey", "salam", "السلام عليكم", "اهلا", "أهلاً", "مرحبا".
   - For a greeting-only message, reply with a warm greeting and briefly ask what cybersecurity topic, explanation, resource, quiz, or plan the student wants. Do not ask for Beginner/Intermediate/Expert unless the student explicitly asks for a plan.
   - Only trigger the study-plan flow when the latest user message clearly asks for a plan, roadmap, schedule, learning path, curriculum, or uses Arabic equivalents like "خطة", "مسار", "جدول", "خارطة طريق".
   - Do not infer a plan request from the default workflow, from a greeting, or from generic messages like "start", "help", "hiii", or "what can you do?".
   - If the student says only "plan" or "خطة", that is an explicit study-plan request.

7. Safety boundaries:
   - Refuse requests that enable real-world harm, including stealing credentials, phishing, malware, ransomware, unauthorized access, evasion, persistence, exploit payloads for real targets, bypassing authentication, or hiding activity.
   - Do not provide step-by-step offensive instructions, weaponized code, payloads, or target-specific exploitation guidance.
   - When refusing, stay polite and redirect to safe learning: defensive concepts, lab-only practice, ethics, detection, prevention, incident response, or high-level explanations.
   - Safe examples are allowed only when clearly framed for legal labs, defensive education, or conceptual understanding.

8. Study plan requests:
   - If the student asks for a cybersecurity learning plan, roadmap, schedule, or path, do not provide the plan immediately unless their level is already clear from the conversation.
   - First ask them to choose exactly one level: Beginner, Intermediate, or Expert.
   - Keep that clarification response short and focused.
   - English example response:
     "Hello! I am Cyber Mentor, your specialized cybersecurity study assistant.

     Before I build your plan, please choose your current level:
     1. Beginner - new to cybersecurity or still learning basics.
     2. Intermediate - you understand networking/Linux/security basics and want structure.
     3. Expert - you already have hands-on experience and want advanced specialization.

     Which level are you?"
   - Arabic example response:
     "مرحباً! أنا Cyber Mentor، مساعدك المتخصص في الأمن السيبراني.

     قبل ما أبني لك الخطة، اختر مستواك الحالي:
     1. Beginner - جديد في الأمن السيبراني أو ما زلت تتعلم الأساسيات.
     2. Intermediate - تفهم أساسيات الشبكات/Linux/الأمن وتحتاج خطة منظمة.
     3. Expert - لديك خبرة عملية وتريد تخصصاً متقدماً.

     ما هو مستواك؟"

## Original Guidelines
# Purpose
Help students create effective, personalized study plans focused on cyber security. Provide curated resources, explain core concepts, and recommend hands-on labs and exercises.

## General Guidelines
- Use clear, supportive language.
- Adapt recommendations based on the student's level (beginner, intermediate, advanced).
- Focus content strictly on cyber security topics and skills.

## Skills
- Build tailored study plans: Ask about student goals and experience, suggest daily/weekly schedules.
- Recommend learning materials: Point to articles, courses, labs, and trusted security resources.
- Explain concepts: Break down complex topics into simple explanations.
- Suggest practice exercises: Give practical tasks, such as setting up labs, exploring vulnerabilities in legal labs, or reviewing security news.

## Step-by-Step Workflow
1. Start with a friendly greeting. If the student only greets you, greet them back and ask how you can help with cybersecurity.
2. Ask about experience level and goals only when it is relevant to the student's request, especially when they request a study plan.
3. For study plan requests, first confirm whether the student is Beginner, Intermediate, or Expert before giving the plan.
4. Based on the response, suggest a study plan outline and resources suitable for the student.
5. Provide links to high-quality materials, explain concepts, and recommend practical tasks for each stage.
6. Encourage feedback and adapt the plan as the student progresses.

You are a helpful, patient, and encouraging Cybersecurity Study Mentor.
PROMPT,
        ],
        'navigation' => [
            'name' => 'Navigation Agent',
            'role' => 'Learning Guide',
            'prompt' => 'You are a structured educational guide. Help students choose next lessons, understand learning paths, and navigate the cybersecurity course.',
        ],
        'explanation' => [
            'name' => 'Explanation Agent',
            'role' => 'Concept Tutor',
            'prompt' => 'You are a friendly cybersecurity teacher. Simplify difficult concepts, give safe examples, and check for understanding.',
        ],
        'video' => [
            'name' => 'Video Agent',
            'role' => 'Video Curator',
            'prompt' => 'You recommend only approved embedded educational videos already available in the platform. Keep students focused and avoid unrelated browsing.',
        ],
    ];

    public function respond(User $user, ?Lesson $lesson, string $message, string $agentType, string $version, array $history = []): array
    {
        $agent = self::AGENTS[$agentType] ?? self::AGENTS['single_tutor'];
        $started = microtime(true);
        $content = $this->fallbackResponse($message, $agentType, $lesson);
        $tokens = 0;
        $unsafe = $this->isUnsafeRequest($message);

        $messages = $this->messages($agent['prompt'], $lesson, $history, $message);

        if ($unsafe) {
            $content = $this->safetyResponse($message);
        } elseif (config('services.groq.api_key')) {
            $response = Http::withToken(config('services.groq.api_key'))
                ->timeout(30)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => config('services.groq.model'),
                    'messages' => $messages,
                    'temperature' => 0.7,
                    'max_tokens' => 1200,
                    'top_p' => 0.9,
                ]);

            if ($response->successful()) {
                $payload = $response->json();
                $content = data_get($payload, 'choices.0.message.content', $content);
                $tokens = (int) data_get($payload, 'usage.total_tokens', 0);
            }
        } elseif (config('services.openai.api_key')) {
            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(25)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model'),
                    'messages' => $messages,
                    'temperature' => 0.4,
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                $payload = $response->json();
                $content = data_get($payload, 'choices.0.message.content', $content);
                $tokens = (int) data_get($payload, 'usage.total_tokens', 0);
            }
        }

        $interaction = AiInteraction::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson?->id,
            'platform_version' => $version,
            'agent_type' => $agentType,
            'prompt' => $message,
            'response' => $content,
            'tokens_used' => $tokens,
            'latency_ms' => (int) ((microtime(true) - $started) * 1000),
        ]);

        return [
            'message' => $content,
            'agent' => $agent,
            'interaction_id' => $interaction->id,
        ];
    }

    private function isUnsafeRequest(string $message): bool
    {
        $text = Str::lower($message);

        $harmfulIntent = [
            'steal password',
            'steal credentials',
            'phishing page',
            'phishing kit',
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
            'hack account',
            'hack instagram',
            'hack facebook',
            'hack gmail',
            'ddos',
            'botnet',
            'payload to hack',
            'exploit this target',
            'سرقة كلمة',
            'سرقة حساب',
            'صفحة تصيد',
            'تصيد',
            'اختراق حساب',
            'برمجية خبيثة',
            'رانسوم وير',
            'تعطيل مضاد',
            'تجاوز تسجيل الدخول',
        ];

        $instructionalIntent = [
            'how to',
            'steps',
            'code',
            'script',
            'payload',
            'build',
            'create',
            'make',
            'اكتب',
            'اعمل',
            'ازاي',
            'كيف',
            'خطوات',
            'كود',
            'سكربت',
        ];

        $hasHarmfulIntent = collect($harmfulIntent)->contains(fn ($term) => str_contains($text, $term));
        $asksForInstructions = collect($instructionalIntent)->contains(fn ($term) => str_contains($text, $term));

        return $hasHarmfulIntent && $asksForInstructions;
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

    private function containsArabic(string $message): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $message) === 1;
    }

    private function isGreetingOnly(string $message): bool
    {
        $text = trim(Str::lower($message));
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return preg_match('/^(h+i+|hello+|hey+|salam|السلام عليكم|اهلا|أهلا|أهلاً|مرحبا|مرحباً)$/u', $text) === 1;
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
            'خطة',
            'مسار',
            'جدول',
            'خارطة طريق',
        ])->contains(fn ($term) => str_contains($text, $term));
    }

    private function messages(string $prompt, ?Lesson $lesson, array $history, string $message): array
    {
        $messages = [
            ['role' => 'system', 'content' => $prompt],
            ['role' => 'system', 'content' => 'Lesson context: '.($lesson?->title ?? 'General course support').' - '.Str::limit(strip_tags($lesson?->content ?? ''), 1600)],
        ];

        foreach (array_slice($history, -12) as $item) {
            $role = $item['role'] ?? null;
            $content = trim((string) ($item['content'] ?? ''));

            if (in_array($role, ['user', 'assistant'], true) && $content !== '') {
                $messages[] = ['role' => $role, 'content' => Str::limit($content, 4000)];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }

    private function fallbackResponse(string $message, string $agentType, ?Lesson $lesson): string
    {
        if ($this->isGreetingOnly($message)) {
            if ($this->containsArabic($message)) {
                return 'مرحباً! أنا Cyber Mentor، مساعدك المتخصص في الأمن السيبراني.

كيف أقدر أساعدك اليوم؟ يمكنني شرح مفهوم، اقتراح مصادر، عمل اختبار قصير، أو بناء خطة إذا طلبت ذلك.';
            }

            return 'Hello! I am Cyber Mentor, your specialized cybersecurity study assistant.

How can I help you today? I can explain a concept, suggest resources, quiz you, or build a plan if you ask for one.';
        }

        if ($agentType === 'single_tutor' && $this->isStudyPlanRequest($message)) {
            if ($this->containsArabic($message)) {
                return 'مرحباً! أنا Cyber Mentor، مساعدك المتخصص في الأمن السيبراني.

قبل ما أبني لك الخطة، اختر مستواك الحالي:
1. Beginner - جديد في الأمن السيبراني أو ما زلت تتعلم الأساسيات.
2. Intermediate - تفهم أساسيات الشبكات/Linux/الأمن وتحتاج خطة منظمة.
3. Expert - لديك خبرة عملية وتريد تخصصاً متقدماً.

ما هو مستواك؟';
            }

            return 'Hello! I am Cyber Mentor, your specialized cybersecurity study assistant.

Before I build your plan, please choose your current level:
1. Beginner - new to cybersecurity or still learning basics.
2. Intermediate - you understand networking/Linux/security basics and want structure.
3. Expert - you already have hands-on experience and want advanced specialization.

Which level are you?';
        }

        return match ($agentType) {
            'navigation' => 'Start with the current lesson goals, complete the quiz, then continue to the next lesson in the sidebar. For this topic, focus on: '.($lesson?->summary ?? 'core cybersecurity foundations').'.',
            'video' => 'I can recommend the approved videos embedded for this lesson. Watch the first video, take notes on the key controls, then return for the quiz.',
            default => 'Here is a study-safe explanation: '.($lesson?->summary ?? 'break the concept into definition, risk, example, and defense.').' Your question was: "'.Str::limit($message, 120).'".',
        };
    }
}
