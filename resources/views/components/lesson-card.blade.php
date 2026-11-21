@props(['lesson', 'progress' => 0, 'bookmarked' => false, 'number' => null])
{{-- Lesson tile used by the library, saved lessons and dashboard. Lets the student pick the research condition. --}}
@php
    $t = fn ($value) => \App\Support\LocalizedText::get($value);
    $progress = (int) $progress;
    $done = $progress >= 100;
    $tone = ['Beginner' => 'safe', 'Intermediate' => 'signal', 'Expert' => 'pulse'][$lesson->difficulty] ?? 'muted';
@endphp
<article {{ $attributes->class(['surface spot-card group flex flex-col p-5']) }} data-spotlight>
    <span class="spot-glow" aria-hidden="true"></span>
    <div class="flex items-center justify-between gap-3">
        @if($number)<span class="font-mono text-3xl font-bold text-white/10 transition-colors group-hover:text-signal/30">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>@endif
        <div class="ms-auto flex items-center gap-1.5">
            @if($bookmarked)<span class="text-signal" title="{{ __('messages.bookmarked') }}"><x-ui.icon name="bookmark" class="h-4 w-4 fill-current" /><span class="sr-only">{{ __('messages.bookmarked') }}</span></span>@endif
            @if($done)<x-ui.badge tone="safe" dot>{{ __('messages.completed') }}</x-ui.badge>@endif
            <x-ui.badge :tone="$tone">{{ $t($lesson->difficulty) }}</x-ui.badge>
        </div>
    </div>
    @if($lesson->relationLoaded('section') && $lesson->section)<p class="mt-4 text-xs text-slate-400">{{ $t($lesson->section->title) }}</p>@endif
    <h3 class="mt-1.5 text-lg font-semibold leading-snug text-white">{{ $t($lesson->title) }}</h3>
    <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-400">{{ $t($lesson->summary) }}</p>
    <div class="mt-auto pt-5">
        <div class="mb-1.5 flex items-center justify-between text-xs text-slate-400">
            <span class="inline-flex items-center gap-1.5"><x-ui.icon name="clock" class="h-3.5 w-3.5" />{{ $lesson->duration_minutes }} {{ __('messages.minutes_short') }}</span>
            <span class="font-mono">{{ $progress }}%</span>
        </div>
        <x-ui.progress :value="$progress" :tone="$done ? 'safe' : 'signal'" :label="__('messages.lesson_progress')" />
        <div class="mt-4 grid grid-cols-2 gap-2">
            <x-ui.button size="sm" :href="route('lessons.show', ['version' => 'single', 'lesson' => $lesson])" aria-label="{{ $t($lesson->title) }} — {{ __('messages.single_ai') }}">{{ __('messages.single_ai') }}</x-ui.button>
            <x-ui.button size="sm" variant="ghost" :href="route('lessons.show', ['version' => 'multi', 'lesson' => $lesson])" aria-label="{{ $t($lesson->title) }} — {{ __('messages.multi_ai') }}">{{ __('messages.multi_ai') }}</x-ui.button>
        </div>
    </div>
</article>
