@extends('layouts.app')

@section('title', 'Users — Admin Console')

@section('content')
<div class="container-custom section-spacing">
    <div class="mb-8">
        <h1 class="text-hero mb-2">Users</h1>
        <p class="text-secondary">{{ $total }} {{ \Illuminate\Support\Str::plural('account', $total) }}</p>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="card mb-6 grid sm:grid-cols-[1fr_14rem_auto] gap-3 items-end">
        <x-input name="q" label="Search" placeholder="Name or email" :value="$filters['q'] ?? null" />
        <div class="space-y-1">
            <label for="status" class="block text-sm font-medium text-primary">Status</label>
            <select name="status" id="status" class="input">
                <option value="">All</option>
                @foreach(['PENDING_VERIFICATION', 'ACTIVE', 'LOCKED', 'SUSPENDED', 'DISABLED'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    @if(count($users) === 0)
        <x-empty-state message="No users match these filters" />
    @else
        <div class="card p-0 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-secondary border-b border-default">
                    <tr>
                        <th class="text-left py-3 px-4 font-semibold text-primary">User</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Status</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Verified</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default">
                    @foreach($users as $user)
                        <tr>
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="font-medium text-primary hover:text-accent-primary">{{ $user->name }}</a>
                                <div class="text-xs text-secondary">{{ $user->email }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :variant="$user->status->value === 'ACTIVE' ? 'primary' : ($user->status->value === 'PENDING_VERIFICATION' ? 'default' : 'highlight')">
                                    {{ $user->status->value }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4 text-secondary">{{ $user->emailVerifiedAt ? 'Yes' : 'No' }}</td>
                            <td class="py-3 px-4 text-secondary">{{ \Carbon\Carbon::parse($user->createdAt)->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($lastPage > 1)
            <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Pagination">
                @if($page > 1)
                    <a href="{{ route('admin.users.index', array_merge($filters, ['page' => $page - 1])) }}" class="btn-ghost">← Previous</a>
                @else
                    <span></span>
                @endif
                <span class="text-secondary">Page {{ $page }} of {{ $lastPage }}</span>
                @if($page < $lastPage)
                    <a href="{{ route('admin.users.index', array_merge($filters, ['page' => $page + 1])) }}" class="btn-ghost">Next →</a>
                @else
                    <span></span>
                @endif
            </nav>
        @endif
    @endif
</div>
@endsection
