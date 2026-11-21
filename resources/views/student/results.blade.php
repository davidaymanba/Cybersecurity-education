@extends('layouts.app', ['title' => __('messages.quiz_results')])

@php
    $t = fn ($value) => \App\Support\LocalizedText::get($value);
    $passed = $result->score >= $result->quiz->passing_score;
    $items = $result->answers_snapshot ?? [];
@endphp

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    <section class="surface-strong hud relative overflow-hidden px-6 py-10 text-center sm:px-10">
        <div aria-hidden="true" @class(['pointer-events-none absolute -top-24 start-1/2 h-64 w-[36rem] max-w-[160%] -translate-x-1/2 rounded-full blur-[110px] rtl:translate-x-1/2', 'bg-safe/20' => $passed, 'bg-amber/15' => ! $passed])></div>
        <div class="relative">
            <p class="text-sm text-slate-400">{{ $t($result->quiz->lesson->title) }}</p>
            <h1 class="mt-1 text-2xl font-bold text-white sm:text-3xl">{{ __('messages.quiz_results') }}</h1>

            <div class="mt-8 flex justify-center" @if($passed) data-celebrate @endif>
                <x-ui.ring :value="$result->score" :size="176" :stroke="7" :tone="$passed ? 'safe' : 'amber'" :label="__('messages.score').' '.$result->score.'%'">
                    <span class="block font-mono text-5xl font-bold text-white" dir="ltr"><span data-counter="{{ $result->score }}">{{ $result->score }}</span><span class="text-2xl text-slate-400">%</span></span>
                    <span class="mt-1 block text-xs text-slate-400">{{ __('messages.score') }}</span>
                </x-ui.ring>
            </div>

            <p class="mt-6 text-slate-300">{{ __('messages.correct_of_total', ['correct' => $result->correct_answers, 'total' => $result->total_questions]) }}</p>
            <div class="mt-3 flex justify-center">
                <x-ui.badge :tone="$passed ? 'safe' : 'amber'" dot class="!text-sm !px-3 !py-1">
                    {{ $passed ? __('messages.quiz_passed') : __('messages.quiz_failed', ['score' => $result->quiz->passing_score]) }}
                </x-ui.badge>
            </div>

            <div class="mt-8 flex flex-wrap justify-center gap-2">
                <x-ui.button :href="route('lessons.show', ['version' => 'single', 'lesson' => $result->quiz->lesson])" variant="ghost">{{ __('messages.return_to_lesson') }}</x-ui.button>
                @if($passed)
                    <x-ui.button :href="route('dashboard')" icon-end="arrow">{{ __('messages.next_step') }}</x-ui.button>
                @else
                    <x-ui.button :href="route('quizzes.show', $result->quiz)" variant="safe" icon="refresh">{{ __('messages.try_again') }}</x-ui.button>
                @endif
            </div>
        </div>
    </section>

    @if($items)
        <section class="mt-10" aria-labelledby="review-title">
            <h2 id="review-title" class="mb-4 text-lg font-semibold text-white" data-reveal>{{ __('messages.answer_review') }}</h2>
            <ol class="space-y-3" data-stagger>
                @foreach($items as $item)
                    <li class="surface flex gap-4 p-5">
                        <span @class(['grid h-8 w-8 flex-none place-items-center rounded-lg ring-1', 'bg-safe/10 text-safe ring-safe/30' => $item['correct'], 'bg-danger/10 text-danger ring-danger/30' => ! $item['correct']])>
                            <x-ui.icon :name="$item['correct'] ? 'check' : 'x'" class="h-4 w-4" />
                        </span>
                        <div class="min-w-0">
                            <p class="font-medium leading-7 text-white">{{ $t($item['question']) }}</p>
                            <p @class(['mt-1 text-sm font-medium', 'text-emerald-300' => $item['correct'], 'text-red-300' => ! $item['correct']])>{{ $item['correct'] ? __('messages.correct') : __('messages.incorrect') }}</p>
                            @if(! empty($item['explanation']))<p class="mt-2 text-sm leading-6 text-slate-400">{{ $t($item['explanation']) }}</p>@endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
</div>
@endsection
