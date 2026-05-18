@extends('layouts.app', ['title' => 'CyberLearn AI'])

@section('content')
<section class="mx-auto grid min-h-[calc(100vh-68px)] max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.05fr_.95fr]">
    <div>
        <p class="mb-4 inline-flex rounded-full border border-cyan-300/30 bg-cyan-300/10 px-3 py-1 text-sm text-cyan-100">Academic platform for AI-assisted cybersecurity learning</p>
        <h1 class="max-w-3xl text-4xl font-semibold leading-tight tracking-tight text-white sm:text-6xl">Cybersecurity education with single-agent and multi-agent AI modes.</h1>
        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">Compare how one tutor versus specialized AI agents affects learning progress, quiz performance, focus, and engagement across the same lessons and assessments.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('register') }}" class="rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950 shadow-xl shadow-cyan-500/20">Start learning</a>
            <a href="{{ route('login') }}" class="rounded-lg border border-white/15 px-5 py-3 font-semibold text-white hover:bg-white/10">Research login</a>
        </div>
    </div>
    <div class="rounded-xl border border-white/10 bg-white/10 p-4 shadow-2xl shadow-slate-950/50 backdrop-blur">
        <div class="aspect-[4/3] rounded-lg bg-slate-900 p-5">
            <div class="grid h-full grid-cols-[.8fr_1.2fr] gap-4">
                <aside class="rounded-lg bg-slate-950/80 p-4">
                    <div class="mb-4 h-3 w-24 rounded bg-cyan-300/70"></div>
                    @foreach(['Network Security','Threat Modeling','Incident Response','Secure Coding'] as $item)
                        <div class="mb-3 rounded-md bg-white/8 px-3 py-2 text-xs text-slate-200">{{ $item }}</div>
                    @endforeach
                </aside>
                <div class="space-y-4">
                    <div class="rounded-lg bg-white/10 p-4">
                        <div class="mb-3 h-4 w-44 rounded bg-white/40"></div>
                        <div class="space-y-2">
                            <div class="h-2 rounded bg-slate-500/60"></div>
                            <div class="h-2 w-5/6 rounded bg-slate-500/60"></div>
                            <div class="h-2 w-3/4 rounded bg-slate-500/60"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach(['Guide','Tutor','Video'] as $agent)
                            <div class="rounded-lg border border-cyan-300/20 bg-cyan-300/10 p-3 text-center text-xs text-cyan-50">{{ $agent }}</div>
                        @endforeach
                    </div>
                    <div class="rounded-lg bg-emerald-300/10 p-4 text-sm text-emerald-50">Quiz score and AI usage metrics captured for research analytics.</div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
