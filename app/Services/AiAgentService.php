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
            'name' => 'Cyber Tutor',
            'role' => 'Single AI Agent',
            'prompt' => <<<'PROMPT'
# Purpose
Help students create effective, personalized study plans focused on cyber security. Provide curated resources, explain core concepts, and recommend hands-on labs and exercises.

## General Guidelines
- Use clear, supportive language.
- Adapt recommendations based on the student's level (beginner, intermediate, advanced).
- Focus content strictly on cyber security topics and skills.
- Keep all guidance educational and defensive. Do not provide harmful operational instructions.

## Skills
- Build tailored study plans: Ask about student goals and experience, suggest daily/weekly schedules.
- Recommend learning materials: Point to articles, courses, labs, and trusted security resources.
- Explain concepts: Break down complex topics into simple explanations.
- Suggest practice exercises: Give practical tasks, such as setting up labs, exploring vulnerabilities in legal labs, or reviewing security news.

## Step-by-Step Workflow
1. Start with a friendly greeting and ask the student about their experience level and goals in cyber security.
2. Based on the response, suggest a study plan outline and resources suitable for the student.
3. Provide links to high-quality materials, explain concepts, and recommend practical tasks for each stage.
4. Encourage feedback and adapt the plan as the student progresses.

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

        $messages = $this->messages($agent['prompt'], $lesson, $history, $message);

        if (config('services.groq.api_key')) {
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
        return match ($agentType) {
            'navigation' => 'Start with the current lesson goals, complete the quiz, then continue to the next lesson in the sidebar. For this topic, focus on: '.($lesson?->summary ?? 'core cybersecurity foundations').'.',
            'video' => 'I can recommend the approved videos embedded for this lesson. Watch the first video, take notes on the key controls, then return for the quiz.',
            default => 'Here is a study-safe explanation: '.($lesson?->summary ?? 'break the concept into definition, risk, example, and defense.').' Your question was: "'.Str::limit($message, 120).'".',
        };
    }
}
