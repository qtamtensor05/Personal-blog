@extends ('layouts.app')

@section ('title', __('auth.reset_password'))

@section ('content')
    <x-room-background
        id="login-bg"
        min-height="100vh"
        :pan="10"
        :dolly="0.12"
        :seconds="24"
        :scrim="0.65"
    >
        <div class="flex h-50 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md">
                <!-- Header -->
                <div class="mb-8 text-center">
                    <h1 class="text-mb-2 text-3xl font-bold" style="color: #ffffff">
                        {{ __('auth.forgot_title') }}
                    </h1>
                    <p
                        class="text-secondary"
                        style="
                            color: rgba(255, 255, 255, 0.9);
                            text-shadow: 0 1px 8px rgba(0, 0, 0, 0.5);
                        "
                    >{{ __('auth.forgot_description') }}</p>
                </div>

                <!-- Form Card -->
                <div class="card">
                    @if (session('status'))
                        <x-alert type="success" dismissible class="mb-6">
                            {{ session('status') }}
                        </x-alert>
                    @endif

                    @if ($errors->any())
                        <x-alert type="error" dismissible class="mb-6">
                            {{
                                $errors->first('email') ??
                                    $errors->first()
                            }}
                        </x-alert>
                    @endif

                    <form method="POST" action="{{ url('/password/email') }}" class="space-y-5">
                        @csrf

                        <!-- Email Field -->
                        <x-input
                            type="email"
                            name="email"
                            :label="__('auth.email')"
                            :error="$errors->first('email')"
                            value="{{ old('email') }}"
                            placeholder="you@example.com"
                            required
                            autofocus
                        />

                        <!-- Submit Button -->
                        <x-button type="submit" class="w-full justify-center">
                            {{ __('auth.send_reset_link') }}
                        </x-button>
                    </form>

                    <!-- Back to Login -->
                    <div class="mt-6 text-center">
                        <a
                            href="{{ route('login') }}"
                            class="text-accent-primary text-sm hover:underline"
                        >
                            ← Back to Sign In
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </x-room-background>
@endsection
