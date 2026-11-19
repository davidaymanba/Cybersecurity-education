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
    private const HISTORY_TURNS = 6;

    private const USER_TURN_CHARS = 500;

    private const ASSISTANT_TURN_CHARS = 600;

    private const LESSON_CONTEXT_CHARS = 1000;

    public function __construct(
        private PromptLoader $promptLoader,
        private IntentDetector $intents,
        private QuizTutor $quizzes,
    ) {}

    public function messages(User $user, string $promptKey, ?Lesson $lesson, array $history, string $message): array
    {
        // One compact system message keeps prompt tokens low (Groq rate limits count tokens per minute).
        $messages = [[
            'role' => 'system',
            'content' => implode("\n\n", [
                $this->promptLoader->get('core.policy'),
                $this->promptLoader->get('core.behavior'),
                $this->promptLoader->get($promptKey),
                $this->studentContext($user, $history, $message),
                $this->lessonContext($lesson),
            ]),
        ]];

        foreach (array_slice($history, -self::HISTORY_TURNS) as $item) {
            $role = $item['role'] ?? null;
            $content = trim((string) ($item['content'] ?? ''));

            if (in_array($role, ['user', 'assistant'], true) && $content !== '') {
                $limit = $role === 'assistant' ? self::ASSISTANT_TURN_CHARS : self::USER_TURN_CHARS;
                $messages[] = ['role' => $role, 'content' => Str::limit($content, $limit)];
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

        $profile = "## Student\n- Name: {$name}\n- Current level: ".($effectiveLevel ?? 'unknown (ask only when needed for a plan)')
            ."\n- Reply language: ".$this->replyLanguage($message, $history)
            .' — write the whole reply in it, including short verdicts like correct/incorrect.';

        if ($chatMemory !== '') {
            $profile .= "\n".$chatMemory;
        }

        return $profile."\n- Recent questions (other tabs too; avoid repeating yourself):\n".$recent;
    }

    /**
     * The language of the latest message decides the reply language. Stating it explicitly stops
     * the model from drifting into Arabic because of earlier Arabic turns or the Saudi context.
     */
    private function replyLanguage(string $message, array $history): string
    {
        $arabic = preg_match_all('/\p{Arabic}/u', $message);
        $latin = preg_match_all('/[A-Za-z]/', $message);

        if ($arabic === 0 && preg_match('/\b(ezay|izay|3ayez|3awez|3awz|keda|kda|7aga|a3mel|e3mel|ab8a|abgha|wesh|shlon|eish)\b/i', $message)) {
            return 'Arabic (the student wrote Arabizi; answer in Arabic script)';
        }

        // Quiz answers like "A" or "2" carry no language: continue in the conversation's language.
        if ($arabic === 0 && $latin < 3) {
            return $this->intents->prefersArabicReply($message, $history) ? 'Arabic' : 'English';
        }

        return $arabic >= $latin ? 'Arabic' : 'English';
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

        return implode("\n", $lines);
    }

    private function recentInteractionContext(User $user): string
    {
        $interactions = $user->aiInteractions()
            ->latest()
            ->take(3)
            ->get(['agent_type', 'prompt']);

        if ($interactions->isEmpty()) {
            return '  - none';
        }

        // Only the student's questions: enough for continuity without resending long replies.
        return $interactions
            ->map(fn ($interaction) => "  - [{$interaction->agent_type}] ".Str::limit(preg_replace('/\s+/u', ' ', trim((string) $interaction->prompt)) ?? '', 100))
            ->implode("\n");
    }

    private function lessonContext(?Lesson $lesson): string
    {
        if (! $lesson) {
            return '## Lesson\nGeneral course support (no specific lesson open).';
        }

        $context = "## Lesson\n".$lesson->title.' - '.Str::limit(preg_replace('/\s+/u', ' ', strip_tags($lesson->content)) ?? '', self::LESSON_CONTEXT_CHARS);
        $videos = $lesson->approvedVideoList();

        if ($videos->isEmpty()) {
            return $context."\nApproved videos: none available for this lesson.";
        }

        $videoLines = $videos
            ->map(fn ($video) => '- '.$video->title.' ('.$video->channel_name.'): https://www.youtube.com/embed/'.$video->youtube_id)
            ->implode("\n");

        return $context."\nApproved videos:\n".$videoLines;
    }
}
