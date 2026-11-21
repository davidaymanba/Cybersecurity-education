import { useLayoutEffect, useRef } from 'react';
import { gsap, prefersReducedMotion, scrollToTarget } from '../motion';
import LetterGlitch from '../components/LetterGlitch';
import DecryptedText from '../components/DecryptedText';
import SplitWords from '../components/SplitWords';
import Magnet from '../components/Magnet';
import Counter from '../components/Counter';
import AgentConsole from './AgentConsole';

export default function Hero({ t, data }) {
    const root = useRef(null);

    useLayoutEffect(() => {
        if (prefersReducedMotion()) return undefined;
        const ctx = gsap.context(() => {
            const intro = gsap.timeline({ defaults: { ease: 'expo.out' } });
            intro
                .from('.hero-hud', { opacity: 0, y: 12, duration: 0.8 })
                .from('.hero-title .split-word', { yPercent: 118, duration: 1.15, stagger: 0.055 }, '-=0.45')
                .from('.hero-lead', { opacity: 0, y: 22, duration: 0.9 }, '-=0.75')
                .from('.hero-actions > *', { opacity: 0, y: 16, duration: 0.7, stagger: 0.08 }, '-=0.65')
                .from('.hero-stat', { opacity: 0, y: 16, duration: 0.6, stagger: 0.07 }, '-=0.55')
                .from('.hero-console', { opacity: 0, y: 60, rotateX: 12, transformPerspective: 1200, duration: 1.4 }, 0.35);

            // Depth while scrolling away from the hero.
            const scrub = { trigger: root.current, start: 'top top', end: 'bottom top', scrub: true };
            gsap.to('.hero-console', { yPercent: -10, ease: 'none', scrollTrigger: scrub });
            gsap.to('.hero-copy', { yPercent: 14, opacity: 0.2, ease: 'none', scrollTrigger: scrub });
            gsap.to('.hero-glitch', { opacity: 0.04, ease: 'none', scrollTrigger: scrub });
        }, root);
        return () => ctx.revert();
    }, []);

    const stats = [
        ['lessons', data.stats.lessons],
        ['quizzes', data.stats.quizzes],
        ['videos', data.stats.videos],
        ['agents', data.stats.agents],
    ];

    return (
        <section id="top" ref={root} className="relative flex min-h-[100svh] items-center overflow-hidden pb-16 pt-28">
            {/* background: glyph field + vignette + grid + scanline */}
            <div className="hero-glitch pointer-events-none absolute inset-0 opacity-[0.22]">
                <LetterGlitch className="absolute inset-0" />
            </div>
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_70%_60%_at_50%_45%,transparent_0%,var(--color-void)_78%)]" />
            <div className="grid-bg pointer-events-none absolute inset-0" />
            <div className="scanline" />
            <div className="pointer-events-none absolute -top-40 start-1/4 h-[32rem] w-[32rem] rounded-full bg-signal/20 blur-[140px]" />
            <div className="pointer-events-none absolute -bottom-40 end-0 h-[28rem] w-[28rem] rounded-full bg-pulse/15 blur-[140px]" />

            <div className="relative mx-auto grid w-full max-w-7xl items-center gap-14 px-5 lg:grid-cols-[1.05fr_.95fr]">
                <div className="hero-copy">
                    <div className="hero-hud mb-7 inline-flex items-center gap-3 rounded-full border border-signal/25 bg-signal/[0.07] px-4 py-2 font-mono text-[11px] tracking-widest text-signal">
                        <span className="pulse-dot h-1.5 w-1.5 rounded-full bg-safe" />
                        <span dir="ltr">SYS://</span>
                        <DecryptedText text={t.hud} speed={34} delay={500} />
                    </div>

                    <p className="mb-4 text-sm font-medium text-slate-400">{t.tag}</p>

                    <h1 className="hero-title text-[2.6rem] font-bold leading-[1.15] tracking-tight text-white sm:text-6xl lg:text-[4.2rem]">
                        <SplitWords text={t.titleA} className="block" />
                        {/* gradient on each word: background-clip:text does not paint through transformed children */}
                        <SplitWords text={t.titleB} className="mt-1 block" wordClassName="split-word text-signal-gradient" />
                    </h1>

                    <p className="hero-lead mt-7 max-w-xl text-lg leading-8 text-slate-400">{t.lead}</p>

                    <div className="hero-actions mt-9 flex flex-wrap items-center gap-4">
                        <Magnet>
                            <a href={data.auth ? data.routes.lessons : data.routes.register} className="group relative inline-flex items-center gap-3 overflow-hidden rounded-xl bg-signal px-7 py-4 font-semibold text-void shadow-[0_0_40px_-6px_rgba(34,211,238,0.7)] transition hover:shadow-[0_0_60px_-4px_rgba(34,211,238,0.9)]">
                                <span className="relative z-10">{t.ctaPrimary}</span>
                                <svg viewBox="0 0 24 24" className="relative z-10 h-4 w-4 transition-transform group-hover:translate-x-1 rtl:-scale-x-100 rtl:group-hover:-translate-x-1" fill="none" stroke="currentColor" strokeWidth="2.2"><path d="M5 12h14M13 6l6 6-6 6" strokeLinecap="round" strokeLinejoin="round" /></svg>
                                <span className="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/50 to-transparent transition-transform duration-700 group-hover:translate-x-full" />
                            </a>
                        </Magnet>
                        <a href="#team" onClick={(e) => { e.preventDefault(); scrollToTarget('#team'); }} className="rounded-xl px-5 py-4 font-medium text-slate-300 ring-1 ring-white/15 transition hover:bg-white/5 hover:text-white">
                            {t.ctaSecondary}
                        </a>
                    </div>

                    <dl className="mt-12 grid max-w-xl grid-cols-2 gap-x-6 gap-y-5 sm:grid-cols-4">
                        {stats.map(([key, value]) => (
                            <div key={key} className="hero-stat border-s border-white/10 ps-4">
                                <dt className="order-2 mt-1 text-xs text-slate-500">{t.stats[key]}</dt>
                                <dd className="font-mono text-3xl font-bold text-white"><Counter value={value} /></dd>
                            </div>
                        ))}
                    </dl>
                </div>

                <div id="console" className="hero-console flex justify-center lg:justify-end">
                    <AgentConsole t={t} />
                </div>
            </div>
        </section>
    );
}
