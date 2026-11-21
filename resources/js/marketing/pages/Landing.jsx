import { useEffect } from 'react';
import { ScrollTrigger, setupSmoothScroll } from '../motion';
import { CursorGlow, ScrollProgress } from '../sections/Effects';
import Header from '../sections/Header';
import Hero from '../sections/Hero';
import ConceptMarquee from '../sections/ConceptMarquee';
import TeamSection from '../sections/TeamSection';
import LessonsSection from '../sections/LessonsSection';
import FinalCTA from '../sections/FinalCTA';
import Footer from '../sections/Footer';

export default function Landing({ t, data }) {
    // Lenis only here: the long marketing scroll benefits from it, forms do not.
    useEffect(() => setupSmoothScroll(), []);

    // Web fonts change text metrics: re-measure pinned/scrubbed sections once they load.
    useEffect(() => {
        document.fonts?.ready.then(() => ScrollTrigger.refresh());
    }, []);

    return (
        <>
            <ScrollProgress />
            <CursorGlow />
            <Header t={t} data={data} />
            <main className="relative z-10">
                <Hero t={t} data={data} />
                <ConceptMarquee items={t.marquee} />
                <TeamSection t={t} />
                <LessonsSection t={t} data={data} />
                <FinalCTA t={t} data={data} />
            </main>
            <Footer t={t} data={data} />
        </>
    );
}
