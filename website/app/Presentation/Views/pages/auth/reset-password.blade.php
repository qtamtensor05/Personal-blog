@extends ('layouts.app')

@section ('title', __('auth.set_new_password'))

@section ('content')
    <x-room-background
        id="login-bg"
        min-height="100vh"
        :pan="10"
        :dolly="0.12"
        :seconds="24"
        :scrim="0.65"
    >
        <div class="flex min-h-80 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <h1 class="text-mb-2 text-3xl font-bold" style="color: #ffffff">
                        {{ __('auth.set_new_password') }}
                    </h1>
                    <p
                        class="text-secondary"
                        style="
                            color: rgba(255, 255, 255, 0.9);
                            text-shadow: 0 1px 8px rgba(0, 0, 0, 0.5);
                        "
                    >{{ __('auth.reset_help') }}</p>
                </div>

                <!-- Form Card -->
                <div class="card">
                    @if ($errors->any())
                        <x-alert type="error" dismissible class="mb-6">
                            <strong>{{ __('auth.fix_errors') }}</strong>
                            <ul class="mt-2 ml-4 list-disc text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-alert>
                    @endif

                    <form method="POST" action="{{ url('/password/reset') }}" class="space-y-5">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}" />

                        <!-- Email Field (readonly) -->
                        <x-input
                            type="email"
                            name="email"
                            :label="__('auth.email')"
                            :error="$errors->first('email')"
                            value="{{ old('email', request('email')) }}"
                            readonly
                        />

                        <!-- New Password Field -->
                        <x-input
                            type="password"
                            name="password"
                            :label="__('auth.new_password')"
                            :error="$errors->first('password')"
                            :placeholder="__('auth.password_min')"
                            required
                            autofocus
                        />

                        <!-- Confirm Password Field -->
                        <x-input
                            type="password"
                            name="password_confirmation"
                            :label="__('auth.confirm_new_password')"
                            :error="$errors->first('password_confirmation')"
                            :placeholder="__('auth.repeat_password')"
                            required
                        />

                        <!-- Password Requirements -->
                        <div class="bg-surface-secondary rounded-lg p-4">
                            <p class="text-primary mb-2 text-xs font-medium">{{ __('auth.password_requirements_title') }}</p>
                            <ul class="text-secondary space-y-1 text-xs">
                                <li>• {{ __('auth.password_min_length') }}</li>
                                <li>• {{ __('auth.password_case_mix') }}</li>
                                <li>• {{ __('auth.password_extra_security') }}</li>
                            </ul>
                        </div>

                        <!-- Submit Button -->
                        <x-button type="submit" class="w-full justify-center">
                            {{ __('auth.reset_password') }}
                        </x-button>
                    </form>
                </div>
            </div>
        </div>
    </x-room-background>
@endsection
