@extends('layouts.app', ['title' => 'Admin Dashboard'])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <p class="text-sm text-cyan-200">Research admin</p>
            <h1 class="text-3xl font-semibold">Platform analytics</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.lessons') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm">Lessons</a>
            <a href="{{ route('admin.quizzes') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm">Quizzes</a>
            <a href="{{ route('admin.users') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm">Users</a>
        </div>
    </div>
    <div class="grid gap-4 md:grid-cols-5">
        @foreach(['Students' => $analytics['students'], 'Lessons' => $analytics['lessons'], 'Completion' => $analytics['completion_rate'].'%', 'Avg Score' => $analytics['average_quiz_score'].'%', 'AI Chats' => $analytics['ai_interactions']] as $label => $value)
            <div class="rounded-xl border border-white/10 bg-white/10 p-5"><p class="text-sm text-slate-300">{{ $label }}</p><p class="mt-2 text-3xl font-semibold">{{ $value }}</p></div>
        @endforeach
    </div>
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-white/10 bg-white/10 p-5">
            <h2 class="text-xl font-semibold">AI agent usage</h2>
            <div class="mt-5 space-y-3">
                @forelse($analytics['agent_usage'] as $agent => $count)
                    <div>
                        <div class="mb-1 flex justify-between text-sm"><span>{{ str_replace('_', ' ', \Illuminate\Support\Str::title($agent)) }}</span><span>{{ $count }}</span></div>
                        <div class="h-3 rounded bg-slate-800"><div class="h-3 rounded bg-cyan-400" style="width: {{ min(100, $count * 15) }}%"></div></div>
                    </div>
                @empty
                    <p class="text-sm text-slate-300">No AI interactions yet.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-xl border border-white/10 bg-white/10 p-5">
            <h2 class="text-xl font-semibold">Recent quiz performance</h2>
            <div class="mt-4 space-y-3">
                @forelse($analytics['quiz_performance'] as $result)
                    <div class="rounded-lg bg-slate-950/60 p-3 text-sm"><span class="font-medium">{{ $result->quiz->lesson->title }}</span><span class="float-right">{{ $result->score }}%</span></div>
                @empty
                    <p class="text-sm text-slate-300">No quiz data yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
