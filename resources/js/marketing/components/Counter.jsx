import { useEffect, useRef } from 'react';
import { gsap, prefersReducedMotion } from '../motion';

/** Counts up to `value` the first time it scrolls into view. */
export default function Counter({ value, duration = 1.6, className = '' }) {
    const ref = useRef(null);

    useEffect(() => {
        const el = ref.current;
        if (prefersReducedMotion()) {
            el.textContent = String(value);
            return undefined;
        }
        const state = { n: 0 };
        el.textContent = '0';
        const tween = gsap.to(state, {
            n: value,
            duration,
            ease: 'power2.out',
            paused: true,
            onUpdate: () => { el.textContent = String(Math.round(state.n)); },
        });
        const io = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                tween.play();
                io.disconnect();
            }
        }, { threshold: 0.6 });
        io.observe(el);
        return () => {
            io.disconnect();
            tween.kill();
        };
    }, [value, duration]);

    return <span ref={ref} className={`tabular-nums ${className}`}>{value}</span>;
}
