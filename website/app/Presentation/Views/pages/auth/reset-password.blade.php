@extends('layouts.app')

@section('title', 'Set New Password')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">Set New Password</h1>
            <p class="text-secondary">Enter your new password below</p>
        </div>

        <!-- Form Card -->
        <div class="card">
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

            <form method="POST" action="{{ url('/password/reset') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <!-- Email Field (readonly) -->
                <x-input
                    type="email"
                    name="email"
                    label="Email Address"
                    :error="$errors->first('email')"
                    value="{{ old('email', request('email')) }}"
                    readonly
                />

                <!-- New Password Field -->
                <x-input
                    type="password"
                    name="password"
                    label="New Password"
                    :error="$errors->first('password')"
                    placeholder="At least 8 characters"
                    required
                    autofocus
                />

                <!-- Confirm Password Field -->
                <x-input
                    type="password"
                    name="password_confirmation"
                    label="Confirm New Password"
                    :error="$errors->first('password_confirmation')"
                    placeholder="Repeat your new password"
                    required
                />

                <!-- Password Requirements -->
                <div class="bg-surface-secondary rounded-lg p-4">
                    <p class="text-xs font-medium text-primary mb-2">Password Requirements:</p>
                    <ul class="text-xs text-secondary space-y-1">
                        <li>• At least 8 characters long</li>
                        <li>• Mix of uppercase and lowercase letters recommended</li>
                        <li>• Include numbers or special characters for extra security</li>
                    </ul>
                </div>

                <!-- Submit Button -->
                <x-button type="submit" class="w-full justify-center">
                    Reset Password
                </x-button>
            </form>
        </div>
    </div>
</div>
@endsection
