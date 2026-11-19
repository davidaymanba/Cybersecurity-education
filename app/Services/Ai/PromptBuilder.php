<?php

namespace App\Services\Ai;

use App\Models\Lesson;
use App\Models\User;
use App\Services\PromptLoader;
use Illuminate\Support\Str;

/**
 * Builds the chat-completion message list (system policy, student and lesson context, history).
 */
class PromptBuilder
{
    public function __construct(
        private PromptLoader $promptLoader,
        private IntentDetector $intents,
        private QuizTutor $quizzes,
    ) {}

    public function messages(User $user, string $promptKey, ?Lesson $lesson, array $history, string $message): array
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
        $name = trim($user->name) !== '' ? $user->name : 'Student';
        $level = $this->intents->studentLevel($user, $history, $message);

        // Pull cross-agent context from the session (set by maybePersistLevel).
        $sessionLevel = null;
        if (function_exists('session')) {
            $sessionLevel = session('cyber_context.level');
        }

        $effectiveLevel = $level ?? $sessionLevel;
        $chatMemory = $this->chatMemoryContext($history, $message);
        $recent = $this->recentInteractionContext($user);

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
        $lastAssistant = $this->intents->lastAssistantMessage($history);

        if ($lastAssistant && $this->quizzes->lastQuizFromHistory($history)) {
            $lines[] = '- Pending quiz state: if the latest student message is an answer or asks for another question, grade or continue the quiz before changing topics.';
        }

        if ($lastAssistant && (str_contains($lastAssistant, 'Which level are you?') || str_contains($lastAssistant, 'وش مستواك؟'))) {
            $lines[] = '- Pending plan state: the assistant asked for Beginner, Intermediate, or Expert; use the student reply as the level and continue the plan.';
        }

        $lines[] = '- current student message: '.Str::limit(preg_replace('/\s+/u', ' ', trim($message)) ?? trim($message), 180);

        return implode("\n", $lines);
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
        $videos = $lesson->approvedVideoList();

        if ($videos->isEmpty()) {
            return $context."\nApproved videos: none available for this lesson.";
        }

        $videoLines = $videos
            ->map(fn ($video) => '- '.$video->title.' | Channel: '.$video->channel_name.' | Embed: https://www.youtube.com/embed/'.$video->youtube_id)
            ->implode("\n");

        return $context."\nApproved videos:\n".$videoLines;
    }
}
