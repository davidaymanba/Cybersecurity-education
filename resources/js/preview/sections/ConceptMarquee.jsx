import { useLayoutEffect, useRef } from 'react';
import { gsap, onScrollVelocity, prefersReducedMotion } from '../motion';

/** Two infinite rows of security concepts; scroll speed (from Lenis) accelerates and skews them. */
export default function ConceptMarquee({ items }) {
    const root = useRef(null);

    useLayoutEffect(() => {
        if (prefersReducedMotion()) return undefined;
        const ctx = gsap.context(() => {
            const rows = gsap.utils.toArray('.marquee-track');
            // Each track holds the list twice, so sliding exactly half its width loops seamlessly.
            const tweens = rows.map((row, i) => gsap.fromTo(
                row,
                { xPercent: i % 2 === 0 ? 0 : -50 },
                { xPercent: i % 2 === 0 ? -50 : 0, duration: 38, ease: 'none', repeat: -1 },
            ));

            const skewTo = gsap.quickTo('.marquee-track', 'skewX', { duration: 0.5, ease: 'power3.out' });
            const off = onScrollVelocity((velocity) => {
                const boost = 1 + Math.min(Math.abs(velocity) * 0.12, 5);
                tweens.forEach((tw) => gsap.to(tw, { timeScale: boost, duration: 0.3, overwrite: true }));
                skewTo(gsap.utils.clamp(-8, 8, velocity * -0.35));
            });
            const settle = gsap.ticker.add(() => {
                tweens.forEach((tw) => { if (tw.timeScale() > 1.01) tw.timeScale(tw.timeScale() * 0.97); });
            });
            return () => { off(); gsap.ticker.remove(settle); };
        }, root);
        return () => ctx.revert();
    }, [items]);

    const row = (cls) => (
        <div className="flex overflow-hidden" dir="ltr">
            <div className={`marquee-track flex shrink-0 gap-4 pe-4 ${cls}`}>
                {[...items, ...items].map((item, i) => (
                    <span key={i} className="flex shrink-0 items-center gap-3 rounded-full border border-white/10 bg-white/[0.03] px-6 py-3 text-lg text-slate-300">
                        <span className="h-1.5 w-1.5 rounded-full bg-signal" />{item}
                    </span>
                ))}
            </div>
        </div>
    );

    return (
        <section ref={root} aria-hidden="true" className="relative space-y-4 overflow-hidden border-y border-white/[0.06] py-10">
            <div className="pointer-events-none absolute inset-y-0 start-0 z-10 w-32 bg-gradient-to-r from-void to-transparent rtl:bg-gradient-to-l" />
            <div className="pointer-events-none absolute inset-y-0 end-0 z-10 w-32 bg-gradient-to-l from-void to-transparent rtl:bg-gradient-to-r" />
            {row('')}
            {row('text-slate-500')}
        </section>
    );
}
