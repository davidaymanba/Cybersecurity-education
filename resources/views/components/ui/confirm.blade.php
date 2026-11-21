@props(['action', 'method' => 'POST', 'message' => null, 'variant' => 'danger', 'icon' => null, 'size' => 'sm', 'confirmLabel' => null])
{{--
    A one-button form that asks first (shared confirm dialog in the layout).
    <x-ui.confirm :action="route('admin.lessons.delete', $lesson)" method="DELETE" :message="__('messages.confirm_delete_lesson')">Delete</x-ui.confirm>
--}}
<form method="post" action="{{ $action }}" data-confirm="{{ $message ?? __('messages.are_you_sure') }}"
      data-confirm-tone="{{ $variant === 'danger' ? 'danger' : 'primary' }}"
      @if($confirmLabel) data-confirm-label="{{ $confirmLabel }}" @endif
      {{ $attributes->only('class')->class(['inline']) }}>
    @csrf
    @if(strtoupper($method) !== 'POST') @method($method) @endif
    <x-ui.button :variant="$variant" :size="$size" :icon="$icon" {{ $attributes->except('class') }}>{{ $slot }}</x-ui.button>
</form>
