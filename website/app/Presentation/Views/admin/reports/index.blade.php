@extends('layouts.app')

@section('title', 'Content Reports — Admin Console')

@section('content')
<div class="container-custom section-spacing">
    <div class="mb-8">
        <h1 class="text-hero mb-2">Content Reports</h1>
        <p class="text-secondary">Reports from readers about blogs and posts.</p>
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="card mb-6 flex flex-wrap items-end gap-3">
        <div class="space-y-1">
            <label for="status" class="block text-sm font-medium text-primary">Status</label>
            <select name="status" id="status" class="input">
                <option value="">All</option>
                @foreach(['OPEN', 'REVIEWING', 'RESOLVED', 'DISMISSED'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-secondary">Filter</button>
    </form>

    @if(count($reports) === 0)
        <x-empty-state message="No reports" />
    @else
        <div class="card p-0 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-surface-secondary border-b border-default">
                    <tr>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Target</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Reason</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Status</th>
                        <th class="text-left py-3 px-4 font-semibold text-primary">Reported</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default">
                    @foreach($reports as $report)
                        <tr>
                            <td class="py-3 px-4">
                                <a href="{{ route('admin.reports.show', $report->id) }}" class="font-medium text-primary hover:text-accent-primary">
                                    {{ $report->target_type === 'POST' ? $report->post_title : $report->blog_name }}
                                </a>
                                <div class="text-xs text-secondary">{{ $report->target_type }} · {{ $report->blog_name }}</div>
                            </td>
                            <td class="py-3 px-4 text-secondary max-w-md">{{ \Illuminate\Support\Str::limit($report->reason, 100) }}</td>
                            <td class="py-3 px-4">
                                <x-badge :variant="in_array($report->status, ['OPEN', 'REVIEWING'], true) ? 'highlight' : 'default'">{{ $report->status }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-secondary">{{ \Carbon\Carbon::parse($report->created_at)->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($lastPage > 1)
            <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Pagination">
                @if($page > 1)
                    <a href="{{ route('admin.reports.index', array_merge($filters, ['page' => $page - 1])) }}" class="btn-ghost">← Previous</a>
                @else
                    <span></span>
                @endif
                <span class="text-secondary">Page {{ $page }} of {{ $lastPage }}</span>
                @if($page < $lastPage)
                    <a href="{{ route('admin.reports.index', array_merge($filters, ['page' => $page + 1])) }}" class="btn-ghost">Next →</a>
                @else
                    <span></span>
                @endif
            </nav>
        @endif
    @endif
</div>
@endsection
