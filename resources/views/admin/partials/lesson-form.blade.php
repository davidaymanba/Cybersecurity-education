{{-- Lesson create/edit fields. Expects $sections and optional $lesson. --}}
@php
    $difficulties = ['Beginner' => __('messages.beginner'), 'Intermediate' => __('messages.intermediate'), 'Expert' => __('messages.expert')];
    if (isset($lesson) && $lesson->difficulty && ! isset($difficulties[$lesson->difficulty])) {
        $difficulties[$lesson->difficulty] = $lesson->difficulty;
    }
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    <x-ui.select name="section_id" :label="__('messages.section')" :options="$sections->pluck('title', 'id')->all()" :value="$lesson->section_id ?? null" required class="sm:col-span-2" />
    <x-ui.input name="title" dir="auto" :label="__('messages.lesson_title')" :value="$lesson->title ?? null" required maxlength="160" class="sm:col-span-2" />
    <x-ui.select name="difficulty" :label="__('messages.difficulty')" :options="$difficulties" :value="$lesson->difficulty ?? 'Beginner'" required />
    <div class="grid grid-cols-2 gap-4">
        <x-ui.input name="duration_minutes" type="number" min="1" :label="__('messages.duration_minutes')" :value="$lesson->duration_minutes ?? 20" required />
        <x-ui.input name="sort_order" type="number" min="0" :label="__('messages.sort_order')" :value="$lesson->sort_order ?? 0" />
    </div>
    <x-ui.textarea name="summary" dir="auto" :label="__('messages.summary')" :value="$lesson->summary ?? null" rows="3" required class="sm:col-span-2" />
    <x-ui.textarea name="content" :label="__('messages.content')" :value="$lesson->content ?? null" rows="8" required class="sm:col-span-2" dir="ltr" :hint="__('messages.content_placeholder')" />
    <x-ui.checkbox name="is_published" :label="__('messages.published')" :description="__('messages.published_hint')" :checked="$lesson->is_published ?? true" class="sm:col-span-2" />
</div>
