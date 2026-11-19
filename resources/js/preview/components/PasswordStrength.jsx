import { useLayoutEffect, useRef } from 'react';
import { gsap, prefersReducedMotion } from '../motion';

const COMMON = ['password', '123456', 'qwerty', '111111', 'abc123', 'letmein', 'admin'];

export function scorePassword(pw) {
    const checks = {
        length: pw.length >= 12,
        case: /[a-z]/.test(pw) && /[A-Z]/.test(pw),
        number: /\d/.test(pw),
        symbol: /[^A-Za-z0-9\s]/.test(pw),
    };
    if (!pw) return { score: 0, checks };
    let score = Object.values(checks).filter(Boolean).length;
    if (pw.length >= 16) score += 1;
    if (pw.length < 8) score = Math.min(score, 1);
    if (COMMON.some((w) => pw.toLowerCase().includes(w))) score = Math.min(score, 1);
    return { score: Math.min(score, 4), checks };
}

const COLORS = ['#f87171', '#fb923c', '#fbbf24', '#34d399', '#22d3ee'];

/** Four-segment meter + checklist that animate as the user types. */
export default function PasswordStrength({ password, t }) {
    const root = useRef(null);
    const { score, checks } = scorePassword(password);
    const color = COLORS[score];

    useLayoutEffect(() => {
        const segments = root.current.querySelectorAll('.pw-seg');
        gsap.to(segments, {
            scaleX: (i) => (password && i < Math.max(score, 1) ? 1 : 0),
            backgroundColor: color,
            duration: prefersReducedMotion() ? 0 : 0.45,
            stagger: 0.05,
            ease: 'power3.out',
        });
    }, [score, password, color]);

    return (
        <div ref={root} className="rounded-xl border border-white/10 bg-white/[0.03] p-4">
            <div className="mb-3 flex items-center justify-between text-xs">
                <span className="text-slate-400">{t.title}</span>
                <span className="font-semibold transition-colors" style={{ color: password ? color : '#64748b' }}>{password ? t.levels[score] : '—'}</span>
            </div>
            <div className="grid grid-cols-4 gap-1.5">
                {[0, 1, 2, 3].map((i) => (
                    <span key={i} className="h-1.5 overflow-hidden rounded-full bg-white/10">
                        <span className="pw-seg block h-full origin-left scale-x-0 rounded-full rtl:origin-right" />
                    </span>
                ))}
            </div>
            <ul className="mt-3 grid grid-cols-2 gap-x-3 gap-y-1.5 text-[11px]">
                {Object.entries(t.checks).map(([key, label]) => (
                    <li key={key} className={`flex items-center gap-1.5 transition-colors ${checks[key] ? 'text-safe' : 'text-slate-500'}`}>
                        <span className={`grid h-3.5 w-3.5 place-items-center rounded-full text-[9px] transition-all ${checks[key] ? 'scale-100 bg-safe/20' : 'scale-90 bg-white/5'}`}>{checks[key] ? '✓' : ''}</span>
                        {label}
                    </li>
                ))}
            </ul>
        </div>
    );
}
