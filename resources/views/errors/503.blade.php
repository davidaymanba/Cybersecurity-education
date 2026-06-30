@extends('errors.layout', [
    'title'       => '503 — Maintenance',
    'accentHex'   => '#8b5cf6',
    'accentLight' => '#c4b5fd',
    'accentDark'  => '#6d28d9',
    'glowBg'      => 'rgba(139,92,246,0.07)',
])

@section('content')
<div class="code-watermark">503</div>

<div style="width:100%;max-width:580px;text-align:center;position:relative;">

    {{-- Icon with pulse rings --}}
    <div class="animate-fade-up" style="display:flex;justify-content:center;margin-bottom:32px;">
        <div class="icon-float" style="position:relative;display:inline-flex;">
            <div class="pulse-ring"></div>
            <div class="pulse-ring pulse-ring-2"></div>
            <div class="rotate-ring"></div>
            <div style="width:88px;height:88px;border-radius:50%;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.25);display:grid;place-items:center;position:relative;z-index:1;">
                <svg width="40" height="40" fill="none" stroke="#8b5cf6" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.65-5.65 1.896-1.897a2.548 2.548 0 1 1 3.586 3.586L19.18 12.18"/>
                </svg>
            </div>
        </div>
    </div>

    {{-- Error code --}}
    <div class="animate-fade-up delay-100">
        <p class="gradient-code" style="font-size:clamp(5rem,14vw,9rem);font-weight:900;letter-spacing:-.04em;line-height:1;">
            503
        </p>
    </div>

    {{-- Text --}}
    <div class="animate-fade-up delay-200" style="margin-top:20px;">
        <h1 style="font-size:1.6rem;font-weight:700;color:#f1f5f9;letter-spacing:-.02em;">Under maintenance</h1>
        <p style="margin-top:10px;color:#64748b;line-height:1.7;font-size:.95rem;">
            The service is temporarily offline for scheduled maintenance.<br>
            We'll be back up shortly — thank you for your patience.
        </p>
    </div>

    {{-- Progress / status bar --}}
    <div class="animate-fade-up delay-300" style="margin-top:28px;">
        <div style="display:flex;justify-content:center;">
            <div class="terminal-badge" style="padding:12px 20px;display:inline-flex;flex-direction:column;gap:10px;min-width:280px;">
                <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;color:#64748b;">
                    <span>Maintenance progress</span>
                    <span style="color:#8b5cf6;">In progress…</span>
                </div>
                <div style="height:4px;background:rgba(255,255,255,.06);border-radius:99px;overflow:hidden;">
                    <div style="width:60%;height:100%;background:linear-gradient(90deg,#8b5cf6,#c4b5fd);border-radius:99px;animation:shimmer 2.4s linear infinite;background-size:200% auto;"></div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;font-size:12px;color:#475569;">
                    <span style="color:#8b5cf6;">&#x25CF;</span>
                    <span>System update running</span>
                    <span class="cursor"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="animate-fade-up delay-400" style="margin-top:36px;display:flex;flex-wrap:wrap;gap:12px;justify-content:center;">
        <button onclick="location.reload()" class="btn-primary" style="background:#8b5cf6;">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
            </svg>
            Check again
        </button>
    </div>

    {{-- Divider --}}
    <div class="animate-fade-up delay-500" style="margin-top:48px;display:flex;align-items:center;gap:16px;">
        <div style="flex:1;height:1px;background:rgba(255,255,255,.04);"></div>
        <span style="font-family:monospace;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#1e293b;">HTTP 503</span>
        <div style="flex:1;height:1px;background:rgba(255,255,255,.04);"></div>
    </div>
</div>
@endsection
