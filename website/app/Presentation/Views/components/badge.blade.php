@props(['variant' => 'default'])

@php
$baseClasses = 'badge';
$variantClasses = match($variant) {
    'primary' => 'badge-primary',
    'highlight' => 'badge-highlight',
    default => '',
};
@endphp

<span {{ $attributes->merge(['class' => "$baseClasses $variantClasses"]) }}>
    {{ $slot }}
</span>
