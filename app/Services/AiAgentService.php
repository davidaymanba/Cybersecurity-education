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
            'prompt' => 'You are a careful cybersecurity education tutor. Explain concepts simply, answer only learning-focused questions, use examples, and avoid operational harmful instructions.',
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

    public function respond(User $user, ?Lesson $lesson, string $message, string $agentType, string $version): array
    {
        $agent = self::AGENTS[$agentType] ?? self::AGENTS['single_tutor'];
        $started = microtime(true);
        $content = $this->fallbackResponse($message, $agentType, $lesson);
        $tokens = 0;

        if (config('services.openai.api_key')) {
            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(25)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model'),
                    'messages' => [
                        ['role' => 'system', 'content' => $agent['prompt']],
                        ['role' => 'system', 'content' => 'Lesson context: '.($lesson?->title ?? 'General course support').' - '.Str::limit(strip_tags($lesson?->content ?? ''), 1600)],
                        ['role' => 'user', 'content' => $message],
                    ],
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

    private function fallbackResponse(string $message, string $agentType, ?Lesson $lesson): string
    {
        return match ($agentType) {
            'navigation' => 'Start with the current lesson goals, complete the quiz, then continue to the next lesson in the sidebar. For this topic, focus on: '.($lesson?->summary ?? 'core cybersecurity foundations').'.',
            'video' => 'I can recommend the approved videos embedded for this lesson. Watch the first video, take notes on the key controls, then return for the quiz.',
            default => 'Here is a study-safe explanation: '.($lesson?->summary ?? 'break the concept into definition, risk, example, and defense.').' Your question was: "'.Str::limit($message, 120).'".',
        };
    }
}
