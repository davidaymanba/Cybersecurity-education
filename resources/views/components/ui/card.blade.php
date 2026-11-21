@props(['hud' => false, 'spotlight' => false, 'padding' => 'p-5 sm:p-6', 'as' => 'div', 'tone' => 'signal'])
@php
    $spots = ['signal' => 'rgba(34,211,238,0.14)', 'safe' => 'rgba(52,211,153,0.14)', 'pulse' => 'rgba(167,139,250,0.16)', 'amber' => 'rgba(251,191,36,0.12)'];
@endphp
<{{ $as }} {{ $attributes->class(['surface', $padding, 'hud' => $hud, 'spot-card' => $spotlight]) }}
    @if($spotlight) data-spotlight style="--spot: {{ $spots[$tone] ?? $spots['signal'] }}" @endif>
    @if($spotlight)<span class="spot-glow" aria-hidden="true"></span>@endif
    {{ $slot }}
</{{ $as }}>
