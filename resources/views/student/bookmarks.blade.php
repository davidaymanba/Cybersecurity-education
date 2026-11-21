@extends('layouts.app', ['title' => __('messages.bookmarks')])

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
    <x-ui.page-header :kicker="__('messages.lessons')" :title="__('messages.bookmarks')" :lead="__('messages.bookmarks_lead')">
        <x-ui.button variant="ghost" icon="book" :href="route('lessons.index')">{{ __('messages.browse_lessons') }}</x-ui.button>
    </x-ui.page-header>

    <div class="mt-10">
        @if($lessons->isEmpty())
            <x-ui.empty icon="bookmark" :title="__('messages.no_bookmarks')" :body="__('messages.no_bookmarks_hint')">
                <x-ui.button size="sm" :href="route('lessons.index')">{{ __('messages.browse_lessons') }}</x-ui.button>
            </x-ui.empty>
        @else
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3" data-stagger>
                @foreach($lessons as $lesson)
                    <x-lesson-card :lesson="$lesson" :progress="$progress[$lesson->id] ?? 0" bookmarked />
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
