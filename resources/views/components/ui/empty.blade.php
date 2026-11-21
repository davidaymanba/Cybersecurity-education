@props(['icon' => 'sparkles', 'title', 'body' => null])
<div {{ $attributes->class(['surface grid place-items-center px-6 py-12 text-center']) }}>
    <span class="grid h-12 w-12 place-items-center rounded-xl bg-signal/10 text-signal ring-1 ring-signal/25"><x-ui.icon :name="$icon" /></span>
    <p class="mt-4 font-semibold text-white">{{ $title }}</p>
    @if($body)<p class="mt-1 max-w-sm text-sm text-slate-400">{{ $body }}</p>@endif
    @if(trim($slot))<div class="mt-5">{{ $slot }}</div>@endif
</div>
