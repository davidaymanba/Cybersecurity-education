<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Ai\AiProviderClient;
use App\Services\Ai\CannedResponses;
use App\Services\Ai\IntentDetector;
use App\Services\Ai\PromptBuilder;
use App\Services\Ai\QuizTutor;
use App\Traits\DetectsArabic;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Orchestrates a tutoring turn: limits, safety, deterministic routing, caching,
 * the AI call (via Ai\AiProviderClient) and research logging.
 */
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

    public function __construct(
        private IntentDetector $intents,
        private QuizTutor $quizzes,
        private CannedResponses $replies,
        private PromptBuilder $prompts,
        private AiProviderClient $providers,
    ) {}

    public function respond(User $user, ?Lesson $lesson, string $message, string $agentType, string $version, array $history = []): array
    {
        $started = microtime(true);
        $history = $this->verifiedHistory($user, $history);
        $agent = self::AGENTS[$agentType] ?? self::AGENTS['single_tutor'];
        $turn = $this->prepareTurn($user, $lesson, $message, $agentType, $history, $agent);

        $content = $turn['content'];
        $tokens = 0;

        if ($content === null) {
            $result = $this->providers->resolveAiResponse(
                $turn['messages'],
                $this->replies->fallbackResponse($message, $agentType, $lesson, $user),
                $version,
            );
            $content = $result['content'];
            $tokens = $result['tokens'];
        }

        $interaction = $this->recordTurn($user, $lesson, $message, $agentType, $version, $history, $turn, $content, $tokens, $started);

        return [
            'message' => $this->personalizeResponse($content, $message, $user),
            'agent' => $agent,
            'interaction_id' => $interaction->id,
            'meta' => $turn['meta'],
        ];
    }

    /**
     * Everything that happens before an AI call, shared by respond() and respondStreaming():
     * limits, safety, deterministic routing, agent scope and the per-user cache.
     *
     * 'content' is the final (un-personalised) reply when no AI call is needed; otherwise it is
     * null and 'messages' holds the prompt to send to the provider.
     *
     * @return array{content: ?string, messages: ?array, meta: array, hash: ?string, cache_hit: bool}
     */
    private function prepareTurn(User $user, ?Lesson $lesson, string $message, string $agentType, array $history, array $agent): array
    {
        $turn = ['content' => null, 'messages' => null, 'meta' => [], 'hash' => null, 'cache_hit' => false];

        if (! $this->withinRateLimit($user)) {
            $turn['content'] = $this->replies->rateLimitResponse($message);
        } elseif (! $this->withinDailyLimit($user)) {
            $turn['content'] = $this->replies->dailyLimitResponse($message, $user);
        } elseif ($this->intents->isUnsafeRequest($message)) {
            $turn['content'] = $this->replies->safetyResponse($message);
        } elseif ($deterministic = $this->deterministicResponse($message, $agentType, $history, $lesson, $user)) {
            $turn['content'] = $deterministic;
        } elseif ($scope = $this->scopeResponse($message, $agentType)) {
            $turn['content'] = $scope['message'];
            $turn['meta'] = $scope['meta'];
        } else {
            // Only context-free questions are cacheable, and only per user: replies are
            // personalised (name, level, recent context) and must never leak across users.
            $turn['hash'] = empty($history) ? $this->messageHash($message, $agentType, $lesson?->id) : null;
            $cached = $turn['hash'] ? $this->findCachedResponse($turn['hash'], $user->id) : null;

            if ($cached !== null) {
                $turn['content'] = $cached;
                $turn['cache_hit'] = true;
            } else {
                $turn['messages'] = $this->prompts->messages($user, $agent['prompt_key'], $lesson, $history, $message);
            }
        }

        return $turn;
    }

    /**
     * Persist the turn for research analytics. The raw (un-personalised) reply is stored so it
     * can be reused as a cache hit and matched by verifiedHistory().
     */
    private function recordTurn(
        User $user,
        ?Lesson $lesson,
        string $message,
        string $agentType,
        string $version,
        array $history,
        array $turn,
        string $rawContent,
        int $tokens,
        float $started,
    ): AiInteraction {
        $this->maybePersistLevel($user, $message, $history);

        return AiInteraction::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson?->id,
            'platform_version' => $version,
            'agent_type' => $agentType,
            'message_hash' => $turn['hash'],
            'prompt' => $message,
            'response' => $rawContent,
            'tokens_used' => $tokens,
            'latency_ms' => (int) ((microtime(true) - $started) * 1000),
            'cache_hit' => $turn['cache_hit'],
        ]);
    }

    /**
     * History comes from the browser, so a student could forge "assistant" turns to inject
     * instructions. Keep an assistant turn only if it matches a reply this server actually
     * sent to this user recently (optionally with the name prefix, or truncated).
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function verifiedHistory(User $user, array $history): array
    {
        if (! collect($history)->contains(fn ($turn) => ($turn['role'] ?? null) === 'assistant')) {
            return $history;
        }

        $normalize = fn (string $text): string => trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));
        $name = trim((string) $user->name);

        $candidates = AiInteraction::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('response')
            ->latest()
            ->take(100)
            ->pluck('response')
            ->flatMap(function (string $raw) use ($normalize, $name): array {
                $raw = $normalize($raw);

                return $name === '' ? [$raw] : [$raw, $normalize("{$name}, {$raw}"), $normalize("{$name}، {$raw}")];
            })
            ->filter()
            ->unique()
            ->values();

        return collect($history)
            ->filter(function (array $turn) use ($candidates, $normalize): bool {
                if (($turn['role'] ?? null) !== 'assistant') {
                    return true;
                }

                $content = $normalize((string) ($turn['content'] ?? ''));
                $truncated = str_ends_with($content, '...') ? rtrim(mb_substr($content, 0, -3)) : null;

                return $candidates->contains(fn (string $sent): bool => $sent === $content
                    || ($truncated !== null && mb_strlen($truncated) >= 200 && str_starts_with($sent, $truncated)));
            })
            ->values()
            ->all();
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
    private function findCachedResponse(string $hash, int $userId): ?string
    {
        $ttlDays = config('services.ai.cache_ttl_days', 30);

        $response = AiInteraction::where('message_hash', $hash)
            ->where('user_id', $userId)
            ->where('tokens_used', '>', 0)
            ->where('cache_hit', false)
            ->whereNotNull('response')
            ->where('created_at', '>=', now()->subDays($ttlDays))
            ->latest()
            ->value('response');

        return $response ?: null;
    }

    // containsArabic() is provided by the DetectsArabic trait.

    private function deterministicResponse(string $message, string $agentType, array $history, ?Lesson $lesson, User $user): ?string
    {
        if ($this->intents->isGreetingOnly($message)) {
            return $this->replies->fallbackResponse($message, $agentType, $lesson, $user);
        }

        if ($this->intents->isSmallTalk($message) && ! $this->intents->hasLearningIntent($message)) {
            return $this->replies->smallTalkResponse($message, $agentType, $user);
        }

        if (
            in_array($agentType, ['single_tutor', 'navigation'], true)
            && $this->intents->isStudyPlanRequest($message)
            && ! $this->intents->studentLevel($user, $history, $message)
        ) {
            return $this->replies->fallbackResponse($message, $agentType, $lesson, $user);
        }

        if (in_array($agentType, ['single_tutor', 'explanation'], true)) {
            if ($this->quizzes->isAnotherQuizRequest($message, $history)) {
                return $this->quizzes->quizQuestionResponse($message, $lesson, $history);
            }

            if ($this->quizzes->isQuizDeclineRequest($message, $history)) {
                return $this->quizzes->quizDeclineResponse($message, $history);
            }

            if ($response = $this->quizzes->quizEvaluationResponse($message, $history)) {
                return $response;
            }

            if ($this->intents->isQuizRequest($message)) {
                return $this->quizzes->quizQuestionResponse($message, $lesson, $history);
            }
        }

        if (in_array($agentType, ['single_tutor', 'navigation', 'explanation'], true) && $this->intents->isResourceRequest($message)) {
            return $this->replies->resourceLinksResponse($message, $agentType);
        }

        if ($agentType === 'video' && $this->intents->isResourceRequest($message)) {
            return $this->replies->videoFallbackResponse($lesson, $message);
        }

        return null;
    }

    /**
     * @return array{message: string, meta: array<string, mixed>}|null
     */
    private function scopeResponse(string $message, string $agentType): ?array
    {
        if ($agentType === 'single_tutor' || $this->intents->isGreetingOnly($message)) {
            return null;
        }

        return match ($agentType) {
            'navigation' => ($this->intents->isGuideRequest($message) || $this->intents->isResourceRequest($message))
                ? null
                : $this->replies->agentRedirectResponse($message, 'navigation', $this->intents->targetAgentFor($message)),
            'explanation' => ($this->intents->isConceptRequest($message) || $this->intents->isResourceRequest($message))
                ? null
                : $this->replies->agentRedirectResponse($message, 'explanation', $this->intents->targetAgentFor($message)),
            'video' => $this->intents->isVideoRequest($message)
                ? null
                : $this->replies->agentRedirectResponse($message, 'video', $this->intents->targetAgentFor($message)),
            default => null,
        };
    }

    /**
     * Persist a newly detected learning level to the user record and the session
     * so every subsequent agent can use it without re-asking.
     */
    private function maybePersistLevel(User $user, string $message, array $history): void
    {
        // Already stored — nothing to do.
        if ($user->learning_level && $this->intents->detectLevel((string) $user->learning_level)) {
            return;
        }

        $sources = array_merge([$message], array_column($history, 'content'));

        foreach ($sources as $source) {
            $level = $this->intents->detectLevel((string) $source);

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
        $started = microtime(true);
        $history = $this->verifiedHistory($user, $history);
        $agent = self::AGENTS[$agentType] ?? self::AGENTS['single_tutor'];
        $turn = $this->prepareTurn($user, $lesson, $message, $agentType, $history, $agent);

        $content = $turn['content'];
        $tokens = 0;
        $streamed = false;

        if ($content === null) {
            $groqKey = $this->providers->groqApiKey($version);

            if ($groqKey) {
                $buffer = '';
                $groq = $this->providers->streamFromGroq(
                    $turn['messages'],
                    $groqKey,
                    function (string $token) use ($onChunk, &$buffer): void {
                        $buffer .= $token;
                        $onChunk($token);
                    }
                );

                if ($groq['ok']) {
                    $streamed = true;
                    $content = $buffer;
                    $tokens = $groq['tokens'];
                } else {
                    $this->providers->logProviderFailure('Groq streaming failed; trying OpenAI.', $groq);
                }
            }

            if (! $streamed && config('services.openai.api_key')) {
                $openai = $this->providers->callOpenAI($turn['messages']);

                if ($openai['ok']) {
                    $content = $openai['content'];
                    $tokens = $openai['tokens'];
                } else {
                    $this->providers->logProviderFailure('OpenAI failed; using fallback response.', $openai);
                }
            }

            $content ??= $this->replies->fallbackResponse($message, $agentType, $lesson, $user);
        }

        // Streamed Groq tokens were already sent as-is; everything else is personalised and chunked.
        $finalContent = $streamed ? $content : $this->personalizeResponse($content, $message, $user);

        if (! $streamed) {
            foreach (mb_str_split($finalContent, 6) as $chunk) {
                $onChunk($chunk);
            }
        }

        $interaction = $this->recordTurn($user, $lesson, $message, $agentType, $version, $history, $turn, $content, $tokens, $started);

        $onDone([
            'message' => $finalContent,
            'agent' => $agent,
            'interaction_id' => $interaction->id,
            'meta' => $turn['meta'],
        ]);
    }
}
