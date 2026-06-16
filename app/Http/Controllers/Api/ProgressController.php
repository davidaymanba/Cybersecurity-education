<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgressTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lesson_id'         => ['required', 'exists:lessons,id'],
            'progress_percent'  => ['required', 'integer', 'min:0', 'max:100'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $userId = $request->user()->id;

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
            $data['progress_percent']
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
