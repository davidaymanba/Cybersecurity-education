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
            'lesson_id' => ['required', 'exists:lessons,id'],
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'time_spent_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $progress = ProgressTracking::updateOrCreate(
            ['user_id' => $request->user()->id, 'lesson_id' => $data['lesson_id']],
            [
                'progress_percent' => $data['progress_percent'],
                'time_spent_seconds' => $data['time_spent_seconds'] ?? 0,
                'started_at' => now(),
                'completed_at' => $data['progress_percent'] >= 100 ? now() : null,
            ]
        );

        return response()->json($progress);
    }
}
