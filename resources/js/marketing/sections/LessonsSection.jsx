import { useLayoutEffect, useRef } from 'react';
import { gsap, ScrollTrigger, prefersReducedMotion } from '../motion';
import SpotlightCard from '../components/SpotlightCard';
import SplitWords from '../components/SplitWords';

const DIFFICULTY_COLOR = ['text-safe bg-safe/10', 'text-signal bg-signal/10', 'text-pulse bg-pulse/10'];

export default function LessonsSection({ t, data }) {
    const root = useRef(null);
    const lessons = data.lessons || [];

    useLayoutEffect(() => {
        if (prefersReducedMotion()) return undefined;
        const ctx = gsap.context(() => {
            gsap.from('.lessons-head .split-word', {
                yPercent: 115,
                duration: 1,
                stagger: 0.05,
                ease: 'expo.out',
                scrollTrigger: { trigger: '.lessons-head', start: 'top 82%' },
            });
            gsap.set('.lesson-card', { opacity: 0, y: 60, rotateX: -8, transformPerspective: 900 });
            ScrollTrigger.batch('.lesson-card', {
                start: 'top 88%',
                once: true,
                onEnter: (batch) => gsap.to(batch, { opacity: 1, y: 0, rotateX: 0, duration: 1, stagger: 0.1, ease: 'expo.out' }),
            });
        }, root);
        return () => ctx.revert();
    }, [lessons.length]);

    if (!lessons.length) return null;

    return (
        <section id="lessons" ref={root} className="relative mx-auto max-w-7xl px-5 py-28">
            <div className="lessons-head mb-14 flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p className="mono-label font-mono text-xs tracking-widest text-signal">{t.lessons.kicker}</p>
                    <h2 className="mt-4 text-4xl font-bold text-white sm:text-5xl"><SplitWords text={t.lessons.title} /></h2>
                </div>
                <a href={data.routes.lessons} className="rounded-xl px-5 py-3 text-sm font-medium text-slate-300 ring-1 ring-white/15 transition hover:bg-white/5 hover:text-white">
                    {t.lessons.all}
                </a>
            </div>

            <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                {lessons.map((lesson, i) => (
                    <SpotlightCard key={i} className="lesson-card group flex flex-col rounded-2xl border border-white/10 bg-panel/60 p-6 transition-colors hover:border-signal/40">
                        <div className="flex items-center justify-between">
                            <span className="font-mono text-4xl font-bold text-white/10 transition-colors group-hover:text-signal/30">{String(i + 1).padStart(2, '0')}</span>
                            <span className={`rounded-full px-3 py-1 text-xs font-medium ${DIFFICULTY_COLOR[i % 3]}`}>{lesson.difficulty}</span>
                        </div>
                        {lesson.section && <p className="mt-6 text-xs text-slate-500">{lesson.section}</p>}
                        <h3 dir="auto" className="mt-2 text-start text-xl font-semibold leading-snug text-white">{lesson.title}</h3>
                        <p dir="auto" className="mb-6 mt-3 line-clamp-3 text-start text-sm leading-6 text-slate-400">{lesson.summary}</p>
                        <div className="mt-auto flex items-center justify-between border-t border-white/[0.06] pt-4 text-sm">
                            <span className="mono-label font-mono text-xs text-slate-500">{lesson.minutes} {t.lessons.minutes}</span>
                            <a href={data.routes.lessons} className="inline-flex items-center gap-2 font-medium text-signal">
                                {t.lessons.cta}
                                <svg viewBox="0 0 24 24" className="h-4 w-4 transition-transform group-hover:translate-x-1 rtl:-scale-x-100 rtl:group-hover:-translate-x-1" fill="none" stroke="currentColor" strokeWidth="2"><path d="M5 12h14M13 6l6 6-6 6" strokeLinecap="round" strokeLinejoin="round" /></svg>
                            </a>
                        </div>
                    </SpotlightCard>
                ))}
            </div>
        </section>
    );
}
