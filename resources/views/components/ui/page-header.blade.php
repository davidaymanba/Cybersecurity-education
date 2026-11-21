@props(['kicker' => null, 'title', 'lead' => null])
<div {{ $attributes->class(['flex flex-col gap-4 md:flex-row md:items-end md:justify-between']) }}>
    <div class="min-w-0">
        @if($kicker)<p class="mono-label font-mono text-xs uppercase tracking-[0.2em] text-signal" data-reveal="fade">{{ $kicker }}</p>@endif
        <h1 class="mt-2 text-3xl font-bold leading-tight text-white sm:text-4xl" data-split>{{ $title }}</h1>
        @if($lead)<p class="mt-3 max-w-2xl leading-7 text-slate-400" data-reveal data-delay="0.1">{{ $lead }}</p>@endif
    </div>
    @if(trim($slot))<div class="flex flex-wrap items-center gap-2" data-reveal data-delay="0.15">{{ $slot }}</div>@endif
</div>
