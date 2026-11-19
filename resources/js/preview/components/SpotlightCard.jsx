import { useRef } from 'react';

/** React Bits–style "SpotlightCard": a soft light that follows the pointer inside the card. */
export default function SpotlightCard({ children, className = '', spotlight = 'rgba(34, 211, 238, 0.16)', ...rest }) {
    const ref = useRef(null);

    const onMove = (event) => {
        const rect = ref.current.getBoundingClientRect();
        ref.current.style.setProperty('--x', `${event.clientX - rect.left}px`);
        ref.current.style.setProperty('--y', `${event.clientY - rect.top}px`);
    };

    return (
        <div ref={ref} onPointerMove={onMove} className={`spotlight-card ${className}`} style={{ '--spot': spotlight }} {...rest}>
            {children}
        </div>
    );
}
