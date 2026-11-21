@props(['points', 'series', 'label'])
{{--
    Two-or-more series line chart, rendered server-side as SVG (works without JS);
    resources/js/modules/charts.js adds the crosshair + tooltip.
    points: [['date' => 'Y-m-d', key => int, …]]   series: [key => ['label' => …, 'color' => …]]
    Time runs left→right in both languages.
--}}
@php
    $w = 640; $h = 220; $padL = 36; $padR = 16; $padT = 12; $padB = 26;
    $n = max(count($points), 1);
    $max = max(1, ...array_map(fn ($p) => max(array_map(fn ($k) => (int) $p[$k], array_keys($series))), $points ?: [[array_key_first($series) => 0]]));
    $step = max(1, (int) ceil($max / 4));
    $niceMax = $step * 4;
    $x = fn ($i) => $padL + ($n === 1 ? 0 : $i * ($w - $padL - $padR) / ($n - 1));
    $y = fn ($v) => $padT + ($h - $padT - $padB) * (1 - $v / $niceMax);
    $locale = app()->getLocale();
    $fmt = fn ($date) => \Illuminate\Support\Carbon::parse($date)->locale($locale)->translatedFormat('j M');
    $points = array_values($points);
    $chartPoints = [];
    foreach ($points as $i => $p) {
        $chartPoints[] = ['x' => round($x($i), 1), 'label' => $fmt($p['date'])] + array_intersect_key($p, $series);
    }
@endphp
<figure {{ $attributes->class(['relative']) }} data-chart="line"
        data-points="{{ json_encode($chartPoints) }}" data-series="{{ json_encode($series) }}">
    <div class="mb-3 flex flex-wrap items-center gap-4 text-xs text-slate-300" aria-hidden="true">
        @foreach($series as $s)
            <span class="inline-flex items-center gap-2"><span class="h-0.5 w-4 rounded-full" style="background: {{ $s['color'] }}"></span>{{ $s['label'] }}</span>
        @endforeach
    </div>
    <div class="relative" dir="ltr">
        <svg viewBox="0 0 {{ $w }} {{ $h }}" class="h-auto w-full overflow-visible" role="img" aria-label="{{ $label }}">
            @foreach(range(0, 4) as $g)
                @php($gy = $y($g * $step))
                <line x1="{{ $padL }}" x2="{{ $w - $padR }}" y1="{{ $gy }}" y2="{{ $gy }}" stroke="rgb(148 163 184 / {{ $g === 0 ? '0.25' : '0.08' }})" stroke-width="1" />
                <text x="{{ $padL - 8 }}" y="{{ $gy + 4 }}" text-anchor="end" class="fill-slate-500 font-mono text-[10px]">{{ $g * $step }}</text>
            @endforeach
            @foreach($points as $i => $p)
                @if($i % max(1, (int) ceil($n / 7)) === 0 || $i === $n - 1)
                    <text x="{{ $x($i) }}" y="{{ $h - 6 }}" text-anchor="middle" class="fill-slate-500 text-[10px]">{{ $fmt($p['date']) }}</text>
                @endif
            @endforeach
            @foreach($series as $key => $s)
                @php($path = collect($points)->values()->map(fn ($p, $i) => round($x($i), 1).','.round($y((int) $p[$key]), 1))->implode(' '))
                <polyline points="{{ $path }}" fill="none" stroke="{{ $s['color'] }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                @php($last = end($points))
                <circle cx="{{ $x($n - 1) }}" cy="{{ $y((int) $last[$key]) }}" r="4" fill="{{ $s['color'] }}" stroke="#07111f" stroke-width="2" />
            @endforeach
            <line data-crosshair x1="0" x2="0" y1="{{ $padT }}" y2="{{ $h - $padB }}" stroke="rgb(226 232 240 / 0.35)" stroke-width="1" visibility="hidden" />
            @foreach($series as $key => $s)
                <circle data-dot="{{ $key }}" r="4" fill="{{ $s['color'] }}" stroke="#07111f" stroke-width="2" visibility="hidden" />
            @endforeach
            <rect data-hit x="{{ $padL - 10 }}" y="0" width="{{ $w - $padL - $padR + 20 }}" height="{{ $h }}" fill="transparent" />
        </svg>
        <div data-tooltip role="status" class="pointer-events-none absolute top-0 hidden min-w-32 rounded-lg bg-raised px-3 py-2 text-xs shadow-xl ring-1 ring-white/10"></div>
    </div>
    <details class="mt-3 text-xs text-slate-400">
        <summary class="cursor-pointer select-none hover:text-white">{{ __('messages.show_as_table') }}</summary>
        <div class="relative mt-2 overflow-x-auto" data-lenis-prevent>
            <table class="data-table">
                <thead><tr><th>{{ __('messages.date') }}</th>@foreach($series as $s)<th>{{ $s['label'] }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach($points as $p)
                        <tr><td class="font-mono">{{ $p['date'] }}</td>@foreach($series as $key => $s)<td class="font-mono">{{ $p[$key] }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
