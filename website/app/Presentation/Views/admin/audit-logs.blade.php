@extends('layouts.app')

@section('title', 'Audit Logs — Admin Console')

@section('content')
<div class="container-custom section-spacing">
    <div class="mb-8">
        <h1 class="text-hero mb-2">Audit Logs</h1>
        <p class="text-secondary">Administrative actions, newest first.</p>
    </div>

    @if(count($logs) === 0)
        <x-empty-state message="No admin actions recorded yet" />
    @else
        <div class="card p-0 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-secondary border-b border-default">
                    <tr>
                        <th class="text-left py-3 px-4 font-semibold text-primary">When</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Admin</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Action</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Resource</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Change</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default align-top">
                    @foreach($logs as $log)
                        <tr>
                            <td class="py-3 px-4 text-secondary whitespace-nowrap">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="py-3 px-4">
                                {{ $log->actor_email }}
                                @if($log->ip_address)<div class="text-xs text-secondary">{{ $log->ip_address }}</div>@endif
                            </td>
                            <td class="py-3 px-4 font-mono text-xs">{{ $log->action }}</td>
                            <td class="py-3 px-4">
                                {{ $log->resource_type }} #{{ $log->resource_id }}
                                @if($log->resource_type === 'user' && $log->resource_id)
                                    <a href="{{ route('admin.users.show', (int) $log->resource_id) }}" class="block text-xs text-accent-primary hover:underline">Open</a>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono text-xs text-secondary max-w-md break-words">
                                @if($log->before_data)<div>before: {{ $log->before_data }}</div>@endif
                                @if($log->after_data)<div>after: {{ $log->after_data }}</div>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($lastPage > 1)
            <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Pagination">
                @if($page > 1)
                    <a href="{{ route('admin.audit-logs', ['page' => $page - 1]) }}" class="btn-ghost">← Newer</a>
                @else
                    <span></span>
                @endif
                <span class="text-secondary">Page {{ $page }} of {{ $lastPage }}</span>
                @if($page < $lastPage)
                    <a href="{{ route('admin.audit-logs', ['page' => $page + 1]) }}" class="btn-ghost">Older →</a>
                @else
                    <span></span>
                @endif
            </nav>
        @endif
    @endif
</div>
@endsection
