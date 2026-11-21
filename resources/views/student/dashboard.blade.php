@extends('layouts.app', ['title' => __('messages.student_dashboard')])

@php
    $t = fn ($value) => \App\Support\LocalizedText::get($value);
    $user = auth()->user();
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
    <x-ui.page-header :kicker="__('messages.student_dashboard')" :title="__('messages.dashboard_greeting', ['name' => $user->name])" :lead="__('messages.dashboard_lead')">
        <x-ui.button variant="ghost" icon="book" :href="route('lessons.index')">{{ __('messages.browse_lessons') }}</x-ui.button>
    </x-ui.page-header>

    {{-- Stats --}}
    <section class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-stagger aria-label="{{ __('messages.your_progress') }}">
        <div class="surface flex items-center gap-4 p-5">
            <x-ui.ring :value="$stats['course_progress']" :size="72" :stroke="9" tone="signal" :label="__('messages.course_progress').' '.$stats['course_progress'].'%'">
                <span class="font-mono text-sm font-semibold text-white">{{ $stats['course_progress'] }}%</span>
            </x-ui.ring>
            <div>
                <p class="text-sm text-slate-400">{{ __('messages.course_progress') }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('messages.course_progress_hint') }}</p>
            </div>
        </div>
        <x-ui.stat :label="__('messages.lessons_completed')" :value="$stats['completed']" :suffix="' / '.$stats['total']" icon="check" tone="safe" />
        <x-ui.stat :label="__('messages.quizzes_taken')" :value="$stats['attempts']" icon="quiz" tone="pulse" />
        <x-ui.stat :label="__('messages.best_score')" :value="$stats['best']" suffix="%" icon="trophy" tone="amber" />
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <div class="space-y-6">
            {{-- Continue learning --}}
            @if($continue)
                @php($p = (int) ($progress[$continue->id] ?? 0))
                <x-ui.card hud spotlight padding="p-6 sm:p-8" data-reveal>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="mono-label font-mono text-xs uppercase tracking-[0.2em] text-signal">{{ $p > 0 ? __('messages.continue_learning') : __('messages.start_here') }}</span>
                        <x-ui.badge>{{ $t($continue->section?->title) }}</x-ui.badge>
                    </div>
                    <h2 class="mt-4 text-2xl font-bold text-white sm:text-3xl">{{ $t($continue->title) }}</h2>
                    <p class="mt-3 max-w-2xl leading-7 text-slate-400">{{ $t($continue->summary) }}</p>
                    <div class="mt-6 max-w-md">
                        <div class="mb-1.5 flex justify-between text-xs text-slate-400"><span>{{ __('messages.lesson_progress') }}</span><span class="font-mono">{{ $p }}%</span></div>
                        <x-ui.progress :value="$p" tone="gradient" size="h-2" :label="__('messages.lesson_progress')" />
                    </div>
                    <div class="mt-6 flex flex-wrap gap-2">
                        <x-ui.button :href="route('lessons.show', ['version' => 'single', 'lesson' => $continue])" icon-end="arrow">{{ __('messages.single_ai') }}</x-ui.button>
                        <x-ui.button variant="ghost" :href="route('lessons.show', ['version' => 'multi', 'lesson' => $continue])">{{ __('messages.multi_ai') }}</x-ui.button>
                    </div>
                </x-ui.card>
            @elseif($lessons->isNotEmpty())
                <x-ui.card hud padding="p-6 sm:p-8" data-reveal>
                    <span class="grid h-12 w-12 place-items-center rounded-xl bg-safe/10 text-safe ring-1 ring-safe/25"><x-ui.icon name="trophy" /></span>
                    <h2 class="mt-4 text-2xl font-bold text-white">{{ __('messages.all_lessons_done') }}</h2>
                    <p class="mt-2 text-slate-400">{{ __('messages.all_lessons_done_lead') }}</p>
                </x-ui.card>
            @endif

            {{-- Learning path --}}
            <section class="surface p-5 sm:p-6" data-reveal>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-white">{{ __('messages.your_path') }}</h2>
                    <a href="{{ route('lessons.index') }}" class="inline-flex items-center gap-1 text-sm text-signal hover:underline">{{ __('messages.view_all_lessons') }}<x-ui.icon name="chevron" flip class="h-4 w-4" /></a>
                </div>
                @if($lessons->isEmpty())
                    <p class="text-sm text-slate-400">{{ __('messages.no_lessons') }}</p>
                @else
                    <ol class="divide-y divide-white/[0.06]">
                        @foreach($lessons as $lesson)
                            @php($p = (int) ($progress[$lesson->id] ?? 0))
                            <li>
                                <a href="{{ route('lessons.show', ['version' => 'single', 'lesson' => $lesson]) }}" class="group flex items-center gap-4 rounded-lg px-2 py-3 transition hover:bg-white/[0.03]">
                                    <span @class([
                                        'grid h-8 w-8 flex-none place-items-center rounded-lg font-mono text-xs ring-1',
                                        'bg-safe/15 text-safe ring-safe/30' => $p >= 100,
                                        'bg-signal/10 text-signal ring-signal/30' => $p > 0 && $p < 100,
                                        'bg-white/[0.03] text-slate-400 ring-white/10' => $p === 0,
                                    ])>
                                        @if($p >= 100)<x-ui.icon name="check" class="h-4 w-4" />@else{{ $loop->iteration }}@endif
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-white group-hover:text-signal">{{ $t($lesson->title) }}</span>
                                        <span class="block truncate text-xs text-slate-500">{{ $t($lesson->section?->title) }} · {{ $lesson->duration_minutes }} {{ __('messages.minutes_short') }}</span>
                                    </span>
                                    <span class="hidden w-28 sm:block"><x-ui.progress :value="$p" :tone="$p >= 100 ? 'safe' : 'signal'" :label="__('messages.lesson_progress')" /></span>
                                    <span class="w-10 text-end font-mono text-xs text-slate-400">{{ $p }}%</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            {{-- Recent results --}}
            <section class="surface p-5 sm:p-6" data-reveal="start">
                <h2 class="mb-4 font-semibold text-white">{{ __('messages.recent_results') }}</h2>
                @forelse($recentResults as $result)
                    @php($passed = $result->quiz && $result->score >= $result->quiz->passing_score)
                    <a href="{{ route('results.show', $result) }}" class="surface-inset mb-2 flex items-center gap-3 p-3 last:mb-0">
                        <x-ui.ring :value="$result->score" :size="44" :stroke="10" :tone="$passed ? 'safe' : 'amber'">
                            <span class="font-mono text-[10px] font-semibold text-white">{{ $result->score }}</span>
                        </x-ui.ring>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-white">{{ $t($result->quiz?->lesson?->title ?? __('messages.deleted_lesson')) }}</span>
                            <span class="block text-xs text-slate-500">{{ $result->created_at->locale(app()->getLocale())->diffForHumans() }}</span>
                        </span>
                        <x-ui.badge :tone="$passed ? 'safe' : 'amber'">{{ $passed ? __('messages.passed') : __('messages.not_passed') }}</x-ui.badge>
                    </a>
                @empty
                    <x-ui.empty icon="quiz" :title="__('messages.no_results_yet')" :body="__('messages.complete_quiz_prompt')" class="!bg-none !shadow-none !py-6" />
                @endforelse
            </section>

            {{-- Saved lessons --}}
            <section class="surface p-5 sm:p-6" data-reveal="start" data-delay="0.08">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-white">{{ __('messages.bookmarks') }}</h2>
                    @if($bookmarks->isNotEmpty())<a href="{{ route('bookmarks.index') }}" class="text-sm text-signal hover:underline">{{ __('messages.view_all') }}</a>@endif
                </div>
                @forelse($bookmarks as $lesson)
                    <a href="{{ route('lessons.show', ['version' => 'single', 'lesson' => $lesson]) }}" class="surface-inset mb-2 flex items-center gap-3 p-3 last:mb-0">
                        <span class="grid h-8 w-8 flex-none place-items-center rounded-lg bg-signal/10 text-signal"><x-ui.icon name="bookmark" class="h-4 w-4" /></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-white">{{ $t($lesson->title) }}</span>
                            <span class="block truncate text-xs text-slate-500">{{ $t($lesson->section?->title) }}</span>
                        </span>
                        <span class="font-mono text-xs text-slate-400">{{ (int) ($progress[$lesson->id] ?? 0) }}%</span>
                    </a>
                @empty
                    <p class="text-sm leading-6 text-slate-400">{{ __('messages.no_bookmarks_hint') }}</p>
                @endforelse
            </section>
        </div>
    </div>
</div>
@endsection
