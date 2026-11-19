export default function Footer({ t }) {
    return (
        <footer className="border-t border-white/[0.06]">
            <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-8 text-sm text-slate-500">
                <span dir="ltr" className="font-mono">© {new Date().getFullYear()} CyberLearn<span className="text-signal">.AI</span></span>
                <span>{t.footer}</span>
            </div>
        </footer>
    );
}
