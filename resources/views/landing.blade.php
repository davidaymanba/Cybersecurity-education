@extends('layouts.app', ['title' => 'CyberLearn AI'])

@section('content')
@php
    $featuredLessons = ($lessons ?? collect())->take(3);

    $stats = [
        ['value' => '2', 'label' => 'AI learning modes'],
        ['value' => '4+', 'label' => 'Cybersecurity tracks'],
        ['value' => '24/7', 'label' => 'Guided AI support'],
        ['value' => '100%', 'label' => 'Progress visibility'],
    ];

    $tracks = [
        ['title' => 'Network Security', 'copy' => 'Understand attacks, defenses, protocols, and layered controls through guided practice.'],
        ['title' => 'Threat Modeling', 'copy' => 'Map assets, risks, attack paths, and mitigation choices before incidents happen.'],
        ['title' => 'Incident Response', 'copy' => 'Practice triage, containment, evidence review, and structured recovery decisions.'],
        ['title' => 'Secure Coding', 'copy' => 'Learn how vulnerabilities appear in code and how to prevent them early.'],
    ];

    $agents = [
        ['name' => 'Guide Agent', 'role' => 'Keeps learners oriented and recommends the next best step.'],
        ['name' => 'Tutor Agent', 'role' => 'Explains concepts, answers questions, and adapts explanations to the learner.'],
        ['name' => 'Quiz Agent', 'role' => 'Checks understanding with targeted questions and instant feedback.'],
    ];

    $steps = [
        ['number' => '01', 'title' => 'Choose a lesson', 'copy' => 'Start with a focused cybersecurity topic and clear learning outcomes.'],
        ['number' => '02', 'title' => 'Learn with AI', 'copy' => 'Compare single-agent support with a multi-agent learning team.'],
        ['number' => '03', 'title' => 'Test knowledge', 'copy' => 'Complete quizzes that measure comprehension and confidence.'],
        ['number' => '04', 'title' => 'Track progress', 'copy' => 'Review engagement, score patterns, and learning performance over time.'],
    ];
@endphp

