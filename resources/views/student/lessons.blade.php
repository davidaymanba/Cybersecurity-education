@extends('layouts.app', ['title' => 'Lessons'])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <h1 class="text-3xl font-semibold text-white">Cybersecurity course library</h1>
    <p class="mt-2 text-slate-300">Choose either research condition when opening a lesson.</p>
    <div class="mt-8 space-y-6">
        @foreach($sections as $section)
            <section class="rounded-xl border border-white/10 bg-white/10 p-5">
                <h2 class="text-xl font-semibold">{{ $section->title }}</h2>
                <p class="mt-1 text-sm text-slate-300">{{ $section->description }}</p>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach($section->lessons as $lesson)
                        <article class="rounded-lg border border-white/10 bg-slate-950/60 p-4">
                            <p class="text-xs text-cyan-200">{{ $lesson->difficulty }} · {{ $lesson->duration_minutes }} min</p>
                            <h3 class="mt-2 font-semibold">{{ $lesson->title }}</h3>
                            <p class="mt-2 line-clamp-3 text-sm text-slate-300">{{ $lesson->summary }}</p>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <a class="rounded-lg bg-cyan-400 px-3 py-2 text-center text-sm font-semibold text-slate-950" href="{{ route('lessons.show', ['version' => 'single', 'lesson' => $lesson]) }}">Single AI</a>
                                <a class="rounded-lg border border-white/15 px-3 py-2 text-center text-sm font-semibold" href="{{ route('lessons.show', ['version' => 'multi', 'lesson' => $lesson]) }}">Multi AI</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</section>
@endsection
