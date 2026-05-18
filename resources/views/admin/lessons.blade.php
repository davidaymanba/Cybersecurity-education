@extends('layouts.app', ['title' => 'Manage Lessons'])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <h1 class="text-3xl font-semibold">Lessons</h1>
    <form method="post" action="{{ route('admin.lessons.store') }}" class="mt-6 grid gap-4 rounded-xl border border-white/10 bg-white/10 p-5 md:grid-cols-2">
        @csrf
        <select name="section_id" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">@foreach($sections as $section)<option value="{{ $section->id }}">{{ $section->title }}</option>@endforeach</select>
        <input name="title" placeholder="Lesson title" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        <input name="difficulty" value="Beginner" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        <input name="duration_minutes" type="number" value="20" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        <textarea name="summary" placeholder="Summary" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white md:col-span-2" required></textarea>
        <textarea name="content" placeholder="Rich HTML lesson content" class="h-32 rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white md:col-span-2" required></textarea>
        <button class="rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950 md:col-span-2">Add lesson</button>
    </form>
    <div class="mt-6 space-y-4">
        @foreach($lessons as $lesson)
            <form method="post" action="{{ route('admin.lessons.update', $lesson) }}" class="rounded-xl border border-white/10 bg-white/10 p-5">
                @csrf @method('put')
                <div class="grid gap-3 md:grid-cols-4">
                    <input name="title" value="{{ $lesson->title }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white md:col-span-2">
                    <input name="difficulty" value="{{ $lesson->difficulty }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
                    <input name="duration_minutes" type="number" value="{{ $lesson->duration_minutes }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
                    <textarea name="summary" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white md:col-span-4">{{ $lesson->summary }}</textarea>
                    <textarea name="content" class="h-28 rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white md:col-span-4">{{ $lesson->content }}</textarea>
                </div>
                <div class="mt-3 flex items-center justify-between">
                    <label class="text-sm"><input type="checkbox" name="is_published" value="1" @checked($lesson->is_published)> Published</label>
                    <button class="rounded-lg bg-cyan-400 px-4 py-2 font-semibold text-slate-950">Save</button>
                </div>
            </form>
        @endforeach
    </div>
    <div class="mt-4">{{ $lessons->links() }}</div>
</section>
@endsection
