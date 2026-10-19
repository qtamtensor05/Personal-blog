@extends ('layouts.app')

@section ('title', 'Sign In to Your Account')

@section ('content')
    <x-room-background
        id="login-bg"
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
                    Welcome Back
                </h1>
                <p
                    style="
                        color: rgba(255, 255, 255, 0.9);
                        text-shadow: 0 1px 8px rgba(0, 0, 0, 0.5);
                    "
                >Sign in to continue to your account</p>
            </div>

            <!-- Login Form -->
            <div class="card">
                @if (session('status'))
                    <x-alert type="success" dismissible class="mb-6">
                        {{ session('status') }}
                    </x-alert>
                @endif

                @if ($errors->any())
                    <x-alert type="error" dismissible class="mb-6">
                        @foreach ($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                    </x-alert>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <!-- Email Field -->
                    <x-input
                        type="email"
                        name="email"
                        label="Email Address"
                        :error="$errors->first('email')"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        required
                        autofocus
                    />

                    <!-- Password Field -->
                    <div>
                        <x-input
                            name="password"
                            label="Password"
                            type="password"
                            autocomplete="current-password"
                            :toggle-password="true"
                            required
                        />
                        <div class="mt-2 text-right">
                            <a
                                href="{{ url('/password/forgot') }}"
                                class="text-accent-primary text-sm hover:underline"
                            >
                                Forgot your password?
                            </a>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="border-default text-accent-primary focus:ring-accent-primary h-4 w-4 rounded focus:ring-2"
                        />
                        <label for="remember" class="text-secondary ml-2 text-sm">
                            Remember me for 30 days
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <x-button type="submit" class="w-full justify-center"> Sign In </x-button>
                </form>

                <!-- Register Link -->
                <div class="mt-6 text-center">
                    <p class="text-secondary text-sm">
                        Don't have an account?
                        <a
                            href="{{ route('register') }}"
                            class="text-accent-primary font-medium hover:underline"
                        >
                            Create one now
                        </a>
                    </p>
                </div>
            </div>

            <!-- Security Note -->
            <div class="mt-6 text-center">
                <p
                    class="text-xs"
                    style="
                        color: rgba(255, 255, 255, 0.75);
                        text-shadow: 0 1px 6px rgba(0, 0, 0, 0.5);
                    "
                >Your connection is secure and your data is encrypted.</p>
            </div>
        </div>
    </x-room-background>
@endsection
