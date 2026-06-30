@extends('layouts.app', ['title' => __('messages.student_dashboard')])

@php
    $t = fn ($value) => \App\Support\LocalizedText::get($value);
@endphp

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <p class="text-sm text-cyan-200">{{ __('messages.student_dashboard') }}</p>
            <h1 class="text-3xl font-semibold text-white">{{ __('messages.dashboard_greeting', ['name' => auth()->user()->name]) }}</h1>
        </div>
        <a href="{{ route('lessons.index') }}" class="rounded-lg bg-cyan-400 px-4 py-3 text-center font-semibold text-slate-950">{{ __('messages.browse_lessons') }}</a>
    </div>
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-xl border border-white/10 bg-white/10 p-5"><p class="text-sm text-slate-300">{{ __('messages.lessons') }}</p><p class="mt-2 text-3xl font-semibold">{{ $lessons->count() }}</p></div>
        <div class="rounded-xl border border-white/10 bg-white/10 p-5"><p class="text-sm text-slate-300">{{ __('messages.avg_progress') }}</p><p class="mt-2 text-3xl font-semibold">{{ (int) $progress->avg() }}%</p></div>
        <div class="rounded-xl border border-white/10 bg-white/10 p-5"><p class="text-sm text-slate-300">{{ __('messages.quizzes_taken') }}</p><p class="mt-2 text-3xl font-semibold">{{ $recentResults->count() }}</p></div>
        <div class="rounded-xl border border-white/10 bg-white/10 p-5"><p class="text-sm text-slate-300">{{ __('messages.study_mode') }}</p><p class="mt-2 text-3xl font-semibold">AI</p></div>
    </div>
    <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_.8fr]">
        <div class="rounded-xl border border-white/10 bg-white/10 p-5">
            <h2 class="mb-4 text-xl font-semibold">{{ __('messages.recommended_lessons') }}</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($lessons->take(4) as $lesson)
                    <a href="{{ route('lessons.show', ['version' => 'single', 'lesson' => $lesson]) }}" class="rounded-lg border border-white/10 bg-slate-950/60 p-4 transition hover:border-cyan-300/60">
                        <p class="text-xs uppercase tracking-wide text-cyan-200">{{ $t($lesson->section->title) }}</p>
                        <h3 class="mt-2 font-semibold text-white">{{ $lesson->title }}</h3>
                        <p class="mt-2 text-sm text-slate-300">{{ $t($lesson->summary) }}</p>
                        <div class="mt-4 h-2 rounded bg-slate-800"><div class="h-2 rounded bg-cyan-400" style="width: {{ $progress[$lesson->id] ?? 0 }}%"></div></div>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="rounded-xl border border-white/10 bg-white/10 p-5">
            <h2 class="mb-4 text-xl font-semibold">{{ __('messages.recent_results') }}</h2>
            @forelse($recentResults as $result)
                <div class="mb-3 rounded-lg bg-slate-950/60 p-4">
                    <p class="font-medium">{{ $result->quiz->lesson->title }}</p>
                    <p class="text-sm text-slate-300">{{ __('messages.score') }} {{ $result->score }}%</p>
                </div>
            @empty
                <p class="text-sm text-slate-300">{{ __('messages.complete_quiz_prompt') }}</p>
            @endforelse
        </div>
    </div>
</section>
@endsection
