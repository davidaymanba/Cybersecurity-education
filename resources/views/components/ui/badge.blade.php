@props(['tone' => 'muted', 'dot' => false])
@php
    $tones = [
        'signal' => 'bg-signal/10 text-cyan-200 ring-1 ring-signal/25',
        'safe' => 'bg-safe/10 text-emerald-200 ring-1 ring-safe/25',
        'pulse' => 'bg-pulse/10 text-violet-200 ring-1 ring-pulse/25',
        'amber' => 'bg-amber/10 text-amber-200 ring-1 ring-amber/25',
        'danger' => 'bg-danger/10 text-red-200 ring-1 ring-danger/25',
        'muted' => 'bg-white/5 text-slate-300 ring-1 ring-white/10',
    ];
    $dots = ['signal' => 'bg-signal', 'safe' => 'bg-safe', 'pulse' => 'bg-pulse', 'amber' => 'bg-amber', 'danger' => 'bg-danger', 'muted' => 'bg-slate-400'];
@endphp
<span {{ $attributes->class(['badge', $tones[$tone] ?? $tones['muted']]) }}>
    @if($dot)<span class="h-1.5 w-1.5 rounded-full {{ $dots[$tone] ?? $dots['muted'] }}"></span>@endif
    {{ $slot }}
</span>
