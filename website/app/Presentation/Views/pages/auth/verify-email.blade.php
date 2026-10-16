@extends('layouts.app')

@section('title', 'Verify Your Email')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Icon -->
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-accent-primary rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-surface" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">Check Your Email</h1>
            <p class="text-secondary">We've sent a verification link to your email address</p>
        </div>

        <!-- Content Card -->
        <div class="card">
            @if(session('status'))
                <x-alert type="success" dismissible class="mb-6">
                    {{ session('status') }}
                </x-alert>
            @endif

            @if($errors->any())
                <x-alert type="error" dismissible class="mb-6">
                    {{ $errors->first() }}
                </x-alert>
            @endif

            <div class="text-center space-y-4">
                <p class="text-sm text-secondary">
                    Please check your inbox and click the verification link to activate your account.
                    The link will expire in 24 hours.
                </p>

                <div class="bg-surface-secondary rounded-lg p-4">
                    <p class="text-sm text-primary font-medium mb-2">Didn't receive the email?</p>
                    <ul class="text-xs text-secondary space-y-1 text-left">
                        <li>• Check your spam or junk folder</li>
                        <li>• Make sure the email address is correct</li>
                        <li>• Wait a few minutes for the email to arrive</li>
                    </ul>
                </div>

                <!-- Resend Form -->
                <form method="POST" action="{{ route('identity.email.resend') }}" class="pt-4">
                    @csrf
                    <x-button type="submit" variant="secondary" class="w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Resend Verification Email
                    </x-button>
                </form>

                <!-- Back to Home -->
                <div class="pt-4 border-t border-default">
                    <a href="{{ url('/') }}" class="text-sm text-accent-primary hover:underline">
                        ← Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
