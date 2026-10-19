@extends ('layouts.app')

@section ('title', 'Report #' . $report->id . ' — Admin Console')

@php
    $pending = in_array($report->status, ['OPEN', 'REVIEWING'], true);
    $actionLabels = [
        'none' => 'No action on content',
        'suspend_blog' => 'Suspend the blog',
        'archive_post' => 'Archive the post',
    ];
@endphp

@section ('content')
    <div class="container-custom section-spacing max-w-3xl">
        <div class="mb-8">
            <a
                href="{{ route('admin.reports.index') }}"
                class="text-secondary hover:text-accent-primary text-sm"
                >← Reports</a
            >
            <div class="mt-2 flex items-center gap-3">
                <h1 class="text-hero">Report #{{ $report->id }}</h1>
                <x-badge
                    :variant="$pending ? 'highlight' : 'default'"
                    >{{ $report->status }}</x-badge
                >
            </div>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <section class="card mb-6 space-y-3 text-sm">
            <div>
                <span class="text-secondary">Blog:</span>
                <span class="text-primary font-medium">{{ $report->blog_name }}</span>
                <x-badge>{{ $report->blog_status }}</x-badge>
            </div>
            @if ($report->target_type === 'POST')
                <div>
                    <span class="text-secondary">Post:</span>
                    <span class="text-primary font-medium">{{ $report->post_title }}</span>
                    <x-badge>{{ $report->post_status }}</x-badge>
                    @if ($report->post_status === 'PUBLISHED' && $report->blog_status === 'PUBLISHED')
                        <a
                            href="{{ route('public.post', [$report->blog_slug, $report->post_slug]) }}"
                            target="_blank"
                            rel="noopener"
                            class="text-accent-primary ml-1 hover:underline"
                            >Open</a
                        >
                    @endif
                </div>
            @elseif ($report->blog_status === 'PUBLISHED')
                <a
                    href="{{ route('public.blog', $report->blog_slug) }}"
                    target="_blank"
                    rel="noopener"
                    class="text-accent-primary hover:underline"
                    >Open blog</a
                >
            @endif
            <div>
                <span class="text-secondary">Reported by:</span> {{
                    $report->reporter_email ??
                        'deleted user'
                }}
            </div>
            <div class="bg-surface-secondary rounded-md p-3 whitespace-pre-line">
                {{ $report->reason }}
            </div>
            @if ($report->resolution_note)
                <div>
                    <span class="text-secondary">Resolution note:</span>
                    {{ $report->resolution_note }}
                </div>
            @endif
        </section>

        @if ($pending)
            @if ($report->status === 'OPEN')
                <form
                    method="POST"
                    action="{{ route('admin.reports.review', $report->id) }}"
                    class="mb-6"
                >
                    @csrf
                    <button type="submit" class="btn-secondary">Start review</button>
                </form>
            @endif

            <form
                method="POST"
                action="{{ route('admin.reports.close', $report->id) }}"
                class="card space-y-4"
                data-modal-confirm-form
                data-confirm-title="Close report"
                data-confirm-message="Close this report?"
            >
                @csrf
                <h2 class="text-primary text-lg font-semibold">Decision</h2>
                <div class="space-y-1">
                    <label for="status" class="text-primary block text-sm font-medium"
                        >Outcome</label
                    >
                    <select name="status" id="status" class="input" required>
                        <option value="RESOLVED">Resolved (report is valid)</option>
                        <option value="DISMISSED">Dismissed (no violation)</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label for="action" class="text-primary block text-sm font-medium"
                        >Action</label
                    >
                    <select name="action" id="action" class="input" required>
                        @foreach ($actions as $action)
                            @continue ($action === 'archive_post' && $report->target_type !== 'POST')
                            <option value="{{ $action }}">{{ $actionLabels[$action] }}</option>
                        @endforeach
                    </select>
                </div>
                <x-textarea name="note" label="Note (optional)" rows="2" />
                <button type="submit" class="btn-primary">Close report</button>
            </form>
        @elseif ($report->blog_status === 'SUSPENDED')
            <form
                method="POST"
                action="{{ route('admin.reports.lift', $report->id) }}"
                data-modal-confirm-form
                data-confirm-title="Lift suspension"
                data-confirm-message="Lift the suspension of this blog?"
            >
                @csrf
                <button type="submit" class="btn-secondary">Lift blog suspension</button>
            </form>
        @endif
    </div>
@endsection
