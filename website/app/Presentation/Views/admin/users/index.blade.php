@extends ('layouts.app')

@section ('title', 'Users — Admin Console')

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-hero mb-2">User management</h1>
                <p class="text-secondary">
                    {{ $total }} {{
                        \Illuminate\Support\Str::plural(
                            'account',
                            $total,
                        )
                    }}
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
                    label="Create admin account"
                    placeholder="admin@example.com"
                    :value="old('email')"
                    required
                />
                <button type="submit" class="btn-primary rounded-md px-4 py-2">Create admin</button>
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
                label="Search accounts"
                placeholder="Name or email"
                :value="$filters['q'] ?? null"
            />

            <div class="space-y-1">
                <label for="status" class="text-primary block text-sm font-medium">
                    Account status
                </label>
                <select name="status" id="status" class="input">
                    <option value="">All statuses</option>
                    @foreach (['PENDING_VERIFICATION', 'ACTIVE', 'LOCKED', 'SUSPENDED', 'DISABLED'] as $status)
                        <option
                            value="{{ $status }}"
                            @selected (($filters['status'] ?? null) === $status)
                        >
                            {{ str_replace('_', ' ', $status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary my-auto rounded-md px-6 py-2">Filter</button>
        </form>

        @if (count($users) === 0)
            <x-empty-state message="No accounts match these filters." />
        @else
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-sm">
                    <thead class="bg-surface-secondary border-default border-b">
                        <tr>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Account</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Status</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">
                                Email verified
                            </th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Joined</th>
                            <th class="text-primary px-4 py-3 text-right font-semibold">Action</th>
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
                                        {{
                                            str_replace(
                                                '_',
                                                ' ',
                                                $user->status->value,
                                            )
                                        }}
                                    </x-badge>
                                </td>

                                <td class="text-secondary px-4 py-3">
                                    {{
                                        $user->emailVerifiedAt
                                            ? 'Verified'
                                            : 'Not verified'
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
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($lastPage > 1)
                <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Pagination">
                    @if ($page > 1)
                        <a
                            href="{{ route('admin.users.index', array_merge($filters, ['page' => $page - 1])) }}"
                            class="btn-ghost"
                        >
                            ← Previous
                        </a>
                    @else
                        <span></span>
                    @endif

                    <span class="text-secondary">Page {{ $page }} of {{ $lastPage }}</span>

                    @if ($page < $lastPage)
                        <a
                            href="{{ route('admin.users.index', array_merge($filters, ['page' => $page + 1])) }}"
                            class="btn-ghost"
                        >
                            Next →
                        </a>
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
@endsection
