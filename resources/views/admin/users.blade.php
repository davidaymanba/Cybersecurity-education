@extends('layouts.app', ['title' => 'Manage Users'])

@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <h1 class="text-3xl font-semibold">Users</h1>
    <div class="mt-6 overflow-hidden rounded-xl border border-white/10 bg-white/10">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-950/70 text-slate-300"><tr><th class="p-3">Name</th><th class="p-3">Email</th><th class="p-3">Role</th><th class="p-3">Joined</th></tr></thead>
            <tbody>
                @foreach($users as $user)
                    <tr class="border-t border-white/10"><td class="p-3">{{ $user->name }}</td><td class="p-3">{{ $user->email }}</td><td class="p-3">{{ $user->role->label }}</td><td class="p-3">{{ $user->created_at->format('M d, Y') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
</section>
@endsection
