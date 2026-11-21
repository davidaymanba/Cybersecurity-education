{{--
    Shared error page. Kept independent of the session, auth and database so it renders even
    when those are what failed. Expects $code, $icon, $tone and optional $actions.
--}}
@php
    $locale = app()->getLocale();
    $tones = ['signal' => '#22d3ee', 'amber' => '#fbbf24', 'danger' => '#f87171', 'pulse' => '#a78bfa'];
    $color = $tones[$tone] ?? $tones['signal'];
    $actions ??= ['home', 'back'];
    $hasBuild = file_exists(public_path('build/manifest.json'));
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ __('messages.error_'.$code.'_title') }} · {{ __('messages.site_name') }}</title>
    @if($hasBuild)
        @vite(['resources/css/app.css'])
    @else
        {{-- Minimal fallback so the page stays readable even without the built CSS. --}}
        <style>
            html, body { margin: 0; background: #030a14; color: #e2e8f0; font-family: 'IBM Plex Sans Arabic', system-ui, sans-serif; }
            main { min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; text-align: center; }
            a { color: #22d3ee; margin: 0 .5rem; } svg { width: 2rem; height: 2rem; }
        </style>
    @endif
    <style>
        .err-code { color: {{ $color }}; }
        @keyframes err-float { 50% { transform: translateY(-6px); } }
        @media (prefers-reduced-motion: reduce) { .err-anim { animation: none !important; } }
    </style>
</head>
<body class="min-h-screen overflow-x-clip">
    <div aria-hidden="true" class="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
        <div class="grid-bg absolute inset-0"></div>
        <div class="absolute top-1/3 start-1/2 h-[26rem] w-[40rem] max-w-[150vw] -translate-x-1/2 -translate-y-1/2 rounded-full blur-[130px] rtl:translate-x-1/2" style="background: {{ $color }}1f"></div>
    </div>

    <main class="relative mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-6 py-16 text-center">
        <a href="{{ url('/') }}" class="mb-12 inline-flex items-center gap-3" aria-label="{{ __('messages.site_name') }}">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-signal/10 font-mono text-sm font-bold text-signal ring-1 ring-signal/40">CL</span>
            <span class="font-semibold text-white" dir="ltr">CyberLearn<span class="text-signal">.AI</span></span>
        </a>

        <div class="err-anim relative grid h-24 w-24 place-items-center rounded-full ring-1" style="color: {{ $color }}; background: {{ $color }}14; --tw-ring-color: {{ $color }}55; animation: err-float 4s ease-in-out infinite">
            <span class="ring-spin absolute -inset-3 rounded-full border border-dashed" style="border-color: {{ $color }}40" aria-hidden="true"></span>
            <x-ui.icon :name="$icon" class="h-10 w-10" />
        </div>

        <p class="err-code mt-8 font-mono text-7xl font-bold tracking-tight sm:text-8xl" dir="ltr" data-scramble="{{ $code }}" aria-hidden="true">{{ $code }}</p>
        <h1 class="mt-4 text-2xl font-bold text-white sm:text-3xl">{{ __('messages.error_'.$code.'_title') }}</h1>
        <p class="mt-3 max-w-md leading-7 text-slate-400">{{ __('messages.error_'.$code.'_body') }}</p>

        <div class="surface-inset mt-8 w-full max-w-sm px-4 py-3 text-start font-mono text-xs text-slate-400" dir="ltr">
            <span class="text-safe">$</span> curl -I {{ request()->path() === '/' ? '/' : '/'.\Illuminate\Support\Str::limit(request()->path(), 32) }}<br>
            <span style="color: {{ $color }}">HTTP/1.1 {{ $code }}</span> <span class="caret"></span>
        </div>

        <div class="mt-8 flex flex-wrap justify-center gap-2">
            @foreach($actions as $action)
                @php($style = $loop->first ? 'btn btn-primary' : 'btn btn-ghost')
                @if($action === 'home')
                    <a href="{{ url('/') }}" class="{{ $style }}"><x-ui.icon name="home" class="h-4 w-4" />{{ __('messages.error_home') }}</a>
                @elseif($action === 'back')
                    <button type="button" class="{{ $style }}" onclick="history.length > 1 ? history.back() : (location.href = '/')"><x-ui.icon name="arrow" flip class="h-4 w-4 rotate-180" />{{ __('messages.error_back') }}</button>
                @elseif($action === 'reload')
                    <button type="button" class="{{ $style }}" onclick="location.reload()"><x-ui.icon name="refresh" class="h-4 w-4" />{{ __('messages.error_reload') }}</button>
                @elseif($action === 'login')
                    <a href="{{ url('/login') }}" class="{{ $style }}"><x-ui.icon name="lock" class="h-4 w-4" />{{ __('messages.login') }}</a>
                @endif
            @endforeach
        </div>
    </main>

    <script>
        // Digits decrypt into place (Latin numbers only; skipped with reduced motion).
        (function () {
            var el = document.querySelector('[data-scramble]');
            if (!el || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            var target = el.getAttribute('data-scramble'), frame = 0;
            var timer = setInterval(function () {
                frame++;
                el.textContent = target.split('').map(function (ch, i) {
                    return frame > 6 + i * 5 ? ch : String(Math.floor(Math.random() * 10));
                }).join('');
                if (frame > 6 + target.length * 5) { clearInterval(timer); el.textContent = target; }
            }, 45);
        })();
    </script>
</body>
</html>
