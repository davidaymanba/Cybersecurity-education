@extends('layouts.app', ['title' => __('messages.manage_videos')])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-medium text-cyan-200">{{ __('messages.admin_content') }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ __('messages.video_resources') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-300">
                {{ __('messages.videos_lead') }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.lessons') }}" class="inline-flex items-center justify-center rounded-lg border border-cyan-300/30 px-4 py-2 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-300/10">{{ __('messages.lessons') }}</a>
            <a href="{{ route('admin.quizzes') }}" class="inline-flex items-center justify-center rounded-lg border border-cyan-300/30 px-4 py-2 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-300/10">{{ __('messages.quizzes') }}</a>
        </div>
    </div>

    @if($errors->any())
        <div class="mt-6 rounded-lg border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Add video form --}}
    <form method="post" action="{{ route('admin.videos.store') }}"
          class="mt-6 grid gap-4 rounded-lg border border-white/10 bg-white/[0.08] p-4 shadow-2xl shadow-slate-950/20 sm:p-5 lg:grid-cols-6">
        @csrf

        <div class="lg:col-span-3">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.lesson') }}</label>
            <select name="lesson_id" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
                <option value="">{{ __('messages.choose_lesson') }}</option>
                @foreach($lessons as $lesson)
                    <option value="{{ $lesson->id }}" @selected(old('lesson_id') == $lesson->id)>{{ $lesson->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-3">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.video_title') }}</label>
            <input name="title" value="{{ old('title') }}" placeholder="{{ __('messages.video_title_placeholder') }}"
                   class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.youtube_id') }}</label>
            <input name="youtube_id" value="{{ old('youtube_id') }}" placeholder="dQw4w9WgXcQ"
                   class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white font-mono" required>
            <p class="mt-1 text-xs text-slate-500">{!! __('messages.youtube_id_help', ['code' => '<code>watch?v=</code>']) !!}</p>
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.channel_name') }}</label>
            <input name="channel_name" value="{{ old('channel_name') }}" placeholder="{{ __('messages.channel_placeholder') }}"
                   class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.description_optional') }}</label>
            <input name="description" value="{{ old('description') }}" placeholder="{{ __('messages.video_description_placeholder') }}"
                   class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:col-span-6">
            <label class="inline-flex items-center gap-3 text-sm text-slate-200">
                <input type="checkbox" name="approved" value="1" class="h-4 w-4 rounded border-white/20 bg-slate-950" checked>
                {{ __('messages.approved_visible') }}
            </label>
            <button class="rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">
                {{ __('messages.add_video') }}
            </button>
        </div>
    </form>

    {{-- Video list --}}
    <div class="mt-8 grid gap-4">
        @forelse($videos as $video)
            <article class="rounded-lg border border-white/10 bg-white/[0.08] p-4 sm:p-5">
                <div class="mb-4 flex flex-col justify-between gap-2 md:flex-row md:items-start">
                    <div class="flex items-start gap-4">
                        {{-- Thumbnail --}}
                        <img src="https://img.youtube.com/vi/{{ $video->youtube_id }}/mqdefault.jpg"
                             alt="{{ $video->title }}"
                             class="h-16 w-28 flex-shrink-0 rounded-md object-cover border border-white/10"
                             loading="lazy">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-cyan-200">{{ $video->lesson?->title ?? 'No lesson' }}</p>
                            <h2 class="mt-0.5 text-lg font-semibold">{{ $video->title }}</h2>
                            <p class="text-sm text-slate-400">{{ $video->channel_name }} &middot; <code class="font-mono text-xs">{{ $video->youtube_id }}</code></p>
                        </div>
                    </div>
                    <span class="inline-flex h-fit rounded-full px-3 py-1 text-xs font-semibold {{ $video->approved ? 'bg-emerald-400/15 text-emerald-100' : 'bg-amber-400/15 text-amber-100' }}">
                        {{ $video->approved ? __('messages.approved') : __('messages.hidden') }}
                    </span>
                </div>

                {{-- Edit form --}}
                <form method="post" action="{{ route('admin.videos.update', $video) }}" class="grid gap-3 lg:grid-cols-6">
                    @csrf
                    @method('put')

                    <select name="lesson_id" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2" required>
                        @foreach($lessons as $lesson)
                            <option value="{{ $lesson->id }}" @selected($video->lesson_id === $lesson->id)>{{ $lesson->title }}</option>
                        @endforeach
                    </select>

                    <input name="title" value="{{ $video->title }}"
                           class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-4" required>

                    <input name="youtube_id" value="{{ $video->youtube_id }}"
                           class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white font-mono lg:col-span-2" required>

                    <input name="channel_name" value="{{ $video->channel_name }}"
                           class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2" required>

                    <input name="description" value="{{ $video->description }}"
                           placeholder="{{ __('messages.description_optional') }}"
                           class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:col-span-6">
                        <label class="inline-flex items-center gap-3 text-sm">
                            <input type="checkbox" name="approved" value="1" class="h-4 w-4 rounded border-white/20 bg-slate-950" @checked($video->approved)>
                            {{ __('messages.approved') }}
                        </label>
                        <button class="rounded-lg bg-cyan-400 px-5 py-2.5 font-semibold text-slate-950 transition hover:bg-cyan-300">
                            {{ __('messages.save_changes') }}
                        </button>
                    </div>
                </form>

                <form method="post" action="{{ route('admin.videos.delete', $video) }}"
                      class="mt-3 flex justify-end"
                      onsubmit="return confirm(@js(__('messages.confirm_delete_video')))">
                    @csrf
                    @method('delete')
                    <button class="rounded-lg border border-rose-300/30 px-4 py-2 text-sm font-semibold text-rose-100 transition hover:bg-rose-400/10">
                        {{ __('messages.delete_video') }}
                    </button>
                </form>
            </article>
        @empty
            <div class="rounded-lg border border-white/10 bg-white/[0.08] p-6 text-sm text-slate-300">
                {{ __('messages.no_videos') }}
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $videos->links() }}</div>

</section>
@endsection
