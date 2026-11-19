@extends('layouts.app', ['title' => __('messages.manage_quizzes')])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-medium text-cyan-200">{{ __('messages.admin_assessment') }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight">{{ __('messages.quizzes_crud') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-300">{{ __('messages.quizzes_crud_lead') }}</p>
        </div>
        <a href="{{ route('admin.lessons') }}" class="inline-flex items-center justify-center rounded-lg border border-cyan-300/30 px-4 py-2 text-sm font-semibold text-cyan-100 transition hover:bg-cyan-300/10">{{ __('messages.manage_lessons') }}</a>
    </div>

    @if($errors->any())
        <div class="mt-6 rounded-lg border border-rose-400/30 bg-rose-400/10 px-4 py-3 text-sm text-rose-100">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('admin.quizzes.store') }}" class="mt-6 grid gap-4 rounded-lg border border-white/10 bg-white/[0.08] p-4 shadow-2xl shadow-slate-950/20 sm:p-5 lg:grid-cols-6">
        @csrf
        <div class="lg:col-span-3">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.lesson') }}</label>
            <select name="lesson_id" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
                @foreach($lessons as $lesson)
                    <option value="{{ $lesson->id }}" @selected(old('lesson_id') == $lesson->id)>{{ $lesson->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-3">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.quiz_title') }}</label>
            <input name="title" value="{{ old('title', 'Knowledge Check') }}" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        </div>
        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.time_limit_seconds') }}</label>
            <input name="timer_seconds" type="number" min="60" step="30" value="{{ old('timer_seconds', 600) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        </div>
        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.passing_score') }}</label>
            <input name="passing_score" type="number" min="1" max="100" value="{{ old('passing_score', 70) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
        </div>
        <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.question_order') }}</label>
            <input name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
        </div>
        <div class="lg:col-span-6">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.first_question') }}</label>
            <textarea name="question" placeholder="{{ __('messages.question_placeholder') }}" class="min-h-24 w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>{{ old('question') }}</textarea>
        </div>
        <div class="grid gap-3 lg:col-span-6 lg:grid-cols-2">
            @for($i = 0; $i < 4; $i++)
                <input name="answers[]" value="{{ old('answers.'.$i) }}" placeholder="{{ __('messages.answer_n', ['n' => $i + 1]) }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
            @endfor
        </div>
        <div class="lg:col-span-3">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.correct_answer') }}</label>
            <select name="correct_index" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
                @for($i = 0; $i < 4; $i++)
                    <option value="{{ $i }}" @selected((int) old('correct_index', 0) === $i)>{{ __('messages.answer_n_correct', ['n' => $i + 1]) }}</option>
                @endfor
            </select>
        </div>
        <div class="lg:col-span-3">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('messages.explanation') }}</label>
            <input name="explanation" value="{{ old('explanation') }}" placeholder="{{ __('messages.explanation_placeholder') }}" class="w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
        </div>
        <div class="flex justify-end lg:col-span-6">
            <button class="rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-300">{{ __('messages.create_quiz') }}</button>
        </div>
    </form>

    <div class="mt-8 grid gap-5">
        @forelse($quizzes as $quiz)
            <article class="rounded-lg border border-white/10 bg-white/[0.08] p-4 sm:p-5">
                <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-start">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-cyan-200">{{ $quiz->lesson?->title ?? 'No lesson' }}</p>
                        <h2 class="mt-1 text-xl font-semibold">{{ $quiz->title }}</h2>
                        <p class="mt-2 text-sm text-slate-300">{{ __('messages.quiz_meta', ['count' => $quiz->questions->count(), 'timer' => gmdate('i:s', $quiz->timer_seconds), 'pass' => $quiz->passing_score]) }}</p>
                    </div>
                    <form method="post" action="{{ route('admin.quizzes.delete', $quiz) }}" onsubmit="return confirm(@js(__('messages.confirm_delete_quiz')))">
                        @csrf
                        @method('delete')
                        <button class="w-full rounded-lg border border-rose-300/30 px-4 py-2 text-sm font-semibold text-rose-100 transition hover:bg-rose-400/10 lg:w-auto">{{ __('messages.delete_quiz') }}</button>
                    </form>
                </div>

                <form method="post" action="{{ route('admin.quizzes.update', $quiz) }}" class="mt-5 grid gap-3 lg:grid-cols-6">
                    @csrf
                    @method('put')
                    <select name="lesson_id" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2" required>
                        @foreach($lessons as $lesson)
                            <option value="{{ $lesson->id }}" @selected($quiz->lesson_id === $lesson->id)>{{ $lesson->title }}</option>
                        @endforeach
                    </select>
                    <input name="title" value="{{ $quiz->title }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2" required>
                    <input name="timer_seconds" type="number" min="60" step="30" value="{{ $quiz->timer_seconds }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
                    <input name="passing_score" type="number" min="1" max="100" value="{{ $quiz->passing_score }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
                    <div class="flex justify-end lg:col-span-6">
                        <button class="rounded-lg bg-cyan-400 px-5 py-2.5 font-semibold text-slate-950 transition hover:bg-cyan-300">{{ __('messages.save_quiz') }}</button>
                    </div>
                </form>

                <div class="mt-6 grid gap-4 xl:grid-cols-2">
                    @foreach($quiz->questions as $question)
                        @php
                            $answers = $question->answers->values();
                            $correctIndex = $answers->search(fn ($answer) => $answer->is_correct);
                            $correctIndex = $correctIndex === false ? 0 : $correctIndex;
                        @endphp
                        <div class="rounded-lg border border-white/10 bg-slate-950/45 p-4">
                            <form method="post" action="{{ route('admin.questions.update', $question) }}" class="grid gap-3">
                                @csrf
                                @method('put')
                                <div class="grid gap-3 sm:grid-cols-[1fr_120px]">
                                    <textarea name="question" class="min-h-24 rounded-lg border border-white/10 bg-slate-950/80 p-3 text-white" required>{{ $question->question }}</textarea>
                                    <input name="sort_order" type="number" min="0" value="{{ $question->sort_order }}" class="h-12 rounded-lg border border-white/10 bg-slate-950/80 p-3 text-white" title="Sort order">
                                </div>
                                <div class="grid gap-2">
                                    @for($i = 0; $i < 4; $i++)
                                        <input name="answers[]" value="{{ $answers[$i]->answer ?? '' }}" placeholder="{{ __('messages.answer_n', ['n' => $i + 1]) }}" class="rounded-lg border border-white/10 bg-slate-950/80 p-3 text-white" required>
                                    @endfor
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <select name="correct_index" class="rounded-lg border border-white/10 bg-slate-950/80 p-3 text-white">
                                        @for($i = 0; $i < 4; $i++)
                                            <option value="{{ $i }}" @selected((int) $correctIndex === $i)>Answer {{ $i + 1 }} correct</option>
                                        @endfor
                                    </select>
                                    <input name="explanation" value="{{ $question->explanation }}" placeholder="{{ __('messages.explanation') }}" class="rounded-lg border border-white/10 bg-slate-950/80 p-3 text-white">
                                </div>
                                <div class="flex justify-end">
                                    <button class="rounded-lg bg-emerald-400 px-4 py-2 font-semibold text-slate-950 transition hover:bg-emerald-300">{{ __('messages.save_question') }}</button>
                                </div>
                            </form>
                            <form method="post" action="{{ route('admin.questions.delete', $question) }}" class="mt-3 flex justify-end" onsubmit="return confirm(@js(__('messages.confirm_delete_question')))">
                                @csrf
                                @method('delete')
                                <button class="rounded-lg border border-rose-300/30 px-4 py-2 text-sm font-semibold text-rose-100 transition hover:bg-rose-400/10">{{ __('messages.delete_question') }}</button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <form method="post" action="{{ route('admin.questions.store', $quiz) }}" class="mt-5 grid gap-3 rounded-lg border border-dashed border-cyan-300/30 p-4 lg:grid-cols-6">
                    @csrf
                    <textarea name="question" placeholder="{{ __('messages.new_question_placeholder') }}" class="min-h-24 rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-5" required></textarea>
                    <input name="sort_order" type="number" min="0" value="{{ $quiz->questions->count() }}" class="h-12 rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" title="Sort order">
                    <div class="grid gap-3 lg:col-span-6 lg:grid-cols-2">
                        @for($i = 0; $i < 4; $i++)
                            <input name="answers[]" placeholder="{{ __('messages.answer_n', ['n' => $i + 1]) }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white" required>
                        @endfor
                    </div>
                    <select name="correct_index" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-2">
                        @for($i = 0; $i < 4; $i++)
                            <option value="{{ $i }}">{{ __('messages.answer_n_correct', ['n' => $i + 1]) }}</option>
                        @endfor
                    </select>
                    <input name="explanation" placeholder="{{ __('messages.optional_explanation') }}" class="rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white lg:col-span-3">
                    <button class="rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950 transition hover:bg-cyan-300">{{ __('messages.add_question') }}</button>
                </form>
            </article>
        @empty
            <div class="rounded-lg border border-white/10 bg-white/[0.08] p-6 text-sm text-slate-300">{{ __('messages.no_quizzes') }}</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $quizzes->links() }}</div>
</section>
@endsection
