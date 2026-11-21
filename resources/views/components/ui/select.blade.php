@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'id' => null])
{{-- options: [value => label]. Slot content is appended after the generated options. --}}
@php
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $id ??= 'f-'.\Illuminate\Support\Str::slug(str_replace('.', '-', $key)).'-'.substr(md5(uniqid('', true)), 0, 5);
    $message = $errors->first($key) ?: null;
    $current = (string) old($key, $value);
@endphp
<div {{ $attributes->only('class')->class(['min-w-0']) }}>
    <div class="field" data-invalid="{{ $message ? 'true' : 'false' }}">
        <select id="{{ $id }}" name="{{ $name }}"
                @if($message) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
                {{ $attributes->except('class')->class(['field-control']) }}>
            @if($placeholder)<option value="">{{ $placeholder }}</option>@endif
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected($current === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <label for="{{ $id }}" class="field-label" data-fixed>{{ $label }}</label>
        <x-ui.icon name="chevron-down" class="field-chevron h-4 w-4" />
    </div>
    @if($message)<p id="{{ $id }}-error" class="field-error">{{ $message }}</p>@endif
    @if($hint)<p class="field-hint">{{ $hint }}</p>@endif
</div>
