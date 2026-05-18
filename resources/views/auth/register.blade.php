@extends('layouts.app', ['title' => 'Register'])

@section('content')
<section class="mx-auto max-w-xl px-4 py-14 sm:px-6">
    <form method="post" action="{{ route('register.store') }}" class="rounded-xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
        @csrf
        <h1 class="text-3xl font-semibold text-white">Create student account</h1>
        <label class="mt-6 block text-sm text-slate-300">Name<input name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-3 py-3 text-white" required></label>
        <label class="mt-4 block text-sm text-slate-300">Email<input name="email" type="email" value="{{ old('email') }}" class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-3 py-3 text-white" required></label>
        <label class="mt-4 block text-sm text-slate-300">Password<input name="password" type="password" class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-3 py-3 text-white" required></label>
        <label class="mt-4 block text-sm text-slate-300">Confirm password<input name="password_confirmation" type="password" class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/70 px-3 py-3 text-white" required></label>
        @if($errors->any())<p class="mt-3 text-sm text-red-300">{{ $errors->first() }}</p>@endif
        <button class="mt-6 w-full rounded-lg bg-cyan-400 px-4 py-3 font-semibold text-slate-950">Register</button>
    </form>
</section>
@endsection
