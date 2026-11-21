@extends('layouts.admin', ['title' => __('messages.manage_quizzes')])

@php($lessonOptions = $lessons->pluck('title', 'id')->all())

@section('admin')
    <x-ui.page-header :kicker="__('messages.admin_assessment')" :title="__('messages.quizzes_crud')" :lead="__('messages.quizzes_crud_lead')">
        <x-ui.button type="button" icon="plus" data-modal-open="quiz-create">{{ __('messages.create_quiz') }}</x-ui.button>
    </x-ui.page-header>

    <div class="mt-6 space-y-4">
        @forelse($quizzes as $quiz)
            <article class="surface p-5" aria-labelledby="quiz-{{ $quiz->id }}-title">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-xs text-signal">{{ $quiz->lesson?->title ?? __('messages.deleted_lesson') }}</p>
                        <h2 id="quiz-{{ $quiz->id }}-title" class="mt-0.5 text-lg font-semibold text-white">{{ $quiz->title }}</h2>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <x-ui.badge><x-ui.icon name="quiz" class="h-3 w-3" />{{ __('messages.questions_count', ['count' => $quiz->questions->count()]) }}</x-ui.badge>
                            <x-ui.badge><x-ui.icon name="clock" class="h-3 w-3" /><span dir="ltr">{{ gmdate('i:s', $quiz->timer_seconds) }}</span></x-ui.badge>
                            <x-ui.badge tone="safe">{{ __('messages.passing_score') }} {{ $quiz->passing_score }}%</x-ui.badge>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <x-ui.button type="button" size="sm" variant="ghost" icon="edit" data-modal-open="quiz-{{ $quiz->id }}">{{ __('messages.quiz_settings') }}</x-ui.button>
                        <x-ui.button type="button" size="sm" variant="ghost" icon="plus" data-modal-open="question-new-{{ $quiz->id }}">{{ __('messages.add_question') }}</x-ui.button>
                        <x-ui.confirm :action="route('admin.quizzes.delete', $quiz)" method="DELETE" :message="__('messages.confirm_delete_quiz')" icon="trash" :confirm-label="__('messages.delete_quiz')" aria-label="{{ __('messages.delete_quiz') }}"><span class="sr-only">{{ __('messages.delete_quiz') }}</span></x-ui.confirm>
                    </div>
                </div>

                @if($quiz->questions->isNotEmpty())
                    <ol class="mt-4 divide-y divide-white/[0.06] rounded-xl ring-1 ring-white/[0.06]">
                        @foreach($quiz->questions as $question)
                            @php($formId = 'question-'.$question->id)
                            <li>
                                <details class="group" @if(old('_form') === $formId) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-3 hover:bg-white/[0.02]">
                                        <span class="font-mono text-xs text-slate-500">{{ $loop->iteration }}</span>
                                        <span class="min-w-0 flex-1 truncate text-sm text-slate-200">{{ $question->question }}</span>
                                        <span class="hidden max-w-[40%] truncate text-xs text-safe sm:inline">✓ {{ $question->answers->firstWhere('is_correct', true)?->answer }}</span>
                                        <x-ui.icon name="chevron-down" class="h-4 w-4 text-slate-500 transition group-open:rotate-180" />
                                    </summary>
                                    <div class="border-t border-white/[0.06] p-4">
                                        <x-ui.form :action="route('admin.questions.update', $question)" method="PUT" :form-id="$formId" :busy="__('messages.saving')" class="space-y-4">
                                            @include('admin.partials.question-fields', ['question' => $question, 'formId' => $formId])
                                            <div class="flex justify-end"><x-ui.button size="sm" icon="check">{{ __('messages.save_question') }}</x-ui.button></div>
                                        </x-ui.form>
                                        <div class="mt-3 flex justify-end border-t border-white/[0.06] pt-3">
                                            <x-ui.confirm :action="route('admin.questions.delete', $question)" method="DELETE" :message="__('messages.confirm_delete_question')" icon="trash" :confirm-label="__('messages.delete_question')">{{ __('messages.delete_question') }}</x-ui.confirm>
                                        </div>
                                    </div>
                                </details>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </article>
        @empty
            <x-ui.empty icon="quiz" :title="__('messages.no_quizzes')" />
        @endforelse
    </div>
    <div class="mt-4">{{ $quizzes->links() }}</div>

    {{-- Create quiz (with its first question) --}}
    <x-ui.modal id="quiz-create" :title="__('messages.create_quiz')" size="max-w-2xl" :open-on-load="old('_form') === 'quiz-create'">
        <x-ui.form :action="route('admin.quizzes.store')" form-id="quiz-create" :busy="__('messages.saving')" class="space-y-4">
            <x-ui.select name="lesson_id" :label="__('messages.lesson')" :options="$lessonOptions" :placeholder="__('messages.choose_lesson')" required />
            <x-ui.input name="title" dir="auto" :label="__('messages.quiz_title')" value="Knowledge Check" required maxlength="160" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui.input name="timer_seconds" type="number" min="60" step="30" :label="__('messages.time_limit_seconds')" value="600" required />
                <x-ui.input name="passing_score" type="number" min="1" max="100" :label="__('messages.passing_score')" value="70" required />
            </div>
            <p class="pt-2 text-sm font-medium text-white">{{ __('messages.first_question') }}</p>
            @include('admin.partials.question-fields', ['question' => null, 'formId' => 'quiz-create'])
            <p class="text-xs text-slate-500">{{ __('messages.create_quiz_hint') }}</p>
            <div class="flex justify-end gap-2"><x-ui.button type="button" variant="ghost" data-modal-close>{{ __('messages.cancel') }}</x-ui.button><x-ui.button icon="plus">{{ __('messages.create_quiz') }}</x-ui.button></div>
        </x-ui.form>
    </x-ui.modal>

    @foreach($quizzes as $quiz)
        <x-ui.modal id="quiz-{{ $quiz->id }}" :title="__('messages.quiz_settings')" :open-on-load="old('_form') === 'quiz-'.$quiz->id">
            <x-ui.form :action="route('admin.quizzes.update', $quiz)" method="PUT" :form-id="'quiz-'.$quiz->id" :busy="__('messages.saving')" class="space-y-4">
                <x-ui.select name="lesson_id" :label="__('messages.lesson')" :options="$lessonOptions" :value="$quiz->lesson_id" required />
                <x-ui.input name="title" dir="auto" :label="__('messages.quiz_title')" :value="$quiz->title" required maxlength="160" />
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input name="timer_seconds" type="number" min="60" step="30" :label="__('messages.time_limit_seconds')" :value="$quiz->timer_seconds" required />
                    <x-ui.input name="passing_score" type="number" min="1" max="100" :label="__('messages.passing_score')" :value="$quiz->passing_score" required />
                </div>
                <div class="flex justify-end gap-2"><x-ui.button type="button" variant="ghost" data-modal-close>{{ __('messages.cancel') }}</x-ui.button><x-ui.button icon="check">{{ __('messages.save_quiz') }}</x-ui.button></div>
            </x-ui.form>
        </x-ui.modal>

        <x-ui.modal id="question-new-{{ $quiz->id }}" :title="__('messages.add_question')" size="max-w-2xl" :open-on-load="old('_form') === 'question-new-'.$quiz->id">
            <x-ui.form :action="route('admin.questions.store', $quiz)" :form-id="'question-new-'.$quiz->id" :busy="__('messages.saving')" class="space-y-4">
                @include('admin.partials.question-fields', ['question' => null, 'formId' => 'question-new-'.$quiz->id, 'nextOrder' => $quiz->questions->count()])
                <div class="flex justify-end gap-2"><x-ui.button type="button" variant="ghost" data-modal-close>{{ __('messages.cancel') }}</x-ui.button><x-ui.button icon="plus">{{ __('messages.add_question') }}</x-ui.button></div>
            </x-ui.form>
        </x-ui.modal>
    @endforeach
@endsection
