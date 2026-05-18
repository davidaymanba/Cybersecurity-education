@extends('layouts.app', ['title' => 'Manage Quizzes'])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <h1 class="text-3xl font-semibold">Quizzes</h1>
    <form method="post" action="{{ route('admin.quizzes.store') }}" class="mt-6 grid gap-4 rounded-xl border border-white/10 bg-white/10 p-5 md:grid-cols-2">
        @csrf
        <select name="lesson_id" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">@foreach($lessons as $lesson)<option value="{{ $lesson->id }}">{{ $lesson->title }}</option>@endforeach</select>
        <input name="title" value="Knowledge Check" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
        <input name="timer_seconds" type="number" value="600" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
        <input name="passing_score" type="number" value="70" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
        <textarea name="question" placeholder="Question" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white md:col-span-2" required></textarea>
        @for($i=0; $i<4; $i++)
            <input name="answers[]" placeholder="Answer {{ $i + 1 }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        @endfor
        <select name="correct_index" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white"><option value="0">Answer 1 correct</option><option value="1">Answer 2 correct</option><option value="2">Answer 3 correct</option><option value="3">Answer 4 correct</option></select>
        <button class="rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950">Save question</button>
    </form>
    <div class="mt-6 grid gap-4 md:grid-cols-2">
        @foreach($quizzes as $quiz)
            <div class="rounded-xl border border-white/10 bg-white/10 p-5">
                <p class="text-sm text-cyan-200">{{ $quiz->lesson->title }}</p>
                <h2 class="mt-1 text-xl font-semibold">{{ $quiz->title }}</h2>
                <p class="mt-2 text-sm text-slate-300">{{ $quiz->questions->count() }} questions · pass {{ $quiz->passing_score }}%</p>
            </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $quizzes->links() }}</div>
</section>
@endsection
