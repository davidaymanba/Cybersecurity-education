@extends('layouts.admin', ['title' => __('messages.admin_dashboard')])

@php
    $t = fn ($value) => \App\Support\LocalizedText::get($value);
    $a = $analytics;
    $agentNames = [
        'single_tutor' => 'Cyber Mentor',
        'navigation' => __('messages.agent_guide'),
        'explanation' => __('messages.agent_tutor'),
        'video' => __('messages.agent_video'),
    ];
    $usage = collect($a['agent_usage'] ?? [])->map(fn ($count) => is_array($count) ? (int) ($count['total'] ?? 0) : (int) $count)->sortDesc();
    $usageMax = max(1, (int) $usage->max());
    $usageTotal = max(1, (int) $usage->sum());
    $series = [
        'chats' => ['label' => __('messages.stat_ai_chats'), 'color' => '#0ea5c4'],
        'quizzes' => ['label' => __('messages.quiz_attempts'), 'color' => '#8b7cf0'],
    ];
    $activity = $a['activity'] ?? [];
    $activityTotal = collect($activity)->sum(fn ($d) => $d['chats'] + $d['quizzes']);
@endphp

@section('admin')
    <x-ui.page-header :kicker="__('messages.research_admin')" :title="__('messages.platform_analytics')" :lead="__('messages.analytics_lead')" />

    <section class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="{{ __('messages.platform_analytics') }}">
        <x-ui.stat :label="__('messages.stat_students')" :value="$a['students']" icon="users" />
        <x-ui.stat :label="__('messages.lessons')" :value="$a['lessons']" icon="book" tone="pulse" />
        <x-ui.stat :label="__('messages.quiz_attempts')" :value="$a['quiz_attempts'] ?? 0" icon="quiz" tone="amber" />
        <x-ui.stat :label="__('messages.stat_completion')" :value="$a['completion_rate']" suffix="%" icon="target" tone="safe" :hint="__('messages.completion_hint')" />
        <x-ui.stat :label="__('messages.stat_avg_score')" :value="$a['average_quiz_score']" suffix="%" icon="trophy" tone="amber" />
        <x-ui.stat :label="__('messages.stat_ai_chats')" :value="$a['ai_interactions']" icon="sparkles" tone="pulse" />
    </section>

    <section class="surface mt-6 p-5 sm:p-6" aria-labelledby="activity-title">
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="activity-title" class="font-semibold text-white">{{ __('messages.activity_14_days') }}</h2>
            <span class="text-xs text-slate-500">{{ __('messages.activity_hint') }}</span>
        </div>
        @if($activityTotal > 0)
            <x-chart.line :points="$activity" :series="$series" :label="__('messages.activity_14_days')" />
        @else
            <x-ui.empty icon="chart" :title="__('messages.no_activity')" class="!bg-none !shadow-none !py-8" />
        @endif
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="surface p-5 sm:p-6" aria-labelledby="agents-title">
            <h2 id="agents-title" class="font-semibold text-white">{{ __('messages.ai_agent_usage') }}</h2>
            <p class="mt-1 text-xs text-slate-500">{{ __('messages.agent_usage_hint') }}</p>
            @if($usage->isEmpty())
                <p class="mt-6 text-sm text-slate-400">{{ __('messages.no_ai_interactions') }}</p>
            @else
                <ul class="mt-5 space-y-4">
                    @foreach($usage as $agent => $count)
                        <li title="{{ $agentNames[$agent] ?? \Illuminate\Support\Str::headline((string) $agent) }}: {{ $count }}">
                            <div class="mb-1.5 flex items-baseline justify-between gap-3 text-sm">
                                <span class="text-slate-200">{{ $agentNames[$agent] ?? \Illuminate\Support\Str::headline((string) $agent) }}</span>
                                <span class="font-mono text-xs text-slate-400"><span class="text-white">{{ number_format($count) }}</span> · {{ round($count / $usageTotal * 100) }}%</span>
                            </div>
                            <x-ui.progress :value="$count / $usageMax * 100" size="h-2" :label="$agentNames[$agent] ?? (string) $agent" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="surface p-5 sm:p-6" aria-labelledby="ai-title">
            <h2 id="ai-title" class="font-semibold text-white">{{ __('messages.ai_health') }}</h2>
            <div class="mt-5 flex flex-col gap-6 sm:flex-row sm:items-center">
                <x-ui.ring :value="$a['ai_cache_hit_rate']" :size="128" :stroke="9" tone="safe" :label="__('messages.cache_hit_rate').' '.$a['ai_cache_hit_rate'].'%'">
                    <span class="block font-mono text-2xl font-semibold text-white" dir="ltr">{{ $a['ai_cache_hit_rate'] }}%</span>
                    <span class="block text-[11px] text-slate-400">{{ __('messages.cache_hit_rate') }}</span>
                </x-ui.ring>
                <dl class="grid flex-1 grid-cols-2 gap-3 text-sm">
                    <div class="surface-inset p-3"><dt class="text-xs text-slate-400">{{ __('messages.tokens_used') }}</dt><dd class="mt-1 font-mono text-lg text-white">{{ number_format($a['ai_tokens_total']) }}</dd></div>
                    <div class="surface-inset p-3"><dt class="text-xs text-slate-400">{{ __('messages.avg_latency') }}</dt><dd class="mt-1 font-mono text-lg text-white" dir="ltr" style="text-align: start">{{ number_format($a['ai_avg_latency_ms']) }} ms</dd></div>
                    <div class="surface-inset col-span-2 p-3"><dt class="text-xs text-slate-400">{{ __('messages.zero_token_replies') }}</dt><dd class="mt-1 font-mono text-lg text-white">{{ number_format($a['ai_safety_filter_hits']) }}</dd><dd class="mt-0.5 text-[11px] text-slate-500">{{ __('messages.zero_token_hint') }}</dd></div>
                </dl>
            </div>
        </section>
    </div>

    <section class="surface mt-6 p-5 sm:p-6" aria-labelledby="quiz-perf-title">
        <h2 id="quiz-perf-title" class="font-semibold text-white">{{ __('messages.recent_quiz_performance') }}</h2>
        @if(empty($a['quiz_performance']))
            <p class="mt-4 text-sm text-slate-400">{{ __('messages.no_quiz_data') }}</p>
        @else
            <ul class="mt-4 divide-y divide-white/[0.06]">
                @foreach($a['quiz_performance'] as $row)
                    @php
                        $score = (int) ($row['score'] ?? 0);
                        $pass = (int) ($row['passing_score'] ?? 0);
                        $ok = $pass > 0 && $score >= $pass;
                    @endphp
                    <li class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-4 gap-y-1.5 py-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto]">
                        <span class="truncate text-sm text-slate-200">{{ $row['lesson_title'] ? $t($row['lesson_title']) : __('messages.deleted_lesson') }}</span>
                        <span class="relative col-span-2 h-2 rounded-full bg-white/[0.07] sm:col-span-1" aria-hidden="true">
                            <span class="absolute inset-y-0 start-0 rounded-full {{ $ok ? 'bg-safe' : 'bg-amber' }}" style="width: {{ $score }}%"></span>
                            @if($pass)<span class="absolute -inset-y-1 w-0.5 rounded bg-white/60" style="inset-inline-start: {{ $pass }}%" title="{{ __('messages.passing_score') }} {{ $pass }}%"></span>@endif
                        </span>
                        <span class="row-start-1 flex items-center gap-2 justify-self-end sm:col-start-3">
                            <span class="font-mono text-sm text-white">{{ $score }}%</span>
                            <x-ui.badge :tone="$ok ? 'safe' : 'amber'"><x-ui.icon :name="$ok ? 'check' : 'alert'" class="h-3 w-3" />{{ $ok ? __('messages.passed') : __('messages.not_passed') }}</x-ui.badge>
                        </span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 flex items-center gap-2 text-[11px] text-slate-500"><span class="h-3 w-0.5 rounded bg-white/60"></span>{{ __('messages.passing_line') }}</p>
        @endif
    </section>
@endsection
