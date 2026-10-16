@props(['name', 'label' => null, 'error' => null, 'hint' => null, 'required' => false, 'type' => 'text', 'value' => null])

<div class="space-y-1">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-primary">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'input ' . ($error ? 'border-red-500' : '')]) }}
        @if($required) required @endif
    />

    @if($hint)
        <p class="text-xs text-secondary">{{ $hint }}</p>
    @endif

    @if($error)
        <p class="text-sm text-red-500">{{ $error }}</p>
    @endif
</div>
