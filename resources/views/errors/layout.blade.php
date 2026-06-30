<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Error' }} — {{ config('app.name', 'CyberLearn') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @endif
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            min-height: 100vh;
            background: #041225;
            color: #e2e8f0;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* ── Animations ─────────────────────────────────── */
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(32px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33%       { transform: translateY(-10px) rotate(1deg); }
            66%       { transform: translateY(-4px) rotate(-1deg); }
        }
        @keyframes scan {
            0%   { top: -4px; }
            100% { top: 100%; }
        }
        @keyframes pulse-ring {
            0%   { transform: scale(1);   opacity: .6; }
            100% { transform: scale(1.8); opacity: 0; }
        }
        @keyframes shimmer {
            0%   { background-position: -200% center; }
            100% { background-position:  200% center; }
        }
        @keyframes blink-cursor {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0; }
        }
        @keyframes rotate-ring {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        @keyframes flicker {
            0%,19%,21%,23%,25%,54%,56%,100% { opacity: 1; }
            20%,24%,55% { opacity: .4; }
        }

        /* ── Page enter animation ────────────────────────── */
        .animate-fade-up  { animation: fade-up .7s cubic-bezier(.22,1,.36,1) both; }
        .delay-100 { animation-delay: .1s; }
        .delay-200 { animation-delay: .2s; }
        .delay-300 { animation-delay: .3s; }
        .delay-400 { animation-delay: .4s; }
        .delay-500 { animation-delay: .5s; }

        /* ── Icon float ──────────────────────────────────── */
        .icon-float { animation: float 5s ease-in-out infinite; }

        /* ── Scan line ───────────────────────────────────── */
        .scan-line {
            position: absolute; left: 0; width: 100%; height: 2px;
            background: linear-gradient(to right, transparent, var(--accent-color, #22d3ee), transparent);
            opacity: .25;
            animation: scan 4s linear infinite;
        }

        /* ── Pulse ring around icon ──────────────────────── */
        .pulse-ring {
            position: absolute; inset: -12px; border-radius: 50%;
            border: 2px solid var(--accent-color, #22d3ee);
            animation: pulse-ring 2.2s ease-out infinite;
            pointer-events: none;
        }
        .pulse-ring-2 {
            animation-delay: 1.1s;
        }

        /* ── Error code bg watermark ─────────────────────── */
        .code-watermark {
            position: absolute;
            font-size: clamp(180px, 30vw, 360px);
            font-weight: 900;
            letter-spacing: -0.05em;
            color: transparent;
            -webkit-text-stroke: 1px var(--accent-color, #22d3ee);
            opacity: .04;
            user-select: none;
            pointer-events: none;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            line-height: 1;
            white-space: nowrap;
        }

        /* ── Gradient text ───────────────────────────────── */
        .gradient-code {
            background: linear-gradient(135deg, var(--accent-light, #67e8f9) 0%, var(--accent-color, #22d3ee) 40%, var(--accent-dark, #0891b2) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 0 40px var(--accent-color, #22d3ee));
        }
        .gradient-code-flicker {
            animation: flicker 8s step-start infinite;
        }

        /* ── Glass card ──────────────────────────────────── */
        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px) saturate(1.5);
            -webkit-backdrop-filter: blur(20px) saturate(1.5);
            border: 1px solid rgba(255,255,255,0.07);
            box-shadow: 0 32px 64px -16px rgba(0,0,0,.6),
                        inset 0 1px 0 rgba(255,255,255,0.06);
        }

        /* ── Terminal badge ──────────────────────────────── */
        .terminal-badge {
            font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', ui-monospace, monospace;
            background: rgba(0,0,0,.5);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
        }
        .cursor::after {
            content: '▌';
            color: var(--accent-color, #22d3ee);
            animation: blink-cursor .9s step-start infinite;
        }

        /* ── Rotating ring ───────────────────────────────── */
        .rotate-ring {
            position: absolute; inset: -20px;
            border: 1px dashed var(--accent-color, #22d3ee);
            border-radius: 50%;
            opacity: .15;
            animation: rotate-ring 20s linear infinite;
        }

        /* ── Buttons ─────────────────────────────────────── */
        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--accent-color, #22d3ee);
            color: #020917;
            font-weight: 600;
            padding: 12px 28px;
            border-radius: 9999px;
            border: none; cursor: pointer;
            transition: filter .2s, transform .15s;
            font-size: 15px;
            text-decoration: none;
        }
        .btn-primary:hover { filter: brightness(1.12); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0) scale(.97); }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,.04);
            color: #94a3b8;
            font-weight: 500;
            padding: 12px 28px;
            border-radius: 9999px;
            border: 1px solid rgba(255,255,255,.1);
            cursor: pointer;
            transition: background .2s, color .2s, transform .15s;
            font-size: 15px;
            text-decoration: none;
        }
        .btn-ghost:hover { background: rgba(255,255,255,.08); color: #e2e8f0; transform: translateY(-1px); }
    </style>
</head>
<body>

    {{-- CSS accent color variable --}}
    <style>
        :root {
            --accent-color: {{ $accentHex ?? '#22d3ee' }};
            --accent-light: {{ $accentLight ?? '#67e8f9' }};
            --accent-dark:  {{ $accentDark  ?? '#0891b2' }};
            --glow-bg:      {{ $glowBg      ?? 'rgba(34,211,238,0.06)' }};
        }
    </style>

    {{-- ░░░ Background layer ░░░ --}}
    <div style="position:fixed;inset:0;z-index:-10;overflow:hidden;">
        {{-- Base gradient --}}
        <div style="position:absolute;inset:0;background:linear-gradient(160deg,#041225 0%,#071426 60%,#060c1a 100%);"></div>

        {{-- Dot-grid --}}
        <svg style="position:absolute;inset:0;width:100%;height:100%;opacity:.025;" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                    <circle cx="1" cy="1" r="1" fill="var(--accent-color)"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots)"/>
        </svg>

        {{-- Top radial glow --}}
        <div style="position:absolute;top:-120px;left:50%;width:700px;height:500px;transform:translateX(-50%);background:radial-gradient(ellipse at center,var(--glow-bg) 0%,transparent 70%);pointer-events:none;"></div>

        {{-- Bottom-right glow --}}
        <div style="position:absolute;bottom:-100px;right:-80px;width:400px;height:400px;background:radial-gradient(circle,rgba(99,102,241,0.05) 0%,transparent 70%);pointer-events:none;"></div>

        {{-- Animated scan line --}}
        <div class="scan-line"></div>
    </div>

    {{-- ░░░ Header ░░░ --}}
    <header style="position:relative;z-index:10;border-bottom:1px solid rgba(255,255,255,.05);">
        <div style="max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding:14px 24px;">
            <a href="/" style="display:flex;align-items:center;gap:12px;text-decoration:none;color:#f1f5f9;font-weight:600;letter-spacing:-.01em;">
                <span style="width:36px;height:36px;border-radius:9px;background:var(--accent-color);color:#020917;display:grid;place-items:center;font-weight:800;font-size:13px;">CL</span>
                <span>{{ config('app.name', 'CyberLearn') }}</span>
            </a>
            <a href="/" class="btn-ghost" style="padding:8px 18px;font-size:13px;">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
                </svg>
                Go home
            </a>
        </div>
    </header>

    {{-- ░░░ Main content ░░░ --}}
    <main style="position:relative;z-index:10;flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 24px;">
        @yield('content')
    </main>

</body>
</html>
