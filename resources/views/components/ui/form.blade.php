@props(['action', 'method' => 'POST', 'formId' => null, 'busy' => null])
{{--
    <x-ui.form :action="…" method="PUT" form-id="lesson-5"> … </x-ui.form>
    Fields inside read form-id through @aware: when several forms share field names (inline edit
    rows), old input and validation errors only come back to the form that was submitted.
--}}
<form method="post" action="{{ $action }}" @if($busy) data-busy-text="{{ $busy }}" @endif {{ $attributes }}>
    @csrf
    @if(strtoupper($method) !== 'POST') @method($method) @endif
    @if($formId)<input type="hidden" name="_form" value="{{ $formId }}">@endif
    {{ $slot }}
</form>
