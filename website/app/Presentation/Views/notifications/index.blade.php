@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-custom section-spacing max-w-4xl">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-hero mb-2">Notifications</h1>
            <p class="text-secondary">Role, account restriction and moderation updates.</p>
        </div>
        @if($notificationUnreadCount > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-secondary">Mark all as read</button>
            </form>
        @endif
    </div>

    @if(session('status'))
        <x-alert type="success" class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if(count($notifications->items) === 0)
        <x-empty-state message="No notifications yet" />
    @else
        <div class="space-y-3">
            @foreach($notifications->items as $notification)
                <article class="card {{ $notification->isUnread() ? 'border-accent-primary bg-surface-secondary' : '' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="font-semibold text-primary">{{ $notification->data['title'] ?? 'Notification' }}</h2>
                            <p class="mt-1 text-sm text-secondary">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="mt-2 text-xs text-secondary">{{ \Carbon\Carbon::parse($notification->createdAt)->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if($notification->isUnread())
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-ghost text-sm">Mark read</button>
                                </form>
                            @endif
                                <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}"
                                    data-modal-confirm-form
                                    data-confirm-title="Delete notification"
                                    data-confirm-message="Delete this notification?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost text-sm text-red-500">Delete</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if($notifications->lastPage() > 1)
            <nav class="mt-6 flex items-center justify-between text-sm" aria-label="Notification pagination">
                @if($notifications->page > 1)
                    <a class="btn-ghost" href="{{ route('notifications.index', ['page' => $notifications->page - 1]) }}">Previous</a>
                @else
                    <span></span>
                @endif
                <span class="text-secondary">Page {{ $notifications->page }} of {{ $notifications->lastPage() }}</span>
                @if($notifications->page < $notifications->lastPage())
                    <a class="btn-ghost" href="{{ route('notifications.index', ['page' => $notifications->page + 1]) }}">Next</a>
                @else
                    <span></span>
                @endif
            </nav>
        @endif
    @endif
</div>
@endsection
