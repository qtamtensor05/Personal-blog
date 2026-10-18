@extends('layouts.app')

@section('title', 'Reset Your Password')

@section('content')
<x-room-background id="login-bg" min-height="100vh" :pan="10" :dolly="0.12" :seconds="24" :scrim="0.65">
<div class="h-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-mb-2" style="color: #ffffff;">Forgot Your Password?</h1>
            <p class="text-secondary" style="color:rgba(255,255,255,.9);text-shadow:0 1px 8px rgba(0,0,0,.5);">
                No worries! Enter your email and we'll send you reset instructions
            </p>
        </div>

        <!-- Form Card -->
        <div class="card">
            @if(session('status'))
                <x-alert type="success" dismissible class="mb-6">
                    {{ session('status') }}
                </x-alert>
            @endif

            @if($errors->any())
                <x-alert type="error" dismissible class="mb-6">
                    {{ $errors->first('email') ?? $errors->first() }}
                </x-alert>
            @endif

            <form method="POST" action="{{ url('/password/email') }}" class="space-y-5">
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

                <!-- Submit Button -->
                <x-button type="submit" class="w-full justify-center">
                    Send Reset Link
                </x-button>
            </form>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-accent-primary hover:underline">
                    ← Back to Sign In
                </a>
            </div>
        </div>
    </div>
</div>
</x-room-background>
@endsection
