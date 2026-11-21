import { useLayoutEffect, useRef } from 'react';
import { gsap, prefersReducedMotion } from '../motion';

// Node positions inside the square stage (percent). Lines below use the same coordinates.
const NODES = {
    guide: { x: 50, y: 13, color: 'text-safe', bg: 'bg-safe', ring: 'ring-safe/50', glow: 'shadow-[0_0_40px_-4px_rgba(52,211,153,0.7)]' },
    tutor: { x: 15, y: 74, color: 'text-pulse', bg: 'bg-pulse', ring: 'ring-pulse/50', glow: 'shadow-[0_0_40px_-4px_rgba(167,139,250,0.7)]' },
    video: { x: 85, y: 74, color: 'text-amber', bg: 'bg-amber', ring: 'ring-amber/50', glow: 'shadow-[0_0_40px_-4px_rgba(251,191,36,0.6)]' },
};
const LINKS = [['guide', 'tutor'], ['tutor', 'video'], ['video', 'guide']];
const CENTER = { x: 50, y: 52 };

export default function TeamSection({ t }) {
    const root = useRef(null);
    const reduced = prefersReducedMotion();

    useLayoutEffect(() => {
        if (reduced) return undefined;
        const ctx = gsap.context(() => {
            gsap.set('.team-node', { left: `${CENTER.x}%`, top: `${CENTER.y}%`, scale: 0.3, opacity: 0 });
            gsap.set('.team-line', { strokeDashoffset: 1 });
            gsap.set('.team-step', { opacity: 0, y: 40 });
            gsap.set('.team-step-0', { opacity: 1, y: 0 });
            gsap.set('.team-meter-bar', { scaleX: 0 });

            const show = (i) => gsap.timeline()
                .to(`.team-step-${i - 1}`, { opacity: 0, y: -40, duration: 0.5 })
                .to(`.team-step-${i}`, { opacity: 1, y: 0, duration: 0.5 }, '<0.15');

            const tl = gsap.timeline({
                defaults: { ease: 'power2.inOut' },
                scrollTrigger: { trigger: root.current, start: 'top top', end: '+=2800', scrub: 1, pin: '.team-pin', anticipatePin: 1 },
            });

            tl.to('.team-core', { scale: 1.12, duration: 0.8 })
                .add(show(1), '+=0.2')
                .to('.team-core', { scale: 0.2, opacity: 0, duration: 1 }, '<')
                .to('.team-spokes', { opacity: 1, duration: 0.4 }, '<0.3');

            Object.entries(NODES).forEach(([key, pos]) => {
                tl.to(`.team-node-${key}`, { left: `${pos.x}%`, top: `${pos.y}%`, scale: 1, opacity: 1, duration: 1.2, ease: 'expo.out' }, '<0.08');
            });

            tl.to('.team-line', { strokeDashoffset: 0, duration: 1, stagger: 0.25 }, '-=0.4')
                .to('.team-spokes', { opacity: 0, duration: 0.5 }, '<')
                .add(show(2), '+=0.2')
                .from('.team-node-caption', { opacity: 0, y: 8, stagger: 0.2, duration: 0.6 }, '<')
                .add(show(3), '+=0.6')
                .to('.team-packet', { opacity: 1, duration: 0.4 }, '<')
                .to('.team-meters', { opacity: 1, y: 0, duration: 0.5 }, '<')
                .to('.team-meter-bar', { scaleX: 1, stagger: 0.18, duration: 1 }, '<0.2')
                .to({}, { duration: 0.6 });
        }, root);
        return () => ctx.revert();
    }, [reduced]);

    const stepClass = reduced ? 'relative mb-10' : 'absolute inset-x-0 top-0';

    return (
        <section id="team" ref={root} className="relative">
            <div className="team-pin relative flex min-h-[100svh] items-center overflow-hidden py-24">
                <div className="grid-bg pointer-events-none absolute inset-0" />
                <div className="pointer-events-none absolute start-1/2 top-1/2 h-[40rem] w-[40rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-signal/10 blur-[160px]" />

                <div className="relative mx-auto grid w-full max-w-7xl items-center gap-12 px-5 lg:grid-cols-[.9fr_1.1fr]">
                    <div className={`relative ${reduced ? '' : 'min-h-[17rem] sm:min-h-[15rem]'}`}>
                        {t.team.steps.map((step, i) => (
                            <div key={i} className={`team-step team-step-${i} ${stepClass}`}>
                                <p className="mono-label font-mono text-xs tracking-widest text-signal">{step.kicker}</p>
                                <h2 className="mt-4 text-2xl font-bold leading-tight text-white sm:text-5xl">{step.title}</h2>
                                <p className="mt-5 max-w-md text-lg leading-8 text-slate-400">{step.body}</p>
                            </div>
                        ))}
                    </div>

                    <div className="relative mx-auto mb-24 aspect-square w-[min(70vw,28rem)] lg:mb-16">
                        <svg viewBox="0 0 100 100" className="absolute inset-0 h-full w-full overflow-visible" aria-hidden="true">
                            <g className="team-spokes" style={{ opacity: reduced ? 0 : 0 }}>
                                {Object.values(NODES).map((n, i) => (
                                    <line key={i} x1={CENTER.x} y1={CENTER.y} x2={n.x} y2={n.y} stroke="rgba(34,211,238,0.25)" strokeWidth="0.3" strokeDasharray="1 1.5" />
                                ))}
                            </g>
                            {LINKS.map(([a, b]) => (
                                <line
                                    key={`${a}-${b}`}
                                    className="team-line"
                                    pathLength="1"
                                    strokeDasharray="1"
                                    strokeDashoffset={reduced ? 0 : 1}
                                    x1={NODES[a].x} y1={NODES[a].y} x2={NODES[b].x} y2={NODES[b].y}
                                    stroke="url(#team-gradient)"
                                    strokeWidth="0.45"
                                />
                            ))}
                            <defs>
                                {/* userSpaceOnUse: a horizontal line has a zero-height box, so objectBoundingBox paints nothing */}
                                <linearGradient id="team-gradient" gradientUnits="userSpaceOnUse" x1="0" y1="0" x2="100" y2="100">
                                    <stop offset="0%" stopColor="#34d399" />
                                    <stop offset="50%" stopColor="#22d3ee" />
                                    <stop offset="100%" stopColor="#a78bfa" />
                                </linearGradient>
                            </defs>
                            {!reduced && LINKS.map(([a, b], i) => (
                                <circle key={`p-${a}`} className="team-packet" r="0.9" fill="#e0f2fe" style={{ opacity: 0 }}>
                                    <animateMotion dur={`${2.2 + i * 0.4}s`} repeatCount="indefinite" path={`M${NODES[a].x},${NODES[a].y} L${NODES[b].x},${NODES[b].y}`} />
                                </circle>
                            ))}
                        </svg>

                        {!reduced && (
                            <div className="team-core absolute start-1/2 top-[52%] grid h-40 w-40 -translate-x-1/2 -translate-y-1/2 place-items-center rtl:translate-x-1/2">
                                <span className="ring-spin absolute inset-0 rounded-full border border-dashed border-signal/50" />
                                <span className="ring-spin-rev absolute inset-4 rounded-full border border-signal/25" />
                                <span className="absolute inset-8 rounded-full bg-signal/15 shadow-[0_0_80px_-10px_rgba(34,211,238,0.8)]" />
                                <span className="relative text-center font-mono text-sm font-bold text-signal">{t.team.core}</span>
                            </div>
                        )}

                        {Object.entries(NODES).map(([key, pos]) => (
                            <div
                                key={key}
                                className={`team-node team-node-${key} absolute -translate-x-1/2 -translate-y-1/2`}
                                style={reduced ? { left: `${pos.x}%`, top: `${pos.y}%` } : undefined}
                            >
                                <div className={`mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-panel ring-1 sm:h-20 sm:w-20 ${pos.ring} ${pos.glow}`}>
                                    <span className={`h-3 w-3 rounded-full ${pos.bg}`} />
                                </div>
                                <div className="team-node-caption mx-auto mt-3 w-fit rounded-lg bg-void/85 px-3 py-1 text-center backdrop-blur">
                                    <p className={`text-sm font-bold ${pos.color}`}>{t.team.nodes[key][0]}</p>
                                    <p className="text-xs text-slate-500">{t.team.nodes[key][1]}</p>
                                </div>
                            </div>
                        ))}

                        <div className="team-meters absolute inset-x-[8%] -bottom-24 rounded-xl border border-white/10 bg-panel/80 p-3 backdrop-blur sm:-bottom-16" style={reduced ? undefined : { opacity: 0, transform: 'translateY(16px)' }}>
                            {t.team.meters.map((label) => (
                                <div key={label} className="flex items-center gap-3 py-1">
                                    <span className="w-32 shrink-0 text-[11px] text-slate-400">{label}</span>
                                    <span className="h-1 flex-1 overflow-hidden rounded-full bg-white/10">
                                        <span className="team-meter-bar block h-full origin-left rounded-full bg-gradient-to-r from-safe via-signal to-pulse rtl:origin-right" />
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
