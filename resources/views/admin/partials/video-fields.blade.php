{{-- Video create/edit fields. Expects $lessonOptions; optional $video. --}}
<x-ui.select name="lesson_id" :label="__('messages.lesson')" :options="$lessonOptions" :placeholder="isset($video) ? null : __('messages.choose_lesson')" :value="$video->lesson_id ?? null" required />
<x-ui.input name="title" dir="auto" :label="__('messages.video_title')" :value="$video->title ?? null" required maxlength="200" />
<div class="grid gap-4 sm:grid-cols-2">
    <x-ui.input name="youtube_id" :label="__('messages.youtube_id')" :value="$video->youtube_id ?? null" required maxlength="20" pattern="[A-Za-z0-9_\-]{6,20}" dir="ltr" :hint="__('messages.youtube_id_help', ['code' => 'v='])" />
    <x-ui.input name="channel_name" dir="auto" :label="__('messages.channel_name')" :value="$video->channel_name ?? null" required maxlength="120" />
</div>
<x-ui.input name="description" dir="auto" :label="__('messages.description_optional')" :value="$video->description ?? null" maxlength="1000" />
<x-ui.checkbox name="approved" :label="__('messages.approved')" :description="__('messages.approved_visible')" :checked="$video->approved ?? true" />
