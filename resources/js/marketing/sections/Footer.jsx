export default function Footer({ t, data }) {
    const isArabic = data.locale !== 'en';
    return (
        <footer className="border-t border-white/[0.06]">
            <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-8 text-sm text-slate-400">
                <span dir="ltr" className="font-mono">© {new Date().getFullYear()} CyberLearn<span className="text-signal">.AI</span></span>
                <span>{t.footer}</span>
                <nav className="flex items-center gap-4" aria-label="footer">
                    {data.auth ? (
                        <a href={data.routes.dashboard} className="transition hover:text-white">{t.nav.dashboard}</a>
                    ) : (
                        <>
                            <a href={data.routes.login} className="transition hover:text-white">{t.nav.login}</a>
                            <a href={data.routes.register} className="transition hover:text-white">{t.nav.register}</a>
                        </>
                    )}
                    <a href={isArabic ? data.routes.langEn : data.routes.langAr} lang={isArabic ? 'en' : 'ar'} className="transition hover:text-white">{isArabic ? 'English' : 'العربية'}</a>
                </nav>
            </div>
        </footer>
    );
}
