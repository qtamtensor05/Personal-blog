@extends ('layouts.app')

@section ('title', __('admin.report_page_title', ['id' => $report->id]) . ' — ' . __('admin.console'))

@php
    $pending = in_array($report->status, ['OPEN', 'REVIEWING'], true);
    $actionLabels = [
        'none' => __('admin.no_content_action'),
        'suspend_blog' => __('admin.suspend_blog'),
        'archive_post' => __('admin.archive_post'),
        'hide_comment' => __('admin.hide_comment'),
    ];
@endphp

@section ('content')
    <div class="container-custom section-spacing max-w-3xl">
        <div class="mb-8">
            <a
                href="{{ route('admin.reports.index') }}"
                class="text-secondary hover:text-accent-primary text-sm"
            >
                <i class="fa-solid fa-arrow-left"></i>
                {{ __('admin.reports') }}
            </a>
            <div class="mt-2 flex items-center gap-3">
                <h1 class="text-hero">{{ __('admin.report_page_title', ['id' => $report->id]) }}</h1>
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
                <span class="text-secondary">{{ __('admin.blog') }}</span>
                <span class="text-primary font-medium">{{ $report->blog_name }}</span>
                <x-badge>{{ $report->blog_status }}</x-badge>
            </div>
            @if ($report->target_type === 'COMMENT')
                <div>
                    <span class="text-secondary">{{ __('admin.comment') }} #{{ $report->comment_id }}</span>
                    <p class="bg-surface-secondary mt-2 rounded-md p-3 whitespace-pre-line">{{ $report->comment_body ?? __('admin.comment_deleted') }}</p>
                    @if ($report->post_status === 'PUBLISHED' && $report->blog_status === 'PUBLISHED')
                        <a href="{{ route('public.post', [$report->blog_slug, $report->post_slug]) }}#comment-{{ $report->comment_id }}" target="_blank" rel="noopener" class="text-accent-primary hover:underline">{{ __('admin.open_comment') }}</a>
                    @endif
                </div>
            @elseif ($report->target_type === 'POST')
                <div>
                    <span class="text-secondary">{{ __('admin.post') }}</span>
                    <span class="text-primary font-medium">{{ $report->post_title }}</span>
                    <x-badge>{{ $report->post_status }}</x-badge>
                    @if ($report->post_status === 'PUBLISHED' && $report->blog_status === 'PUBLISHED')
                        <a
                            href="{{ route('public.post', [$report->blog_slug, $report->post_slug]) }}"
                            target="_blank"
                            rel="noopener"
                            class="text-accent-primary ml-1 hover:underline"
                            >{{ __('admin.open_post') }}</a
                        >
                    @endif
                </div>
            @elseif ($report->blog_status === 'PUBLISHED')
                <a
                    href="{{ route('public.blog', $report->blog_slug) }}"
                    target="_blank"
                    rel="noopener"
                    class="text-accent-primary hover:underline"
                    >{{ __('admin.open_blog') }}</a
                >
            @endif
            <div>
                <span class="text-secondary">{{ __('admin.reported_by') }}</span> {{
                    $report->reporter_email ??
                    __('admin.deleted_user')
                }}
            </div>
            <div class="bg-surface-secondary rounded-md p-3 whitespace-pre-line">
                {{ $report->reason }}
            </div>
            @if ($report->resolution_note)
                <div>
                <span class="text-secondary">{{ __('admin.resolution_note') }}</span>
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
                    <button type="submit" class="btn-secondary">{{ __('admin.start_review') }}</button>
                </form>
            @endif

            <form
                method="POST"
                action="{{ route('admin.reports.close', $report->id) }}"
                class="card space-y-4"
                data-modal-confirm-form
                data-confirm-title="{{ __('admin.close_report_confirm_title') }}"
                data-confirm-message="{{ __('admin.close_report_confirm') }}"
            >
                @csrf
                <h2 class="text-primary text-lg font-semibold">{{ __('admin.decision') }}</h2>
                <div class="space-y-1">
                    <label for="status" class="text-primary block text-sm font-medium"
                        >{{ __('admin.outcome') }}</label
                    >
                    <select name="status" id="status" class="input" required>
                        <option value="RESOLVED">{{ __('admin.resolved_valid') }}</option>
                        <option value="DISMISSED">{{ __('admin.dismissed_no_violation') }}</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label for="action" class="text-primary block text-sm font-medium"
                        >{{ __('admin.action') }}</label
                    >
                    <select name="action" id="action" class="input" required>
                        @foreach ($actions as $action)
                            @continue ($action === 'archive_post' && $report->target_type !== 'POST')
                            @continue ($action === 'hide_comment' && $report->target_type !== 'COMMENT')
                            <option value="{{ $action }}">{{ $actionLabels[$action] }}</option>
                        @endforeach
                    </select>
                </div>
                <x-textarea name="note" :label="__('admin.note_optional')" rows="2" />
                <button type="submit" class="btn-primary">{{ __('admin.close_report') }}</button>
            </form>
        @elseif ($report->blog_status === 'SUSPENDED')
            <form
                method="POST"
                action="{{ route('admin.reports.lift', $report->id) }}"
                data-modal-confirm-form
                data-confirm-title="{{ __('admin.lift_suspension_confirm_title') }}"
                data-confirm-message="{{ __('admin.lift_suspension_confirm') }}"
            >
                @csrf
                <button type="submit" class="btn-secondary">{{ __('admin.lift_blog_suspension') }}</button>
            </form>
        @endif
    </div>
@endsection
