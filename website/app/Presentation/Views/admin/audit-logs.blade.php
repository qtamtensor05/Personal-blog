@extends ('layouts.app')

@section ('title', 'Audit Logs — Admin Console')

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8">
            <h1 class="text-hero mb-2">Audit Logs</h1>
            <p class="text-secondary">Administrative actions, newest first.</p>
        </div>

        @if (count($logs) === 0)
            <x-empty-state message="No admin actions recorded yet" />
        @else
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-sm">
                    <thead class="bg-surface-secondary border-default border-b">
                        <tr>
                            <th class="text-primary px-4 py-3 text-left font-semibold">When</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Admin</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Action</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Resource</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Change</th>
                        </tr>
                    </thead>
                    <tbody class="divide-default divide-y align-top">
                        @foreach ($logs as $log)
                            <tr>
                                <td class="text-secondary px-4 py-3 whitespace-nowrap">
                                    {{
                                        \Carbon\Carbon::parse($log->created_at)->format(
                                            'd/m/Y H:i',
                                        )
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $log->actor_email }}
                                    @if ($log->ip_address)
                                        <div class="text-secondary text-xs">
                                            {{ $log->ip_address }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-xs">{{ $log->action }}</td>
                                <td class="px-4 py-3">
                                    {{ $log->resource_type }} #{{ $log->resource_id }}
                                    @if ($log->resource_type === 'user' && $log->resource_id)
                                        <a
                                            href="{{ route('admin.users.show', (int) $log->resource_id) }}"
                                            class="text-accent-primary block text-xs hover:underline"
                                            >Open</a
                                        >
                                    @endif
                                </td>
                                <td
                                    class="text-secondary max-w-md px-4 py-3 font-mono text-xs break-words"
                                >
                                    @if ($log->before_data)
                                        <div>before: {{ $log->before_data }}</div>
                                    @endif
                                    @if ($log->after_data)
                                        <div>after: {{ $log->after_data }}</div>
                                    @endif
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
                            href="{{ route('admin.audit-logs', ['page' => $page - 1]) }}"
                            class="btn-ghost"
                            >← Newer</a
                        >
                    @else
                        <span></span>
                    @endif
                    <span class="text-secondary">Page {{ $page }} of {{ $lastPage }}</span>
                    @if ($page < $lastPage)
                        <a
                            href="{{ route('admin.audit-logs', ['page' => $page + 1]) }}"
                            class="btn-ghost"
                            >Older →</a
                        >
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
@endsection
