@extends('layouts.app', ['title' => 'Quiz Results'])

@section('content')
<section class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <div class="rounded-xl border border-white/10 bg-white/10 p-6 text-center">
        <p class="text-sm text-cyan-200">{{ $result->quiz->lesson->title }}</p>
        <h1 class="mt-2 text-4xl font-semibold">Score {{ $result->score }}%</h1>
        <p class="mt-3 text-slate-300">{{ $result->correct_answers }} of {{ $result->total_questions }} correct</p>
        <a href="{{ route('lessons.show', ['version' => 'single', 'lesson' => $result->quiz->lesson]) }}" class="mt-6 inline-flex rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950">Return to lesson</a>
    </div>
    <div class="mt-6 space-y-4">
        @foreach($result->answers_snapshot ?? [] as $item)
            <div class="rounded-lg border border-white/10 bg-white/10 p-4">
                <p class="font-medium">{{ $item['question'] }}</p>
                <p class="mt-2 text-sm {{ $item['correct'] ? 'text-emerald-300' : 'text-red-300' }}">{{ $item['correct'] ? 'Correct' : 'Incorrect' }}</p>
                <p class="mt-2 text-sm text-slate-300">{{ $item['explanation'] }}</p>
            </div>
        @endforeach
    </div>
</section>
@endsection
