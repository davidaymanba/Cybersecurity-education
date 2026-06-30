@extends('layouts.app', ['title' => 'Forgot Password'])

@push('styles')
<style>
    @keyframes spin-slow  { to { transform: rotate(360deg); } }
    @keyframes float-icon { 0%,100%{ transform:translateY(0) } 50%{ transform:translateY(-10px) } }
    @keyframes pulse-glow {
        0%,100% { box-shadow: 0 0 0 0 rgba(34,211,238,.35); }
        50%      { box-shadow: 0 0 0 16px rgba(34,211,238,0); }
    }
    @keyframes fade-in-up {
        from { opacity:0; transform:translateY(20px); }
        to   { opacity:1; transform:translateY(0); }
    }
    @keyframes draw-line { from { stroke-dashoffset: 200; } to { stroke-dashoffset: 0; } }

    .spin-slow  { animation: spin-slow 18s linear infinite; }
    .float-icon { animation: float-icon 4s ease-in-out infinite; }
    .pulse-glow { animation: pulse-glow 2.4s ease-in-out infinite; }

    .step-enter { animation: fade-in-up .5s ease both; }
    .step-enter:nth-child(1) { animation-delay:.1s }
    .step-enter:nth-child(2) { animation-delay:.25s }
    .step-enter:nth-child(3) { animation-delay:.4s }

    .form-enter { animation: fade-in-up .6s .15s ease both; }

    .input-field {
        width:100%; padding:13px 16px 13px 44px;
        background:rgba(2,8,23,.7);
        border:1px solid rgba(255,255,255,.1);
        border-radius:.625rem; color:#f1f5f9;
        font-size:.9375rem; outline:none;
        transition: border-color .2s, box-shadow .2s;
    }
    .input-field::placeholder { color:#334155; }
    .input-field:focus {
        border-color: #22d3ee;
        box-shadow: 0 0 0 3px rgba(34,211,238,.12);
    }
    .input-field.error { border-color: rgba(248,113,113,.6); }
</style>
@endpush

@section('content')

{{-- ─── Subtle radial glow behind content ─────────────────────────────── --}}
<div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute -top-32 left-1/2 h-[600px] w-[900px] -translate-x-1/2
                rounded-full bg-cyan-500/5 blur-[140px]"></div>
</div>

<section class="mx-auto grid min-h-[calc(100vh-72px)] max-w-6xl items-center
                gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:gap-20">

    {{-- ══════════════════════════════════════════════════════
         LEFT PANEL  –  branding + steps
    ════════════════════════════════════════════════════════ --}}
    <div class="max-lg:hidden">

        {{-- Animated shield / lock illustration --}}
        <div class="relative mb-10 flex justify-center">
            {{-- Outer rotating ring --}}
            <svg class="spin-slow absolute h-56 w-56 opacity-15" viewBox="0 0 200 200"
                 fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="100" cy="100" r="96" stroke="#22d3ee" stroke-width="1"
                        stroke-dasharray="8 6"/>
            </svg>
            {{-- Inner glow ring --}}
            <svg class="absolute h-40 w-40 opacity-20" viewBox="0 0 160 160"
                 fill="none" style="animation:spin-slow 28s linear infinite reverse">
                <circle cx="80" cy="80" r="76" stroke="#67e8f9" stroke-width=".5"
                        stroke-dasharray="4 10"/>
            </svg>
            {{-- Icon container --}}
            <div class="float-icon pulse-glow relative z-10 flex h-28 w-28 items-center
                        justify-center rounded-3xl border border-cyan-400/20
                        bg-gradient-to-br from-cyan-400/10 to-slate-900/60 backdrop-blur-sm">
                <svg class="h-14 w-14 text-cyan-400" fill="none" stroke="currentColor"
                     stroke-width="1.3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1
                             3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9
                             11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571
                             -.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                </svg>
            </div>
        </div>

        {{-- Heading --}}
        <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl">
            Account<br>
            <span class="bg-gradient-to-r from-cyan-300 to-cyan-500 bg-clip-text text-transparent">
                Recovery
            </span>
        </h1>
        <p class="mt-4 max-w-sm text-base leading-relaxed text-slate-400">
            Reset your password in three quick steps — no hassle, fully secured.
        </p>

        {{-- 3-step process --}}
        <ol class="mt-10 space-y-4">
            @foreach([
                ['01', 'Enter your email', "We'll look up your registered account."],
                ['02', 'Check your inbox', 'A secure one-time reset link will arrive shortly.'],
                ['03', 'Set new password', "Choose a strong password and you're back in."],
            ] as $step)
            <li class="step-enter flex items-start gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl
                             border border-cyan-400/25 bg-cyan-400/8
                             font-mono text-xs font-bold text-cyan-400">
                    {{ $step[0] }}
                </span>
                <div class="pt-1">
                    <p class="text-sm font-semibold text-slate-200">{{ $step[1] }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $step[2] }}</p>
                </div>
            </li>
            @endforeach
        </ol>

        {{-- Security note --}}
        <div class="mt-10 inline-flex items-center gap-2 rounded-full border border-white/8
                    bg-white/4 px-4 py-2 text-xs text-slate-500">
            <svg class="h-3.5 w-3.5 text-cyan-500" fill="none" stroke="currentColor"
                 stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M16.5 10.5V7.125A4.125 4.125 0 0 0 8.25 7.125V10.5M5.25
                         10.5h13.5a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5 1.5 0 0 1-1.5
                         1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-7.5a1.5 1.5 0 0 1 1.5-1.5Z"/>
            </svg>
            Reset links expire after 60 minutes
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         RIGHT PANEL  –  form card
    ════════════════════════════════════════════════════════ --}}
    <div class="form-enter w-full">

        {{-- Success banner --}}
        @if(session('status'))
            <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-400/25
                        bg-emerald-400/8 px-5 py-4 text-sm text-emerald-300">
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <div>
                    <p class="font-semibold">Email sent!</p>
                    <p class="mt-0.5 text-emerald-400/80">{{ session('status') }}</p>
                </div>
            </div>
        @endif

        {{-- Card --}}
        <div class="rounded-3xl border border-white/8 bg-gradient-to-b from-white/6 to-white/3
                    p-8 shadow-2xl shadow-black/40 backdrop-blur-xl sm:p-10">

            {{-- Card header --}}
            <div class="mb-8 flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl
                            border border-cyan-400/20 bg-cyan-400/10">
                    <svg class="h-6 w-6 text-cyan-400" fill="none" stroke="currentColor"
                         stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16.5 10.5V7.125A4.125 4.125 0 0 0 8.25 7.125V10.5
                                 M5.25 10.5h13.5a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5
                                 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5
                                 -1.5v-7.5a1.5 1.5 0 0 1 1.5-1.5Z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">Reset your password</h2>
                    <p class="text-sm text-slate-500">We'll email you a secure reset link</p>
                </div>
            </div>

            <form method="post" action="{{ route('password.email') }}">
                @csrf

                {{-- Email field --}}
                <div class="mb-5">
                    <label class="mb-2 block text-sm font-medium text-slate-300">
                        Email address
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex
                                     items-center pl-4 text-slate-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                 stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25
                                         2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5
                                         0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0
                                         0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0
                                         1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0
                                         1-2.36 0L3.32 8.91a2.25 2.25 0 0
                                         1-1.07-1.916V6.75"/>
                            </svg>
                        </span>
                        <input name="email" type="email" value="{{ old('email') }}"
                               autocomplete="email" autofocus required
                               placeholder="you@example.com"
                               class="input-field {{ $errors->has('email') ? 'error' : '' }}">
                    </div>
                    @error('email')
                        <p class="mt-2 flex items-center gap-1.5 text-xs text-red-400">
                            <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16
                                     0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5
                                     0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                     clip-rule="evenodd"/>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="group relative w-full overflow-hidden rounded-xl
                               bg-cyan-400 px-5 py-3.5 font-semibold text-slate-950
                               transition-all hover:bg-cyan-300 active:scale-[.98]">
                    <span class="relative flex items-center justify-center gap-2">
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                             fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485
                                     12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0
                                     0h7.5"/>
                        </svg>
                        Send reset link
                    </span>
                </button>

            </form>

            {{-- Divider --}}
            <div class="my-6 flex items-center gap-3">
                <div class="h-px flex-1 bg-white/6"></div>
                <span class="text-xs text-slate-600">or</span>
                <div class="h-px flex-1 bg-white/6"></div>
            </div>

            {{-- Back to login --}}
            <a href="{{ route('login') }}"
               class="flex w-full items-center justify-center gap-2 rounded-xl
                      border border-white/8 bg-white/4 py-3 text-sm font-medium
                      text-slate-400 transition hover:border-white/15 hover:text-slate-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                     stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
                </svg>
                Back to login
            </a>

        </div>

        {{-- Mobile: show steps below card --}}
        <div class="mt-6 block lg:hidden">
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-600">
                How it works
            </p>
            <ol class="space-y-3">
                @foreach(['Enter email', 'Check inbox', 'Set new password'] as $i => $s)
                <li class="flex items-center gap-3 text-sm text-slate-500">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md
                                 bg-cyan-400/10 font-mono text-[11px] font-bold text-cyan-500">
                        0{{ $i + 1 }}
                    </span>
                    {{ $s }}
                </li>
                @endforeach
            </ol>
        </div>

    </div>
</section>
@endsection
