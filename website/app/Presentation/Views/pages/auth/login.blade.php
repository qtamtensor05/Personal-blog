@extends('layouts.app')

@section('title', 'Sign In to Your Account')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">Welcome Back</h1>
            <p class="text-secondary">Sign in to continue to your account</p>
        </div>

        <!-- Login Form -->
        <div class="card">
            @if(session('status'))
                <x-alert type="success" dismissible class="mb-6">
                    {{ session('status') }}
                </x-alert>
            @endif

            @if($errors->any())
                <x-alert type="error" dismissible class="mb-6">
                    @foreach($errors->all() as $error)
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
                        type="password"
                        name="password"
                        label="Password"
                        :error="$errors->first('password')"
                        placeholder="Enter your password"
                        required
                    />
                    <div class="mt-2 text-right">
                        <a href="{{ url('/password/forgot') }}" class="text-sm text-accent-primary hover:underline">
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
                        class="w-4 h-4 border-default rounded text-accent-primary focus:ring-accent-primary focus:ring-2"
                    >
                    <label for="remember" class="ml-2 text-sm text-secondary">
                        Remember me for 30 days
                    </label>
                </div>

                <!-- Submit Button -->
                <x-button type="submit" class="w-full justify-center">
                    Sign In
                </x-button>
            </form>

            <!-- Register Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-secondary">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-medium text-accent-primary hover:underline">
                        Create one now
                    </a>
                </p>
            </div>
        </div>

        <!-- Security Note -->
        <div class="mt-6 text-center">
            <p class="text-xs text-secondary">
                Your connection is secure and your data is encrypted.
            </p>
        </div>
    </div>
</div>
@endsection
