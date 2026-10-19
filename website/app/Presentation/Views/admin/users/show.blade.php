@extends ('layouts.app')

@section ('title', $user->name . ' — Admin Console')

@php
    $restricted = in_array($user->status->value, ['LOCKED', 'SUSPENDED', 'DISABLED'], true);
@endphp

@section ('content')
    <div class="container-custom section-spacing max-w-4xl">
        <div class="mb-8">
            <a
                href="{{ route('admin.users.index') }}"
                class="text-secondary hover:text-accent-primary text-sm"
            >
                <i class="fa-solid fa-arrow-left"></i>
                Users
            </a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-hero">{{ $user->name }}</h1>
                <x-badge
                    :variant="$user->status->value === 'ACTIVE' ? 'primary' : 'highlight'"
                    >{{ $user->status->value }}</x-badge
                >
            </div>
            <p class="text-secondary">{{ $user->email }}</p>
            <p class="text-secondary mt-1 text-sm">
                Joined {{
                    \Carbon\Carbon::parse($user->createdAt)->format(
                        'd/m/Y',
                    )
                }} · Email {{
                    $user->emailVerifiedAt
                        ? 'verified'
                        : 'not verified'
                }} · Last login {{
                    $user->lastLoginAt
                        ? \Carbon\Carbon::parse($user->lastLoginAt)->diffForHumans()
                        : 'never'
                }}
            </p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        @if ($isSelf)
            <x-alert type="info" class="mb-6"
                >This is your own account. Changes to it must be made by another admin.</x-alert
            >
        @endif

        <div class="grid gap-6 md:grid-cols-2">
            {{-- Account restrictions --}}
            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">Account access</h2>

                @foreach ($user->activeRestrictions as $restriction)
                    <div class="bg-surface-secondary rounded-md p-3 text-sm">
                        <div class="text-primary font-medium">{{ $restriction['kind'] }}</div>
                        <div class="text-secondary">{{ $restriction['reason'] }}</div>
                        <div class="text-secondary mt-1 text-xs">
                            Since {{
                                \Carbon\Carbon::parse($restriction['starts_at'])->format(
                                    'd/m/Y H:i',
                                )
                            }}
                            @if ($restriction['ends_at'])
                                · until{{
                                    \Carbon\Carbon::parse($restriction['ends_at'])->format(
                                        'd/m/Y H:i',
                                    )
                                }}
                            @endif
                        </div>
                    </div>
                @endforeach

                @unless ($isSelf)
                    @if ($restricted)
                        <form
                            method="POST"
                            action="{{ route('admin.users.lift', $user->id) }}"
                            class="space-y-3"
                        >
                            @csrf
                            @method ('DELETE')
                            <x-textarea
                                name="reason"
                                label="Reason for lifting"
                                rows="2"
                                required
                            />
                            <button type="submit" class="btn-primary rounded-md px-4 py-2">
                                Lift restriction
                            </button>
                        </form>
                    @else
                        <form
                            method="POST"
                            action="{{ route('admin.users.restrict', $user->id) }}"
                            class="space-y-3"
                            data-modal-confirm-form
                            data-confirm-title="Restrict account"
                            data-confirm-message="Restrict this account? The user will be signed out immediately."
                        >
                            @csrf
                            <div class="space-y-1">
                                <label for="kind" class="text-primary block text-sm font-medium"
                                    >Action</label
                                >
                                <select name="kind" id="kind" class="input" required>
                                    <option value="LOCK">Lock (temporary)</option>
                                    <option value="SUSPEND">Suspend</option>
                                    <option value="DISABLE">Disable (no end date)</option>
                                </select>
                            </div>
                            <x-input
                                type="datetime-local"
                                name="ends_at"
                                label="Ends at (optional)"
                            />
                            <x-textarea name="reason" label="Reason" rows="2" required />
                            <button type="submit" class="btn-primary rounded-md px-4 py-2">
                                Apply restriction
                            </button>
                        </form>
                    @endif
                @endunless
            </section>

            {{-- Platform roles --}}
            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">Platform roles</h2>

                @if (count($user->roles) === 0)
                    <p class="text-secondary text-sm">No platform roles.</p>
                @else
                    <ul class="divide-default divide-y">
                        @foreach ($user->roles as $role)
                            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                <span>
                                    <span
                                        class="text-primary font-medium"
                                        >{{ $role['name'] }}</span
                                    >
                                    <span
                                        class="text-secondary font-mono text-xs"
                                        >{{ $role['code'] }}</span
                                    >
                                    @if ($role['expires_at'])
                                        <span class="text-secondary block text-xs"
                                            >expires {{
                                                \Carbon\Carbon::parse($role['expires_at'])->format(
                                                    'd/m/Y',
                                                )
                                            }}</span
                                        >
                                    @endif
                                </span>
                                @if ($canManageRoles && !$isSelf)
                                    <form
                                        method="POST"
                                        action="{{ route('admin.users.roles.revoke', [$user->id, $role['role_id']]) }}"
                                        data-modal-confirm-form
                                        data-confirm-title="Revoke role"
                                        data-confirm-message="Revoke this role?"
                                    >
                                        @csrf
                                        @method ('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">
                                            Revoke
                                        </button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($canManageRoles && !$isSelf && count($roles) > 0)
                    <form
                        method="POST"
                        action="{{ route('admin.users.roles.grant', $user->id) }}"
                        class="border-default space-y-3 border-t pt-4"
                    >
                        @csrf
                        <div class="space-y-1">
                            <label for="role_id" class="text-primary block text-sm font-medium"
                                >Grant role</label
                            >
                            <select name="role_id" id="role_id" class="input" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-input type="date" name="expires_at" label="Expires (optional)" />
                        <button type="submit" class="btn-primary rounded-md px-4 py-2">
                            Grant
                        </button>
                    </form>
                @endif
            </section>

            {{-- Individual permission overrides --}}
            <section class="card space-y-4">
                <h2 class="text-primary text-lg font-semibold">Individual permissions</h2>

                @if (count($permissionOverrides) > 0)
                    <ul class="divide-default divide-y">
                        @foreach ($permissionOverrides as $override)
                            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                <span class="text-primary font-mono">{{ $override['code'] }}</span>
                                <x-badge
                                    :variant="$override['effect'] === 'GRANT' ? 'primary' : 'highlight'"
                                >
                                    {{ $override['effect'] }}
                                </x-badge>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-secondary text-sm">No individual permission overrides.</p>
                @endif

                @if ($canManageRoles && !$isSelf && count($permissions) > 0)
                    <form
                        method="POST"
                        action="{{ route('admin.users.permissions.set', $user->id) }}"
                        class="border-default space-y-3 border-t pt-4"
                    >
                        @csrf
                        <div class="space-y-1">
                            <label
                                for="permission_id"
                                class="text-primary block text-sm font-medium"
                                >Permission</label
                            >
                            <select name="permission_id" id="permission_id" class="input" required>
                                @foreach ($permissions as $permission)
                                    <option value="{{ $permission['id'] }}">
                                        {{ $permission['code'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label
                                for="permission_effect"
                                class="text-primary block text-sm font-medium"
                                >Action</label
                            >
                            <select name="effect" id="permission_effect" class="input" required>
                                <option value="GRANT">Grant permission</option>
                                <option value="DENY">Deny permission</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary rounded-md px-4 py-2">
                            Update permission
                        </button>
                    </form>
                @endif
            </section>
        </div>
    </div>
@endsection
