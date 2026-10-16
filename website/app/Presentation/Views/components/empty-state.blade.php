@props(['message', 'action' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-surface-secondary mb-4">
        <svg class="w-8 h-8 text-secondary opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
    </div>

    <h3 class="text-lg font-semibold text-primary mb-2">{{ $message }}</h3>

    @if($slot->isNotEmpty())
        <p class="text-sm text-secondary mb-6 max-w-md mx-auto">
            {{ $slot }}
        </p>
    @endif

    @if($action && $actionLabel)
        <a href="{{ $action }}" class="btn-primary inline-flex">
            {{ $actionLabel }}
        </a>
    @endif
</div>
