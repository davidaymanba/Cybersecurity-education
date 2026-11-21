@extends('layouts.app', ['title' => __('messages.voice_assistant')])

@php
    $voiceStrings = [
        'listening' => __('messages.voice_listening'),
        'idle' => __('messages.voice_idle'),
        'thinking' => __('messages.thinking'),
        'unsupported' => __('messages.voice_unsupported'),
        'error' => __('messages.voice_error'),
        'ttsMaking' => __('messages.voice_tts_making'),
        'ttsFailed' => __('messages.voice_tts_failed'),
        'start' => __('messages.voice_start'),
        'stop' => __('messages.voice_stop'),
    ];
@endphp

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10 sm:px-6" data-voice
     data-respond-url="{{ route('voice.respond') }}" data-tts-url="{{ route('voice.tts') }}"
     data-lang="{{ app()->getLocale() === 'ar' ? 'ar-SA' : 'en-US' }}">
    <x-ui.page-header :kicker="'Cyber Mentor'" :title="__('messages.voice_assistant')" :lead="__('messages.voice_lead')" />

    <section class="surface-strong hud relative mt-10 overflow-hidden px-6 py-10 text-center" data-reveal>
        <div class="grid-bg pointer-events-none absolute inset-0 opacity-70" aria-hidden="true"></div>
        <div class="relative">
            <button type="button" data-voice-mic aria-pressed="false" aria-label="{{ __('messages.voice_start') }}"
                    class="group relative mx-auto grid h-32 w-32 place-items-center rounded-full bg-signal/10 text-signal ring-1 ring-signal/40 transition hover:bg-signal/15 aria-pressed:bg-danger/15 aria-pressed:text-red-300 aria-pressed:ring-danger/50">
                <span class="absolute inset-0 rounded-full ring-1 ring-signal/20 group-aria-pressed:animate-ping group-aria-pressed:ring-danger/40" aria-hidden="true"></span>
                <span class="ring-spin absolute -inset-3 rounded-full border border-dashed border-signal/25" aria-hidden="true"></span>
                <x-ui.icon name="mic" class="h-10 w-10 group-aria-pressed:hidden" />
                <x-ui.icon name="stop" class="hidden h-9 w-9 group-aria-pressed:block" />
            </button>
            <p class="mt-6 text-sm text-slate-300" data-voice-status role="status">{{ __('messages.voice_idle') }}</p>

            <form class="mx-auto mt-8 flex max-w-xl gap-2" data-voice-form>
                <label for="voice-text" class="sr-only">{{ __('messages.voice_type') }}</label>
                <input id="voice-text" type="text" class="field-control flex-1 rounded-xl bg-white/[0.04] !py-3 ring-1 ring-white/10 focus:ring-signal/60" placeholder="{{ __('messages.voice_type') }}" maxlength="2000">
                <x-ui.button icon="send">{{ __('messages.send') }}</x-ui.button>
            </form>
        </div>
    </section>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <section class="surface p-5" data-reveal>
            <h2 class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-400"><x-ui.icon name="user" class="h-4 w-4" />{{ __('messages.voice_transcript') }}</h2>
            <p class="min-h-12 leading-7 text-white" data-voice-transcript dir="auto">—</p>
        </section>
        <section class="surface p-5" data-reveal data-delay="0.06">
            <div class="mb-2 flex items-center justify-between gap-2">
                <h2 class="flex items-center gap-2 text-sm font-medium text-slate-400"><x-ui.icon name="sparkles" class="h-4 w-4 text-signal" />{{ __('messages.voice_reply') }}</h2>
                <label class="flex cursor-pointer items-center gap-1.5 text-xs text-slate-400"><input type="checkbox" data-voice-speak checked class="accent-cyan-400">{{ __('messages.voice_speak') }}</label>
            </div>
            <p class="min-h-12 whitespace-pre-line leading-7 text-slate-200" data-voice-reply dir="auto" aria-live="polite">—</p>
            @if($serverTts)
                <button type="button" class="btn btn-ghost btn-sm mt-4" data-voice-server-tts><x-ui.icon name="speaker" class="h-4 w-4" />{{ __('messages.voice_server_tts') }}</button>
            @endif
        </section>
    </div>
</div>
<script>window.__voiceStrings = @json($voiceStrings);</script>
@endsection
