@props(['variant' => 'primary', 'type' => 'button'])

@php
$baseClasses = 'btn';
$variantClasses = match($variant) {
    'secondary' => 'btn-secondary',
    'ghost' => 'btn-ghost',
    default => 'btn-primary',
};
@endphp

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => "$baseClasses $variantClasses"]) }}
>
    {{ $slot }}
</button>
