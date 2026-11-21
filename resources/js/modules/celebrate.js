import { prefersReducedMotion } from '../ui/motion';

/**
 * One-shot confetti burst on a passed quiz. 2D canvas, capped DPR, stops itself after ~2.5s
 * and never runs with reduced motion.
 */
export function celebrate(anchor) {
    if (prefersReducedMotion() || !anchor) return;
    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    canvas.className = 'pointer-events-none fixed inset-0 z-50';
    document.body.appendChild(canvas);
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const resize = () => {
        canvas.width = window.innerWidth * dpr;
        canvas.height = window.innerHeight * dpr;
        canvas.style.width = `${window.innerWidth}px`;
        canvas.style.height = `${window.innerHeight}px`;
    };
    resize();
    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);

    const rect = anchor.getBoundingClientRect();
    const cx = rect.left + rect.width / 2;
    const cy = rect.top + rect.height / 2;
    const colors = ['#22d3ee', '#34d399', '#a78bfa', '#fbbf24', '#ffffff'];
    const pieces = Array.from({ length: 110 }, () => {
        const angle = Math.random() * Math.PI * 2;
        const speed = 4 + Math.random() * 7;
        return {
            x: cx, y: cy,
            vx: Math.cos(angle) * speed, vy: Math.sin(angle) * speed - 4,
            w: 4 + Math.random() * 5, h: 7 + Math.random() * 6,
            r: Math.random() * Math.PI, vr: (Math.random() - 0.5) * 0.3,
            color: colors[Math.floor(Math.random() * colors.length)],
        };
    });

    const start = performance.now();
    const frame = (now) => {
        const elapsed = now - start;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const alpha = Math.max(0, 1 - elapsed / 2500);
        pieces.forEach((p) => {
            p.vy += 0.22;
            p.vx *= 0.99;
            p.x += p.vx;
            p.y += p.vy;
            p.r += p.vr;
            ctx.save();
            ctx.globalAlpha = alpha;
            ctx.translate(p.x, p.y);
            ctx.rotate(p.r);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
        });
        if (elapsed < 2500) requestAnimationFrame(frame);
        else canvas.remove();
    };
    requestAnimationFrame(frame);
}
