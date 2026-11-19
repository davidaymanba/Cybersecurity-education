import { useEffect, useRef } from 'react';
import { prefersReducedMotion } from '../motion';

/**
 * React Bits–style "LetterGlitch": a canvas of glyphs that randomly re-roll and change colour.
 * Pauses off-screen, caps device-pixel-ratio and stays static for reduced-motion users.
 */
export default function LetterGlitch({
    colors = ['#0e7490', '#22d3ee', '#34d399', '#155e75'],
    characters = '0123456789ABCDEF<>/{}[]#$%&*+=?!',
    fontSize = 15,
    speed = 70,
    className = '',
}) {
    const canvasRef = useRef(null);

    useEffect(() => {
        const canvas = canvasRef.current;
        const ctx = canvas.getContext('2d');
        const glyphs = Array.from(characters);
        const pick = (list) => list[Math.floor(Math.random() * list.length)];
        const cellW = fontSize * 0.68;
        const cellH = fontSize * 1.3;
        let cols = 0;
        let letters = [];
        let raf = 0;
        let last = 0;
        let visible = true;

        const draw = () => {
            const { width, height } = canvas.getBoundingClientRect();
            ctx.clearRect(0, 0, width, height);
            ctx.font = `${fontSize}px "JetBrains Mono", monospace`;
            ctx.textBaseline = 'top';
            for (let i = 0; i < letters.length; i++) {
                const l = letters[i];
                ctx.globalAlpha = l.alpha;
                ctx.fillStyle = l.color;
                ctx.fillText(l.ch, (i % cols) * cellW, Math.floor(i / cols) * cellH);
            }
            ctx.globalAlpha = 1;
        };

        const resize = () => {
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            const { width, height } = canvas.parentElement.getBoundingClientRect();
            canvas.width = width * dpr;
            canvas.height = height * dpr;
            canvas.style.width = `${width}px`;
            canvas.style.height = `${height}px`;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            cols = Math.ceil(width / cellW);
            const rows = Math.ceil(height / cellH);
            letters = Array.from({ length: cols * rows }, () => ({
                ch: pick(glyphs),
                color: pick(colors),
                alpha: Math.random() * 0.55 + 0.08,
            }));
            draw();
        };

        const update = () => {
            const count = Math.max(1, Math.floor(letters.length * 0.035));
            for (let k = 0; k < count; k++) {
                const l = letters[Math.floor(Math.random() * letters.length)];
                if (!l) continue;
                l.ch = pick(glyphs);
                l.color = pick(colors);
                l.alpha = Math.random() * 0.7 + 0.06;
            }
            draw();
        };

        const loop = (time) => {
            raf = requestAnimationFrame(loop);
            if (visible && time - last > speed) {
                update();
                last = time;
            }
        };

        resize();
        const resizeObserver = new ResizeObserver(resize);
        resizeObserver.observe(canvas.parentElement);
        const io = new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; });
        io.observe(canvas);
        if (!prefersReducedMotion()) raf = requestAnimationFrame(loop);

        return () => {
            cancelAnimationFrame(raf);
            resizeObserver.disconnect();
            io.disconnect();
        };
    }, [characters, colors, fontSize, speed]);

    return <canvas ref={canvasRef} className={className} aria-hidden="true" />;
}
