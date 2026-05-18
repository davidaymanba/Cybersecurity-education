@extends('layouts.app', ['title' => 'Login'])

@section('content')
<section class="mx-auto grid max-w-6xl items-center gap-8 px-4 py-14 sm:px-6 lg:grid-cols-2">
    <div>
        <h1 class="text-4xl font-semibold text-white">Welcome back</h1>
        <p class="mt-4 text-slate-300">Sign in to continue your cybersecurity learning path and research session.</p>
    </div>
    <form method="post" action="{{ route('login.store') }}" class="rounded-xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
        @csrf
        <label class="block text-sm text-slate-300">Email<input name="email" type="email" value="{{ old('email') }}" class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-3 py-3 text-white outline-none focus:border-cyan-300" required></label>
        <label class="mt-4 block text-sm text-slate-300">Password<input name="password" type="password" class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-3 py-3 text-white outline-none focus:border-cyan-300" required></label>
        <div class="mt-4 flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-slate-300"><input type="checkbox" name="remember" class="rounded"> Remember me</label>
            <a href="{{ route('password.request') }}" class="text-cyan-200">Forgot password?</a>
        </div>
        @error('email')<p class="mt-3 text-sm text-red-300">{{ $message }}</p>@enderror
        <button class="mt-6 w-full rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950">Login</button>
        <p class="mt-4 text-center text-sm text-slate-300">New here? <a href="{{ route('register') }}" class="text-cyan-200">Create an account</a></p>
    </form>
</section>
@endsection
