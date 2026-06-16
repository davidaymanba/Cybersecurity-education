<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\AiAgentService;
use App\Traits\DetectsArabic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    use DetectsArabic;

    public function __invoke(Request $request, AiAgentService $agents): JsonResponse
    {
        $data   = $this->validatedChatData($request);
        $lesson = isset($data['lesson_id']) ? Lesson::find($data['lesson_id']) : null;

        return response()->json($agents->respond(
            $request->user(),
            $lesson,
            $data['message'],
            $data['agent_type'],
            $data['platform_version'],
            $data['history'] ?? []
        ));
    }

    /**
     * Real-time streaming endpoint.
     * Uses Groq's native token-by-token SSE stream; falls back to OpenAI or hardcoded
     * fallbacks, which are then chunked and emitted at the same event-stream format.
     */
    public function stream(Request $request, AiAgentService $agents): StreamedResponse
    {
        $data   = $this->validatedChatData($request);
        $lesson = isset($data['lesson_id']) ? Lesson::find($data['lesson_id']) : null;
        $user   = $request->user();

        return response()->stream(function () use ($agents, $data, $lesson, $user): void {
            $send = function (string $event, array $payload): void {
                echo "event: {$event}\n";
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            };

            try {
                $send('start', ['ok' => true]);

                $agents->respondStreaming(
                    $user,
                    $lesson,
                    $data['message'],
                    $data['agent_type'],
                    $data['platform_version'],
                    $data['history'] ?? [],
                    // onChunk: called for every token/chunk from Groq or chunked fallback
                    function (string $token) use ($send): void {
                        $send('chunk', ['text' => $token]);
                    },
                    // onDone: called once with final metadata after all chunks
                    function (array $result) use ($send): void {
                        $send('done', $result);
                    },
                );
            } catch (\Throwable $exception) {
                report($exception);

                $send('error', [
                    'message' => $this->containsArabic($data['message'])
                        ? 'تعذر الاتصال بخدمة الذكاء الاصطناعي حالياً. حاول مرة أخرى بعد لحظات.'
                        : 'The AI service is currently unavailable. Please try again in a moment.',
                ]);
            }
        }, 200, [
            'Cache-Control'    => 'no-cache, no-transform',
            'Content-Type'     => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Validate and return sanitized chat request data.
     * History content is stripped of HTML and control characters to prevent prompt injection.
     *
     * @return array<string, mixed>
     */
    private function validatedChatData(Request $request): array
    {
        $data = $request->validate([
            'message'          => ['required', 'string', 'max:2000'],
            'history'          => ['nullable', 'array', 'max:20'],
            'history.*.role'   => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
            'lesson_id'        => ['nullable', 'exists:lessons,id'],
            'agent_type'       => ['nullable', 'in:single_tutor,navigation,explanation,video'],
            'platform_version' => ['nullable', 'in:single,multi'],
        ]);

        $data['agent_type']       ??= 'single_tutor';
        $data['platform_version'] ??= 'single';

        // Sanitize history: strip HTML and control characters to prevent prompt-injection attacks.
        if (!empty($data['history'])) {
            $data['history'] = $this->sanitizeHistory($data['history']);
        }

        return $data;
    }

    /**
     * Strip HTML tags and control characters from each history turn.
     * Limits content length to prevent context overflow attacks.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    private function sanitizeHistory(array $history): array
    {
        return collect($history)
            ->map(function (array $turn): array {
                $content = strip_tags((string) ($turn['content'] ?? ''));
                // Remove ASCII control characters except tab and newlines
                $content = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/', '', $content);
                $content = Str::limit(trim($content), 3000);

                return ['role' => $turn['role'], 'content' => $content];
            })
            ->filter(fn (array $turn): bool => $turn['content'] !== '')
            ->values()
            ->all();
    }
}

