<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\ProgressTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    private const MAX_CLIENT_PROGRESS_WITH_QUIZ = 90;

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lesson_id'         => ['required', 'exists:lessons,id'],
            'progress_percent'  => ['required', 'integer', 'min:0', 'max:100'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $userId = $request->user()->id;
        $lesson = Lesson::withExists('quiz')->findOrFail($data['lesson_id']);
        abort_unless($lesson->is_published || $request->user()->isAdmin(), 404);

        // Lessons with a quiz are completed only by passing it (StudentController::submitQuiz),
        // so client-reported progress is capped below 100 for them.
        $clientProgress = $lesson->quiz_exists
            ? min($data['progress_percent'], self::MAX_CLIENT_PROGRESS_WITH_QUIZ)
            : $data['progress_percent'];

        // Find or initialise the record — never overwrite started_at on updates.
        $progress = ProgressTracking::firstOrNew(
            ['user_id' => $userId, 'lesson_id' => $data['lesson_id']]
        );

        if (! $progress->exists) {
            $progress->started_at = now();
        }

        // Never let progress go backwards (e.g. stale client re-sending an old value).
        $progress->progress_percent = max(
            $progress->progress_percent ?? 0,
            $clientProgress
        );

        // Accumulate time rather than replacing it, and clamp at a sane daily max.
        $progress->time_spent_seconds = min(
            ($progress->time_spent_seconds ?? 0) + ($data['time_spent_seconds'] ?? 0),
            86400
        );

        // Only mark completed once; never reset a completed lesson.
        if ($progress->progress_percent >= 100 && ! $progress->completed_at) {
            $progress->completed_at = now();
        }

        $progress->save();

        return response()->json($progress->fresh());
    }
}
