<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('messages.site_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased {{ app()->getLocale() === 'ar' ? 'rtl' : '' }}">
    <div class="fixed inset-0 -z-10 bg-gradient-to-b from-[#041225] to-[#071426]"></div>

    <header class="sticky top-0 z-40 border-b border-white/6 bg-transparent">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6">
            <a href="{{ route('landing') }}" class="flex items-center gap-3 font-semibold tracking-tight">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-cyan-400 text-slate-950">CL</span>
                <span>{{ __('messages.site_name') }}</span>
            </a>
            <nav class="hidden items-center gap-4 text-sm text-slate-300 md:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="hover:text-white">{{ __('messages.dashboard') }}</a>
                    <a href="{{ route('lessons.index') }}" class="hover:text-white">{{ __('messages.lessons') }}</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="hover:text-white">{{ __('messages.admin') }}</a>
                    @endif
                    <form action="{{ route('logout') }}" method="post">@csrf<button class="hover:text-white">{{ __('messages.logout') }}</button></form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-white">{{ __('messages.login') }}</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-cyan-400 px-4 py-2 font-semibold text-slate-950 shadow-lg shadow-cyan-500/20">{{ __('messages.register') }}</a>
                @endauth
                <div class="ml-4 flex items-center gap-2">
                    <a href="{{ route('lang.switch', 'en') }}" class="px-2 py-1 text-xs {{ app()->getLocale() === 'en' ? 'font-bold text-white' : 'text-slate-300' }}">EN</a>
                    <a href="{{ route('lang.switch', 'ar') }}" class="px-2 py-1 text-xs {{ app()->getLocale() === 'ar' ? 'font-bold text-white' : 'text-slate-300' }}">AR</a>
                </div>
            </nav>
        </div>
    </header>

    @if(session('status'))
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6">
            <div class="rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-100">{{ session('status') }}</div>
        </div>
    @endif

    <main>{{ $slot ?? '' }}@yield('content')</main>
</body>
</html>
