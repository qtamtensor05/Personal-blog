@extends ('layouts.app')

@section ('title', __('admin.reports_title'))

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8">
            <h1 class="text-hero mb-2">{{ __('admin.reports_title') }}</h1>
            <p class="text-secondary">{{ __('admin.reports_help') }}</p>
        </div>

        <form
            method="GET"
            action="{{ route('admin.reports.index') }}"
            class="card mb-6 flex flex-wrap items-end gap-3"
        >
            <div class="space-y-1">
                <label for="status" class="text-primary block text-sm font-medium">{{ __('admin.status') }}</label>
                <select name="status" id="status" class="input">
                    <option value="">{{ __('admin.all') }}</option>
                    @foreach (['OPEN', 'REVIEWING', 'RESOLVED', 'DISMISSED'] as $status)
                        <option
                            value="{{ __('content_status.' . $status) }}"
                            @selected (($filters['status'] ?? null) === $status)
                        >
                            {{ __('content_status.' . $status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary rounded-md px-4 py-2">{{ __('admin.filter') }}</button>
        </form>

        @if (count($reports) === 0)
            <x-empty-state :message="__('admin.no_reports')" />
        @else
            <div class="card overflow-x-auto p-0">
                <table class="w-full text-sm">
                    <thead class="bg-surface-secondary border-default border-b">
                        <tr>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('admin.target') }}</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('admin.reason_col') }}</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('admin.status') }}</th>
                            <th class="text-primary px-4 py-3 text-left font-semibold">{{ __('admin.reported') }}</th>
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
                                        >{{ __('content_status.' . $report->status) }}</x-badge
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
                <nav class="mt-6 flex items-center justify-between text-sm" :aria-label="__('notifications.pagination')">
                    @if ($page > 1)
                        <a
                            href="{{ route('admin.reports.index', array_merge($filters, ['page' => $page - 1])) }}"
                            class="btn-ghost"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            {{ __('admin.previous') }}
                        </a>
                    @else
                        <span></span>
                    @endif
                    <span class="text-secondary">Page {{ $page }} of {{ $lastPage }}</span>
                    @if ($page < $lastPage)
                        <a
                            href="{{ route('admin.reports.index', array_merge($filters, ['page' => $page + 1])) }}"
                            class="btn-ghost"
                            >{{ __('admin.next') }} →</a
                        >
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
@endsection
