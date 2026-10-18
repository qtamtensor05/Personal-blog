@extends('layouts.app')

@section('title', $user->name.' — Admin Console')

@php
    $restricted = in_array($user->status->value, ['LOCKED', 'SUSPENDED', 'DISABLED'], true);
@endphp

@section('content')
<div class="container-custom section-spacing max-w-4xl">
    <div class="mb-8">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-secondary hover:text-accent-primary">← Users</a>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-hero">{{ $user->name }}</h1>
            <x-badge :variant="$user->status->value === 'ACTIVE' ? 'primary' : 'highlight'">{{ $user->status->value }}</x-badge>
        </div>
        <p class="text-secondary">{{ $user->email }}</p>
        <p class="text-sm text-secondary mt-1">
            Joined {{ \Carbon\Carbon::parse($user->createdAt)->format('d/m/Y') }}
            · Email {{ $user->emailVerifiedAt ? 'verified' : 'not verified' }}
            · Last login {{ $user->lastLoginAt ? \Carbon\Carbon::parse($user->lastLoginAt)->diffForHumans() : 'never' }}
        </p>
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    @if($isSelf)
        <x-alert type="info" class="mb-6">This is your own account. Changes to it must be made by another admin.</x-alert>
    @endif

    <div class="grid md:grid-cols-2 gap-6">
        {{-- Account restrictions --}}
        <section class="card space-y-4">
            <h2 class="text-lg font-semibold text-primary">Account access</h2>

            @foreach($user->activeRestrictions as $restriction)
                <div class="rounded-md bg-surface-secondary p-3 text-sm">
                    <div class="font-medium text-primary">{{ $restriction['kind'] }}</div>
                    <div class="text-secondary">{{ $restriction['reason'] }}</div>
                    <div class="text-xs text-secondary mt-1">
                        Since {{ \Carbon\Carbon::parse($restriction['starts_at'])->format('d/m/Y H:i') }}
                        @if($restriction['ends_at']) · until {{ \Carbon\Carbon::parse($restriction['ends_at'])->format('d/m/Y H:i') }} @endif
                    </div>
                </div>
            @endforeach

            @unless($isSelf)
                @if($restricted)
                    <form method="POST" action="{{ route('admin.users.lift', $user->id) }}" class="space-y-3">
                        @csrf
                        @method('DELETE')
                        <x-textarea name="reason" label="Reason for lifting" rows="2" required />
                        <button type="submit" class="btn-primary py-2 px-4 rounded-md">Lift restriction</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.users.restrict', $user->id) }}" class="space-y-3"
                          data-modal-confirm-form data-confirm-title="Restrict account" data-confirm-message="Restrict this account? The user will be signed out immediately.">
                        @csrf
                        <div class="space-y-1">
                            <label for="kind" class="block text-sm font-medium text-primary">Action</label>
                            <select name="kind" id="kind" class="input" required>
                                <option value="LOCK">Lock (temporary)</option>
                                <option value="SUSPEND">Suspend</option>
                                <option value="DISABLE">Disable (no end date)</option>
                            </select>
                        </div>
                        <x-input type="datetime-local" name="ends_at" label="Ends at (optional)" />
                        <x-textarea name="reason" label="Reason" rows="2" required />
                        <button type="submit" class="btn-primary py-2 px-4 rounded-md">Apply restriction</button>
                    </form>
                @endif
            @endunless
        </section>

        {{-- Platform roles --}}
        <section class="card space-y-4">
            <h2 class="text-lg font-semibold text-primary">Platform roles</h2>

            @if(count($user->roles) === 0)
                <p class="text-sm text-secondary">No platform roles.</p>
            @else
                <ul class="divide-y divide-default">
                    @foreach($user->roles as $role)
                        <li class="py-2 flex items-center justify-between gap-3 text-sm">
                            <span>
                                <span class="font-medium text-primary">{{ $role['name'] }}</span>
                                <span class="text-xs text-secondary font-mono">{{ $role['code'] }}</span>
                                @if($role['expires_at'])
                                    <span class="block text-xs text-secondary">expires {{ \Carbon\Carbon::parse($role['expires_at'])->format('d/m/Y') }}</span>
                                @endif
                            </span>
                            @if($canManageRoles && ! $isSelf)
                                <form method="POST" action="{{ route('admin.users.roles.revoke', [$user->id, $role['role_id']]) }}"
                                      data-modal-confirm-form data-confirm-title="Revoke role" data-confirm-message="Revoke this role?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline">Revoke</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($canManageRoles && ! $isSelf && count($roles) > 0)
                <form method="POST" action="{{ route('admin.users.roles.grant', $user->id) }}" class="space-y-3 border-t border-default pt-4">
                    @csrf
                    <div class="space-y-1">
                        <label for="role_id" class="block text-sm font-medium text-primary">Grant role</label>
                        <select name="role_id" id="role_id" class="input" required>
                            @foreach($roles as $role)
                                <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-input type="date" name="expires_at" label="Expires (optional)" />
                    <button type="submit" class="btn-primary py-2 px-4 rounded-md">Grant</button>
                </form>
            @endif
        </section>

        {{-- Individual permission overrides --}}
        <section class="card space-y-4">
            <h2 class="text-lg font-semibold text-primary">Individual permissions</h2>

            @if(count($permissionOverrides) > 0)
                <ul class="divide-y divide-default">
                    @foreach($permissionOverrides as $override)
                        <li class="py-2 flex items-center justify-between gap-3 text-sm">
                            <span class="font-mono text-primary">{{ $override['code'] }}</span>
                            <x-badge :variant="$override['effect'] === 'GRANT' ? 'primary' : 'highlight'">
                                {{ $override['effect'] }}
                            </x-badge>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-secondary">No individual permission overrides.</p>
            @endif

            @if($canManageRoles && ! $isSelf && count($permissions) > 0)
                <form method="POST" action="{{ route('admin.users.permissions.set', $user->id) }}" class="space-y-3 border-t border-default pt-4">
                    @csrf
                    <div class="space-y-1">
                        <label for="permission_id" class="block text-sm font-medium text-primary">Permission</label>
                        <select name="permission_id" id="permission_id" class="input" required>
                            @foreach($permissions as $permission)
                                <option value="{{ $permission['id'] }}">{{ $permission['code'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="permission_effect" class="block text-sm font-medium text-primary">Action</label>
                        <select name="effect" id="permission_effect" class="input" required>
                            <option value="GRANT">Grant permission</option>
                            <option value="DENY">Deny permission</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary py-2 px-4 rounded-md">Update permission</button>
                </form>
            @endif
        </section>
    </div>
</div>
@endsection
