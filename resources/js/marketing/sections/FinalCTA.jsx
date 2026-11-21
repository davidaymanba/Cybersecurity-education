import { useLayoutEffect, useRef } from 'react';
import { gsap, prefersReducedMotion } from '../motion';
import ClickSpark from '../components/ClickSpark';
import Magnet from '../components/Magnet';
import SplitWords from '../components/SplitWords';
import LetterGlitch from '../components/LetterGlitch';

export default function FinalCTA({ t, data }) {
    const root = useRef(null);

    useLayoutEffect(() => {
        if (prefersReducedMotion()) return undefined;
        const ctx = gsap.context(() => {
            const tl = gsap.timeline({ scrollTrigger: { trigger: root.current, start: 'top 75%' } });
            tl.from('.cta-panel', { clipPath: 'inset(18% 18% 18% 18% round 32px)', duration: 1.3, ease: 'expo.out' })
                .from('.cta-panel .split-word', { yPercent: 120, duration: 1, stagger: 0.07, ease: 'expo.out' }, '-=0.9')
                .from('.cta-fade', { opacity: 0, y: 20, duration: 0.8, stagger: 0.1 }, '-=0.7');
        }, root);
        return () => ctx.revert();
    }, []);

    const href = data.auth ? data.routes.lessons : data.routes.register;
    // Let the sparks play for a moment before navigating.
    const onClick = (event) => {
        if (prefersReducedMotion()) return;
        event.preventDefault();
        setTimeout(() => { window.location.href = href; }, 320);
    };

    return (
        <section ref={root} className="mx-auto max-w-7xl px-5 pb-28">
            <div className="cta-panel hud relative overflow-hidden rounded-[2rem] border border-signal/20 bg-panel px-6 py-20 text-center sm:px-16" style={{ clipPath: 'inset(0 0 0 0 round 32px)' }}>
                <div className="pointer-events-none absolute inset-0 opacity-[0.12]"><LetterGlitch className="absolute inset-0" speed={110} /></div>
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_10%,var(--color-panel)_75%)]" />
                <div className="pointer-events-none absolute -top-24 start-1/2 h-72 w-[40rem] -translate-x-1/2 rounded-full bg-signal/25 blur-[120px] rtl:translate-x-1/2" />

                <div className="relative">
                    <h2 className="text-4xl font-bold text-white sm:text-6xl"><SplitWords text={t.final.title} /></h2>
                    <p className="cta-fade mx-auto mt-6 max-w-xl text-lg leading-8 text-slate-400">{t.final.body}</p>
                    <div className="cta-fade mt-10">
                        <Magnet strength={0.4}>
                            <ClickSpark>
                                <a href={href} onClick={onClick} className="inline-flex items-center gap-3 rounded-2xl bg-white px-9 py-5 text-lg font-semibold text-void shadow-[0_0_60px_-10px_rgba(255,255,255,0.6)] transition hover:bg-signal">
                                    {t.final.cta}
                                </a>
                            </ClickSpark>
                        </Magnet>
                    </div>
                </div>
            </div>
        </section>
    );
}