<section class="relative overflow-hidden">
    <div class="absolute inset-0 -z-20 bg-gradient-to-br from-slate-900 via-cyan-900/40 to-slate-800/60"></div>
    <div class="mx-auto grid min-h-[calc(100vh-68px)] max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.02fr_.98fr] lg:py-24">
        <div>
            <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-cyan-300/30 bg-cyan-300/10 px-4 py-2 text-sm font-medium text-cyan-100">
                AI-assisted cybersecurity learning platform
            </p>
            <h1 class="max-w-4xl text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-6xl md:text-7xl">
                Learn cybersecurity with intelligent guidance, practice, and measurable progress.
            </h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
                CyberLearn AI helps students study core security topics through structured lessons, AI tutoring, adaptive quizzes, and research-ready analytics comparing single-agent and multi-agent learning experiences.
            </p>
            <div class="mt-9 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="rounded-full bg-cyan-400 px-6 py-3 font-semibold text-slate-950 shadow-xl shadow-cyan-500/20 transition hover:bg-cyan-300">
                    Start learning
                </a>
                <a href="{{ route('login') }}" class="rounded-full border border-white/15 px-6 py-3 font-semibold text-white transition hover:border-white/30 hover:bg-white/10">
                    Research login
                </a>
                <a href="#tracks" class="ml-2 rounded-full px-4 py-3 text-sm font-medium text-slate-200 hover:underline">Explore tracks</a>
            </div>
            <div class="mt-10 grid max-w-3xl grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($stats as $stat)
                    @php
                        $isSimpleNumeric = preg_match('/^\d+%?$/', trim($stat['value']));
                        $numericVal = $isSimpleNumeric ? intval($stat['value']) : null;
                        $suffix = $isSimpleNumeric && str_ends_with(trim($stat['value']), '%') ? '%' : '';
                    @endphp
                    <div class="rounded-lg border border-white/10 bg-white/8 p-4">
                        @if($isSimpleNumeric)
                            <div class="text-2xl font-semibold text-white">
                                <span class="stat-value" data-target="{{ $numericVal }}">0</span><span class="stat-suffix">{{ $suffix }}</span>
                            </div>
                        @else
                            <div class="text-2xl font-semibold text-white">{{ $stat['value'] }}</div>
                        @endif
                        <div class="mt-1 text-sm leading-5 text-slate-400">{{ $stat['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="relative">
            <div class="absolute -inset-6 -z-10 rounded-[2rem] bg-gradient-to-tr from-cyan-500/8 via-slate-900/6 to-transparent blur-3xl"></div>
            <div class="rounded-2xl border border-white/6 bg-gradient-to-b from-slate-900/80 to-slate-950/70 p-4 shadow-2xl shadow-slate-950/60 backdrop-blur">
                <div class="rounded-xl border border-white/10 bg-slate-950 overflow-hidden">
                    <div class="px-4 py-6">
                        <!-- Compact illustration -->
                        <div class="mx-auto max-w-sm text-center opacity-90">
                            <svg class="mx-auto h-36 w-36 text-cyan-300" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden>
                                <rect x="2" y="14" width="60" height="36" rx="4" stroke="currentColor" stroke-opacity="0.12" stroke-width="2" />
                                <circle cx="18" cy="32" r="6" fill="currentColor" opacity="0.10" />
                                <path d="M34 22h14M34 30h14M34 38h10" stroke="currentColor" stroke-opacity="0.14" stroke-width="1.4" stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-white/8 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="h-3 w-3 rounded-full bg-rose-400"></span>
                            <span class="h-3 w-3 rounded-full bg-amber-300"></span>
                            <span class="h-3 w-3 rounded-full bg-emerald-400"></span>
                        </div>
                        <span class="text-xs font-medium text-slate-400">Learning Console</span>
                    </div>

                    <div class="grid gap-4 p-4 md:grid-cols-[.82fr_1.18fr]">
                        <aside class="rounded-lg border border-white/10 bg-white/5 p-4">
                            <div class="mb-4 flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-200">Tracks</span>
                                <span class="rounded-full bg-emerald-300/10 px-2 py-1 text-xs text-emerald-200">Active</span>
                            </div>
                            <div class="space-y-3">
                                @foreach($tracks as $track)
                                    <div class="rounded-lg bg-slate-900 px-3 py-3">
                                        <div class="text-sm font-medium text-white">{{ $track['title'] }}</div>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-800">
                                            <div class="h-full rounded-full bg-cyan-300" style="width: {{ 52 + ($loop->index * 10) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </aside>

                        <div class="space-y-4">
                            <div class="rounded-lg border border-cyan-300/20 bg-cyan-300/10 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-cyan-100">Multi-agent mode</p>
                                        <h2 class="mt-2 text-2xl font-semibold text-white">Specialized AI support for every step.</h2>
                                    </div>
                                    <span class="rounded-lg bg-cyan-300 px-3 py-2 text-sm font-bold text-slate-950">AI</span>
                                </div>
                                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                    @foreach($agents as $agent)
                                        <div class="rounded-lg border border-white/10 bg-slate-950/70 p-3">
                                            <div class="text-sm font-semibold text-white">{{ $agent['name'] }}</div>
                                            <p class="mt-2 text-xs leading-5 text-slate-400">{{ $agent['role'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                                    <p class="text-sm text-slate-400">Current quiz score</p>
                                    <div class="mt-2 text-3xl font-semibold text-white">86%</div>
                                </div>
                                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                                    <p class="text-sm text-slate-400">Learning confidence</p>
                                    <div class="mt-2 text-3xl font-semibold text-emerald-200">High</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const els = document.querySelectorAll('.stat-value');
    if(!els.length) return;

    const animateTo = (el, target, duration = 1400) => {
        let start = null;
        const from = 0;
        const step = (timestamp) => {
            if (!start) start = timestamp;
            const progress = Math.min((timestamp - start) / duration, 1);
            const value = Math.floor(progress * (target - from) + from);
            el.textContent = value;
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target;
        };
        requestAnimationFrame(step);
    };

    const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const t = parseInt(el.getAttribute('data-target') || '0', 10);
                if (!isNaN(t)) animateTo(el, t);
                obs.unobserve(el);
            }
        });
    }, { threshold: 0.5 });

    els.forEach(e => {
        e.style.display = 'inline-block';
        e.style.transform = 'translateY(6px)';
        e.style.opacity = '0';
        e.style.transition = 'transform .6s ease, opacity .6s ease';
        // small reveal observer
        const r = new IntersectionObserver((entries, ro) => {
            entries.forEach(en => {
                if (en.isIntersecting) {
                    e.style.transform = 'translateY(0)';
                    e.style.opacity = '1';
                    ro.unobserve(e);
                }
            });
        }, { threshold: 0.5 });
        r.observe(e);
        io.observe(e);
    });
});
</script>

