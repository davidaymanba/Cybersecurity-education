import { Fragment } from 'react';

/** Renders our static copy: **bold** segments and line breaks (no HTML injection). */
export default function RichText({ text }) {
    return text.split('\n').map((line, li, lines) => (
        <Fragment key={li}>
            {line.split(/(\*\*[^*]+\*\*)/g).map((part, pi) =>
                part.startsWith('**') && part.endsWith('**')
                    ? <strong key={pi} className="font-semibold text-white">{part.slice(2, -2)}</strong>
                    : <Fragment key={pi}>{part}</Fragment>,
            )}
            {li < lines.length - 1 && <br />}
        </Fragment>
    ));
}
