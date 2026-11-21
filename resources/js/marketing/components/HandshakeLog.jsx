import { useEffect, useState } from 'react';
import { prefersReducedMotion } from '../motion';

/** Terminal that types the security steps one by one, then marks each as done (loops). */
export default function HandshakeLog({ lines, title }) {
    const reduced = prefersReducedMotion();
    const [shown, setShown] = useState(reduced ? lines.map((l) => ({ text: l, done: true })) : []);

    useEffect(() => {
        if (reduced) {
            setShown(lines.map((l) => ({ text: l, done: true })));
            return undefined;
        }
        let cancelled = false;
        const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
        (async () => {
            while (!cancelled) {
                setShown([]);
                for (const line of lines) {
                    for (let i = 1; i <= line.length && !cancelled; i += 2) {
                        const text = line.slice(0, i);
                        setShown((s) => [...s.filter((x) => x.done), { text, done: false }]);
                        await sleep(22);
                    }
                    if (cancelled) return;
                    await sleep(380);
                    setShown((s) => [...s.filter((x) => x.done), { text: line, done: true }]);
                    await sleep(260);
                }
                await sleep(4200);
            }
        })();
        return () => { cancelled = true; };
    }, [lines, reduced]);

    return (
        <div className="hud w-full max-w-md rounded-xl border border-white/10 bg-black/40 backdrop-blur">
            <div className="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
                <span className="font-mono text-[11px] text-slate-500" dir="ltr">~/{title}</span>
                <span className="flex gap-1.5" dir="ltr">
                    <span className="h-2 w-2 rounded-full bg-white/15" /><span className="h-2 w-2 rounded-full bg-white/15" /><span className="h-2 w-2 rounded-full bg-safe/70" />
                </span>
            </div>
            <ul className="min-h-[9.5rem] space-y-2.5 px-4 py-4 text-[13px] leading-6">
                {shown.map((line, i) => (
                    <li key={i} className="flex items-start gap-3">
                        <span className={`mt-[3px] grid h-4 w-4 shrink-0 place-items-center rounded font-mono text-[10px] ${line.done ? 'bg-safe/15 text-safe' : 'bg-signal/15 text-signal'}`}>
                            {line.done ? '✓' : '›'}
                        </span>
                        <span className={line.done ? 'text-slate-300' : 'caret text-signal'}>{line.text}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}
