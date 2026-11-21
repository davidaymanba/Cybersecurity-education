@extends('layouts.app', ['title' => \App\Support\LocalizedText::get($quiz->title)])

@php
    $t = fn ($value) => \App\Support\LocalizedText::get($value);
    $count = $quiz->questions->count();
@endphp

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <nav aria-label="breadcrumb" class="mb-5 flex flex-wrap items-center gap-1.5 text-sm text-slate-400">
        <a href="{{ route('lessons.show', ['version' => 'single', 'lesson' => $quiz->lesson]) }}" class="hover:text-white">{{ $t($quiz->lesson->title) }}</a>
        <x-ui.icon name="chevron" flip class="h-3.5 w-3.5" />
        <span>{{ __('messages.quizzes') }}</span>
    </nav>

    {{-- Sticky header with the countdown --}}
    <div class="sticky top-16 z-20 -mx-4 mb-8 bg-void/85 px-4 py-3 backdrop-blur-xl sm:mx-0 sm:rounded-2xl sm:px-0 sm:py-0 sm:backdrop-blur-none sm:bg-transparent">
        <div class="surface-strong hud flex items-center gap-4 p-4 sm:p-5">
            <div data-quiz-clock class="group relative grid h-16 w-16 flex-none place-items-center sm:h-20 sm:w-20" role="timer" aria-label="{{ __('messages.timer') }}">
                <svg viewBox="0 0 100 100" class="absolute inset-0 h-full w-full -rotate-90" aria-hidden="true">
                    <circle cx="50" cy="50" r="44" fill="none" stroke="rgb(148 163 184 / 0.14)" stroke-width="8" />
                    <circle cx="50" cy="50" r="44" fill="none" stroke-width="8" stroke-linecap="round" pathLength="100" stroke-dasharray="100"
                            stroke-dashoffset="{{ 100 - ($remainingSeconds / max(1, $quiz->timer_seconds)) * 100 }}"
                            class="stroke-signal transition-[stroke-dashoffset,stroke] duration-1000 ease-linear group-data-[urgent]:stroke-danger" data-quiz-ring />
                </svg>
                <span id="quiz-timer" class="relative font-mono text-sm font-semibold text-white group-data-[urgent]:text-red-300 sm:text-base" dir="ltr">{{ gmdate('i:s', $remainingSeconds) }}</span>
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs text-slate-400">{{ $t($quiz->lesson->title) }}</p>
                <h1 class="truncate text-lg font-bold text-white sm:text-2xl">{{ $t($quiz->title) }}</h1>
                <p class="mt-1 text-xs text-slate-400">
                    {{ __('messages.answered') }}
                    <span class="font-mono text-white" data-answered>0</span><span class="font-mono">/{{ $count }}</span>
                    · {{ __('messages.passing_score') }} <span class="font-mono">{{ $quiz->passing_score }}%</span>
                </p>
            </div>
        </div>
    </div>
    <p class="sr-only" aria-live="assertive" data-quiz-announce data-template="{{ __('messages.seconds_left', ['seconds' => ':seconds']) }}"></p>

    <form id="quiz-form" method="post" action="{{ route('quizzes.submit', $quiz) }}" class="space-y-5" data-busy-text="{{ __('messages.submitting') }}">
        @csrf
        @foreach($quiz->questions as $question)
            <fieldset class="surface p-5 transition-shadow data-[answered]:shadow-[inset_0_0_0_1px_rgb(52_211_153/0.35)] sm:p-6" data-question data-reveal>
                <legend class="sr-only">{{ __('messages.question_n', ['n' => $loop->iteration]) }}</legend>
                <div class="flex gap-3">
                    <span class="grid h-8 w-8 flex-none place-items-center rounded-lg bg-signal/10 font-mono text-sm text-signal ring-1 ring-signal/30">{{ $loop->iteration }}</span>
                    <p class="pt-1 font-semibold leading-7 text-white">{{ $t($question->question) }}</p>
                </div>
                <div class="mt-4 grid gap-2.5 sm:ps-11">
                    @foreach($question->answers as $answer)
                        <label class="choice">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $answer->id }}" required @checked(old('answers.'.$question->id) == $answer->id)>
                            <span class="text-sm leading-6 text-slate-200">{{ $t($answer->answer) }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <div class="flex flex-col-reverse items-stretch gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
            <x-ui.button variant="quiet" :href="route('lessons.show', ['version' => 'single', 'lesson' => $quiz->lesson])" icon="arrow" class="[&_svg]:rotate-180">{{ __('messages.return_to_lesson') }}</x-ui.button>
            <x-ui.button variant="safe" size="lg" icon="check">{{ __('messages.submit_quiz') }}</x-ui.button>
        </div>
    </form>
</div>
<script>window.quizSeconds = {{ $remainingSeconds }}; window.quizTotalSeconds = {{ (int) $quiz->timer_seconds }};</script>
@endsection
