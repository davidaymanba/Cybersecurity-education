@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null, 'id' => null, 'error' => null, 'field' => null])
@aware(['formId' => null])
{{--
    Floating-label input. Repopulates from old() (never for passwords) and shows the validation
    error for its field. Array names like answers[2] map to the "answers.2" error/old key.
--}}
@php
    $key = $field ?? trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $inScope = old('_form') === null || old('_form') === $formId;
    $id ??= 'f-'.\Illuminate\Support\Str::slug(str_replace('.', '-', $key)).'-'.substr(md5(uniqid('', true)), 0, 5);
    $message = $error ?? ($inScope ? ($errors->first($key) ?: null) : null);
    $current = $type === 'password' ? null : ($inScope ? old($key, $value) : $value);
    $describedBy = trim(($message ? $id.'-error ' : '').($hint ? $id.'-hint' : ''));
@endphp
<div {{ $attributes->only('class')->class(['min-w-0']) }}>
    <div class="field" data-invalid="{{ $message ? 'true' : 'false' }}">
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" placeholder=" "
               @if($current !== null) value="{{ $current }}" @endif
               @if($message) aria-invalid="true" @endif
               @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
               {{ $attributes->except('class')->class(['field-control', 'pe-12' => $type === 'password']) }}>
        <label for="{{ $id }}" class="field-label">{{ $label }}</label>
        @if($type === 'password')
            <button type="button" class="field-adornment btn btn-quiet btn-icon" data-password-toggle aria-pressed="false" aria-label="{{ __('messages.show_password') }}">
                <x-ui.icon name="eye" class="h-4 w-4" data-eye />
                <x-ui.icon name="eye-off" class="h-4 w-4" data-eye-off hidden />
            </button>
        @endif
    </div>
    @if($message)<p id="{{ $id }}-error" class="field-error">{{ $message }}</p>@endif
    @if($hint)<p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>@endif
</div>
