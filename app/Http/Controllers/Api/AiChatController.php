<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\AiAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    public function __invoke(Request $request, AiAgentService $agents): JsonResponse
    {
        $data = $this->validatedChatData($request);
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

    public function stream(Request $request, AiAgentService $agents): StreamedResponse
    {
        $data = $this->validatedChatData($request);
        $lesson = isset($data['lesson_id']) ? Lesson::find($data['lesson_id']) : null;
        $user = $request->user();

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

                $result = $agents->respond(
                    $user,
                    $lesson,
                    $data['message'],
                    $data['agent_type'],
                    $data['platform_version'],
                    $data['history'] ?? []
                );

                foreach ($this->streamChunks($result['message']) as $chunk) {
                    $send('chunk', ['text' => $chunk]);
                    usleep(15000);
                }

                $send('done', [
                    'message' => $result['message'],
                    'interaction_id' => $result['interaction_id'],
                    'meta' => $result['meta'] ?? [],
                ]);
            } catch (\Throwable $exception) {
                report($exception);

                $send('error', [
                    'message' => $this->containsArabic($data['message'])
                        ? 'تعذر الاتصال بخدمة الذكاء الاصطناعي حالياً. حاول مرة أخرى بعد لحظات.'
                        : 'The AI service is currently unavailable. Please try again in a moment.',
                ]);
            }
        }, 200, [
            'Cache-Control' => 'no-cache, no-transform',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedChatData(Request $request): array
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:4000'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'agent_type' => ['nullable', 'in:single_tutor,navigation,explanation,video'],
            'platform_version' => ['nullable', 'in:single,multi'],
        ]);

        $data['agent_type'] ??= 'single_tutor';
        $data['platform_version'] ??= 'single';

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function streamChunks(string $message): array
    {
        return mb_str_split($message, 80);
    }

    private function containsArabic(string $message): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $message) === 1;
    }
}
