@props(['id', 'title', 'size' => 'max-w-lg', 'openOnLoad' => false])
{{-- Native <dialog>: focus trapping, Esc to close and the backdrop come from the browser. Open with data-modal-open="id". --}}
<dialog id="{{ $id }}" class="modal {{ $size }}" aria-labelledby="{{ $id }}-title" @if($openOnLoad) data-open-on-load @endif>
    <div {{ $attributes->class(['surface-strong hud max-h-[calc(100dvh-2rem)] overflow-y-auto p-5 sm:p-6 shadow-2xl shadow-black/60']) }} data-lenis-prevent>
        <div class="mb-4 flex items-start justify-between gap-4">
            <h2 id="{{ $id }}-title" class="text-lg font-semibold text-white">{{ $title }}</h2>
            <button type="button" class="btn btn-quiet btn-icon -m-1" data-modal-close aria-label="{{ __('messages.close') }}"><x-ui.icon name="x" class="h-4 w-4" /></button>
        </div>
        {{ $slot }}
    </div>
</dialog>
