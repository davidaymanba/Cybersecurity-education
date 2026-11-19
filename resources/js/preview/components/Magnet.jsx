import { useEffect, useRef } from 'react';
import { gsap, prefersReducedMotion } from '../motion';

/** React Bits–style "Magnet": the child drifts toward the pointer when it comes close. */
export default function Magnet({ children, strength = 0.32, padding = 90, className = '' }) {
    const ref = useRef(null);

    useEffect(() => {
        if (prefersReducedMotion() || window.matchMedia('(hover: none)').matches) return undefined;
        const el = ref.current;
        const xTo = gsap.quickTo(el, 'x', { duration: 0.6, ease: 'power3.out' });
        const yTo = gsap.quickTo(el, 'y', { duration: 0.6, ease: 'power3.out' });

        const onMove = (event) => {
            const rect = el.getBoundingClientRect();
            const dx = event.clientX - (rect.left + rect.width / 2);
            const dy = event.clientY - (rect.top + rect.height / 2);
            const near = Math.abs(dx) < rect.width / 2 + padding && Math.abs(dy) < rect.height / 2 + padding;
            xTo(near ? dx * strength : 0);
            yTo(near ? dy * strength : 0);
        };

        window.addEventListener('pointermove', onMove);
        return () => window.removeEventListener('pointermove', onMove);
    }, [strength, padding]);

    return <div ref={ref} className={`inline-block will-change-transform ${className}`}>{children}</div>;
}
