@props(['tone' => 'signal', 'icon' => null, 'dismissible' => false, 'title' => null])
@php
    $tones = [
        'signal' => ['border-signal/30 bg-signal/[0.07] text-cyan-50', 'text-signal', 'info'],
        'safe' => ['border-safe/30 bg-safe/[0.07] text-emerald-50', 'text-safe', 'check'],
        'amber' => ['border-amber/30 bg-amber/[0.07] text-amber-50', 'text-amber', 'alert'],
        'danger' => ['border-danger/35 bg-danger/[0.08] text-red-50', 'text-danger', 'alert'],
    ];
    [$box, $iconColor, $defaultIcon] = $tones[$tone] ?? $tones['signal'];
@endphp
<div {{ $attributes->class(['flex items-start gap-3 rounded-xl border px-4 py-3 text-sm', $box]) }}
     role="{{ $tone === 'danger' ? 'alert' : 'status' }}" @if($dismissible) data-dismissible @endif>
    <x-ui.icon :name="$icon ?? $defaultIcon" class="mt-0.5 h-4 w-4 {{ $iconColor }}" />
    <div class="min-w-0 flex-1 leading-6">
        @if($title)<p class="font-semibold">{{ $title }}</p>@endif
        {{ $slot }}
    </div>
    @if($dismissible)
        <button type="button" class="-m-1 rounded-md p-1 opacity-70 hover:opacity-100" data-dismiss aria-label="{{ __('messages.dismiss') }}"><x-ui.icon name="x" class="h-4 w-4" /></button>
    @endif
</div>
