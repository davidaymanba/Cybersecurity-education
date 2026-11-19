@extends('layouts.app', ['title' => __('messages.account')])

@section('content')
<section class="mx-auto max-w-xl px-4 py-10 sm:px-6">
    <h1 class="text-3xl font-semibold">{{ __('messages.account') }}</h1>
    <p class="mt-2 text-sm text-slate-300">{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>

    <form method="post" action="{{ route('account.password') }}" class="mt-8 space-y-4 rounded-xl border border-white/10 bg-white/10 p-6">
        @csrf
        @method('PUT')
        <h2 class="text-lg font-semibold">{{ __('messages.change_password') }}</h2>

        @foreach(['current_password' => 'current_password', 'password' => 'new_password', 'password_confirmation' => 'confirm_new_password'] as $field => $label)
            <label class="block text-sm">
                <span class="text-slate-200">{{ __('messages.'.$label) }}</span>
                <input type="password" name="{{ $field }}" required autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}"
                       class="mt-1 w-full rounded-lg border border-white/10 bg-slate-950/70 p-3 text-white">
                @error($field)<span class="mt-1 block text-xs text-red-300">{{ $message }}</span>@enderror
            </label>
        @endforeach

        <button class="rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950">{{ __('messages.save') }}</button>
    </form>
</section>
@endsection
