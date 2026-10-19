@extends ('layouts.app')

@section ('title', 'Content Reports — Admin Console')

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8">
            <h1 class="text-hero mb-2">Content Reports</h1>
            <p class="text-secondary">Reports from readers about blogs and posts.</p>
        </div>

        <form
            method="GET"
            action="{{ route('admin.reports.index') }}"
            class="card mb-6 flex flex-wrap items-end gap-3"
        >
            <div class="space-y-1">
                <label for="status" class="text-primary block text-sm font-medium">Status</label>
                <select name="status" id="status" class="input">
                    <option value="">All</option>
                    @foreach (['OPEN', 'REVIEWING', 'RESOLVED', 'DISMISSED'] as $status)
                        <option
                            value="{{ $status }}"
                            @selected (($filters['status'] ?? null) === $status)
                        >
                            {{ $status }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-secondary">Filter</button>
        </form>

        @if (count($reports) === 0)
            <x-empty-state message="No reports" />
        @else
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-sm">
                    <thead class="bg-surface-secondary border-default border-b">
                        <tr>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Target</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Reason</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Status</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">Reported</th>
                        </tr>
                    </thead>
                    <tbody class="divide-default divide-y">
                        @foreach ($reports as $report)
                            <tr>
                                <td class="px-4 py-3">
                                    <a
                                        href="{{ route('admin.reports.show', $report->id) }}"
                                        class="text-primary hover:text-accent-primary font-medium"
                                    >
                                        {{
                                            $report->target_type === 'POST'
                                                ? $report->post_title
                                                : $report->blog_name
                                        }}
                                    </a>
                                    <div class="text-secondary text-xs">
                                        {{ $report->target_type }} · {{ $report->blog_name }}
                                    </div>
                                </td>
                                <td class="text-secondary max-w-md px-4 py-3">
                                    {{
                                        \Illuminate\Support\Str::limit(
                                            $report->reason,
                                            100,
                                        )
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    <x-badge
                                        :variant="in_array($report->status, ['OPEN', 'REVIEWING'], true) ? 'highlight' : 'default'"
                                        >{{ $report->status }}</x-badge
                                    >
                                </td>
                                <td class="text-secondary px-4 py-3">
                                    {{
                                        \Carbon\Carbon::parse(
                                            $report->created_at,
                                        )->diffForHumans()
                                    }}
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
                            href="{{ route('admin.reports.index', array_merge($filters, ['page' => $page - 1])) }}"
                            class="btn-ghost"
                            >← Previous</a
                        >
                    @else
                        <span></span>
                    @endif
                    <span class="text-secondary">Page {{ $page }} of {{ $lastPage }}</span>
                    @if ($page < $lastPage)
                        <a
                            href="{{ route('admin.reports.index', array_merge($filters, ['page' => $page + 1])) }}"
                            class="btn-ghost"
                            >Next →</a
                        >
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
@endsection
