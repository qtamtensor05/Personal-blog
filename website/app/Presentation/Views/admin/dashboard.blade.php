@extends ('layouts.app')

@section ('title', 'Admin Console')

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8">
            <p class="text-accent-primary mb-2 text-sm font-semibold tracking-wider uppercase">Administration</p>
            <h1 class="text-hero mb-2">Admin Console</h1>
            <p class="text-secondary">Manage the areas your platform role allows you to access.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @if ($canManageUsers)
                <a href="{{ route('admin.users.index') }}" class="card group block">
                    <svg class="text-accent-primary mb-4 h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H2v-2a4 4 0 014-4h3m6 6v-2a4 4 0 00-4-4H9m6-6a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 10-3-3" />
                    </svg>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        Users
                    </h2>
                    <p class="text-secondary mt-1 text-sm">Accounts, restrictions and platform roles.</p>
                </a>
            @endif

            @if ($canModerateContent)
                <a href="{{ route('admin.reports.index') }}" class="card group block">
                    <svg class="green-accent-warm mb-4 h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z" />
                    </svg>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        Content reports
                    </h2>
                    <p class="text-secondary mt-1 text-sm">Review reports and apply moderation actions.</p>
                </a>
            @endif

            @if ($canManageTemplates)
                <a href="{{ route('admin.templates.index') }}" class="card group block">
                    <svg class="green-accent-highlight mb-4 h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a2 2 0 012-2h12a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm4 2h8M8 11h8M8 15h5" />
                    </svg>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        Templates
                    </h2>
                    <p class="text-secondary mt-1 text-sm">Create and publish blog templates.</p>
                </a>
            @endif

            @if ($canManageSettings)
                <a href="{{ route('admin.settings.edit') }}" class="card group block">
                    <svg class="text-accent-primary mb-4 h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 16v-2m6-6h2M4 12h2m10.243-4.243 1.414-1.414M6.343 17.657l1.414-1.414m8.486 0 1.414 1.414M6.343 6.343l1.414 1.414M12 15a3 3 0 100-6 3 3 0 000 6z" />
                    </svg>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        Platform settings
                    </h2>
                    <p class="text-secondary mt-1 text-sm">Global registration, locale, limits and banner.</p>
                </a>
            @endif

            @if ($canViewAuditLogs)
                <a href="{{ route('admin.audit-logs') }}" class="card group block">
                    <svg class="green-accent-warm mb-4 h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 3a11.955 11.955 0 01-8.618 2.984A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <h2 class="text-primary group-hover:text-accent-primary text-lg font-semibold">
                        Audit logs
                    </h2>
                    <p class="text-secondary mt-1 text-sm">Review recorded administrative actions.</p>
                </a>
            @endif
        </div>
    </div>
@endsection
