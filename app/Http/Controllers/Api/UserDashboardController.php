<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\ProgressTracking;
use App\Models\QuizResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $completedLessonIds = ProgressTracking::where('user_id', $user->id)
            ->where('progress_percent', '>=', 100)
            ->pluck('lesson_id')
            ->toArray();

        $completedLessons = Lesson::whereIn('id', $completedLessonIds)
            ->get(['id', 'title']);

        $progress = ProgressTracking::where('user_id', $user->id)
            ->with('lesson')
            ->get(['id', 'lesson_id', 'progress_percent', 'time_spent_seconds', 'started_at', 'completed_at']);

        $recentQuizzes = QuizResult::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get(['id', 'quiz_id', 'score', 'created_at']);

        $points = (int) $recentQuizzes->sum('score');

        return response()->json([
            'completed_lessons' => $completedLessons,
            'progress' => $progress,
            'recent_quiz_results' => $recentQuizzes,
            'points' => $points,
        ]);
    }
}
