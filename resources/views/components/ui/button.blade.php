@props(['variant' => 'primary', 'size' => null, 'href' => null, 'icon' => null, 'iconEnd' => null, 'type' => 'submit'])
{{-- <x-ui.button variant="primary|safe|light|ghost|quiet|danger" size="sm|lg" href="…" icon="plus" icon-end="arrow"> --}}
@php
    $classes = ['btn', 'btn-'.$variant, $size ? 'btn-'.$size : null];
    $directional = ['arrow', 'chevron'];
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if($icon)<x-ui.icon :name="$icon" :flip="in_array($icon, $directional)" class="h-4 w-4" />@endif
        <span data-label>{{ $slot }}</span>
        @if($iconEnd)<x-ui.icon :name="$iconEnd" :flip="in_array($iconEnd, $directional)" class="h-4 w-4" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        @if($icon)<x-ui.icon :name="$icon" :flip="in_array($icon, $directional)" class="h-4 w-4" />@endif
        <span data-label>{{ $slot }}</span>
        @if($iconEnd)<x-ui.icon :name="$iconEnd" :flip="in_array($iconEnd, $directional)" class="h-4 w-4" />@endif
    </button>
@endif
