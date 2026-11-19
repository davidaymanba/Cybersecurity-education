import { useEffect, useState } from 'react';
import { scrollToTarget } from '../motion';

export default function Header({ t, data }) {
    const [scrolled, setScrolled] = useState(false);
    const isArabic = data.locale !== 'en';

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 24);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    const go = (event, target) => {
        event.preventDefault();
        scrollToTarget(target);
    };

    return (
        <header className={`fixed inset-x-0 top-0 z-50 transition-all duration-500 ${scrolled ? 'border-b border-white/10 bg-void/70 backdrop-blur-xl' : 'border-b border-transparent'}`}>
            <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4">
                <a href="#top" onClick={(e) => go(e, '#top')} className="flex items-center gap-3">
                    <span className="relative grid h-9 w-9 place-items-center rounded-lg bg-signal/10 font-mono text-sm font-bold text-signal ring-1 ring-signal/40">
                        CL
                        <span className="pulse-dot absolute -end-0.5 -top-0.5 h-2 w-2 rounded-full bg-safe" />
                    </span>
                    <span className="font-semibold tracking-tight text-white" dir="ltr">CyberLearn<span className="text-signal">.AI</span></span>
                </a>

                <nav className="hidden items-center gap-7 text-sm text-slate-400 md:flex">
                    <a href="#console" onClick={(e) => go(e, '#console')} className="transition hover:text-white">{t.nav.console}</a>
                    <a href="#team" onClick={(e) => go(e, '#team')} className="transition hover:text-white">{t.nav.team}</a>
                    <a href="#lessons" onClick={(e) => go(e, '#lessons')} className="transition hover:text-white">{t.nav.lessons}</a>
                </nav>

                <div className="flex items-center gap-2">
                    <a href={isArabic ? data.routes.langEn : data.routes.langAr} className="grid h-9 w-9 place-items-center rounded-lg text-sm text-slate-400 ring-1 ring-white/10 transition hover:text-white" aria-label="language">
                        {t.nav.lang}
                    </a>
                    {data.auth ? (
                        <a href={data.routes.dashboard} className="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-void transition hover:bg-signal">{t.nav.dashboard}</a>
                    ) : (
                        <>
                            <a href={data.routes.login} className="hidden rounded-lg px-4 py-2 text-sm text-slate-300 transition hover:text-white sm:block">{t.nav.login}</a>
                            <a href={data.routes.register} className="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-void transition hover:bg-signal">{t.nav.register}</a>
                        </>
                    )}
                </div>
            </div>
        </header>
    );
}
