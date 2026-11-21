@props(['name', 'label', 'value' => null, 'hint' => null, 'id' => null, 'rows' => 4])
@php
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $id ??= 'f-'.\Illuminate\Support\Str::slug(str_replace('.', '-', $key)).'-'.substr(md5(uniqid('', true)), 0, 5);
    $message = $errors->first($key) ?: null;
    $describedBy = trim(($message ? $id.'-error ' : '').($hint ? $id.'-hint' : ''));
@endphp
<div {{ $attributes->only('class')->class(['min-w-0']) }}>
    <div class="field" data-invalid="{{ $message ? 'true' : 'false' }}">
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" placeholder=" "
                  @if($message) aria-invalid="true" @endif
                  @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
                  {{ $attributes->except('class')->class(['field-control']) }}>{{ old($key, $value) }}</textarea>
        <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    </div>
    @if($message)<p id="{{ $id }}-error" class="field-error">{{ $message }}</p>@endif
    @if($hint)<p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>@endif
</div>
