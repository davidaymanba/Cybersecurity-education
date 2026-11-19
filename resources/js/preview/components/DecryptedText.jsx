import { useEffect, useRef, useState } from 'react';
import { prefersReducedMotion } from '../motion';

const GLYPHS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789#$%&@!?';
const isArabic = (text) => /[؀-ۿ]/.test(text);

/**
 * React Bits–style "DecryptedText": scrambles then reveals Latin text when it scrolls into view.
 * Arabic is never scrambled (random letters would break letter joining), it just renders.
 */
export default function DecryptedText({ text, speed = 38, delay = 0, className = '', scrambledClassName = 'text-signal' }) {
    const ref = useRef(null);
    const skip = isArabic(text) || prefersReducedMotion();
    const [shown, setShown] = useState(text);
    const [done, setDone] = useState(skip);

    useEffect(() => {
        if (skip) return undefined;
        let frame = 0;
        let timer = 0;
        let started = false;
        const scramble = (revealed) =>
            Array.from(text).map((ch, i) => (ch === ' ' || i < revealed ? ch : GLYPHS[Math.floor(Math.random() * GLYPHS.length)])).join('');

        setShown(scramble(0));
        const start = () => {
            if (started) return;
            started = true;
            setTimeout(() => {
                timer = setInterval(() => {
                    frame += 1;
                    const revealed = Math.floor(frame / 2);
                    if (revealed >= text.length) {
                        clearInterval(timer);
                        setShown(text);
                        setDone(true);
                    } else {
                        setShown(scramble(revealed));
                    }
                }, speed);
            }, delay);
        };

        const io = new IntersectionObserver(([entry]) => {
            if (entry.isIntersecting) {
                start();
                io.disconnect();
            }
        });
        io.observe(ref.current);

        return () => {
            clearInterval(timer);
            io.disconnect();
        };
    }, [text, speed, delay, skip]);

    return (
        <span ref={ref} className={className} aria-label={text}>
            <span aria-hidden="true" className={done ? '' : scrambledClassName}>{shown}</span>
        </span>
    );
}
