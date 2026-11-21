@extends('layouts.app', ['title' => \App\Support\LocalizedText::get($lesson->title)])

@php
    $t = fn ($value) => \App\Support\LocalizedText::get($value);
    $single = $version === 'single';
    $videos = $lesson->approvedVideoList();
    $agents = [
        ['navigation', 'agent_guide', 'map'],
        ['explanation', 'agent_tutor', 'book'],
        ['video', 'agent_video', 'video'],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-[96rem] px-4 py-8 sm:px-6">
    {{-- Breadcrumb + header --}}
    <nav aria-label="breadcrumb" class="mb-5 flex flex-wrap items-center gap-1.5 text-sm text-slate-400" data-reveal="fade">
        <a href="{{ route('lessons.index') }}" class="hover:text-white">{{ __('messages.lessons') }}</a>
        <x-ui.icon name="chevron" flip class="h-3.5 w-3.5" />
        <span>{{ $t($lesson->section?->title) }}</span>
    </nav>

    <header class="flex flex-col gap-5 border-b border-white/[0.06] pb-6 xl:flex-row xl:items-end xl:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2" data-reveal="fade">
                <x-ui.badge :tone="$single ? 'signal' : 'pulse'" dot>{{ $single ? __('messages.single_version') : __('messages.multi_version') }}</x-ui.badge>
                <x-ui.badge>{{ $t($lesson->difficulty) }}</x-ui.badge>
                <x-ui.badge><x-ui.icon name="clock" class="h-3 w-3" />{{ $lesson->duration_minutes }} {{ __('messages.minutes_short') }}</x-ui.badge>
            </div>
            <h1 class="mt-3 text-3xl font-bold leading-tight text-white sm:text-4xl" data-split>{{ $t($lesson->title) }}</h1>
            <p class="mt-3 max-w-3xl leading-7 text-slate-400" data-reveal data-delay="0.1">{{ $t($lesson->summary) }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2" data-reveal data-delay="0.15">
            <x-ui.button variant="ghost" icon="refresh" :href="route('lessons.show', ['version' => $single ? 'multi' : 'single', 'lesson' => $lesson])" title="{{ $single ? __('messages.multi_version') : __('messages.single_version') }}">{{ __('messages.switch_mode') }}</x-ui.button>
            <button type="button" id="bookmark-btn" data-url="{{ route('bookmarks.toggle', $lesson) }}" aria-pressed="{{ $bookmarked ? 'true' : 'false' }}"
                    data-label-on="{{ __('messages.bookmarked') }}" data-label-off="{{ __('messages.bookmark') }}"
                    class="btn btn-ghost aria-pressed:text-signal aria-pressed:shadow-[inset_0_0_0_1px_rgb(34_211_238/0.5)]">
                <x-ui.icon name="bookmark" class="h-4 w-4 [[aria-pressed=true]_&]:fill-current" />
                <span data-label>{{ $bookmarked ? __('messages.bookmarked') : __('messages.bookmark') }}</span>
            </button>
            @if($lesson->quiz)
                <x-ui.button variant="safe" icon="quiz" :href="route('quizzes.show', $lesson->quiz)">{{ __('messages.start_quiz') }}</x-ui.button>
            @endif
        </div>
    </header>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px] xl:grid-cols-[250px_minmax(0,1fr)_400px]">
        {{-- Course outline --}}
        <aside class="hidden xl:block">
            <nav aria-label="{{ __('messages.course_sections') }}" class="sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto pe-1" data-lenis-prevent>
                <p class="mono-label mb-3 font-mono text-xs uppercase tracking-[0.2em] text-slate-500">{{ __('messages.course_sections') }}</p>
                @foreach($sections as $section)
                    <div class="mb-5">
                        <p class="mb-1.5 text-xs font-medium text-slate-400">{{ $t($section->title) }}</p>
                        <ul class="space-y-0.5">
                            @foreach($section->lessons as $navLesson)
                                @php($current = $navLesson->id === $lesson->id)
                                <li>
                                    <a href="{{ route('lessons.show', ['version' => $version, 'lesson' => $navLesson]) }}" @if($current) aria-current="page" @endif
                                       @class(['flex items-start gap-2 rounded-lg px-2.5 py-2 text-sm leading-5 transition', 'bg-signal/10 font-medium text-white ring-1 ring-signal/30' => $current, 'text-slate-400 hover:bg-white/[0.04] hover:text-white' => ! $current])>
                                        <span @class(['mt-1.5 h-1.5 w-1.5 flex-none rounded-full', 'bg-safe' => isset($completed[$navLesson->id]), 'bg-signal' => $current && ! isset($completed[$navLesson->id]), 'bg-slate-600' => ! $current && ! isset($completed[$navLesson->id])])></span>
                                        {{ $t($navLesson->title) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </aside>

        {{-- Lesson --}}
        <article class="min-w-0">
            <details class="surface mb-6 xl:hidden">
                <summary class="flex cursor-pointer items-center justify-between gap-2 px-5 py-3.5 text-sm font-medium text-white">{{ __('messages.course_sections') }}<x-ui.icon name="chevron-down" class="h-4 w-4 text-slate-400" /></summary>
                <div class="border-t border-white/[0.06] px-3 py-3">
                    @foreach($sections as $section)
                        <p class="mt-2 px-2 text-xs text-slate-500">{{ $t($section->title) }}</p>
                        @foreach($section->lessons as $navLesson)
                            <a href="{{ route('lessons.show', ['version' => $version, 'lesson' => $navLesson]) }}" @if($navLesson->id === $lesson->id) aria-current="page" @endif
                               @class(['block rounded-lg px-2 py-2 text-sm', 'text-signal' => $navLesson->id === $lesson->id, 'text-slate-300' => $navLesson->id !== $lesson->id])>{{ $t($navLesson->title) }}</a>
                        @endforeach
                    @endforeach
                </div>
            </details>

            <div class="surface p-6 sm:p-8" data-reveal>
                <div class="prose-lesson">{!! \App\Support\HtmlSanitizer::clean(\App\Support\LocalizedText::html($lesson->content)) !!}</div>
                @foreach($lesson->code_examples ?? [] as $example)
                    <pre class="mt-6 overflow-x-auto rounded-xl bg-void p-4 font-mono text-sm text-cyan-50 ring-1 ring-white/10" dir="ltr"><code>{{ $example }}</code></pre>
                @endforeach
            </div>

            @if($videos->isNotEmpty())
                <section class="mt-8" aria-labelledby="videos-title">
                    <h2 id="videos-title" class="mb-4 flex items-center gap-2 text-xl font-semibold text-white" data-reveal><x-ui.icon name="video" class="h-5 w-5 text-signal" />{{ __('messages.approved_videos') }}</h2>
                    <div class="grid gap-4 md:grid-cols-2" data-stagger>
                        @foreach($videos as $video)
                            <figure class="surface overflow-hidden">
                                <iframe class="aspect-video w-full" src="https://www.youtube-nocookie.com/embed/{{ $video->youtube_id }}" title="{{ $t($video->title) }}" loading="lazy"
                                        allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                <figcaption class="p-4">
                                    <p class="font-medium text-white">{{ $t($video->title) }}</p>
                                    @if($video->channel_name)<p class="mt-0.5 text-sm text-slate-400">{{ $video->channel_name }}</p>@endif
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($lesson->quiz)
                <div class="surface hud mt-8 flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between" data-reveal>
                    <div>
                        <p class="font-semibold text-white">{{ __('messages.ready_for_quiz') }}</p>
                        <p class="mt-1 text-sm text-slate-400">{{ __('messages.quiz_meta_student', ['count' => $lesson->quiz->questions->count(), 'minutes' => (int) ceil($lesson->quiz->timer_seconds / 60), 'pass' => $lesson->quiz->passing_score]) }}</p>
                    </div>
                    <x-ui.button variant="safe" icon-end="arrow" :href="route('quizzes.show', $lesson->quiz)">{{ __('messages.start_quiz') }}</x-ui.button>
                </div>
            @endif
        </article>

        {{-- AI chat --}}
        <aside id="chat-panel" class="min-w-0 scroll-mt-20">
            <div class="surface-strong hud flex flex-col overflow-hidden lg:sticky lg:top-20 lg:h-[calc(100vh-6rem)]">
                <div class="flex items-center justify-between gap-3 border-b border-white/[0.06] px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <span class="relative grid h-8 w-8 place-items-center rounded-lg bg-signal/10 text-signal ring-1 ring-signal/30">
                            <x-ui.icon name="sparkles" class="h-4 w-4" />
                            <span class="pulse-dot absolute -end-0.5 -top-0.5 h-2 w-2 rounded-full bg-safe"></span>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-white">{{ $single ? __('messages.ai_tutor') : __('messages.ai_team') }}</h2>
                            <p class="text-[11px] text-slate-500">{{ $single ? 'Cyber Mentor' : __('messages.agent_team_names') }}</p>
                        </div>
                    </div>
                    <span class="mono-label font-mono text-[10px] uppercase tracking-widest text-safe">{{ __('messages.online') }}</span>
                </div>

                @unless($single)
                    <div class="border-b border-white/[0.06] p-2">
                        <div id="agent-tabs" role="tablist" aria-label="{{ __('messages.ai_team') }}" class="relative grid grid-cols-3 rounded-xl bg-white/[0.03] p-1 ring-1 ring-white/10">
                            <span id="agent-indicator" aria-hidden="true" class="absolute inset-y-1 left-0 rounded-lg bg-signal shadow-[0_0_24px_-6px_rgba(34,211,238,0.8)]" style="width: 0"></span>
                            @foreach($agents as [$key, $label, $icon])
                                <button type="button" role="tab" data-agent="{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                        class="agent-tab relative z-10 rounded-lg px-2 py-2 text-xs font-semibold text-slate-400 transition-colors duration-300 hover:text-white aria-selected:text-void">
                                    {{ __('messages.'.$label) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endunless

                <div id="chat-log" role="log" aria-live="polite" aria-label="{{ $single ? __('messages.ai_tutor') : __('messages.ai_team') }}"
                     class="min-h-[22rem] flex-1 space-y-3 overflow-y-auto px-4 py-4 text-sm lg:min-h-0" data-lenis-prevent tabindex="0"></div>

                <div class="border-t border-white/[0.06] p-3">
                    <div id="quick-replies" class="mb-2 flex flex-wrap gap-1.5"></div>
                    <form id="chat-form" class="relative">
                        <label for="chat-message" class="sr-only">{{ __('messages.ask_ai') }}</label>
                        <textarea id="chat-message" rows="2" class="field-control !min-h-0 resize-none rounded-xl bg-white/[0.04] !py-3 pe-12 ring-1 ring-white/10 transition focus:ring-signal/60" placeholder="{{ __('messages.ask_ai') }}"></textarea>
                        <button type="submit" class="absolute bottom-2 end-2 grid h-9 w-9 place-items-center rounded-lg bg-signal text-void transition hover:bg-cyan-300 disabled:opacity-50" aria-label="{{ __('messages.send') }}">
                            <x-ui.icon name="send" flip class="h-4 w-4" />
                        </button>
                    </form>
                    <p class="mt-1.5 text-[11px] text-slate-500">{{ __('messages.chat_enter_hint') }}</p>
                </div>
            </div>
        </aside>
    </div>

    <a href="#chat-panel" class="btn btn-primary fixed bottom-4 end-4 z-30 shadow-2xl lg:hidden" data-chat-jump><x-ui.icon name="sparkles" class="h-4 w-4" />{{ __('messages.ask_ai_short') }}</a>
</div>

<dialog id="level-picker" class="modal max-w-md" aria-labelledby="level-picker-title">
    <div class="surface-strong hud p-6">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs text-signal">Cyber Mentor</p>
                <h2 id="level-picker-title" class="mt-1 text-lg font-semibold text-white" data-level-title></h2>
            </div>
            <button type="button" class="btn btn-quiet btn-icon -m-1" data-modal-close aria-label="{{ __('messages.close') }}"><x-ui.icon name="x" class="h-4 w-4" /></button>
        </div>
        <div class="space-y-2" data-level-options></div>
    </div>
</dialog>

<script>
window.cyberLesson = {
    lessonId: {{ $lesson->id }},
    version: @json($version),
    agent: @json($single ? 'single_tutor' : 'navigation'),
    chatUrl: @json(route('api.ai.chat')),
    streamUrl: @json(route('api.ai.chat.stream')),
    userName: @json(auth()->user()?->name),
    userLevel: @json(auth()->user()?->learning_level),
    locale: @json(app()->getLocale()),
};
</script>
@endsection
