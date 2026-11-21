@extends('layouts.app', ['title' => __('messages.ui_kit')])

{{-- Local-only component gallery (route registered only when APP_ENV=local). --}}
@section('content')
<div class="mx-auto max-w-7xl space-y-10 px-4 py-10 sm:px-6">
    <x-ui.page-header kicker="design system" :title="__('messages.ui_kit')" :lead="__('messages.ai_platform_tag')">
        <x-ui.button variant="ghost" icon="book">{{ __('messages.lessons') }}</x-ui.button>
        <x-ui.button icon-end="arrow">{{ __('messages.start_learning') }}</x-ui.button>
    </x-ui.page-header>

    <section class="space-y-3">
        <h2 class="text-sm text-slate-400">Buttons</h2>
        <div class="flex flex-wrap gap-2">
            <x-ui.button>{{ __('messages.save') }}</x-ui.button>
            <x-ui.button variant="safe" icon="check">{{ __('messages.submit_quiz') }}</x-ui.button>
            <x-ui.button variant="light">{{ __('messages.register') }}</x-ui.button>
            <x-ui.button variant="ghost" icon="edit">{{ __('messages.save_changes') }}</x-ui.button>
            <x-ui.button variant="quiet">{{ __('messages.cancel') }}</x-ui.button>
            <x-ui.button variant="danger" icon="trash">{{ __('messages.delete_lesson') }}</x-ui.button>
            <x-ui.button size="sm" variant="ghost" icon-end="chevron">{{ __('messages.view_all_lessons') }}</x-ui.button>
            <x-ui.button size="lg" disabled>{{ __('messages.send') }}</x-ui.button>
            <x-ui.confirm action="#" :message="__('messages.confirm_delete_lesson')" icon="trash">{{ __('messages.delete_lesson') }}</x-ui.confirm>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-stagger>
        <x-ui.stat :label="__('messages.stat_students')" :value="128" icon="users" />
        <x-ui.stat :label="__('messages.stat_completion')" :value="64" suffix="%" icon="target" tone="safe" />
        <x-ui.stat :label="__('messages.stat_avg_score')" :value="78.5" :decimals="1" suffix="%" icon="trophy" tone="amber" />
        <x-ui.stat :label="__('messages.stat_ai_chats')" :value="2048" icon="sparkles" tone="pulse" hint="sample data" />
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        <x-ui.card hud spotlight>
            <h2 class="font-semibold text-white">{{ __('messages.change_password') }}</h2>
            <form class="mt-5 space-y-4" onsubmit="event.preventDefault(); window.ui.toast(@js(__('messages.password_changed')), 'safe')">
                <x-ui.input name="name" :label="__('messages.name')" autocomplete="name" required />
                <x-ui.input name="email" type="email" :label="__('messages.email')" dir="ltr" error="{{ __('messages.invalid_credentials') }}" />
                <x-ui.input name="password" type="password" :label="__('messages.password')" :hint="__('messages.min_8_chars')" dir="ltr" />
                <x-ui.select name="difficulty" :label="__('messages.difficulty')" :options="['Beginner' => __('messages.beginner'), 'Intermediate' => __('messages.intermediate'), 'Expert' => __('messages.expert')]" />
                <x-ui.textarea name="summary" :label="__('messages.summary')" rows="3" />
                <x-ui.checkbox name="is_published" :label="__('messages.published')" :description="__('messages.approved_visible')" checked />
                <x-ui.button icon="check">{{ __('messages.save') }}</x-ui.button>
            </form>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card>
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge tone="signal" dot>{{ __('messages.active') }}</x-ui.badge>
                    <x-ui.badge tone="safe">{{ __('messages.published') }}</x-ui.badge>
                    <x-ui.badge tone="amber">{{ __('messages.draft') }}</x-ui.badge>
                    <x-ui.badge tone="pulse">{{ __('messages.multi_ai') }}</x-ui.badge>
                    <x-ui.badge tone="danger">{{ __('messages.incorrect') }}</x-ui.badge>
                    <x-ui.badge>{{ __('messages.hidden') }}</x-ui.badge>
                </div>
                <div class="mt-6 space-y-3">
                    <x-ui.progress :value="72" :label="__('messages.avg_progress')" />
                    <x-ui.progress :value="40" tone="safe" />
                    <x-ui.progress :value="90" tone="gradient" size="h-2" />
                </div>
                <div class="mt-6 flex items-center gap-6">
                    <x-ui.ring :value="82" tone="safe" :label="__('messages.score').' 82%'"><span class="font-mono text-xl font-semibold text-white">82%</span></x-ui.ring>
                    <x-ui.ring :value="35" tone="amber" :size="64" :stroke="10" />
                    <x-ui.button variant="ghost" data-modal-open="demo-modal">{{ __('messages.choose_level') }}</x-ui.button>
                </div>
            </x-ui.card>
            <x-ui.alert tone="safe" dismissible>{{ __('messages.password_changed') }}</x-ui.alert>
            <x-ui.alert tone="danger" :title="__('messages.errors_title')">{{ __('messages.invalid_credentials') }}</x-ui.alert>
            <x-ui.alert tone="amber">{{ __('messages.quiz_time_expired') }}</x-ui.alert>
        </div>
    </section>

    <section class="space-y-3">
        <x-ui.table :caption="__('messages.users')">
            <x-slot:head>
                <th>{{ __('messages.name') }}</th><th>{{ __('messages.email') }}</th><th>{{ __('messages.role') }}</th><th>{{ __('messages.joined') }}</th><th></th>
            </x-slot:head>
            @foreach([['Demo Student', 'student@cyberlearn.test', 'Student'], ['Research Admin', 'admin@cyberlearn.test', 'Admin']] as [$n, $e, $r])
                <tr>
                    <td class="font-medium text-white">{{ $n }}</td>
                    <td dir="ltr" class="text-slate-300" style="text-align: start">{{ $e }}</td>
                    <td><x-ui.badge :tone="$r === 'Admin' ? 'pulse' : 'muted'">{{ $r }}</x-ui.badge></td>
                    <td class="font-mono text-xs text-slate-400">2026-11-19</td>
                    <td class="text-end"><x-ui.button size="sm" variant="ghost" icon="key">{{ __('messages.reset_password') }}</x-ui.button></td>
                </tr>
            @endforeach
        </x-ui.table>
        {{ $paginator->links() }}
    </section>

    <x-ui.empty icon="bookmark" :title="__('messages.no_lessons')" :body="__('messages.course_library_lead')">
        <x-ui.button size="sm" href="#">{{ __('messages.browse_lessons') }}</x-ui.button>
    </x-ui.empty>

    <x-ui.modal id="demo-modal" :title="__('messages.choose_level')">
        <div class="space-y-2">
            @foreach(['beginner', 'intermediate', 'expert'] as $level)
                <label class="choice"><input type="radio" name="level" value="{{ $level }}" @checked($loop->first)><span><span class="block text-sm font-medium text-white">{{ __('messages.'.$level) }}</span><span class="text-xs text-slate-400">{{ __('messages.'.$level.'_desc') }}</span></span></label>
            @endforeach
        </div>
        <div class="mt-5 flex justify-end gap-2"><x-ui.button variant="ghost" type="button" data-modal-close>{{ __('messages.cancel') }}</x-ui.button><x-ui.button type="button" data-modal-close>{{ __('messages.save') }}</x-ui.button></div>
    </x-ui.modal>
</div>
@endsection
