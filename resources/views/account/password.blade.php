@extends('layouts.app', ['title' => __('messages.account')])

@php
    $user = auth()->user();
    $levels = ['Beginner' => __('messages.beginner'), 'Intermediate' => __('messages.intermediate'), 'Expert' => __('messages.expert')];
@endphp

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
    <x-ui.page-header :kicker="__('messages.account')" :title="__('messages.account_title')" :lead="__('messages.account_lead')" />

    <div class="mt-10 grid gap-6 lg:grid-cols-[1fr_1.4fr]">
        <section class="surface h-max p-6" data-reveal="start" aria-labelledby="profile-title">
            <div class="flex items-center gap-4">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-pulse/15 text-xl font-bold text-violet-200 ring-1 ring-pulse/30">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                <div class="min-w-0">
                    <h2 id="profile-title" class="truncate text-lg font-semibold text-white">{{ $user->name }}</h2>
                    <p class="truncate text-sm text-slate-400" dir="ltr" style="text-align: start">{{ $user->email }}</p>
                </div>
            </div>
            <dl class="mt-6 divide-y divide-white/[0.06] text-sm">
                <div class="flex justify-between gap-4 py-3"><dt class="text-slate-400">{{ __('messages.role') }}</dt><dd><x-ui.badge :tone="$user->isAdmin() ? 'pulse' : 'signal'">{{ $user->isAdmin() ? __('messages.role_admin') : __('messages.role_student') }}</x-ui.badge></dd></div>
                @if($user->learning_level)
                    <div class="flex justify-between gap-4 py-3"><dt class="text-slate-400">{{ __('messages.difficulty') }}</dt><dd class="text-white">{{ $levels[$user->learning_level] ?? $user->learning_level }}</dd></div>
                @endif
                <div class="flex justify-between gap-4 py-3"><dt class="text-slate-400">{{ __('messages.joined') }}</dt><dd class="font-mono text-slate-300">{{ $user->created_at?->format('Y-m-d') }}</dd></div>
            </dl>
        </section>

        <section class="surface hud p-6 sm:p-8" data-reveal aria-labelledby="password-title">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-signal/10 text-signal ring-1 ring-signal/30"><x-ui.icon name="key" /></span>
                <div>
                    <h2 id="password-title" class="font-semibold text-white">{{ __('messages.change_password') }}</h2>
                    <p class="text-sm text-slate-400">{{ __('messages.change_password_lead') }}</p>
                </div>
            </div>
            <form method="post" action="{{ route('account.password') }}" class="mt-6 space-y-4" data-busy-text="{{ __('messages.saving') }}">
                @csrf
                @method('PUT')
                <x-ui.input name="current_password" type="password" :label="__('messages.current_password')" autocomplete="current-password" required dir="ltr" />
                <x-ui.input name="password" type="password" :label="__('messages.new_password')" :hint="__('messages.min_8_chars')" autocomplete="new-password" required minlength="8" dir="ltr" />
                <x-ui.input name="password_confirmation" type="password" :label="__('messages.confirm_new_password')" autocomplete="new-password" required dir="ltr" />
                <div class="pt-2"><x-ui.button icon="check">{{ __('messages.save') }}</x-ui.button></div>
            </form>
        </section>
    </div>
</div>
@endsection
