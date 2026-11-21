import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { gsap, prefersReducedMotion } from '../motion';
import RichText from '../components/RichText';

const AGENT_STYLE = {
    mentor: { dot: 'bg-signal', text: 'text-signal', ring: 'ring-signal/40' },
    guide: { dot: 'bg-safe', text: 'text-safe', ring: 'ring-safe/40' },
    tutor: { dot: 'bg-pulse', text: 'text-pulse', ring: 'ring-pulse/40' },
    video: { dot: 'bg-amber', text: 'text-amber', ring: 'ring-amber/40' },
};
const TEAM = ['guide', 'tutor', 'video'];

function Bubble({ message, labels }) {
    const ref = useRef(null);
    useLayoutEffect(() => {
        if (prefersReducedMotion()) return;
        gsap.from(ref.current, { opacity: 0, y: 14, scale: 0.97, duration: 0.45, ease: 'power3.out' });
    }, []);

    if (message.role === 'user') {
        return (
            <div ref={ref} className="ms-auto max-w-[85%] rounded-2xl rounded-ee-md bg-signal px-4 py-2.5 text-sm font-medium text-void">
                {message.text}
            </div>
        );
    }
    const style = AGENT_STYLE[message.agent];
    return (
        <div ref={ref} className="max-w-[92%]">
            <div className={`mono-label mb-1.5 flex items-center gap-2 font-mono text-[11px] uppercase tracking-wider ${style.text}`}>
                <span className={`h-1.5 w-1.5 rounded-full ${style.dot}`} />
                {labels[message.agent]}
            </div>
            <div className={`rounded-2xl rounded-ss-md bg-white/[0.06] px-4 py-3 text-sm leading-7 text-slate-200 ring-1 ${style.ring} ${message.streaming ? 'caret' : ''}`}>
                <RichText text={message.text} />
            </div>
        </div>
    );
}

