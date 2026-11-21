@props(['name', 'label', 'checked' => false, 'value' => '1', 'description' => null])
@php
    $key = trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    $isChecked = old() ? (bool) old($key) : (bool) $checked;
@endphp
<label {{ $attributes->only('class')->class(['choice']) }}>
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" @checked($isChecked) {{ $attributes->except('class') }}>
    <span>
        <span class="block text-sm font-medium text-white">{{ $label }}</span>
        @if($description)<span class="mt-0.5 block text-xs text-slate-400">{{ $description }}</span>@endif
    </span>
</label>
