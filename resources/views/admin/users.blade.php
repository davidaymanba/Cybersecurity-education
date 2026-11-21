@extends('layouts.admin', ['title' => __('messages.manage_users')])

@section('admin')
    <x-ui.page-header :kicker="__('messages.admin')" :title="__('messages.manage_users')" :lead="__('messages.users_lead')" />

    @if($temp = session('temporary_password'))
        <div class="surface hud mt-6 border-s-2 border-safe p-5" role="status" data-dismissible>
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 flex-none place-items-center rounded-xl bg-safe/10 text-safe ring-1 ring-safe/30"><x-ui.icon name="key" /></span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-white">{{ __('messages.temporary_password_title') }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-300">{{ session('status') }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <code class="rounded-lg bg-void px-3 py-2 font-mono text-base tracking-wider text-safe ring-1 ring-safe/30" dir="ltr">{{ $temp['password'] }}</code>
                        <button type="button" class="btn btn-ghost btn-sm" data-copy="{{ $temp['password'] }}"><x-ui.icon name="copy" class="h-4 w-4" />{{ __('messages.copy') }}</button>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ __('messages.temporary_password_once') }}</p>
                </div>
                <button type="button" class="btn btn-quiet btn-icon -m-1" data-dismiss aria-label="{{ __('messages.dismiss') }}"><x-ui.icon name="x" class="h-4 w-4" /></button>
            </div>
        </div>
    @endif

    <x-ui.table class="mt-6" :caption="__('messages.users')">
        <x-slot:head>
            <th>{{ __('messages.name') }}</th>
            <th>{{ __('messages.email') }}</th>
            <th>{{ __('messages.role') }}</th>
            <th class="hidden md:table-cell">{{ __('messages.activity') }}</th>
            <th class="hidden sm:table-cell">{{ __('messages.joined') }}</th>
            <th><span class="sr-only">{{ __('messages.actions') }}</span></th>
        </x-slot:head>
        @forelse($users as $user)
            @php($isAdmin = $user->role?->name === 'admin')
            <tr>
                <td>
                    <div class="flex items-center gap-2.5">
                        <span class="grid h-8 w-8 flex-none place-items-center rounded-lg text-xs font-semibold {{ $isAdmin ? 'bg-pulse/15 text-violet-200' : 'bg-signal/10 text-cyan-200' }}">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <span class="font-medium text-white">{{ $user->name }}</span>
                    </div>
                </td>
                <td class="text-slate-300" dir="ltr" style="text-align: start">{{ $user->email }}</td>
                <td><x-ui.badge :tone="$isAdmin ? 'pulse' : 'muted'">{{ $isAdmin ? __('messages.role_admin') : __('messages.role_student') }}</x-ui.badge></td>
                <td class="hidden text-xs text-slate-400 md:table-cell">{{ __('messages.user_activity', ['lessons' => $user->progress_count, 'quizzes' => $user->quiz_results_count]) }}</td>
                <td class="hidden font-mono text-xs text-slate-400 sm:table-cell">{{ $user->created_at->format('Y-m-d') }}</td>
                <td class="text-end">
                    @unless($user->is(auth()->user()))
                        <x-ui.confirm :action="route('admin.users.reset-password', $user)" variant="ghost" icon="key"
                                      :message="__('messages.confirm_reset_password').' ('.$user->email.')'" :confirm-label="__('messages.reset_password')">
                            {{ __('messages.reset_password') }}
                        </x-ui.confirm>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-10 text-center text-slate-400">{{ __('messages.no_users') }}</td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
