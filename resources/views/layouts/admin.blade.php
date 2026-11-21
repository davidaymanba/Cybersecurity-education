@extends('layouts.app', ['shell' => 'admin', 'errorSummary' => true])

{{-- Admin shell: calm, data-dense. Pages fill @section('admin'). --}}
@php
    $adminNav = [
        ['admin.dashboard', 'messages.admin_overview', 'chart'],
        ['admin.users', 'messages.users', 'users'],
        ['admin.lessons', 'messages.lessons', 'book'],
        ['admin.quizzes', 'messages.quizzes', 'quiz'],
        ['admin.videos', 'messages.video_resources', 'video'],
    ];
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <div class="grid grid-cols-[minmax(0,1fr)] gap-6 lg:grid-cols-[220px_minmax(0,1fr)]">
        <aside class="lg:sticky lg:top-20 lg:h-max">
            <p class="mono-label mb-3 hidden font-mono text-xs uppercase tracking-[0.2em] text-slate-500 lg:block">{{ __('messages.admin') }}</p>
            <nav aria-label="{{ __('messages.admin') }}" class="-mx-4 flex gap-1 overflow-x-auto px-4 pb-1 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0" data-lenis-prevent>
                @foreach($adminNav as [$route, $label, $icon])
                    <a href="{{ route($route) }}" class="nav-link shrink-0 lg:w-full" @if(request()->routeIs($route)) aria-current="page" @endif>
                        <x-ui.icon :name="$icon" class="h-4 w-4" />{{ __($label) }}
                    </a>
                @endforeach
            </nav>
        </aside>
        <div class="min-w-0">@yield('admin')</div>
    </div>
</div>
@endsection
