import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { gsap, prefersReducedMotion } from '../motion';
import LetterGlitch from '../components/LetterGlitch';
import LockCore from '../components/LockCore';
import HandshakeLog from '../components/HandshakeLog';
import PasswordStrength, { scorePassword } from '../components/PasswordStrength';
import ClickSpark from '../components/ClickSpark';

const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function Field({ name, label, error, isRtl, children, ...input }) {
    const ref = useRef(null);
    useLayoutEffect(() => {
        if (error && !prefersReducedMotion()) {
            gsap.fromTo(ref.current, { x: -10 }, { x: 0, duration: 0.6, ease: 'elastic.out(1, 0.3)' });
        }
    }, [error]);

    return (
        <div ref={ref} className="auth-item">
            <div className={`relative rounded-xl bg-white/[0.04] ring-1 transition focus-within:bg-white/[0.07] ${error ? 'ring-red-400/60' : 'ring-white/10 focus-within:ring-signal/60 focus-within:shadow-[0_0_0_4px_rgba(34,211,238,0.08)]'}`}>
                <input
                    id={name}
                    name={name}
                    placeholder=" "
                    aria-invalid={Boolean(error)}
                    aria-describedby={error ? `${name}-error` : undefined}
                    className="peer w-full bg-transparent px-4 pb-2.5 pt-6 text-[15px] text-white outline-none"
                    style={input.dir === 'ltr' ? { textAlign: isRtl ? 'right' : 'left' } : undefined}
                    {...input}
                />
                <label htmlFor={name} className="pointer-events-none absolute start-4 top-4 text-sm text-slate-500 transition-all duration-200 peer-focus:top-2 peer-focus:text-[11px] peer-focus:text-signal peer-[:not(:placeholder-shown)]:top-2 peer-[:not(:placeholder-shown)]:text-[11px]">
                    {label}
                </label>
                {children}
            </div>
            {error && <p id={`${name}-error`} className="mt-1.5 text-xs text-red-300">{error}</p>}
        </div>
    );
}

