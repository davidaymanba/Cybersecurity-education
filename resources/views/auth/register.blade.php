@extends('layouts.app', ['title' => 'Register'])

@php
    $isArabic = app()->getLocale() === 'ar';
@endphp

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
    [dir="rtl"] .input-field {
        padding:13px 44px 13px 16px;
    }
    .field-icon {
        position: absolute;
        inset-block: 0;
        left: 0;
        display: flex;
        align-items: center;
        padding-left: 1rem;
        color: #64748b;
        pointer-events: none;
    }
    [dir="rtl"] .field-icon {
        left: auto;
        right: 0;
        padding-left: 0;
        padding-right: 1rem;
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
<div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
    <div class="absolute -top-32 left-1/2 h-[600px] w-[900px] -translate-x-1/2 rounded-full bg-cyan-500/5 blur-[140px]"></div>
</div>

<section class="mx-auto grid min-h-[calc(100vh-72px)] max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:gap-20">
    <div class="max-lg:hidden">
        <div class="relative mb-10 flex justify-center">
            <svg class="spin-slow absolute h-56 w-56 opacity-15" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="100" cy="100" r="96" stroke="#22d3ee" stroke-width="1" stroke-dasharray="8 6"/>
            </svg>
            <svg class="absolute h-40 w-40 opacity-20" viewBox="0 0 160 160" fill="none" style="animation:spin-slow 28s linear infinite reverse">
                <circle cx="80" cy="80" r="76" stroke="#67e8f9" stroke-width=".5" stroke-dasharray="4 10"/>
            </svg>
            <div class="float-icon pulse-glow relative z-10 flex h-28 w-28 items-center justify-center rounded-3xl border border-cyan-400/20 bg-gradient-to-br from-cyan-400/10 to-slate-900/60 backdrop-blur-sm">
                <svg class="h-14 w-14 text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 0a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h16.5A2.25 2.25 0 0 0 22.5 16.5v-9a2.25 2.25 0 0 0-2.25-2.25m-16.5 0V3.75m16.5 1.5V3.75M8.25 9.75h7.5M8.25 13.5h4.5"/>
                </svg>
            </div>
        </div>

        <h1 class="text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl {{ $isArabic ? 'text-right' : '' }}">
            {{ $isArabic ? 'أنشئ' : 'Create Your' }}<br>
            <span class="bg-gradient-to-r from-cyan-300 to-cyan-500 bg-clip-text text-transparent">Student Account</span>
        </h1>
        <p class="mt-4 max-w-sm text-base leading-relaxed text-slate-400 {{ $isArabic ? 'text-right' : '' }}">
            {{ $isArabic ? 'انضم للمنصة وابدأ رحلتك في الأمن السيبراني مع متابعة التقدم ودعم الذكاء الاصطناعي.' : 'Join the platform and unlock a guided cybersecurity roadmap with AI tutoring and progress tracking.' }}
        </p>

        <ol class="mt-10 space-y-4">
            @foreach([
                ['01', $isArabic ? 'أنشئ ملفك' : 'Create your profile', $isArabic ? 'أضف الاسم والبريد والمستوى.' : 'Add your name, email, and learning level.'],
                ['02', $isArabic ? 'اختر كلمة مرور قوية' : 'Set a strong password', $isArabic ? 'استخدم 8 أحرف على الأقل.' : 'Use at least 8 characters for better security.'],
                ['03', $isArabic ? 'ابدأ فورًا' : 'Start immediately', $isArabic ? 'ادخل للدروس والاختبارات مباشرة.' : 'Access lessons and quizzes right after signup.'],
            ] as $step)
                <li class="step-enter flex items-start gap-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-cyan-400/25 bg-cyan-400/8 font-mono text-xs font-bold text-cyan-400">{{ $step[0] }}</span>
                    <div class="pt-1 {{ $isArabic ? 'text-right' : '' }}">
                        <p class="text-sm font-semibold text-slate-200">{{ $step[1] }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $step[2] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="form-enter w-full">
        <div class="rounded-3xl border border-white/8 bg-gradient-to-b from-white/6 to-white/3 p-8 shadow-2xl shadow-black/40 backdrop-blur-xl sm:p-10">
            <div class="mb-8 flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-cyan-400/20 bg-cyan-400/10">
                    <svg class="h-6 w-6 text-cyan-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white {{ $isArabic ? 'text-right' : '' }}">{{ $isArabic ? 'إنشاء حساب طالب' : 'Create student account' }}</h2>
                    <p class="text-sm text-slate-500 {{ $isArabic ? 'text-right' : '' }}">{{ $isArabic ? 'ابدأ التعلم خلال أقل من دقيقة' : 'Start learning in less than a minute' }}</p>
                </div>
            </div>

            <form method="post" action="{{ route('register.store') }}">
                @csrf

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-slate-300 {{ $isArabic ? 'text-right' : '' }}">{{ $isArabic ? 'الاسم الكامل' : 'Full name' }}</label>
                    <div class="relative">
                        <span class="field-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 1 1 15 0A17.933 17.933 0 0 1 12 21.75a17.933 17.933 0 0 1-7.5-1.632Z"/>
                            </svg>
                        </span>
                        <input name="name" value="{{ old('name') }}" required placeholder="{{ $isArabic ? 'الاسم الكامل' : 'Your full name' }}" class="input-field {{ $errors->has('name') ? 'error' : '' }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-slate-300 {{ $isArabic ? 'text-right' : '' }}">{{ $isArabic ? 'البريد الإلكتروني' : 'Email address' }}</label>
                    <div class="relative">
                        <span class="field-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                            </svg>
                        </span>
                        <input name="email" type="email" value="{{ old('email') }}" required placeholder="you@example.com" class="input-field {{ $errors->has('email') ? 'error' : '' }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-slate-300 {{ $isArabic ? 'text-right' : '' }}">{{ $isArabic ? 'المستوى الحالي' : 'Current level' }}</label>
                    <div class="relative">
                        <span class="field-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v1.5M3 19.5V21m18-18v1.5m0 15V21M7.5 6h9m-9 6h9m-9 6h9"/>
                            </svg>
                        </span>
                        <select name="learning_level" required class="input-field {{ $errors->has('learning_level') ? 'error' : '' }}">
                            @foreach(['Beginner', 'Intermediate', 'Expert'] as $level)
                                <option value="{{ $level }}" @selected(old('learning_level', 'Beginner') === $level)>{{ $level }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-slate-300 {{ $isArabic ? 'text-right' : '' }}">{{ $isArabic ? 'كلمة المرور' : 'Password' }}</label>
                    <div class="relative">
                        <span class="field-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V7.125A4.125 4.125 0 0 0 8.25 7.125V10.5M5.25 10.5h13.5a1.5 1.5 0 0 1 1.5 1.5v7.5a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-7.5a1.5 1.5 0 0 1 1.5-1.5Z"/>
                            </svg>
                        </span>
                        <input name="password" type="password" autocomplete="new-password" required placeholder="{{ $isArabic ? '8 أحرف على الأقل' : 'At least 8 characters' }}" class="input-field {{ $errors->has('password') ? 'error' : '' }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="mb-2 block text-sm font-medium text-slate-300 {{ $isArabic ? 'text-right' : '' }}">{{ $isArabic ? 'تأكيد كلمة المرور' : 'Confirm password' }}</label>
                    <div class="relative">
                        <span class="field-icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M16.5 10.5V7.125A4.125 4.125 0 0 0 8.25 7.125V10.5"/>
                            </svg>
                        </span>
                        <input name="password_confirmation" type="password" autocomplete="new-password" required placeholder="{{ $isArabic ? 'أعد كتابة كلمة المرور' : 'Repeat your password' }}" class="input-field">
                    </div>
                </div>

                @if($errors->any())
                    <div class="mt-2 rounded-xl border border-red-400/30 bg-red-400/10 px-4 py-3 text-sm text-red-300">{{ $errors->first() }}</div>
                @endif

                <button type="submit" class="mt-6 w-full rounded-xl bg-cyan-400 px-4 py-3.5 font-semibold text-slate-950 transition hover:bg-cyan-300 active:scale-[.98]">{{ $isArabic ? 'إنشاء الحساب' : 'Create account' }}</button>
            </form>

            <div class="my-6 flex items-center gap-3">
                <div class="h-px flex-1 bg-white/6"></div>
                <span class="text-xs text-slate-600">{{ $isArabic ? 'أو' : 'or' }}</span>
                <div class="h-px flex-1 bg-white/6"></div>
            </div>

            <a href="{{ route('login') }}" class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/8 bg-white/4 py-3 text-sm font-medium text-slate-400 transition hover:border-white/15 hover:text-slate-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
                </svg>
                {{ $isArabic ? 'العودة لتسجيل الدخول' : 'Back to login' }}
            </a>
        </div>
    </div>
</section>
@endsection