<section class="border-y border-white/10 bg-slate-950/45">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
        <div class="max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[.2em] text-cyan-200">Learning Tracks</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Everything students need to build practical cybersecurity judgment.</h2>
        </div>
        <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            @foreach($tracks as $track)
                <article class="rounded-xl border border-white/10 bg-white/8 p-5 transition hover:border-cyan-300/40 hover:bg-white/12">
                    <div class="mb-5 grid h-11 w-11 place-items-center rounded-lg bg-cyan-300/10 text-lg font-semibold text-cyan-100">
                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                    </div>
                    <h3 class="text-lg font-semibold text-white">{{ $track['title'] }}</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-400">{{ $track['copy'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-20">
    <div class="grid gap-10 lg:grid-cols-[.85fr_1.15fr]">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[.2em] text-cyan-200">How It Works</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">A focused learning workflow from lesson to evidence.</h2>
            <p class="mt-5 text-base leading-7 text-slate-400">
                The platform is built for cybersecurity education and research, so students get a polished learning experience while instructors can review performance signals.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach($steps as $step)
                <article class="rounded-xl border border-white/10 bg-slate-900/70 p-5">
                    <span class="text-sm font-semibold text-cyan-200">{{ $step['number'] }}</span>
                    <h3 class="mt-4 text-xl font-semibold text-white">{{ $step['title'] }}</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-400">{{ $step['copy'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

<section class="bg-white/[.04]">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-20">
        <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[.2em] text-cyan-200">Featured Lessons</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Start with structured lessons and clear outcomes.</h2>
            </div>
            @auth
                <a href="{{ route('lessons.index') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">View all lessons</a>
            @else
                <a href="{{ route('register') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">Create account</a>
            @endauth
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-3">
            @forelse($featuredLessons as $lesson)
                <article class="rounded-xl border border-white/10 bg-slate-950/70 p-5">
                    <span class="rounded-full bg-cyan-300/10 px-3 py-1 text-xs font-medium text-cyan-100">Lesson {{ $loop->iteration }}</span>
                    <h3 class="mt-5 text-xl font-semibold text-white">{{ $lesson->title }}</h3>
                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-400">{{ $lesson->summary ?? $lesson->description ?? 'A practical cybersecurity lesson with AI-assisted learning support and progress tracking.' }}</p>
                </article>
            @empty
                @foreach(['Cybersecurity Foundations', 'Defensive Thinking', 'AI-Guided Assessment'] as $lessonTitle)
                    <article class="rounded-xl border border-white/10 bg-slate-950/70 p-5">
                        <span class="rounded-full bg-cyan-300/10 px-3 py-1 text-xs font-medium text-cyan-100">Preview</span>
                        <h3 class="mt-5 text-xl font-semibold text-white">{{ $lessonTitle }}</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-400">A structured learning module designed for guided study, practical examples, and measurable assessment.</p>
                    </article>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:py-20">
    <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
        <div class="rounded-2xl border border-white/10 bg-slate-900/80 p-6 sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-[.2em] text-cyan-200">Research Analytics</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-white">Designed to compare learning behavior, not just final scores.</h2>
            <p class="mt-5 max-w-2xl text-base leading-7 text-slate-400">
                Capture quiz performance, lesson progress, AI interactions, and engagement patterns to evaluate how different AI support models affect learning outcomes.
            </p>
            <div class="mt-8 grid gap-3 sm:grid-cols-3">
                @foreach(['Quiz results', 'Progress tracking', 'AI interactions'] as $metric)
                    <div class="rounded-lg border border-white/10 bg-white/5 p-4 text-sm font-medium text-slate-200">{{ $metric }}</div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-cyan-300/20 bg-cyan-300/10 p-6 sm:p-8">
            <h2 class="text-3xl font-semibold tracking-tight text-white">Ready to begin?</h2>
            <p class="mt-4 text-base leading-7 text-cyan-50/80">Create an account and start exploring lessons with AI-supported cybersecurity training.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="rounded-lg bg-white px-5 py-3 font-semibold text-slate-950 transition hover:bg-cyan-50">Join now</a>
                <a href="{{ route('login') }}" class="rounded-lg border border-white/25 px-5 py-3 font-semibold text-white transition hover:bg-white/10">Sign in</a>
            </div>
        </div>
    </div>
</section>
@endsection
