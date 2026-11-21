/**
 * Shared motion for server-rendered pages. Pages opt in with data attributes only:
 *
 *   data-reveal[="up|fade|start|scale"]   fade/slide in when scrolled into view (data-delay="0.1")
 *   data-stagger[="0.06"]                 reveal direct children one after another
 *   data-split                            word-by-word reveal of a heading (words, never letters: Arabic joins)
 *   data-counter="42" [data-decimals]     count up to the value already printed in the element
 *   data-progress="73"                    bar grows to 73% (element width is set server-side)
 *   data-ring="73"                        SVG circle (pathLength=100) sweeps to 73%
 *
 * Everything is printed in its final state by the server, so with reduced motion (or no JS)
 * pages are static and complete.
 */
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

export const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
export const isRtl = () => document.documentElement.dir === 'rtl';
/** Horizontal distance toward the reading start (mirrored in RTL). */
export const fromStart = (distance) => (isRtl() ? distance : -distance);

const numberFormat = new Intl.NumberFormat('en-US');

function onEnter(el, play) {
    ScrollTrigger.create({ trigger: el, start: 'top 92%', once: true, onEnter: play });
}

function reveal(el) {
    const kind = el.dataset.reveal || 'up';
    const delay = parseFloat(el.dataset.delay || '0');
    const from = { opacity: 0 };
    if (kind === 'up') from.y = 18;
    if (kind === 'start') from.x = fromStart(24);
    if (kind === 'scale') from.scale = 0.96;

    gsap.set(el, from);
    onEnter(el, () => gsap.to(el, { opacity: 1, x: 0, y: 0, scale: 1, duration: 0.8, delay, ease: 'expo.out', clearProps: 'transform' }));
}

function stagger(el) {
    const children = [...el.children];
    const each = parseFloat(el.dataset.stagger || '0.06') || 0.06;
    gsap.set(children, { opacity: 0, y: 14 });
    onEnter(el, () => gsap.to(children, { opacity: 1, y: 0, duration: 0.7, stagger: each, ease: 'expo.out', clearProps: 'transform' }));
}

function split(el) {
    if (el.dataset.splitDone) return;
    const text = el.textContent.trim().replace(/\s+/g, ' ');
    const gradient = el.dataset.splitGradient !== undefined;
    el.setAttribute('aria-label', text);
    el.textContent = '';
    // In RTL pages each word becomes an inline-block, and the bidi algorithm would lay out a
    // run of Latin words (a name, "Cyber Mentor") in reverse. Keep such runs in one unit.
    const units = [];
    const arabicLetter = /[\u0621-\u064A]/;
    text.split(' ').forEach((word) => {
        const latin = !arabicLetter.test(word);
        const last = units[units.length - 1];
        if (isRtl() && latin && last?.latin) last.text += ` ${word}`;
        else units.push({ text: word, latin });
    });
    units.forEach(({ text: word }, i, all) => {
        const mask = document.createElement('span');
        mask.setAttribute('aria-hidden', 'true');
        mask.className = 'inline-block overflow-hidden pb-[0.12em] align-bottom';
        const inner = document.createElement('span');
        // background-clip:text does not paint through transformed children: gradient goes on each word.
        inner.className = `split-word inline-block will-change-transform${gradient ? ' text-signal-gradient' : ''}`;
        inner.textContent = word;
        mask.appendChild(inner);
        el.appendChild(mask);
        if (i < all.length - 1) el.appendChild(document.createTextNode(' '));
    });
    el.dataset.splitDone = '1';

    const words = el.querySelectorAll('.split-word');
    gsap.set(words, { yPercent: 110 });
    onEnter(el, () => gsap.to(words, { yPercent: 0, duration: 0.9, stagger: 0.05, ease: 'expo.out' }));
}

function counter(el) {
    const target = parseFloat(el.dataset.counter);
    if (!Number.isFinite(target)) return;
    const decimals = parseInt(el.dataset.decimals || '0', 10);
    const state = { value: 0 };
    const render = () => { el.textContent = decimals ? state.value.toFixed(decimals) : numberFormat.format(Math.round(state.value)); };
    render();
    onEnter(el, () => gsap.to(state, { value: target, duration: 1.4, ease: 'power3.out', onUpdate: render }));
}

function progress(el) {
    gsap.set(el, { scaleX: 0, transformOrigin: isRtl() ? 'right center' : 'left center' });
    onEnter(el, () => gsap.to(el, { scaleX: 1, duration: 1.1, ease: 'expo.out', delay: parseFloat(el.dataset.delay || '0') }));
}

function ring(el) {
    const value = Math.max(0, Math.min(100, parseFloat(el.dataset.ring) || 0));
    gsap.set(el, { strokeDashoffset: 100 });
    onEnter(el, () => gsap.to(el, { strokeDashoffset: 100 - value, duration: 1.4, ease: 'expo.out' }));
}

const hooks = [
    ['[data-split]', split],
    ['[data-reveal]', reveal],
    ['[data-stagger]', stagger],
    ['[data-counter]', counter],
    ['[data-progress]', progress],
    ['[data-ring]', ring],
];

/** Wire every motion hook inside `root`. Safe to call again on injected HTML. */
export function initMotion(root = document) {
    clearTimeout(window.__motionFallback);
    if (prefersReducedMotion()) {
        document.documentElement.classList.remove('motion');
        return;
    }
    hooks.forEach(([selector, fn]) => root.querySelectorAll(selector).forEach((el) => {
        if (el.dataset.motionBound) return;
        el.dataset.motionBound = '1';
        fn(el);
    }));

    // Web fonts change text metrics: re-measure trigger positions once they are in.
    document.fonts?.ready.then(() => ScrollTrigger.refresh());
}

export { gsap, ScrollTrigger };
