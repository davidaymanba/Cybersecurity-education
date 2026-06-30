@extends('layouts.app', ['title' => 'Reset Password'])

@section('content')
<section class="flex min-h-[calc(100vh-72px)] items-center justify-center px-4 py-14 sm:px-6">
    <div class="w-full max-w-md">

        {{-- Icon + heading --}}
        <div class="mb-8 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-cyan-400/10 ring-1 ring-cyan-400/30">
                <svg class="h-8 w-8 text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white">Set new password</h1>
            <p class="mt-2 text-sm text-slate-400">Choose a strong password to secure your account.</p>
        </div>

        @if(session('status'))
            <div class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-300">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="post" action="{{ route('password.update') }}"
              class="rounded-2xl border border-white/10 bg-white/5 p-8 shadow-2xl backdrop-blur">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <label class="block text-sm font-medium text-slate-300">
                Email address
                <input name="email" type="email" value="{{ old('email', $email) }}"
                       autocomplete="email" required
                       class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-4 py-3 text-white
                              placeholder-slate-600 outline-none transition
                              focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400/30
                              @error('email') border-red-400/60 @enderror"
                       placeholder="you@example.com">
            </label>
            @error('email')
                <p class="mt-2 flex items-center gap-1.5 text-sm text-red-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                    </svg>
                    {{ $message }}
                </p>
            @enderror

            <label class="mt-5 block text-sm font-medium text-slate-300">
                New password
                <input name="password" type="password" autocomplete="new-password" required
                       class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-4 py-3 text-white
                              placeholder-slate-600 outline-none transition
                              focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400/30
                              @error('password') border-red-400/60 @enderror"
                       placeholder="Minimum 8 characters">
            </label>
            @error('password')
                <p class="mt-2 flex items-center gap-1.5 text-sm text-red-400">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
                    </svg>
                    {{ $message }}
                </p>
            @enderror

            <label class="mt-5 block text-sm font-medium text-slate-300">
                Confirm new password
                <input name="password_confirmation" type="password" autocomplete="new-password" required
                       class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-4 py-3 text-white
                              placeholder-slate-600 outline-none transition
                              focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400/30"
                       placeholder="Repeat your new password">
            </label>

            <button type="submit"
                    class="mt-6 w-full rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950
                           transition hover:bg-cyan-300 active:scale-[0.98]">
                Reset password
            </button>

            <p class="mt-5 text-center text-sm text-slate-500">
                Remembered it?
                <a href="{{ route('login') }}" class="text-cyan-400 hover:text-cyan-300 transition">Back to login</a>
            </p>
        </form>
    </div>
</section>
@endsection
