@extends ('layouts.app')

@section ('title', __('admin.users_title'))

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-hero mb-2">{{ __('admin.user_management') }}</h1>
                <p class="text-secondary">
                    {{ __('admin.account_count', ['count' => $total]) }}
                </p>
            </div>
            <form
                method="POST"
                action="{{ route('admin.users.store') }}"
                class="flex flex-wrap items-end gap-2"
            >
                @csrf
                <x-input
                    type="email"
                    name="email"
                    :label="__('admin.create_admin_account')"
                    placeholder="admin@example.com"
                    :value="old('email')"
                    required
                />
                <button type="submit" class="btn-primary rounded-md px-4 py-2">{{ __('admin.create_admin') }}</button>
            </form>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6"> {{ session('status') }} </x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6"> {{ $errors->first() }} </x-alert>
        @endif

        <form
            method="GET"
            action="{{ route('admin.users.index') }}"
            class="card mb-6 grid items-end gap-3 sm:grid-cols-[1fr_14rem_auto]"
        >
            <x-input
                name="q"
                :label="__('admin.search_accounts')"
                :placeholder="__('admin.name_or_email')"
                :value="$filters['q'] ?? null"
            />

            <div class="space-y-1">
                <label for="status" class="text-primary block text-sm font-medium">
                    {{ __('admin.account_status') }}
                </label>
                <select name="status" id="status" class="input">
                    <option value="">{{ __('admin.all_statuses') }}</option>
                    @foreach (['PENDING_VERIFICATION', 'ACTIVE', 'LOCKED', 'SUSPENDED', 'DISABLED'] as $status)
                        <option
                            value="{{ $status }}"
                            @selected (($filters['status'] ?? null) === $status)
                        >
                            {{ __('account_status.' . $status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary rounded-md px-6 py-2">{{ __('admin.filter') }}</button>
        </form>

        @if (count($users) === 0)
            <x-empty-state :message="__('admin.no_accounts')" />
        @else
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-sm">
                    <thead class="bg-surface-secondary border-default border-b">
                        <tr>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('admin.account') }}</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('admin.status') }}</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">
                                {{ __('admin.email_verified') }}
                            </th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('admin.joined') }}</th>
                            <th class="text-primary px-4 py-3 text-right font-semibold">{{ __('admin.action') }}</th>
                        </tr>
                    </thead>

                    <tbody class="divide-default divide-y">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-3">
                                    <a
                                        href="{{ route('admin.users.show', $user->id) }}"
                                        class="text-primary hover:text-accent-primary font-medium"
                                    >
                                        {{ $user->name }}
                                    </a>
                                    <div class="text-secondary text-xs">{{ $user->email }}</div>
                                </td>

                                <td class="px-4 py-3">
                                    <x-badge
                                        :variant="
                                            $user->status->value === 'ACTIVE'
        ? 'primary'
        : ($user->status->value === 'PENDING_VERIFICATION'
            ? 'default'
            : 'highlight')
                                        "
                                    >
                                        {{ __('account_status.' . $user->status->value) }}
                                    </x-badge>
                                </td>

                                <td class="text-secondary px-4 py-3">
                                    {{
                                        $user->emailVerifiedAt
                                            ? __('admin.verified')
                                            : __('admin.not_verified')
                                    }}
                                </td>

                                <td class="text-secondary px-4 py-3">
                                    {{
                                        \Carbon\Carbon::parse($user->createdAt)->format(
                                            'd/m/Y',
                                        )
                                    }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <a
                                        href="{{ route('admin.users.show', $user->id) }}"
                                        class="btn-ghost"
                                    >
                                        {{ __('admin.manage') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($lastPage > 1)
                <nav class="mt-6 flex items-center justify-between text-sm" :aria-label="__('notifications.pagination')">
                    @if ($page > 1)
                        <a
                            href="{{ route('admin.users.index', array_merge($filters, ['page' => $page - 1])) }}"
                            class="btn-ghost"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            {{ __('admin.previous') }}
                        </a>
                    @else
                        <span></span>
                    @endif

                    <span class="text-secondary">{{ __('admin.page_of', ['page' => $page, 'last' => $lastPage]) }}</span>

                    @if ($page < $lastPage)
                        <a
                            href="{{ route('admin.users.index', array_merge($filters, ['page' => $page + 1])) }}"
                            class="btn-ghost"
                        >
                            {{ __('admin.next') }} →
                        </a>
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
@endsection
