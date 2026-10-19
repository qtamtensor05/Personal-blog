@props ([
    'variant' => 'primary',
    'type' => 'button',
    'size' => 'md',
    'href' => null,
])

@php
    $baseClasses = 'btn';

    $variantClasses = match ($variant) {
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        default => 'btn-primary',
    };

    $sizeClasses = match ($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };

    $classes = trim("$baseClasses $variantClasses $sizeClasses");
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{
            $attributes->merge([
                'class' => $classes,
            ])
        }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        {{
            $attributes->merge([
                'class' => $classes,
            ])
        }}
    >
        {{ $slot }}
    </button>
@endif
