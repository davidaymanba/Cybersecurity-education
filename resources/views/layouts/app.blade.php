@php
    $locale = app()->getLocale();
    $otherLocale = $locale === 'ar' ? 'en' : 'ar';
    $user = auth()->user();
    $nav = $user ? [
        ['dashboard', 'messages.dashboard', 'home', ['dashboard']],
        ['lessons.index', 'messages.lessons', 'book', ['lessons.*', 'quizzes.*', 'results.*']],
        ['bookmarks.index', 'messages.bookmarks', 'bookmark', ['bookmarks.*']],
        ['voice', 'messages.voice_assistant', 'mic', ['voice']],
    ] : [];
    $shell = $shell ?? 'app';
    $uiStrings = [
        'confirm' => __('messages.confirm'),
        'showPassword' => __('messages.show_password'),
        'hidePassword' => __('messages.hide_password'),
        'copied' => __('messages.copied'),
        'openMenu' => __('messages.open_menu'),
        'closeMenu' => __('messages.close_menu'),
    ];
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('partials.head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ __('messages.site_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen" data-shell="{{ $shell }}">
    <a href="#main" class="skip-link">{{ __('messages.skip_to_content') }}</a>

    {{-- Ambient backdrop (static: no canvas or WebGL on app pages). --}}
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="grid-bg absolute inset-0 opacity-60"></div>
        <div class="absolute -top-40 start-1/2 h-[28rem] w-[46rem] max-w-[140vw] -translate-x-1/2 rounded-full bg-signal/[0.07] blur-[120px] rtl:translate-x-1/2"></div>
    </div>

    <header data-app-header class="sticky top-0 z-40 border-b border-transparent transition-colors duration-300 data-[scrolled]:border-white/[0.08] data-[scrolled]:bg-void/80 data-[scrolled]:backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6">
            <a href="{{ $user ? route('dashboard') : route('landing') }}" class="flex shrink-0 items-center gap-3" aria-label="{{ __('messages.site_name') }}">
                <span class="relative grid h-9 w-9 place-items-center rounded-lg bg-signal/10 font-mono text-sm font-bold text-signal ring-1 ring-signal/40">
                    CL
                    <span class="pulse-dot absolute -end-0.5 -top-0.5 h-2 w-2 rounded-full bg-safe"></span>
                </span>
                <span class="hidden font-semibold tracking-tight text-white sm:inline" dir="ltr">CyberLearn<span class="text-signal">.AI</span></span>
            </a>

            @if($nav)
                <nav aria-label="{{ __('messages.main_navigation') }}" class="hidden items-center gap-1 lg:flex">
                    @foreach($nav as [$route, $label, $icon, $patterns])
                        <a href="{{ route($route) }}" class="nav-link" @if(request()->routeIs(...$patterns)) aria-current="page" @endif>
                            <x-ui.icon :name="$icon" class="h-4 w-4" />{{ __($label) }}
                        </a>
                    @endforeach
                    @if($user->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-link" @if(request()->routeIs('admin.*')) aria-current="page" @endif>
                            <x-ui.icon name="shield" class="h-4 w-4" />{{ __('messages.admin') }}
                        </a>
                    @endif
                </nav>
            @endif

            <div class="flex items-center gap-1.5">
                <a href="{{ route('lang.switch', $otherLocale) }}" hreflang="{{ $otherLocale }}" lang="{{ $otherLocale }}"
                   class="btn btn-quiet btn-icon h-9 w-9 font-mono text-sm ring-1 ring-white/10" aria-label="{{ __('messages.switch_language') }}">{{ __('messages.switch_language_short') }}</a>

                @auth
                    <a href="{{ route('account.edit') }}" class="hidden items-center gap-2 rounded-lg py-1 ps-1 pe-3 text-sm text-slate-300 ring-1 ring-white/10 transition hover:text-white hover:ring-white/25 sm:inline-flex"
                       @if(request()->routeIs('account.*')) aria-current="page" @endif>
                        <span class="grid h-7 w-7 place-items-center rounded-md bg-pulse/15 text-xs font-semibold text-violet-200">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <span class="max-w-[9rem] truncate">{{ $user->name }}</span>
                    </a>
                    <form action="{{ route('logout') }}" method="post" class="hidden lg:block">
                        @csrf
                        <button class="btn btn-quiet btn-icon h-9 w-9" aria-label="{{ __('messages.logout') }}" title="{{ __('messages.logout') }}"><x-ui.icon name="logout" flip class="h-4 w-4" /></button>
                    </form>
                    <button type="button" class="btn btn-quiet btn-icon h-9 w-9 ring-1 ring-white/10 lg:hidden" data-menu-toggle aria-controls="mobile-nav" aria-expanded="false" aria-label="{{ __('messages.open_menu') }}">
                        <x-ui.icon name="menu" class="h-5 w-5" data-icon-open />
                        <x-ui.icon name="x" class="h-5 w-5" data-icon-close hidden />
                    </button>
                @else
                    <a href="{{ route('login') }}" class="btn btn-quiet btn-sm hidden sm:inline-flex">{{ __('messages.login') }}</a>
                    <a href="{{ route('register') }}" class="btn btn-light btn-sm">{{ __('messages.register') }}</a>
                @endauth
            </div>
        </div>

        @auth
            <div id="mobile-nav" hidden class="border-t border-white/[0.08] bg-void/95 backdrop-blur-xl lg:hidden">
                <nav aria-label="{{ __('messages.main_navigation') }}" class="mx-auto grid max-w-7xl gap-1 px-4 py-3 sm:px-6">
                    @foreach($nav as [$route, $label, $icon, $patterns])
                        <a href="{{ route($route) }}" class="nav-link py-3 text-base" @if(request()->routeIs(...$patterns)) aria-current="page" @endif>
                            <x-ui.icon :name="$icon" class="h-5 w-5" />{{ __($label) }}
                        </a>
                    @endforeach
                    @if($user->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nav-link py-3 text-base" @if(request()->routeIs('admin.*')) aria-current="page" @endif><x-ui.icon name="shield" class="h-5 w-5" />{{ __('messages.admin') }}</a>
                    @endif
                    <a href="{{ route('account.edit') }}" class="nav-link py-3 text-base" @if(request()->routeIs('account.*')) aria-current="page" @endif><x-ui.icon name="user" class="h-5 w-5" />{{ __('messages.account') }}</a>
                    <form action="{{ route('logout') }}" method="post" class="mt-1 border-t border-white/[0.08] pt-2">
                        @csrf
                        <button class="nav-link w-full py-3 text-base"><x-ui.icon name="logout" flip class="h-5 w-5" />{{ __('messages.logout') }}</button>
                    </form>
                </nav>
            </div>
        @endauth
    </header>

    @if(session('status') || (($errorSummary ?? false) && $errors->any()))
        <div class="mx-auto mt-4 max-w-7xl space-y-3 px-4 sm:px-6">
            @if(session('status'))<x-ui.alert tone="safe" dismissible>{{ session('status') }}</x-ui.alert>@endif
            @if(($errorSummary ?? false) && $errors->any())
                <x-ui.alert tone="danger" :title="__('messages.errors_title')" dismissible>
                    <ul class="mt-1 list-disc space-y-0.5 ps-4">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
                </x-ui.alert>
            @endif
        </div>
    @endif

    <main id="main" tabindex="-1" class="outline-none">{{ $slot ?? '' }}@yield('content')</main>

    <footer class="mx-auto mt-16 max-w-7xl px-4 pb-10 sm:px-6">
        <div class="flex flex-col items-center justify-between gap-3 border-t border-white/[0.06] pt-6 text-xs text-slate-500 sm:flex-row">
            <span dir="ltr">CyberLearn<span class="text-signal">.AI</span></span>
            <span>{{ __('messages.ai_platform_tag') }}</span>
        </div>
    </footer>

    {{-- Shared confirm dialog for forms with data-confirm (see resources/js/ui/shell.js). --}}
    <dialog id="confirm-dialog" class="modal max-w-md" aria-labelledby="confirm-dialog-title">
        <div class="surface-strong hud p-6 shadow-2xl shadow-black/60">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 flex-none place-items-center rounded-xl bg-amber/10 text-amber ring-1 ring-amber/25"><x-ui.icon name="alert" /></span>
                <div>
                    <h2 id="confirm-dialog-title" class="font-semibold text-white">{{ __('messages.are_you_sure') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-300" data-confirm-message></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn btn-ghost" data-modal-close>{{ __('messages.cancel') }}</button>
                <button type="button" class="btn btn-danger" data-confirm-accept>{{ __('messages.confirm') }}</button>
            </div>
        </div>
    </dialog>

    <div id="toast-region" aria-live="polite" class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-center gap-2 sm:inset-x-auto sm:end-6 sm:items-end"></div>

    <script>
        window.__ui = { strings: @json($uiStrings) };
    </script>
    @stack('scripts')
</body>
</html>
