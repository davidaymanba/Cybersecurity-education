@props(['label', 'value', 'suffix' => '', 'icon' => null, 'tone' => 'signal', 'hint' => null, 'decimals' => 0])
{{-- The final value is printed server-side; data-counter only animates it. --}}
@php
    $tones = ['signal' => 'text-signal bg-signal/10', 'safe' => 'text-safe bg-safe/10', 'pulse' => 'text-pulse bg-pulse/10', 'amber' => 'text-amber bg-amber/10'];
    $numeric = is_numeric($value);
@endphp
<div {{ $attributes->class(['surface p-5']) }}>
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-slate-400">{{ $label }}</p>
        @if($icon)
            <span class="grid h-9 w-9 place-items-center rounded-lg {{ $tones[$tone] ?? $tones['signal'] }}"><x-ui.icon :name="$icon" class="h-[18px] w-[18px]" /></span>
        @endif
    </div>
    <p class="mt-3 font-mono text-3xl font-semibold tracking-tight text-white" dir="ltr" style="text-align: start">
        @if($numeric)<span data-counter="{{ $value }}" data-decimals="{{ $decimals }}">{{ $decimals ? number_format((float) $value, $decimals) : number_format((float) $value) }}</span>@else<span>{{ $value }}</span>@endif<span class="text-xl text-slate-400">{{ $suffix }}</span>
    </p>
    @if($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
</div>
