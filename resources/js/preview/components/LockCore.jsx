import { useEffect, useRef } from 'react';
import { gsap, prefersReducedMotion } from '../motion';

/**
 * Concentric scanner rings around a padlock. When `unlocked` flips to true the shackle lifts,
 * the rings speed up and everything shifts from cyan to green.
 */
export default function LockCore({ unlocked, label }) {
    const root = useRef(null);
    const spin = useRef([]);

    useEffect(() => {
        if (prefersReducedMotion()) return undefined;
        const ctx = gsap.context(() => {
            spin.current = [
                gsap.to('.lock-ring-a', { rotate: 360, duration: 24, ease: 'none', repeat: -1, transformOrigin: '50% 50%' }),
                gsap.to('.lock-ring-b', { rotate: -360, duration: 36, ease: 'none', repeat: -1, transformOrigin: '50% 50%' }),
                gsap.to('.lock-ring-c', { rotate: 360, duration: 60, ease: 'none', repeat: -1, transformOrigin: '50% 50%' }),
            ];
            gsap.to('.lock-sweep', { rotate: 360, duration: 4, ease: 'none', repeat: -1, transformOrigin: '50% 50%' });
        }, root);
        return () => ctx.revert();
    }, []);

    useEffect(() => {
        const reduced = prefersReducedMotion();
        gsap.to(root.current.querySelector('.lock-shackle'), { y: unlocked ? -9 : 0, rotate: unlocked ? -18 : 0, transformOrigin: '78% 100%', duration: reduced ? 0 : 0.7, ease: unlocked ? 'back.out(2.2)' : 'power3.out' });
        spin.current.forEach((tween) => gsap.to(tween, { timeScale: unlocked ? 3 : 1, duration: 1 }));
        if (unlocked && !reduced) {
            gsap.fromTo(root.current.querySelector('.lock-burst'), { scale: 0.6, opacity: 0.9 }, { scale: 1.6, opacity: 0, duration: 1.1, ease: 'expo.out', transformOrigin: '50% 50%' });
        }
    }, [unlocked]);

    const color = unlocked ? '#34d399' : '#22d3ee';

    return (
        <div ref={root} className="relative mx-auto aspect-square w-[min(70vw,22rem)]">
            <svg viewBox="0 0 200 200" className="absolute inset-0 h-full w-full overflow-visible" aria-hidden="true">
                <defs>
                    <radialGradient id="lock-glow">
                        <stop offset="0%" stopColor={color} stopOpacity="0.35" />
                        <stop offset="100%" stopColor={color} stopOpacity="0" />
                    </radialGradient>
                    <linearGradient id="lock-sweep-grad" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0%" stopColor={color} stopOpacity="0" />
                        <stop offset="100%" stopColor={color} stopOpacity="0.55" />
                    </linearGradient>
                </defs>
                <circle cx="100" cy="100" r="90" fill="url(#lock-glow)" />
                <circle className="lock-burst" cx="100" cy="100" r="60" fill="none" stroke={color} strokeWidth="1.5" opacity="0" />
                <g className="lock-ring-c"><circle cx="100" cy="100" r="94" fill="none" stroke={color} strokeOpacity="0.18" strokeWidth="0.6" strokeDasharray="2 6" /></g>
                <g className="lock-ring-b"><circle cx="100" cy="100" r="80" fill="none" stroke={color} strokeOpacity="0.35" strokeWidth="1" strokeDasharray="40 14 6 14" /></g>
                <g className="lock-ring-a"><circle cx="100" cy="100" r="66" fill="none" stroke={color} strokeOpacity="0.6" strokeWidth="1.4" strokeDasharray="90 30" strokeLinecap="round" /></g>
                <g className="lock-sweep"><path d="M100 100 L100 34 A66 66 0 0 1 157 67 Z" fill="url(#lock-sweep-grad)" opacity="0.5" /></g>
                {[0, 90, 180, 270].map((a) => (
                    <line key={a} x1="100" y1="2" x2="100" y2="12" stroke={color} strokeOpacity="0.7" strokeWidth="1.5" transform={`rotate(${a} 100 100)`} />
                ))}
                {/* padlock */}
                <g style={{ transition: 'color .6s' }}>
                    <path className="lock-shackle" d="M84 96 V84 a16 16 0 0 1 32 0 V96" fill="none" stroke={color} strokeWidth="5" strokeLinecap="round" />
                    <rect x="74" y="94" width="52" height="40" rx="9" fill="#07111f" stroke={color} strokeWidth="2.5" />
                    <circle cx="100" cy="110" r="5" fill={color} />
                    <rect x="98" y="112" width="4" height="10" rx="2" fill={color} />
                </g>
            </svg>
            <div className="absolute inset-x-0 -bottom-9 text-center">
                <span className={`mono-label inline-flex items-center gap-2 rounded-full px-3 py-1 font-mono text-[11px] uppercase tracking-widest transition-colors duration-500 ${unlocked ? 'bg-safe/10 text-safe' : 'bg-signal/10 text-signal'}`}>
                    <span className={`h-1.5 w-1.5 rounded-full ${unlocked ? 'bg-safe' : 'bg-signal'}`} />{label}
                </span>
            </div>
        </div>
    );
}
