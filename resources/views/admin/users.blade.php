@extends('layouts.app', ['title' => __('messages.manage_users')])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <h1 class="text-3xl font-semibold">{{ __('messages.users') }}</h1>
    <div class="mt-6 overflow-hidden rounded-xl border border-white/10 bg-white/10">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-950/70 text-slate-300"><tr><th class="p-3">{{ __('messages.name') }}</th><th class="p-3">{{ __('messages.email') }}</th><th class="p-3">{{ __('messages.role') }}</th><th class="p-3">{{ __('messages.joined') }}</th><th class="p-3"></th></tr></thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="border-t border-white/10"><td class="p-3">{{ $user->name }}</td><td class="p-3">{{ $user->email }}</td><td class="p-3">{{ $user->role->label }}</td><td class="p-3">{{ $user->created_at->format('M d, Y') }}</td><td class="p-3 text-right">@unless($user->is(auth()->user()))<form method="post" action="{{ route('admin.users.reset-password', $user) }}" onsubmit="return confirm('{{ __('messages.confirm_reset_password') }}')">@csrf<button class="rounded-lg border border-white/15 px-3 py-1 text-xs hover:bg-white/10">{{ __('messages.reset_password') }}</button></form>@endunless</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</section>
@endsection
