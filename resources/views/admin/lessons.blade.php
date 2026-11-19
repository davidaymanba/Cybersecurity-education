@extends('layouts.app', ['title' => __('messages.manage_lessons')])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-medium text-cyan-200">{{ __('messages.admin_content') }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ __('messages.lessons_crud') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-300">{{ __('messages.lessons_crud_lead') }}</p>
        </div>
        <a href="{{ route('admin.quizzes') }}" class="inline-flex items-center justify-center rounded-lg border border-cyan-300/30 px-4 py-2 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-300/10">{{ __('messages.manage_quizzes') }}</a>
        <a href="{{ route('admin.videos') }}" class="inline-flex items-center justify-center rounded-lg border border-cyan-300/30 px-4 py-2 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-300/10">{{ __('messages.manage_videos') }}</a>
    </div>

    @if($errors->any())
        <div class="mt-6 rounded-lg border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('admin.lessons.store') }}" class="mt-6 grid gap-4 rounded-lg border border-white/10 bg-white/[0.08] p-4 shadow-2xl shadow-slate-950/20 sm:p-5 lg:grid-cols-6">
        @csrf
        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.section') }}</label>
            <select name="section_id" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" @selected(old('section_id') == $section->id)>{{ $section->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-4">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.lesson_title') }}</label>
            <input name="title" value="{{ old('title') }}" placeholder="{{ __('messages.lesson_title_placeholder') }}" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        </div>
        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.difficulty') }}</label>
            <input name="difficulty" value="{{ old('difficulty', 'Beginner') }}" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        </div>
        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.duration_minutes') }}</label>
            <input name="duration_minutes" type="number" min="1" value="{{ old('duration_minutes', 20) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        </div>
        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.sort_order') }}</label>
            <input name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
        </div>
        <div class="lg:col-span-6">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.summary') }}</label>
            <textarea name="summary" placeholder="{{ __('messages.summary_placeholder') }}" class="min-h-24 w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>{{ old('summary') }}</textarea>
        </div>
        <div class="lg:col-span-6">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.content') }}</label>
            <textarea name="content" placeholder="{{ __('messages.content_placeholder') }}" class="min-h-40 w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>{{ old('content') }}</textarea>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:col-span-6">
            <label class="inline-flex items-center gap-3 text-sm text-slate-200">
                <input type="checkbox" name="is_published" value="1" class="h-4 w-4 rounded border-white/20 bg-slate-950" checked>
                {{ __('messages.published') }}
            </label>
            <button class="rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">{{ __('messages.add_lesson') }}</button>
        </div>
    </form>

    <div class="mt-8 grid gap-4">
        @forelse($lessons as $lesson)
            <article class="rounded-lg border border-white/10 bg-white/[0.08] p-4 sm:p-5">
                <div class="mb-4 flex flex-col justify-between gap-2 md:flex-row md:items-center">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-cyan-200">{{ $lesson->section?->title ?? 'No section' }}</p>
                        <h2 class="mt-1 text-xl font-semibold">{{ $lesson->title }}</h2>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs text-slate-300">
                        <span class="rounded-full bg-slate-900/80 px-3 py-1">{{ $lesson->difficulty }}</span>
                        <span class="rounded-full bg-slate-900/80 px-3 py-1">{{ $lesson->duration_minutes }} {{ __('messages.minutes_short') }}</span>
                        <span class="rounded-full {{ $lesson->is_published ? 'bg-emerald-400/15 text-emerald-100' : 'bg-amber-400/15 text-amber-100' }} px-3 py-1">{{ $lesson->is_published ? __('messages.published') : __('messages.draft') }}</span>
                    </div>
                </div>

                <form method="post" action="{{ route('admin.lessons.update', $lesson) }}" class="grid gap-3 lg:grid-cols-6">
                    @csrf
                    @method('put')
                    <select name="section_id" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2" required>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" @selected($lesson->section_id === $section->id)>{{ $section->title }}</option>
                        @endforeach
                    </select>
                    <input name="title" value="{{ $lesson->title }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-4" required>
                    <input name="difficulty" value="{{ $lesson->difficulty }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2" required>
                    <input name="duration_minutes" type="number" min="1" value="{{ $lesson->duration_minutes }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2" required>
                    <input name="sort_order" type="number" min="0" value="{{ $lesson->sort_order }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2">
                    <textarea name="summary" class="min-h-24 rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-6" required>{{ $lesson->summary }}</textarea>
                    <textarea name="content" class="min-h-36 rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-6" required>{{ $lesson->content }}</textarea>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:col-span-6">
                        <label class="inline-flex items-center gap-3 text-sm">
                            <input type="checkbox" name="is_published" value="1" class="h-4 w-4 rounded border-white/20 bg-slate-950" @checked($lesson->is_published)>
                            {{ __('messages.published') }}
                        </label>
                        <button class="rounded-lg bg-cyan-400 px-5 py-2.5 font-semibold text-slate-950 transition hover:bg-cyan-300">{{ __('messages.save_changes') }}</button>
                    </div>
                </form>

                <form method="post" action="{{ route('admin.lessons.delete', $lesson) }}" class="mt-3 flex justify-end" onsubmit="return confirm(@js(__('messages.confirm_delete_lesson')))">
                    @csrf
                    @method('delete')
                    <button class="rounded-lg border border-rose-300/30 px-4 py-2 text-sm font-semibold text-rose-100 transition hover:bg-rose-400/10">{{ __('messages.delete_lesson') }}</button>
                </form>
            </article>
        @empty
            <div class="rounded-lg border border-white/10 bg-white/[0.08] p-6 text-sm text-slate-300">{{ __('messages.no_lessons') }}</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $lessons->links() }}</div>
</section>
@endsection
