@props(['value' => 0, 'tone' => 'signal', 'label' => null, 'size' => 'h-1.5'])
@php
    $value = max(0, min(100, (int) round($value)));
    $tones = ['signal' => 'bg-signal', 'safe' => 'bg-safe', 'pulse' => 'bg-pulse', 'amber' => 'bg-amber', 'danger' => 'bg-danger', 'gradient' => 'bg-gradient-to-r from-safe via-signal to-pulse rtl:bg-gradient-to-l'];
@endphp
<div {{ $attributes->class(['w-full overflow-hidden rounded-full bg-white/[0.07]', $size]) }}
     role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $value }}" @if($label) aria-label="{{ $label }}" @endif>
    <div class="h-full rounded-full {{ $tones[$tone] ?? $tones['signal'] }}" style="width: {{ $value }}%" data-progress="{{ $value }}"></div>
</div>
