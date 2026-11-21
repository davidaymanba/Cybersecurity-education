@extends('layouts.admin', ['title' => __('messages.manage_videos')])

@php($lessonOptions = $lessons->pluck('title', 'id')->all())

@section('admin')
    <x-ui.page-header :kicker="__('messages.admin_content')" :title="__('messages.video_resources')" :lead="__('messages.videos_lead')">
        <x-ui.button type="button" icon="plus" data-modal-open="video-create">{{ __('messages.add_video') }}</x-ui.button>
    </x-ui.page-header>

    <x-ui.table class="mt-6" :caption="__('messages.video_resources')">
        <x-slot:head>
            <th>{{ __('messages.video_title') }}</th>
            <th class="hidden md:table-cell">{{ __('messages.lesson') }}</th>
            <th class="hidden sm:table-cell">{{ __('messages.status') }}</th>
            <th><span class="sr-only">{{ __('messages.actions') }}</span></th>
        </x-slot:head>
        @forelse($videos as $video)
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <img src="https://img.youtube.com/vi/{{ $video->youtube_id }}/mqdefault.jpg" alt="" loading="lazy" width="96" height="54" class="hidden h-[54px] w-24 flex-none rounded-md bg-white/5 object-cover ring-1 ring-white/10 sm:block">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-white">{{ $video->title }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $video->channel_name }} · <span class="font-mono" dir="ltr">{{ $video->youtube_id }}</span></p>
                        </div>
                    </div>
                </td>
                <td class="hidden max-w-[14rem] truncate text-slate-300 md:table-cell">{{ $video->lesson?->title ?? __('messages.deleted_lesson') }}</td>
                <td class="hidden sm:table-cell"><x-ui.badge :tone="$video->approved ? 'safe' : 'amber'" dot>{{ $video->approved ? __('messages.approved') : __('messages.hidden') }}</x-ui.badge></td>
                <td>
                    <div class="flex justify-end gap-1.5">
                        <a href="https://www.youtube.com/watch?v={{ $video->youtube_id }}" target="_blank" rel="noopener noreferrer" class="btn btn-quiet btn-sm" aria-label="{{ __('messages.open_on_youtube') }}: {{ $video->title }}" title="{{ __('messages.open_on_youtube') }}"><x-ui.icon name="play" class="h-4 w-4" /></a>
                        <button type="button" class="btn btn-ghost btn-sm" data-modal-open="video-{{ $video->id }}" aria-label="{{ __('messages.edit') }}: {{ $video->title }}"><x-ui.icon name="edit" class="h-4 w-4" /><span class="hidden sm:inline">{{ __('messages.edit') }}</span></button>
                        <x-ui.confirm :action="route('admin.videos.delete', $video)" method="DELETE" :message="__('messages.confirm_delete_video')" icon="trash" :confirm-label="__('messages.delete_video')" aria-label="{{ __('messages.delete_video') }}: {{ $video->title }}"><span class="sr-only">{{ __('messages.delete_video') }}</span></x-ui.confirm>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="py-10 text-center text-slate-400">{{ __('messages.no_videos') }}</td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-4">{{ $videos->links() }}</div>

    <x-ui.modal id="video-create" :title="__('messages.add_video')" :open-on-load="old('_form') === 'video-create'">
        <x-ui.form :action="route('admin.videos.store')" form-id="video-create" :busy="__('messages.saving')" class="space-y-4">
            @include('admin.partials.video-fields', ['video' => null])
            <div class="flex justify-end gap-2"><x-ui.button type="button" variant="ghost" data-modal-close>{{ __('messages.cancel') }}</x-ui.button><x-ui.button icon="plus">{{ __('messages.add_video') }}</x-ui.button></div>
        </x-ui.form>
    </x-ui.modal>

    @foreach($videos as $video)
        <x-ui.modal id="video-{{ $video->id }}" :title="$video->title" :open-on-load="old('_form') === 'video-'.$video->id">
            <x-ui.form :action="route('admin.videos.update', $video)" method="PUT" :form-id="'video-'.$video->id" :busy="__('messages.saving')" class="space-y-4">
                @include('admin.partials.video-fields', ['video' => $video])
                <div class="flex justify-end gap-2"><x-ui.button type="button" variant="ghost" data-modal-close>{{ __('messages.cancel') }}</x-ui.button><x-ui.button icon="check">{{ __('messages.save_changes') }}</x-ui.button></div>
            </x-ui.form>
        </x-ui.modal>
    @endforeach
@endsection
