{{-- Session status + validation summary, rendered at the top of the page content. --}}
@if(session('status') || $errors->any())
    <div {{ $attributes->class(['space-y-3']) }}>
        @if(session('status'))
            <x-ui.alert tone="safe" dismissible>{{ session('status') }}</x-ui.alert>
        @endif
        @if($errors->any())
            <x-ui.alert tone="danger" :title="__('messages.errors_title')" dismissible>
                <ul class="mt-1 list-disc space-y-0.5 ps-4">
                    @foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach
                </ul>
            </x-ui.alert>
        @endif
    </div>
@endif
