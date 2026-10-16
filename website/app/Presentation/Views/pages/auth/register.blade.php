@extends('layouts.app')

@section('title', 'Create Your Account')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">Create Your Account</h1>
            <p class="text-secondary">Join our community and start sharing your ideas</p>
        </div>

        <!-- Registration Form -->
        <div class="card">
            @if(session('status'))
                <x-alert type="success" dismissible class="mb-6">
                    {{ session('status') }}
                </x-alert>
            @endif

            @if($errors->any())
                <x-alert type="error" dismissible class="mb-6">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mt-2 ml-4 list-disc text-sm">
                        @foreach($errors->all() as $error)
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
                    label="Full Name"
                    :error="$errors->first('name')"
                    value="{{ old('name') }}"
                    placeholder="John Doe"
                    required
                />

                <!-- Email Field -->
                <x-input
                    type="email"
                    name="email"
                    label="Email Address"
                    :error="$errors->first('email')"
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    required
                />

                <!-- Password Field -->
                <x-input
                    type="password"
                    name="password"
                    label="Password"
                    :error="$errors->first('password')"
                    placeholder="At least 8 characters"
                    required
                />

                <!-- Password Confirmation Field -->
                <x-input
                    type="password"
                    name="password_confirmation"
                    label="Confirm Password"
                    :error="$errors->first('password_confirmation')"
                    placeholder="Repeat your password"
                    required
                />

                <!-- Terms Acceptance -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input
                            id="terms"
                            name="terms"
                            type="checkbox"
                            class="w-4 h-4 border-default rounded text-accent-primary focus:ring-accent-primary focus:ring-2"
                            required
                        >
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="terms" class="text-secondary">
                            I agree to the <a href="#" class="text-accent-primary hover:underline">Terms of Service</a> and <a href="#" class="text-accent-primary hover:underline">Privacy Policy</a>
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <x-button type="submit" class="w-full justify-center">
                    Create Account
                </x-button>
            </form>

            <!-- Sign In Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-secondary">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-medium text-accent-primary hover:underline">
                        Sign in instead
                    </a>
                </p>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="mt-6 text-center">
            <p class="text-xs text-secondary">
                By creating an account, you'll receive a verification email to confirm your address.
            </p>
        </div>
    </div>
</div>
@endsection
