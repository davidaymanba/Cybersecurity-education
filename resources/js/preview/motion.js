import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger);

export const prefersReducedMotion = () =>
    typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let lenis = null;
const velocityListeners = new Set();

/** Smooth scrolling driven by GSAP's ticker so ScrollTrigger and Lenis stay in sync. */
export function setupSmoothScroll() {
    if (prefersReducedMotion()) return () => {};

    lenis = new Lenis({ lerp: 0.085, smoothWheel: true, wheelMultiplier: 0.95 });
    lenis.on('scroll', (instance) => {
        ScrollTrigger.update();
        velocityListeners.forEach((fn) => fn(instance.velocity, instance.direction));
    });

    const tick = (time) => lenis?.raf(time * 1000);
    gsap.ticker.add(tick);
    gsap.ticker.lagSmoothing(0);

    return () => {
        gsap.ticker.remove(tick);
        lenis?.destroy();
        lenis = null;
    };
}

export function scrollToTarget(target) {
    if (lenis) {
        lenis.scrollTo(target, { offset: -72, duration: 1.4 });
    } else {
        document.querySelector(target)?.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
    }
}

export function onScrollVelocity(fn) {
    velocityListeners.add(fn);
    return () => velocityListeners.delete(fn);
}

export { gsap, ScrollTrigger };
