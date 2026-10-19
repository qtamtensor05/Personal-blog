@extends ('layouts.app')

@section ('title', __('auth.create_account'))

@section ('content')
    <x-room-background
        id="register-bg"
        min-height="100vh"
        :pan="10"
        :dolly="0.12"
        :seconds="24"
        :scrim="0.65"
    >
        <div class="w-full max-w-md py-8">
            <!-- Header (white text, because it sits on the dark 3D scene) -->
            <div class="mb-8 text-center">
                <h1
                    class="mb-2 text-3xl font-bold"
                    style="color: #fff; text-shadow: 0 2px 16px rgba(0, 0, 0, 0.5)"
                >
                    {{ __('auth.create_account') }}
                </h1>
                <p
                    style="
                        color: rgba(255, 255, 255, 0.9);
                        text-shadow: 0 1px 8px rgba(0, 0, 0, 0.5);
                    "
                >{{ __('auth.join_community') }}</p>
            </div>

            <!-- Registration Form -->
            <div class="card">
                @if (session('status'))
                    <x-alert type="success" dismissible class="mb-6">
                        {{ session('status') }}
                    </x-alert>
                @endif

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

                <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                    @csrf

                    <!-- Name Field -->
                    <x-input
                        type="text"
                        name="name"
                        :label="__('auth.full_name')"
                        :error="$errors->first('name')"
                        value="{{ old('name') }}"
                        placeholder="John Doe"
                        required
                    />

                    <!-- Email Field -->
                    <x-input
                        type="email"
                        name="email"
                        :label="__('auth.email')"
                        :error="$errors->first('email')"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        required
                    />

                    <!-- Password Field -->
                    <x-input
                        type="password"
                        name="password"
                        :label="__('auth.password')"
                        :error="$errors->first('password')"
                        :placeholder="__('auth.password_min')"
                        required
                    />

                    <!-- Password Confirmation Field -->
                    <x-input
                        type="password"
                        name="password_confirmation"
                        :label="__('auth.confirm_password')"
                        :error="$errors->first('password_confirmation')"
                        :placeholder="__('auth.repeat_password')"
                        required
                    />

                    <!-- Terms Acceptance -->
                    <div class="flex items-start">
                        <div class="flex h-5 items-center">
                            <input
                                id="terms"
                                name="terms"
                                type="checkbox"
                                class="border-default text-accent-primary focus:ring-accent-primary h-4 w-4 rounded focus:ring-2"
                                required
                            />
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="terms" class="text-secondary">
                                {{ __('auth.agree_to') }}
                                <a
                                    href="#"
                                    class="text-accent-primary hover:underline"
                                    >{{ __('auth.terms') }}</a
                                >
                                {{ __('auth.and') }}
                                <a
                                    href="#"
                                    class="text-accent-primary hover:underline"
                                    >{{ __('auth.privacy') }}</a
                                >
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <x-button type="submit" class="w-full justify-center">
                        {{ __('auth.create_submit') }}
                    </x-button>
                </form>

                <!-- Sign In Link -->
                <div class="mt-6 text-center">
                    <p class="text-secondary text-sm">
                        {{ __('auth.have_account') }}
                        <a
                            href="{{ route('login') }}"
                            class="text-accent-primary font-medium hover:underline"
                        >
                            {{ __('auth.sign_in') }}
                        </a>
                    </p>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="mt-6 text-center">
                <p
                    class="text-xs"
                    style="
                        color: rgba(255, 255, 255, 0.75);
                        text-shadow: 0 1px 6px rgba(0, 0, 0, 0.5);
                    "
                >{{ __('auth.registration_notice') }}</p>
            </div>
        </div>
    </x-room-background>
@endsection
