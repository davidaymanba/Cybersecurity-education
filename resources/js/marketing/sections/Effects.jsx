import { useEffect, useRef } from 'react';
import { gsap, prefersReducedMotion } from '../motion';

/** Thin progress bar along the top of the viewport. */
export function ScrollProgress() {
    const ref = useRef(null);
    useEffect(() => {
        const tween = gsap.to(ref.current, {
            scaleX: 1,
            ease: 'none',
            scrollTrigger: { trigger: document.documentElement, start: 'top top', end: 'bottom bottom', scrub: 0.3 },
        });
        return () => { tween.scrollTrigger?.kill(); tween.kill(); };
    }, []);
    return <div ref={ref} className="fixed inset-x-0 top-0 z-[60] h-[2px] origin-left scale-x-0 bg-gradient-to-r from-safe via-signal to-pulse rtl:origin-right" />;
}

/** Soft light that trails the pointer (desktop only). */
export function CursorGlow() {
    const ref = useRef(null);
    useEffect(() => {
        if (prefersReducedMotion() || window.matchMedia('(hover: none)').matches) return undefined;
        const el = ref.current;
        const xTo = gsap.quickTo(el, 'x', { duration: 0.8, ease: 'power3.out' });
        const yTo = gsap.quickTo(el, 'y', { duration: 0.8, ease: 'power3.out' });
        const onMove = (e) => { gsap.to(el, { opacity: 1, duration: 0.4 }); xTo(e.clientX); yTo(e.clientY); };
        window.addEventListener('pointermove', onMove);
        return () => window.removeEventListener('pointermove', onMove);
    }, []);
    return (
        <div ref={ref} aria-hidden="true" className="pointer-events-none fixed left-0 top-0 z-0 h-[36rem] w-[36rem] -translate-x-1/2 -translate-y-1/2 rounded-full opacity-0"
            style={{ background: 'radial-gradient(circle, rgba(34,211,238,0.09), transparent 60%)' }} />
    );
}