export default function AgentConsole({ t }) {
    const script = t.script;
    const reduced = prefersReducedMotion();
    const rootRef = useRef(null);
    const tabsRef = useRef(null);
    const indicatorRef = useRef(null);
    const [inView, setInView] = useState(false);
    const [run, setRun] = useState({ id: 0, from: 0 });
    const [mode, setMode] = useState(script[0].mode);
    const [agent, setAgent] = useState(script[0].agent);
    const [draft, setDraft] = useState('');
    const [thinking, setThinking] = useState(false);
    const [messages, setMessages] = useState(() => (reduced
        ? [{ role: 'user', text: script[0].user }, { role: 'ai', agent: script[0].agent, text: script[0].reply }]
        : []));

    // Only run the scripted chat while the console is on screen.
    useEffect(() => {
        const io = new IntersectionObserver(([entry]) => setInView(entry.isIntersecting), { threshold: 0.25 });
        io.observe(rootRef.current);
        return () => io.disconnect();
    }, []);

    useEffect(() => {
        if (!inView || reduced) return undefined;
        let cancelled = false;
        const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
        const alive = () => { if (cancelled) throw new Error('cancelled'); };

        (async () => {
            try {
                for (let i = run.from; ; i++) {
                    const step = script[i % script.length];
                    setMode(step.mode);
                    setAgent(step.agent);
                    await sleep(650); alive();

                    for (let k = 1; k <= step.user.length; k++) {
                        setDraft(step.user.slice(0, k));
                        await sleep(28 + Math.random() * 45); alive();
                    }
                    await sleep(320); alive();
                    setDraft('');
                    setMessages((list) => [...list.slice(-2), { role: 'user', text: step.user }]);
                    await sleep(380); alive();

                    setThinking(true);
                    await sleep(950); alive();
                    setThinking(false);

                    setMessages((list) => [...list.slice(-2), { role: 'ai', agent: step.agent, text: '', streaming: true }]);
                    let acc = '';
                    for (const piece of step.reply.split(/(\s+)/)) {
                        acc += piece;
                        const text = acc;
                        setMessages((list) => list.map((m, idx) => (idx === list.length - 1 ? { ...m, text } : m)));
                        if (piece.trim()) { await sleep(48 + Math.random() * 40); alive(); }
                    }
                    setMessages((list) => list.map((m, idx) => (idx === list.length - 1 ? { ...m, streaming: false } : m)));
                    await sleep(2800); alive();
                }
            } catch {
                // cancelled: the console left the screen or the user switched mode
            }
        })();

        return () => { cancelled = true; };
    }, [inView, run, reduced, script]);

    // Slide the active-agent indicator under the current team tab.
    useLayoutEffect(() => {
        const tabs = tabsRef.current;
        const indicator = indicatorRef.current;
        if (!tabs || !indicator) return;
        const active = tabs.querySelector(`[data-agent="${agent}"]`);
        if (!active) {
            gsap.to(indicator, { opacity: 0, duration: 0.2 });
            return;
        }
        const box = tabs.getBoundingClientRect();
        const rect = active.getBoundingClientRect();
        gsap.to(indicator, {
            opacity: 1,
            x: rect.left - box.left,
            width: rect.width,
            duration: reduced ? 0 : 0.55,
            ease: 'expo.out',
        });
    }, [agent, mode, reduced]);

    const switchMode = (next) => {
        const from = script.findIndex((step) => step.mode === next);
        setMessages([]);
        setDraft('');
        setThinking(false);
        setMode(next);
        setAgent(script[from].agent);
        setRun((r) => ({ id: r.id + 1, from }));
    };

    const labels = t.console.agents;

    return (
        <div ref={rootRef} className="hud relative w-full max-w-xl rounded-2xl border border-white/10 bg-panel/80 shadow-[0_40px_120px_-30px_rgba(34,211,238,0.35)] backdrop-blur-xl">
            {/* window bar */}
            <div className="flex items-center justify-between border-b border-white/10 px-4 py-3">
                <div className="flex items-center gap-1.5" dir="ltr">
                    <span className="h-2.5 w-2.5 rounded-full bg-red-400/80" />
                    <span className="h-2.5 w-2.5 rounded-full bg-amber/80" />
                    <span className="h-2.5 w-2.5 rounded-full bg-safe/80" />
                </div>
                <span className="font-mono text-[11px] text-slate-500" dir="ltr">~/{t.console.title} — {mode}</span>
                <span className="mono-label flex items-center gap-2 font-mono text-[11px] uppercase text-safe">
                    <span className="pulse-dot h-1.5 w-1.5 rounded-full bg-safe" />{t.console.live}
                </span>
            </div>

            {/* mode switch */}
            <div className="flex items-center gap-2 px-4 pt-4">
                {['single', 'multi'].map((m) => (
                    <button
                        key={m}
                        type="button"
                        onClick={() => switchMode(m)}
                        className={`rounded-full px-3.5 py-1.5 text-xs font-semibold transition ${mode === m ? 'bg-white text-void' : 'bg-white/5 text-slate-400 hover:bg-white/10 hover:text-white'}`}
                    >
                        {t.console[m]}
                    </button>
                ))}
            </div>

            {/* team tabs (multi) or single mentor badge */}
            <div className="px-4 pt-3">
                {mode === 'multi' ? (
                    <div ref={tabsRef} className="relative grid grid-cols-3 rounded-xl bg-black/30 p-1">
                        <span ref={indicatorRef} className="absolute inset-y-1 rounded-lg bg-white/10 ring-1 ring-white/15" style={{ left: 0, right: 'auto', width: 0, opacity: 0 }} />
                        {TEAM.map((key) => (
                            <span key={key} data-agent={key} className={`relative z-10 flex items-center justify-center gap-2 py-2 text-xs font-semibold transition-colors ${agent === key ? AGENT_STYLE[key].text : 'text-slate-500'}`}>
                                <span className={`h-1.5 w-1.5 rounded-full ${AGENT_STYLE[key].dot}`} />{labels[key]}
                            </span>
                        ))}
                    </div>
                ) : (
                    <div className="flex items-center gap-3 rounded-xl bg-black/30 px-3 py-2.5">
                        <span className="grid h-7 w-7 place-items-center rounded-lg bg-signal/15 font-mono text-[11px] font-bold text-signal">CM</span>
                        <span className="text-xs font-semibold text-signal">{labels.mentor}</span>
                    </div>
                )}
            </div>

            {/* conversation */}
            <div className="relative h-[19rem] overflow-hidden px-4 pt-4">
                <div className="pointer-events-none absolute inset-x-0 top-0 z-10 h-10 bg-gradient-to-b from-panel to-transparent" />
                <div className="flex h-full flex-col justify-end gap-4 pb-4">
                    {messages.map((message, i) => <Bubble key={`${run.id}-${i}-${message.role}`} message={message} labels={labels} />)}
                    {thinking && (
                        <div className={`mono-label flex items-center gap-2 font-mono text-[11px] ${AGENT_STYLE[agent].text}`}>
                            <span className="flex gap-1">
                                <span className={`typing-dot h-1.5 w-1.5 rounded-full ${AGENT_STYLE[agent].dot}`} />
                                <span className={`typing-dot h-1.5 w-1.5 rounded-full ${AGENT_STYLE[agent].dot}`} />
                                <span className={`typing-dot h-1.5 w-1.5 rounded-full ${AGENT_STYLE[agent].dot}`} />
                            </span>
                            {labels[agent]} · {t.console.thinking}
                        </div>
                    )}
                </div>
            </div>

            {/* input */}
            <div className="border-t border-white/10 p-3">
                <div className="flex items-center gap-3 rounded-xl bg-black/40 px-4 py-3 ring-1 ring-white/10">
                    <span className={`min-h-5 flex-1 truncate text-sm ${draft ? 'caret text-white' : 'text-slate-500'}`}>
                        {draft || t.console.placeholder}
                    </span>
                    <span className={`grid h-8 w-8 place-items-center rounded-lg transition ${draft ? 'bg-signal text-void' : 'bg-white/5 text-slate-500'}`}>
                        <svg viewBox="0 0 24 24" className="h-4 w-4 rtl:-scale-x-100" fill="none" stroke="currentColor" strokeWidth="2"><path d="M5 12h14M13 6l6 6-6 6" strokeLinecap="round" strokeLinejoin="round" /></svg>
                    </span>
                </div>
            </div>
        </div>
    );
}
