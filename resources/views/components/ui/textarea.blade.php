@props(['name', 'label', 'value' => null, 'hint' => null, 'id' => null, 'rows' => 4, 'field' => null])
@aware(['formId' => null])
@php
    $key = $field ?? trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $inScope = old('_form') === null || old('_form') === $formId;
    $id ??= 'f-'.\Illuminate\Support\Str::slug(str_replace('.', '-', $key)).'-'.substr(md5(uniqid('', true)), 0, 5);
    $message = $inScope ? ($errors->first($key) ?: null) : null;
    $describedBy = trim(($message ? $id.'-error ' : '').($hint ? $id.'-hint' : ''));
@endphp
<div {{ $attributes->only('class')->class(['min-w-0']) }}>
    <div class="field" data-invalid="{{ $message ? 'true' : 'false' }}">
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" placeholder=" "
                  @if($message) aria-invalid="true" @endif
                  @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                  {{ $attributes->except('class')->class(['field-control']) }}>{{ $inScope ? old($key, $value) : $value }}</textarea>
        <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    </div>
    @if($message)<p id="{{ $id }}-error" class="field-error">{{ $message }}</p>@endif
    @if($hint)<p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>@endif
</div>