export default function AuthPage({ t, data }) {
    const A = t.auth;
    const isRtl = data.locale !== 'en';
    const root = useRef(null);
    const tabsRef = useRef(null);
    const indicatorRef = useRef(null);
    const old = data.old || {};
    const [page, setPage] = useState(data.page === 'register' ? 'register' : 'login');
    const [errors, setErrors] = useState(data.errors || {});
    const [busy, setBusy] = useState(false);
    const [showPw, setShowPw] = useState(false);
    const [form, setForm] = useState({
        name: old.name || '',
        email: old.email || '',
        password: '',
        password_confirmation: '',
        learning_level: old.learning_level || 'Beginner',
        remember: Boolean(old.remember),
    });

    const set = (key) => (event) => {
        const value = event.target.type === 'checkbox' ? event.target.checked : event.target.value;
        setForm((f) => ({ ...f, [key]: value }));
    };
    const err = (key) => errors[key]?.[0];

    const emailOk = EMAIL.test(form.email);
    const { score } = scorePassword(form.password);
    const passwordsMatch = form.password_confirmation !== '' && form.password === form.password_confirmation;
    const unlocked = page === 'login'
        ? emailOk && form.password.length >= 8
        : form.name.trim().length > 1 && emailOk && form.password.length >= 8 && passwordsMatch && score >= 2;

    const levelLabel = A.levels[form.learning_level][0];
    const logLines = A.log[page].map((line) => line.replace('{level}', levelLabel));

    // Intro.
    useLayoutEffect(() => {
        if (prefersReducedMotion()) return undefined;
        const ctx = gsap.context(() => {
            gsap.from('.auth-visual', { opacity: 0, duration: 1.2, ease: 'power2.out' });
            gsap.from('.auth-lock', { scale: 0.85, opacity: 0, duration: 1.4, ease: 'expo.out', delay: 0.15 });
            gsap.from('.auth-item', { opacity: 0, y: 18, duration: 0.7, stagger: 0.06, ease: 'expo.out', delay: 0.1 });
        }, root);
        return () => ctx.revert();
    }, []);

    // Re-animate the form fields after switching between login and register.
    const firstRender = useRef(true);
    useLayoutEffect(() => {
        if (firstRender.current) { firstRender.current = false; return; }
        if (prefersReducedMotion()) return;
        gsap.fromTo(root.current.querySelectorAll('.auth-item'), { opacity: 0, y: 16 }, { opacity: 1, y: 0, duration: 0.55, stagger: 0.05, ease: 'expo.out' });
    }, [page]);

    // Sliding tab indicator.
    useLayoutEffect(() => {
        const tabs = tabsRef.current;
        const active = tabs.querySelector(`[data-tab="${page}"]`);
        const box = tabs.getBoundingClientRect();
        const rect = active.getBoundingClientRect();
        gsap.to(indicatorRef.current, { x: rect.left - box.left - 4, width: rect.width, duration: prefersReducedMotion() ? 0 : 0.6, ease: 'expo.out' });
    }, [page]);

    // Browser back/forward between the two previews.
    useEffect(() => {
        const onPop = () => setPage(window.location.pathname.endsWith('/register') ? 'register' : 'login');
        window.addEventListener('popstate', onPop);
        return () => window.removeEventListener('popstate', onPop);
    }, []);

    const switchTo = (next) => {
        if (next === page) return;
        const swap = () => {
            setErrors({});
            setShowPw(false);
            setPage(next);
            window.history.pushState(null, '', data.routes[next]);
        };
        if (prefersReducedMotion()) { swap(); return; }
        gsap.to(root.current.querySelectorAll('.auth-item'), { opacity: 0, y: -10, duration: 0.22, stagger: 0.02, ease: 'power2.in', onComplete: swap });
    };

    const copy = A[page];
    const eye = (
        <button type="button" onClick={() => setShowPw((v) => !v)} className="absolute end-3 top-1/2 -translate-y-1/2 rounded-lg px-2 py-1 text-xs text-slate-400 transition hover:bg-white/10 hover:text-white">
            {showPw ? A.fields.hide : A.fields.show}
        </button>
    );

    return (
        <div ref={root} className="relative grid min-h-[100svh] lg:grid-cols-[1fr_1.05fr]">
            {/* form side */}
            <div className="relative z-10 flex flex-col px-5 py-6 sm:px-10">
                <div className="flex items-center justify-between">
                    <a href={data.routes.home} className="flex items-center gap-3">
                        <span className="grid h-9 w-9 place-items-center rounded-lg bg-signal/10 font-mono text-sm font-bold text-signal ring-1 ring-signal/40">CL</span>
                        <span className="font-semibold text-white" dir="ltr">CyberLearn<span className="text-signal">.AI</span></span>
                    </a>
                    <div className="flex items-center gap-2 text-sm">
                        <a href={isRtl ? data.routes.langEn : data.routes.langAr} className="grid h-9 w-9 place-items-center rounded-lg text-slate-400 ring-1 ring-white/10 hover:text-white">{t.nav.lang}</a>
                        <a href={data.routes.home} className="rounded-lg px-3 py-2 text-slate-400 hover:text-white">{A.back}</a>
                    </div>
                </div>

                <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
                    <div className="auth-item mb-8 lg:hidden"><div className="mx-auto w-28"><LockMini unlocked={unlocked} /></div></div>

                    <div ref={tabsRef} className="auth-item relative mb-8 grid grid-cols-2 rounded-xl bg-white/[0.04] p-1 ring-1 ring-white/10">
                        <span ref={indicatorRef} className="absolute inset-y-1 rounded-lg bg-white shadow-lg" style={{ left: 4, right: 'auto', width: 0 }} />
                        {['login', 'register'].map((key) => (
                            <button key={key} type="button" data-tab={key} onClick={() => switchTo(key)}
                                className={`relative z-10 rounded-lg py-2.5 text-sm font-semibold transition-colors duration-300 ${page === key ? 'text-void' : 'text-slate-400 hover:text-white'}`}>
                                {A.tabs[key]}
                            </button>
                        ))}
                    </div>

                    <h1 className="auth-item text-3xl font-bold text-white sm:text-4xl">{copy.title}</h1>
                    <p className="auth-item mt-3 leading-7 text-slate-400">{copy.lead}</p>

                    {data.status && <div className="auth-item mt-6 rounded-xl border border-safe/30 bg-safe/10 px-4 py-3 text-sm text-safe">{data.status}</div>}

                    <form method="post" action={page === 'login' ? data.routes.loginStore : data.routes.registerStore} onSubmit={() => setBusy(true)} className="mt-8 space-y-4" noValidate={false}>
                        <input type="hidden" name="_token" value={data.csrf} />

                        {page === 'register' && (
                            <Field name="name" label={A.fields.name} value={form.name} onChange={set('name')} autoComplete="name" required maxLength={120} error={err('name')} isRtl={isRtl} />
                        )}
                        <Field name="email" type="email" label={A.fields.email} value={form.email} onChange={set('email')} autoComplete="email" required dir="ltr" error={err('email')} isRtl={isRtl} />
                        <Field name="password" type={showPw ? 'text' : 'password'} label={A.fields.password} value={form.password} onChange={set('password')} autoComplete={page === 'login' ? 'current-password' : 'new-password'} required minLength={page === 'register' ? 8 : undefined} dir="ltr" error={err('password')} isRtl={isRtl}>
                            {eye}
                        </Field>

                        {page === 'register' && (
                            <>
                                <Field name="password_confirmation" type={showPw ? 'text' : 'password'} label={A.fields.confirm} value={form.password_confirmation} onChange={set('password_confirmation')} autoComplete="new-password" required dir="ltr" isRtl={isRtl} />
                                {form.password_confirmation && (
                                    <p className={`auth-item -mt-2 text-xs ${passwordsMatch ? 'text-safe' : 'text-red-300'}`}>{passwordsMatch ? `✓ ${A.strength.match}` : A.strength.mismatch}</p>
                                )}
                                <div className="auth-item"><PasswordStrength password={form.password} t={A.strength} /></div>

                                <fieldset className="auth-item">
                                    <legend className="mb-2 text-sm text-slate-400">{A.fields.level}</legend>
                                    <div className="grid grid-cols-3 gap-2">
                                        {Object.entries(A.levels).map(([value, [label, hint]]) => (
                                            <label key={value} className={`cursor-pointer rounded-xl p-3 text-center ring-1 transition ${form.learning_level === value ? 'bg-signal/10 ring-signal/60' : 'bg-white/[0.03] ring-white/10 hover:ring-white/25'}`}>
                                                <input type="radio" name="learning_level" value={value} checked={form.learning_level === value} onChange={set('learning_level')} className="sr-only" />
                                                <span className={`block text-sm font-semibold ${form.learning_level === value ? 'text-signal' : 'text-white'}`}>{label}</span>
                                                <span className="mt-0.5 block text-[11px] text-slate-500">{hint}</span>
                                            </label>
                                        ))}
                                    </div>
                                    {err('learning_level') && <p className="mt-1.5 text-xs text-red-300">{err('learning_level')}</p>}
                                </fieldset>
                            </>
                        )}

                        {page === 'login' && (
                            <div className="auth-item flex items-center justify-between text-sm">
                                <label className="flex cursor-pointer items-center gap-2 text-slate-400">
                                    <input type="checkbox" name="remember" value="1" checked={form.remember} onChange={set('remember')} className="h-4 w-4 rounded accent-cyan-400" />
                                    {A.login.remember}
                                </label>
                                <a href={data.routes.forgot} className="text-signal hover:underline">{A.login.forgot}</a>
                            </div>
                        )}

                        <div className="auth-item pt-2">
                            <ClickSpark className="block w-full">
                                <button type="submit" disabled={busy}
                                    className={`relative w-full overflow-hidden rounded-xl py-4 font-semibold transition ${unlocked ? 'bg-safe text-void shadow-[0_0_40px_-8px_rgba(52,211,153,0.8)]' : 'bg-signal text-void shadow-[0_0_40px_-10px_rgba(34,211,238,0.7)]'} disabled:cursor-wait`}>
                                    <span className="relative z-10">{busy ? copy.busy : copy.submit}</span>
                                    {busy && <span className="absolute inset-y-0 start-0 w-1/3 animate-[scan-x_1s_ease-in-out_infinite] bg-white/40" />}
                                </button>
                            </ClickSpark>
                        </div>
                    </form>

                    <p className="auth-item mt-8 text-center text-sm text-slate-500">
                        {copy.switch}{' '}
                        <button type="button" onClick={() => switchTo(page === 'login' ? 'register' : 'login')} className="font-semibold text-signal hover:underline">{copy.switchCta}</button>
                    </p>
                </div>
            </div>

            {/* visual side */}
            <aside className="auth-visual relative hidden overflow-hidden border-s border-white/[0.06] bg-panel/40 lg:sticky lg:top-0 lg:flex lg:h-[100svh] lg:flex-col lg:items-center lg:justify-center lg:gap-16 lg:p-12">
                <div className="pointer-events-none absolute inset-0 opacity-[0.13]"><LetterGlitch className="absolute inset-0" speed={90} /></div>
                <div className="grid-bg pointer-events-none absolute inset-0" />
                <div className="scanline" />
                <div className={`pointer-events-none absolute start-1/2 top-1/3 h-[30rem] w-[30rem] -translate-x-1/2 rounded-full blur-[150px] transition-colors duration-1000 rtl:translate-x-1/2 ${unlocked ? 'bg-safe/20' : 'bg-signal/15'}`} />
                <div className="auth-lock relative"><LockCore unlocked={unlocked} label={unlocked ? A.unlocked : A.locked} /></div>
                <div className="relative w-full max-w-md"><HandshakeLog key={`${page}-${form.learning_level}`} lines={logLines} title={A.logTitle} /></div>
            </aside>
        </div>
    );
}

/** Small padlock for phones (the big scanner lives in the hidden side panel). */
function LockMini({ unlocked }) {
    return (
        <svg viewBox="0 0 100 100" className="w-full" aria-hidden="true">
            <circle cx="50" cy="50" r="46" fill="none" stroke={unlocked ? '#34d399' : '#22d3ee'} strokeOpacity="0.4" strokeDasharray="30 10" className="ring-spin" style={{ transformOrigin: '50% 50%' }} />
            <path d="M38 48 V40 a12 12 0 0 1 24 0 V48" fill="none" stroke={unlocked ? '#34d399' : '#22d3ee'} strokeWidth="4" strokeLinecap="round"
                style={{ transform: unlocked ? 'translateY(-6px) rotate(-15deg)' : 'none', transformOrigin: '80% 100%', transition: 'transform .6s cubic-bezier(.34,1.56,.64,1)' }} />
            <rect x="30" y="46" width="40" height="30" rx="7" fill="#07111f" stroke={unlocked ? '#34d399' : '#22d3ee'} strokeWidth="2.5" />
            <circle cx="50" cy="59" r="4" fill={unlocked ? '#34d399' : '#22d3ee'} />
        </svg>
    );
}
