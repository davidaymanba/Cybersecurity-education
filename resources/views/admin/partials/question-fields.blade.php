{{-- Question + four answers with a "correct" radio each. Expects $formId; optional $question. --}}
@php
    $answers = isset($question) ? $question->answers->sortBy('id')->values() : collect();
    $correct = isset($question) ? $answers->search(fn ($a) => $a->is_correct) : 0;
    $inScope = old('_form') === null || old('_form') === $formId;
    $correct = (int) ($inScope && old('correct_index') !== null ? old('correct_index') : ($correct === false ? 0 : $correct));
@endphp
<div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_8rem]">
    <x-ui.textarea name="question" dir="auto" :label="__('messages.question')" :value="$question->question ?? null" rows="2" required />
    <x-ui.input name="sort_order" type="number" min="0" :label="__('messages.question_order')" :value="$question->sort_order ?? ($nextOrder ?? 0)" />
</div>
<fieldset class="space-y-2">
    <legend class="mb-2 text-xs text-slate-400">{{ __('messages.answers_and_correct') }}</legend>
    @for($i = 0; $i < 4; $i++)
        <div class="flex items-center gap-3">
            <label class="flex flex-none cursor-pointer items-center gap-1.5 rounded-lg px-2 py-1 text-xs text-slate-400 has-[:checked]:bg-safe/10 has-[:checked]:text-safe" title="{{ __('messages.answer_n_correct', ['n' => $i + 1]) }}">
                <input type="radio" name="correct_index" value="{{ $i }}" @checked($correct === $i) class="accent-emerald-400">
                <span class="sr-only">{{ __('messages.answer_n_correct', ['n' => $i + 1]) }}</span>
                <span aria-hidden="true">{{ __('messages.correct') }}</span>
            </label>
            <x-ui.input name="answers[]" dir="auto" :field="'answers.'.$i" :label="__('messages.answer_n', ['n' => $i + 1])" :value="$answers[$i]->answer ?? null" required maxlength="500" class="flex-1" />
        </div>
    @endfor
    @if($inScope && $errors->has('correct_index'))<p class="field-error">{{ $errors->first('correct_index') }}</p>@endif
</fieldset>
<x-ui.input name="explanation" dir="auto" :label="__('messages.optional_explanation')" :value="$question->explanation ?? null" maxlength="1000" />
