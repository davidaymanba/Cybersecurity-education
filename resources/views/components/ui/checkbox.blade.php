@props(['name', 'label', 'checked' => false, 'value' => '1', 'description' => null])
@aware(['formId' => null])
{{-- Sends 0 when unticked (hidden field first), so "boolean('x', true)" defaults never flip it back on. --}}
@php
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $inScope = old('_form') === null || old('_form') === $formId;
    $isChecked = ($inScope && old($key) !== null) ? (bool) old($key) : (bool) $checked;
@endphp
<label {{ $attributes->only('class')->class(['choice']) }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($isChecked) {{ $attributes->except('class') }}>
    <span>
        <span class="block text-sm font-medium text-white">{{ $label }}</span>
        @if($description)<span class="mt-0.5 block text-xs text-slate-400">{{ $description }}</span>@endif
    </span>
</label>
