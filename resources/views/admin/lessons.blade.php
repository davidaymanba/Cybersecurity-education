@extends('layouts.admin', ['title' => __('messages.manage_lessons')])

@section('admin')
    <x-ui.page-header :kicker="__('messages.admin_content')" :title="__('messages.lessons_crud')" :lead="__('messages.lessons_crud_lead')">
        <x-ui.button type="button" icon="plus" data-modal-open="lesson-create">{{ __('messages.add_lesson') }}</x-ui.button>
    </x-ui.page-header>

    <x-ui.table class="mt-6" :caption="__('messages.lessons')">
        <x-slot:head>
            <th>#</th>
            <th>{{ __('messages.lesson_title') }}</th>
            <th class="hidden md:table-cell">{{ __('messages.difficulty') }}</th>
            <th class="hidden sm:table-cell">{{ __('messages.status') }}</th>
            <th class="hidden lg:table-cell">{{ __('messages.quizzes') }}</th>
            <th><span class="sr-only">{{ __('messages.actions') }}</span></th>
        </x-slot:head>
        @forelse($lessons as $lesson)
            <tr>
                <td class="font-mono text-xs text-slate-500">{{ $lesson->sort_order }}</td>
                <td>
                    <p class="font-medium text-white">{{ $lesson->title }}</p>
                    <p class="text-xs text-slate-500">{{ $lesson->section?->title }} · {{ $lesson->duration_minutes }} {{ __('messages.minutes_short') }}</p>
                </td>
                <td class="hidden text-slate-300 md:table-cell">{{ $lesson->difficulty }}</td>
                <td class="hidden sm:table-cell"><x-ui.badge :tone="$lesson->is_published ? 'safe' : 'amber'" dot>{{ $lesson->is_published ? __('messages.published') : __('messages.draft') }}</x-ui.badge></td>
                <td class="hidden lg:table-cell">@if($lesson->quiz)<x-ui.icon name="check" class="h-4 w-4 text-safe" /><span class="sr-only">{{ __('messages.yes') }}</span>@else<span class="text-slate-600">—</span>@endif</td>
                <td>
                    <div class="flex justify-end gap-1.5">
                        <a href="{{ route('lessons.show', ['version' => 'single', 'lesson' => $lesson]) }}" class="btn btn-quiet btn-sm btn-icon" title="{{ __('messages.preview') }}" aria-label="{{ __('messages.preview') }}: {{ $lesson->title }}"><x-ui.icon name="eye" class="h-4 w-4" /></a>
                        <button type="button" class="btn btn-ghost btn-sm" data-modal-open="lesson-{{ $lesson->id }}" aria-label="{{ __('messages.edit') }}: {{ $lesson->title }}"><x-ui.icon name="edit" class="h-4 w-4" /><span class="hidden sm:inline">{{ __('messages.edit') }}</span></button>
                        <x-ui.confirm :action="route('admin.lessons.delete', $lesson)" method="DELETE" :message="__('messages.confirm_delete_lesson')" icon="trash" :confirm-label="__('messages.delete_lesson')" aria-label="{{ __('messages.delete_lesson') }}: {{ $lesson->title }}"><span class="sr-only">{{ __('messages.delete_lesson') }}</span></x-ui.confirm>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-10 text-center text-slate-400">{{ __('messages.no_lessons') }}</td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-4">{{ $lessons->links() }}</div>

    <x-ui.modal id="lesson-create" :title="__('messages.add_lesson')" size="max-w-2xl" :open-on-load="old('_form') === 'lesson-create'">
        <x-ui.form :action="route('admin.lessons.store')" form-id="lesson-create" :busy="__('messages.saving')" class="space-y-5">
            @include('admin.partials.lesson-form', ['lesson' => null])
            <div class="flex justify-end gap-2"><x-ui.button type="button" variant="ghost" data-modal-close>{{ __('messages.cancel') }}</x-ui.button><x-ui.button icon="plus">{{ __('messages.add_lesson') }}</x-ui.button></div>
        </x-ui.form>
    </x-ui.modal>

    @foreach($lessons as $lesson)
        <x-ui.modal id="lesson-{{ $lesson->id }}" :title="$lesson->title" size="max-w-2xl" :open-on-load="old('_form') === 'lesson-'.$lesson->id">
            <x-ui.form :action="route('admin.lessons.update', $lesson)" method="PUT" :form-id="'lesson-'.$lesson->id" :busy="__('messages.saving')" class="space-y-5">
                @include('admin.partials.lesson-form', ['lesson' => $lesson])
                <div class="flex justify-end gap-2"><x-ui.button type="button" variant="ghost" data-modal-close>{{ __('messages.cancel') }}</x-ui.button><x-ui.button icon="check">{{ __('messages.save_changes') }}</x-ui.button></div>
            </x-ui.form>
        </x-ui.modal>
    @endforeach
@endsection
