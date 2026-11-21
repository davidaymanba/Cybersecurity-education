<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('partials.head')
    <title>{{ $title === __('messages.site_name') ? $title : $title.' · '.__('messages.site_name') }}</title>
    <meta name="description" content="{{ __('messages.ai_platform_tag') }}">
    @viteReactRefresh
    @vite(['resources/css/marketing.css', 'resources/js/marketing/main.jsx'])
</head>
<body>
    <div id="marketing-root"></div>
    <noscript>
        <div style="max-width:32rem;margin:4rem auto;padding:0 1rem;color:#e2e8f0;font-family:system-ui,sans-serif;line-height:1.7">
            <p style="font-size:1.25rem;font-weight:600">{{ __('messages.site_name') }}</p>
            <p>{{ __('messages.noscript_notice') }}</p>
        </div>
    </noscript>
    <script>window.__CYBERLEARN__ = @json($payload);</script>
</body>
</html>
