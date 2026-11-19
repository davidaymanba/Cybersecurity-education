@extends('layouts.app', ['title' => $lesson->title])

@section('content')
<div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[260px_1fr_360px]">
    <aside class="h-max rounded-xl border border-white/10 bg-white/10 p-4 lg:sticky lg:top-24">
        <p class="mb-3 text-sm font-semibold text-cyan-200">{{ __('messages.course_sections') }}</p>
        @foreach($sections as $section)
            <div class="mb-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">{{ $section->title }}</p>
                <div class="mt-2 space-y-2">
                    @foreach($section->lessons as $navLesson)
                        <a href="{{ route('lessons.show', ['version' => $version, 'lesson' => $navLesson]) }}" class="block rounded-lg px-3 py-2 text-sm {{ $navLesson->id === $lesson->id ? 'bg-cyan-400 text-slate-950 font-semibold' : 'bg-slate-950/50 text-slate-300 hover:bg-white/10' }}">
                            {{ $navLesson->title }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </aside>

    <article class="rounded-xl border border-white/10 bg-white/10 p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-cyan-200">{{ $version === 'single' ? __('messages.single_version') : __('messages.multi_version') }}</p>
                <h1 class="mt-1 text-3xl font-semibold text-white">{{ $lesson->title }}</h1>
                <p class="mt-2 text-slate-300">{{ $lesson->summary }}</p>
            </div>
            <a href="{{ route('lessons.show', ['version' => $version === 'single' ? 'multi' : 'single', 'lesson' => $lesson]) }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold">{{ __('messages.switch_mode') }}</a>
            {{-- Bookmark toggle --}}
            <button id="bookmark-btn"
                    data-url="{{ route('bookmarks.toggle', $lesson) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold transition hover:border-cyan-300/60 hover:text-cyan-200"
                    aria-label="{{ __('messages.bookmark') }}">
                <svg id="bookmark-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="1.8"
                     class="h-4 w-4 transition-colors">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h14a1 1 0 0 1 1 1v17l-8-4-8 4V4a1 1 0 0 1 1-1z"/>
                </svg>
                <span id="bookmark-label">{{ __('messages.bookmark') }}</span>
            </button>
        </div>
        <div class="prose prose-invert max-w-none prose-pre:bg-slate-950">{!! \App\Support\HtmlSanitizer::clean($lesson->content) !!}</div>
        @if($lesson->code_examples)
            @foreach($lesson->code_examples as $example)
                <pre class="mt-5 overflow-x-auto rounded-lg border border-white/10 bg-slate-950 p-4 text-sm text-cyan-50"><code>{{ $example }}</code></pre>
            @endforeach
        @endif
        <div class="mt-8">
            <h2 class="mb-4 text-xl font-semibold">{{ __('messages.approved_videos') }}</h2>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($lesson->videos as $video)
                    <div class="rounded-lg border border-white/10 bg-slate-950/60 p-3">
                        <iframe class="aspect-video w-full rounded-md" src="https://www.youtube.com/embed/{{ $video->youtube_id }}" title="{{ $video->title }}" allowfullscreen></iframe>
                        <p class="mt-3 font-medium">{{ $video->title }}</p>
                        <p class="text-sm text-slate-400">{{ $video->channel_name }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        @if($lesson->quiz)
            <a href="{{ route('quizzes.show', $lesson->quiz) }}" class="mt-8 inline-flex rounded-lg bg-emerald-400 px-5 py-3 font-semibold text-slate-950">{{ __('messages.start_quiz') }}</a>
        @endif
    </article>

    <aside class="h-max rounded-xl border border-white/10 bg-white/10 p-4 lg:sticky lg:top-24">
        <h2 class="mb-3 text-lg font-semibold">{{ $version === 'single' ? __('messages.ai_tutor') : __('messages.ai_team') }}</h2>
        <div id="agent-tabs" class="{{ $version === 'single' ? 'hidden' : 'mb-3 grid grid-cols-3 gap-2' }}">
            <button data-agent="navigation" class="agent-tab rounded-lg bg-cyan-400 px-2 py-2 text-xs font-semibold text-slate-950">{{ __('messages.agent_guide') }}</button>
            <button data-agent="explanation" class="agent-tab rounded-lg bg-slate-950/70 px-2 py-2 text-xs">{{ __('messages.agent_tutor') }}</button>
            <button data-agent="video" class="agent-tab rounded-lg bg-slate-950/70 px-2 py-2 text-xs">{{ __('messages.agent_video') }}</button>
        </div>
        <div id="chat-log" class="h-80 space-y-3 overflow-y-auto rounded-lg bg-slate-950/70 p-3 text-sm">
            <div class="rounded-lg bg-cyan-400/10 p-3 text-cyan-50">
                {{ __('messages.chat_hello', ['name' => auth()->user()?->name]) }}
            </div>
        </div>
        <div id="quick-replies" class="mt-3 grid grid-cols-2 gap-2">
            <button type="button" data-message-en="I need a cybersecurity learning plan." data-message-ar="أحتاج خطة لتعلم الأمن السيبراني." class="quick-reply rounded-lg border border-white/10 bg-slate-950/70 px-3 py-2 text-start text-xs text-cyan-50 hover:border-cyan-300">{{ __('messages.qr_plan') }}</button>
            <button type="button" data-message-en="Explain a cybersecurity topic in simple terms." data-message-ar="اشرح لي موضوعاً في الأمن السيبراني بطريقة بسيطة." class="quick-reply rounded-lg border border-white/10 bg-slate-950/70 px-3 py-2 text-start text-xs text-cyan-50 hover:border-cyan-300">{{ __('messages.qr_explain') }}</button>
            <button type="button" data-message-en="Share useful cybersecurity learning resources with links." data-message-ar="اعطني مصادر مفيدة لتعلم الأمن السيبراني مع روابط." class="quick-reply rounded-lg border border-white/10 bg-slate-950/70 px-3 py-2 text-start text-xs text-cyan-50 hover:border-cyan-300">{{ __('messages.qr_resources') }}</button>
            <button type="button" data-message-en="Quiz me with safe beginner cybersecurity questions." data-message-ar="اختبرني بأسئلة آمنة للمبتدئين في الأمن السيبراني." class="quick-reply rounded-lg border border-white/10 bg-slate-950/70 px-3 py-2 text-start text-xs text-cyan-50 hover:border-cyan-300">{{ __('messages.qr_quiz') }}</button>
        </div>
        <form id="chat-form" class="mt-3">
            <textarea id="chat-message" class="h-24 w-full resize-none rounded-lg border border-white/10 bg-slate-950/70 p-3 text-sm text-white outline-none focus:border-cyan-300" placeholder="{{ __('messages.ask_ai') }}"></textarea>
            <button class="mt-2 w-full rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950">{{ __('messages.send') }}</button>
        </form>
    </aside>
</div>
<script>
window.cyberLesson = {
    lessonId: {{ $lesson->id }},
    version: @json($version),
    agent: @json($version === 'single' ? 'single_tutor' : 'navigation'),
    chatUrl: @json(route('api.ai.chat')),
    streamUrl: @json(route('api.ai.chat.stream')),
    userName: @json(auth()->user()?->name),
    userLevel: @json(auth()->user()?->learning_level),
    locale: @json(app()->getLocale()),
};
</script>
<script>
(function () {
    const btn   = document.getElementById('bookmark-btn');
    const icon  = document.getElementById('bookmark-icon');
    const label = document.getElementById('bookmark-label');

    if (!btn) return;

    function setBookmarked(active) {
        if (active) {
            icon.style.fill = 'currentColor';
            label.textContent = @json(__('messages.bookmarked'));
            btn.classList.add('text-cyan-300');
        } else {
            icon.style.fill = 'none';
            label.textContent = @json(__('messages.bookmark'));
            btn.classList.remove('text-cyan-300');
        }
    }

    btn.addEventListener('click', function () {
        fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => setBookmarked(data.bookmarked))
        .catch(() => {});
    });
}());
</script>
@endsection
