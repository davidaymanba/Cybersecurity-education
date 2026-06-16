<?php

namespace App\Services;

use App\Models\AiInteraction;
use App\Models\Lesson;
use App\Models\ProgressTracking;
use App\Models\QuizResult;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Cache TTL: 10 minutes is appropriate for a learning platform admin dashboard.
     * The old 60-second TTL caused unnecessary DB load.
     */
    private const CACHE_TTL_SECONDS = 600;

    public function overview(): array
    {
        return Cache::remember('analytics_overview', self::CACHE_TTL_SECONDS, function () {
            $totalInteractions = AiInteraction::count();
            $cacheHits         = AiInteraction::where('cache_hit', true)->count();
            $totalTokens       = (int) AiInteraction::sum('tokens_used');
            $avgLatency        = (int) AiInteraction::where('tokens_used', '>', 0)->avg('latency_ms');

            // Safety filter hits (interactions that cost 0 tokens AND are not cache hits likely hit rate-limit/safety)
            $safetyHits = AiInteraction::where('tokens_used', 0)
                ->where('cache_hit', false)
                ->count();

            return [
                'students' => User::whereHas(
                    'role',
                    fn ($query) => $query->where('name', 'student')
                )->count(),

                'lessons' => Lesson::count(),

                'completion_rate' => (int) ProgressTracking::avg('progress_percent'),

                'average_quiz_score' => (int) QuizResult::avg('score'),

                'ai_interactions' => $totalInteractions,

                'ai_tokens_total' => $totalTokens,

                'ai_cache_hit_rate' => $totalInteractions > 0
                    ? round(($cacheHits / $totalInteractions) * 100, 1)
                    : 0,

                'ai_avg_latency_ms' => $avgLatency,

                'ai_safety_filter_hits' => $safetyHits,

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
                        'score'        => (int) $result->score,
                    ])
                    ->all(),
            ];
        });
    }
}
