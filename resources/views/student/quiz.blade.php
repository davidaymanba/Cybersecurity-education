@extends('layouts.app', ['title' => $quiz->title])

@section('content')
<section class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
    <div class="mb-6 rounded-xl border border-white/10 bg-white/10 p-5">
        <p class="text-sm text-cyan-200">{{ $quiz->lesson->title }}</p>
        <h1 class="mt-1 text-3xl font-semibold">{{ $quiz->title }}</h1>
        <p class="mt-2 text-slate-300">Timer: <span id="quiz-timer">{{ gmdate('i:s', $quiz->timer_seconds) }}</span></p>
    </div>
    <form method="post" action="{{ route('quizzes.submit', $quiz) }}" class="space-y-5">
        @csrf
        @foreach($quiz->questions as $question)
            <fieldset class="rounded-xl border border-white/10 bg-white/10 p-5">
                <legend class="font-semibold">{{ $loop->iteration }}. {{ $question->question }}</legend>
                <div class="mt-4 grid gap-3">
                    @foreach($question->answers as $answer)
                        <label class="flex gap-3 rounded-lg bg-slate-950/60 p-3 text-sm"><input type="radio" name="answers[{{ $question->id }}]" value="{{ $answer->id }}" required> {{ $answer->answer }}</label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
        <button class="rounded-lg bg-emerald-400 px-5 py-3 font-semibold text-slate-950">Submit quiz</button>
    </form>
</section>
<script>window.quizSeconds = {{ $quiz->timer_seconds }};</script>
@endsection
