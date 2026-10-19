@extends ('layouts.app')

@section ('title', 'Admin Console')

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8">
            <p class="text-accent-primary mb-2 text-sm font-semibold tracking-wider uppercase">{{ __('admin.platform') }}</p>
            <h1 class="text-hero mb-2">{{ __('admin.console') }}</h1>
            <p class="text-secondary">{{ __('admin.manage_allowed') }}</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @if ($canManageUsers)
                <a href="{{ route('admin.users.index') }}" class="card group block">
                    <i class="fa-solid fa-user h-8 w-8" aria-hidden="true"></i>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        {{ __('admin.users') }}
                    </h2>
                    <p class="text-secondary mt-1 text-sm">{{ __('admin.users_help') }}</p>
                </a>
            @endif

            @if ($canModerateContent)
                <a href="{{ route('admin.reports.index') }}" class="card group block">
                    <i class="fa-solid fa-file-lines h-8 w-8" aria-hidden="true"></i>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        {{ __('admin.reports') }}
                    </h2>
                    <p class="text-secondary mt-1 text-sm">{{ __('admin.reports_help') }}</p>
                </a>
            @endif

            @if ($canManageTemplates)
                <a href="{{ route('admin.templates.index') }}" class="card group block">
                    <i class="fa-solid fa-pencil h-8 w-8" aria-hidden="true"></i>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        {{ __('admin.templates') }}
                    </h2>
                    <p class="text-secondary mt-1 text-sm">{{ __('admin.templates_help') }}</p>
                </a>
            @endif

            @if ($canManageSettings)
                <a href="{{ route('admin.settings.edit') }}" class="card group block">
                    <i class="fa-solid fa-gear h-8 w-8" aria-hidden="true"></i>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        {{ __('admin.settings') }}
                    </h2>
                    <p class="text-secondary mt-1 text-sm">{{ __('admin.settings_help') }}</p>
                </a>
            @endif

            @if ($canViewAuditLogs)
                <a href="{{ route('admin.audit-logs') }}" class="card group block">
                    <i class="fa-regular fa-message h-8 w-8" aria-hidden="true"></i>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        {{ __('admin.audit') }}
                    </h2>
                    <p class="text-secondary mt-1 text-sm">{{ __('admin.audit_help') }}</p>
                </a>
            @endif
        </div>
    </div>
@endsection
