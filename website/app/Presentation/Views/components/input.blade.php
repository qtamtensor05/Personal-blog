@props ([
    'name',
    'label' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
    'type' => 'text',
    'value' => null
])

@php ($isPassword = $type === 'password')

<div class="space-y-1" data-input-validation>
    @if ($label)
        <label for="{{ $name }}" class="text-primary block text-sm font-medium">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div style="position: relative" @if ($isPassword) x-data="{ show: false }" @endif>
        <input
            type="{{ $type }}"
            @if ($isPassword) :type="show ? 'text' : 'password'" @endif
            name="{{ $name }}"
            id="{{ $name }}"
            data-validation-required="{{ __('validation.required', ['attribute' => $label ?? $name]) }}"
            data-validation-format="{{ __('validation.regex', ['attribute' => $label ?? $name]) }}"
            data-validation-email="{{ __('validation.email', ['attribute' => $label ?? $name]) }}"
            data-validation-url="{{ __('validation.url', ['attribute' => $label ?? $name]) }}"
            data-validation-min="{{ __('validation.min.string', ['attribute' => $label ?? $name, 'min' => $attributes->get('minlength', '')]) }}"
            data-validation-max="{{ __('validation.max.string', ['attribute' => $label ?? $name, 'max' => $attributes->get('maxlength', '')]) }}"
            @unless ($isPassword) value="{{ old($name, $value) }}" @endunless
            @if ($isPassword) style="padding-right: 2.75rem" @endif
            {{
                $attributes->merge([
                    'class' => 'input ' . ($error ? 'border-red-500' : ''),
                ])
            }}
            @if ($required) required @endif
        />

        @if ($isPassword)
            <button
                type="button"
                @click="show = !show"
                style="
                    position: absolute;
                    top: 50%;
                    right: 0.75rem;
                    transform: translateY(-50%);
                    display: flex;
                    align-items: center;
                    background: none;
                    border: 0;
                    padding: 0;
                    cursor: pointer;
                    color: #6b7280;
                "
                :aria-label="show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                tabindex="-1"
            >
                <i x-show="!show" class="fa-solid fa-eye" aria-hidden="true"></i>
                <i x-show="show" x-cloak class="fa-solid fa-eye-slash" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    @if ($hint)
        <p class="text-secondary text-xs">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-sm text-red-500">{{ $error }}</p>
    @endif
    <p class="hidden text-sm text-red-500" data-input-error role="alert"></p>
</div>
