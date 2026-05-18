@extends('layouts.app', ['title' => 'Forgot Password'])

@section('content')
<section class="mx-auto max-w-xl px-4 py-14 sm:px-6">
    <form method="post" action="{{ route('password.email') }}" class="rounded-xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
        @csrf
        <h1 class="text-3xl font-semibold text-white">Reset password</h1>
        <p class="mt-3 text-sm text-slate-300">Enter your email and the platform will send a reset link when mail is configured.</p>
        <label class="mt-6 block text-sm text-slate-300">Email<input name="email" type="email" class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-3 py-3 text-white" required></label>
        <button class="mt-6 w-full rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950">Send reset link</button>
    </form>
</section>
@endsection
