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

<div class="space-y-1">
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
                <svg
                    x-show="!show"
                    style="width: 1.25rem; height: 1.25rem"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <svg
                    x-show="show"
                    x-cloak
                    style="width: 1.25rem; height: 1.25rem"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
            </button>
        @endif
    </div>

    @if ($hint)
        <p class="text-secondary text-xs">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="text-sm text-red-500">{{ $error }}</p>
    @endif
</div>
