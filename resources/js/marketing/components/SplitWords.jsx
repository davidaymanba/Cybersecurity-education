/**
 * Splits text into masked words for GSAP reveals. Words (not letters) so Arabic keeps its
 * letter joining; each word sits in an overflow-hidden mask so it can slide up into view.
 */
export default function SplitWords({ text, wordClassName = 'split-word', className = '' }) {
    const words = text.split(/\s+/).filter(Boolean);
    return (
        <span className={className} aria-label={text}>
            {words.map((word, i) => (
                <span key={`${word}-${i}`} aria-hidden="true" className="inline-block overflow-hidden pb-[0.12em] align-bottom">
                    <span className={`inline-block will-change-transform ${wordClassName}`}>{word}</span>
                    {i < words.length - 1 ? ' ' : ''}
                </span>
            ))}
        </span>
    );
}
