<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\Lesson;
use App\Models\ProgressTracking;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    public function overview(): array
    {
        return Cache::remember('analytics_overview', 60, function () {
            return [
                'students' => User::whereHas('role', fn ($query) => $query->where('name', 'student'))->count(),
                'lessons' => Lesson::count(),
                'completion_rate' => (int) ProgressTracking::avg('progress_percent'),
                'average_quiz_score' => (int) QuizResult::avg('score'),
                'ai_interactions' => AiInteraction::count(),
                'top_agent' => AiInteraction::select('agent_type', DB::raw('count(*) as total'))
                    ->groupBy('agent_type')
                    ->orderByDesc('total')
                    ->first(),
                'agent_usage' => AiInteraction::select('agent_type', DB::raw('count(*) as total'))
                    ->groupBy('agent_type')
                    ->get()
                    ->mapWithKeys(fn ($row) => [$row->agent_type => (int) $row->total])
                    ->all(),
                'quiz_performance' => QuizResult::with('quiz.lesson')
                    ->latest()
                    ->take(8)
                    ->get()
                    ->map(fn ($result) => [
                        'lesson_title' => $result->quiz?->lesson?->title ?? 'Deleted lesson',
                        'score' => (int) $result->score,
                    ])
                    ->all(),
            ];
        });
    }
}
