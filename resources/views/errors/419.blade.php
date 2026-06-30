@extends('errors.layout', [
    'title'       => '419 — Session Expired',
    'accentHex'   => '#f59e0b',
    'accentLight' => '#fcd34d',
    'accentDark'  => '#b45309',
    'glowBg'      => 'rgba(245,158,11,0.07)',
])

@section('content')
<div class="code-watermark">419</div>

<div style="width:100%;max-width:560px;text-align:center;position:relative;">

    {{-- Icon with pulse rings --}}
    <div class="animate-fade-up" style="display:flex;justify-content:center;margin-bottom:32px;">
        <div class="icon-float" style="position:relative;display:inline-flex;">
            <div class="pulse-ring"></div>
            <div class="pulse-ring pulse-ring-2"></div>
            <div class="rotate-ring"></div>
            <div style="width:88px;height:88px;border-radius:50%;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);display:grid;place-items:center;position:relative;z-index:1;">
                <svg width="40" height="40" fill="none" stroke="#f59e0b" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Error code --}}
    <div class="animate-fade-up delay-100">
        <p class="gradient-code gradient-code-flicker" style="font-size:clamp(5rem,14vw,9rem);font-weight:900;letter-spacing:-.04em;line-height:1;">
            419
        </p>
    </div>

    {{-- Text --}}
    <div class="animate-fade-up delay-200" style="margin-top:20px;">
        <h1 style="font-size:1.6rem;font-weight:700;color:#f1f5f9;letter-spacing:-.02em;">Session expired</h1>
        <p style="margin-top:10px;color:#64748b;line-height:1.7;font-size:.95rem;">
            Your session token has expired for security reasons.<br>
            Please refresh the page and try your request again.
        </p>
    </div>

    {{-- Terminal badge --}}
    <div class="animate-fade-up delay-300" style="margin-top:24px;display:flex;justify-content:center;">
        <div class="terminal-badge" style="padding:10px 20px;display:inline-flex;align-items:center;gap:10px;color:#475569;font-size:13px;">
            <span style="color:#f59e0b;">&#9888;</span>
            <span style="color:#94a3b8;">CSRF token mismatch</span>
            <span style="color:#f59e0b;font-weight:600;">419</span>
            <span class="cursor"></span>
        </div>
    </div>

    {{-- Actions --}}
    <div class="animate-fade-up delay-400" style="margin-top:36px;display:flex;flex-wrap:wrap;gap:12px;justify-content:center;">
        <button onclick="location.reload()" class="btn-primary" style="background:#f59e0b;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            Refresh page
        </button>
        <a href="/" class="btn-ghost">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            Go home
        </a>
    </div>

    {{-- Divider --}}
    <div class="animate-fade-up delay-500" style="margin-top:48px;display:flex;align-items:center;gap:16px;">
        <div style="flex:1;height:1px;background:rgba(255,255,255,.04);"></div>
        <span style="font-family:monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#1e293b;">HTTP 419</span>
        <div style="flex:1;height:1px;background:rgba(255,255,255,.04);"></div>
    </div>
</div>
@endsection
