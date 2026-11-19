@extends('layouts.app', ['title' => __('messages.admin_dashboard')])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <p class="text-sm text-cyan-200">{{ __('messages.research_admin') }}</p>
            <h1 class="text-3xl font-semibold">{{ __('messages.platform_analytics') }}</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.lessons') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm">{{ __('messages.lessons') }}</a>
            <a href="{{ route('admin.quizzes') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm">{{ __('messages.quizzes') }}</a>
            <a href="{{ route('admin.users') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm">{{ __('messages.users') }}</a>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-5">
        @foreach([__('messages.stat_students') => $analytics['students'], __('messages.lessons') => $analytics['lessons'], __('messages.stat_completion') => $analytics['completion_rate'].'%', __('messages.stat_avg_score') => $analytics['average_quiz_score'].'%', __('messages.stat_ai_chats') => $analytics['ai_interactions']] as $label => $value)
            <div class="rounded-xl border border-white/10 bg-white/10 p-5"><p class="text-sm text-slate-300">{{ $label }}</p><p class="mt-2 text-3xl font-semibold">{{ $value }}</p></div>
        @endforeach
    </div>
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-white/10 bg-white/10 p-5">
            <h2 class="text-xl font-semibold">{{ __('messages.ai_agent_usage') }}</h2>
            <div class="mt-5 space-y-3">
                @forelse($analytics['agent_usage'] as $agent => $count)
                    @php
                        $countValue = is_array($count) ? (int) ($count['total'] ?? count($count)) : (int) $count;
                    @endphp
                    <div>
                        <div class="mb-1 flex justify-between text-sm"><span>{{ str_replace('_', ' ', \Illuminate\Support\Str::title((string) $agent)) }}</span><span>{{ $countValue }}</span></div>
                        <div class="h-3 rounded bg-slate-800"><div class="h-3 rounded bg-cyan-400" style="width: {{ min(100, $countValue * 15) }}%"></div></div>
                    </div>
                @empty
                    <p class="text-sm text-slate-300">{{ __('messages.no_ai_interactions') }}</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-white/10 bg-white/10 p-5">
            <h2 class="text-xl font-semibold">{{ __('messages.recent_quiz_performance') }}</h2>
            <div class="mt-4 space-y-3">
                @forelse($analytics['quiz_performance'] as $result)
                    @php
                        $lessonTitle = is_array($result)
                            ? ($result['lesson_title'] ?? 'Deleted lesson')
                            : ($result->quiz?->lesson?->title ?? 'Deleted lesson');
                        $score = is_array($result)
                            ? (int) ($result['score'] ?? 0)
                            : (int) ($result->score ?? 0);
                    @endphp
                    <div class="rounded-lg bg-slate-950/60 p-3 text-sm"><span class="font-medium">{{ $lessonTitle }}</span><span class="float-right">{{ $score }}%</span></div>
                @empty
                    <p class="text-sm text-slate-300">{{ __('messages.no_quiz_data') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
