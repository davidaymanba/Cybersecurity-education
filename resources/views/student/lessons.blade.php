@extends('layouts.app', ['title' => __('messages.lessons')])

@php($t = fn ($value) => \App\Support\LocalizedText::get($value))

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
    <x-ui.page-header :kicker="__('messages.lessons')" :title="__('messages.course_library')" :lead="__('messages.course_library_lead')" />

    <div class="mt-6 flex flex-wrap gap-3 text-xs text-slate-400" data-reveal data-delay="0.15">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/[0.03] px-3 py-1.5 ring-1 ring-white/10"><span class="h-2 w-2 rounded-full bg-signal"></span>{{ __('messages.single_ai') }} — {{ __('messages.single_ai_hint') }}</span>
        <span class="inline-flex items-center gap-2 rounded-full bg-white/[0.03] px-3 py-1.5 ring-1 ring-white/10"><span class="h-2 w-2 rounded-full bg-pulse"></span>{{ __('messages.multi_ai') }} — {{ __('messages.multi_ai_hint') }}</span>
    </div>

    @php($number = 0)
    <div class="mt-10 space-y-12">
        @forelse($sections as $section)
            @continue($section->lessons->isEmpty())
            <section aria-labelledby="section-{{ $section->id }}">
                <div class="mb-5 flex flex-wrap items-end justify-between gap-2 border-b border-white/[0.06] pb-4" data-reveal>
                    <div>
                        <h2 id="section-{{ $section->id }}" class="text-xl font-semibold text-white">{{ $t($section->title) }}</h2>
                        @if($section->description)<p class="mt-1 text-sm text-slate-400">{{ $t($section->description) }}</p>@endif
                    </div>
                    <span class="mono-label font-mono text-xs text-slate-500">{{ __('messages.lessons_count', ['count' => $section->lessons->count()]) }}</span>
                </div>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3" data-stagger>
                    @foreach($section->lessons as $lesson)
                        @php($number++)
                        <x-lesson-card :lesson="$lesson" :progress="$progress[$lesson->id] ?? 0" :bookmarked="isset($bookmarked[$lesson->id])" :number="$number" />
                    @endforeach
                </div>
            </section>
        @empty
            <x-ui.empty icon="book" :title="__('messages.no_lessons')" />
        @endforelse
    </div>
</div>
@endsection
