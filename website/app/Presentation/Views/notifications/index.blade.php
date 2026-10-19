@extends ('layouts.app')

@section ('title', __('notifications.title'))

@section ('content')
    <div class="container-custom section-spacing max-w-4xl">
        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-hero mb-2">{{ __('notifications.title') }}</h1>
                <p class="text-secondary">{{ __('notifications.intro') }}</p>
            </div>
            @if ($notificationUnreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method ('PATCH')
                    <button type="submit" class="btn-secondary">
                        {{ __('notifications.mark_all') }}
                    </button>
                </form>
            @endif
        </div>

        @if (session('status'))
            <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if (count($notifications->items) === 0)
            <x-empty-state :message="__('notifications.empty')" />
        @else
            <div class="space-y-3">
                @foreach ($notifications->items as $notification)
                    <article
                        class="card {{ $notification->isUnread() ? 'border-accent-primary bg-surface-secondary' : '' }}"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-primary font-semibold">
                                    {{
                                        $notification->data['title'] ??
                                            __('notifications.fallback_title')
                                    }}
                                </h2>
                                <p class="text-secondary mt-1 text-sm">{{
                                    $notification->data['message'] ??
                                        ''
                                }}</p>
                                <p class="text-secondary mt-2 text-xs">{{
                                    \Carbon\Carbon::parse(
                                        $notification->createdAt,
                                    )->diffForHumans()
                                }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if ($notification->isUnread())
                                    <form
                                        method="POST"
                                        action="{{ route('notifications.read', $notification->id) }}"
                                    >
                                        @csrf
                                        @method ('PATCH')
                                        <button type="submit" class="btn-ghost text-sm">
                                            {{ __('notifications.mark_read') }}
                                        </button>
                                    </form>
                                @endif
                                <form
                                    method="POST"
                                    action="{{ route('notifications.destroy', $notification->id) }}"
                                    data-modal-confirm-form
                                    data-confirm-title="{{ __('notifications.delete_title') }}"
                                    data-confirm-message="{{ __('notifications.delete_confirm') }}"
                                >
                                    @csrf
                                    @method ('DELETE')
                                    <button type="submit" class="btn-ghost text-sm text-red-500">
                                        {{ __('notifications.delete') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($notifications->lastPage() > 1)
                <nav
                    class="mt-6 flex items-center justify-between text-sm"
                    aria-label="{{ __('notifications.pagination') }}"
                >
                    @if ($notifications->page > 1)
                        <a
                            class="btn-ghost"
                            href="{{ route('notifications.index', ['page' => $notifications->page - 1]) }}"
                            >{{ __('notifications.previous') }}</a
                        >
                    @else
                        <span></span>
                    @endif
                    <span class="text-secondary">{{
                        __('notifications.page_of', [
                            'page' => $notifications->page,
                            'total' => $notifications->lastPage(),
                        ])
                    }}</span>
                    @if ($notifications->page < $notifications->lastPage())
                        <a
                            class="btn-ghost"
                            href="{{ route('notifications.index', ['page' => $notifications->page + 1]) }}"
                            >{{ __('notifications.next') }}</a
                        >
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
@endsection
