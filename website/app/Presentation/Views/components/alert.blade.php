@props (['type' => 'info', 'dismissible' => false])

@php
    $typeClasses = match ($type) {
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        default => 'bg-blue-50 border-blue-200 text-blue-800',
    };
@endphp

<div
    {{
        $attributes->merge([
            'class' => "border rounded-lg p-4 flex items-start gap-3 $typeClasses",
        ])
    }}
    x-data="{ show: true }"
    x-show="show"
>
    <div class="flex-shrink-0">
        @if ($type === 'success')
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
        @elseif ($type === 'error')
            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
        @elseif ($type === 'warning')
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        @else
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        @endif
    </div>

    <div class="flex-1 text-sm">{{ $slot }}</div>

    @if ($dismissible)
        <button @click="show = false" class="flex-shrink-0 transition-opacity hover:opacity-70">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    @endif
</div>
