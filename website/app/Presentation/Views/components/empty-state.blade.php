@props (['message', 'action' => null, 'actionLabel' => null])

<div
    {{
        $attributes->merge([
            'class' => 'text-center py-12',
        ])
    }}
>
    <div
        class="bg-surface-secondary mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full"
    >
        <i class="fa-regular fa-folder-open text-secondary h-8 w-8 opacity-50" aria-hidden="true"></i>
    </div>

    <h3 class="text-primary mb-2 text-lg font-semibold">{{ $message }}</h3>

    @if ($slot->isNotEmpty())
        <p class="text-secondary mx-auto mb-6 max-w-md text-sm">{{ $slot }}</p>
    @endif

    @if ($action && $actionLabel)
        <a href="{{ $action }}" class="btn-primary rounded-md px-4 py-2"> {{ $actionLabel }} </a>
    @endif
</div>
