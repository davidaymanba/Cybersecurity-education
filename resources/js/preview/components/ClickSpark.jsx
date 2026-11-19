import { useEffect, useRef } from 'react';
import { prefersReducedMotion } from '../motion';

const BLEED = 48; // sparks may fly outside the wrapped element

/** React Bits–style "ClickSpark": radial sparks burst from the click point. */
export default function ClickSpark({ children, color = '#67e8f9', count = 10, radius = 26, duration = 480, className = '' }) {
    const wrapRef = useRef(null);
    const canvasRef = useRef(null);

    useEffect(() => {
        if (prefersReducedMotion()) return undefined;
        const wrap = wrapRef.current;
        const canvas = canvasRef.current;
        const ctx = canvas.getContext('2d');
        let sparks = [];
        let raf = 0;

        const resize = () => {
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            const rect = wrap.getBoundingClientRect();
            const w = rect.width + BLEED * 2;
            const h = rect.height + BLEED * 2;
            canvas.width = w * dpr;
            canvas.height = h * dpr;
            canvas.style.width = `${w}px`;
            canvas.style.height = `${h}px`;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        };

        const draw = (now) => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            sparks = sparks.filter((s) => {
                const p = (now - s.start) / duration;
                if (p >= 1) return false;
                const eased = 1 - (1 - p) ** 3;
                const dist = eased * radius * 2;
                const len = (1 - eased) * 14;
                ctx.strokeStyle = color;
                ctx.globalAlpha = 1 - p;
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.beginPath();
                ctx.moveTo(s.x + dist * Math.cos(s.a), s.y + dist * Math.sin(s.a));
                ctx.lineTo(s.x + (dist + len) * Math.cos(s.a), s.y + (dist + len) * Math.sin(s.a));
                ctx.stroke();
                return true;
            });
            ctx.globalAlpha = 1;
            raf = sparks.length ? requestAnimationFrame(draw) : 0;
        };

        const onClick = (event) => {
            const rect = wrap.getBoundingClientRect();
            const x = event.clientX - rect.left + BLEED;
            const y = event.clientY - rect.top + BLEED;
            const start = performance.now();
            for (let i = 0; i < count; i++) sparks.push({ x, y, a: (Math.PI * 2 * i) / count, start });
            if (!raf) raf = requestAnimationFrame(draw);
        };

        resize();
        const ro = new ResizeObserver(resize);
        ro.observe(wrap);
        wrap.addEventListener('click', onClick);
        return () => {
            wrap.removeEventListener('click', onClick);
            ro.disconnect();
            cancelAnimationFrame(raf);
        };
    }, [color, count, radius, duration]);

    return (
        <div ref={wrapRef} className={`relative inline-block ${className}`}>
            {children}
            <canvas ref={canvasRef} className="pointer-events-none absolute" style={{ inset: -BLEED }} aria-hidden="true" />
        </div>
    );
}
