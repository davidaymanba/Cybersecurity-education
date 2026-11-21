@props(['caption' => null])
{{-- Scrolls horizontally on small screens; data-lenis-prevent keeps smooth-scroll off the inner scroller. --}}
<div {{ $attributes->class(['surface overflow-hidden']) }}>
    <div class="relative overflow-x-auto" data-lenis-prevent tabindex="0" @if($caption) aria-label="{{ $caption }}" @endif>
        <table class="data-table">
            @if($caption)<caption class="sr-only">{{ $caption }}</caption>@endif
            @isset($head)<thead><tr>{{ $head }}</tr></thead>@endisset
            <tbody>{{ $slot }}</tbody>
        </table>
    </div>
</div>
