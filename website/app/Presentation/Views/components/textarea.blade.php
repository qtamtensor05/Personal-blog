@props(['name', 'label' => null, 'error' => null, 'required' => false, 'rows' => 4])

<div class="space-y-1">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-primary">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => 'textarea ' . ($error ? 'border-red-500' : '')]) }}
        @if($required) required @endif
    >{{ $slot }}</textarea>

    @if($error)
        <p class="text-sm text-red-500">{{ $error }}</p>
    @endif
</div>
