@props(['value' => 0, 'size' => 96, 'stroke' => 8, 'tone' => 'signal', 'label' => null])
{{-- Circular progress (pathLength = 100, so dashoffset = 100 - value). Slot renders in the centre. --}}
@php
    $value = max(0, min(100, (float) $value));
    $colors = ['signal' => '#22d3ee', 'safe' => '#34d399', 'pulse' => '#a78bfa', 'amber' => '#fbbf24', 'danger' => '#f87171'];
    $r = 50 - $stroke / 2;
@endphp
<div {{ $attributes->class(['relative inline-grid place-items-center']) }} style="width: {{ $size }}px; height: {{ $size }}px"
     role="img" @if($label) aria-label="{{ $label }}" @endif>
    <svg viewBox="0 0 100 100" class="absolute inset-0 h-full w-full -rotate-90" aria-hidden="true">
        <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="rgb(148 163 184 / 0.14)" stroke-width="{{ $stroke }}" />
        <circle cx="50" cy="50" r="{{ $r }}" fill="none" stroke="{{ $colors[$tone] ?? $colors['signal'] }}" stroke-width="{{ $stroke }}" stroke-linecap="round"
                pathLength="100" stroke-dasharray="100" stroke-dashoffset="{{ 100 - $value }}" data-ring="{{ $value }}" />
    </svg>
    <div class="relative text-center">{{ $slot }}</div>
</div>
