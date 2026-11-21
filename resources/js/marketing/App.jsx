import { lazy, Suspense } from 'react';
import { content } from './content';

// Each page is its own chunk: the auth forms never download the landing sections and vice versa.
const Landing = lazy(() => import('./pages/Landing'));
const AuthPage = lazy(() => import('./sections/AuthPage'));

const AUTH_PAGES = ['login', 'register', 'forgot', 'reset'];

export default function App({ data }) {
    const t = content[data.locale === 'en' ? 'en' : 'ar'];
    const isAuth = AUTH_PAGES.includes(data.page);

    return (
        <Suspense fallback={<div className="min-h-[100svh]" />}>
            {isAuth ? <AuthPage t={t} data={data} /> : <Landing t={t} data={data} />}
        </Suspense>
    );
}
