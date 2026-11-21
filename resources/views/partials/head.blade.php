{{-- Shared <head> bits for every page: fonts, icons, motion flag. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#030a14">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="alternate icon" href="/favicon.ico">
<script>
    // Reveal targets start hidden only when motion will actually run; if the module never
    // loads, show everything after a short grace period.
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.documentElement.classList.add('motion');
        window.__motionFallback = setTimeout(function () { document.documentElement.classList.add('motion-off'); }, 2500);
    }
</script>
