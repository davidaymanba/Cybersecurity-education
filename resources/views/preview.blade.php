<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#030a14">
    <title>CyberLearn AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @viteReactRefresh
    @vite(['resources/css/preview.css', 'resources/js/preview/main.jsx'])
</head>
<body>
    <div id="preview-root"></div>
    <noscript><p style="padding:2rem;color:#e2e8f0">CyberLearn AI — <a href="{{ route('landing') }}" style="color:#22d3ee">{{ __('messages.site_name') }}</a></p></noscript>
    <script>window.__CYBERLEARN__ = @json($payload);</script>
</body>
</html>
