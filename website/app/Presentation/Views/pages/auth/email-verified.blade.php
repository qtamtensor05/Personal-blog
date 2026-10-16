@extends('layouts.app')

@section('title', 'Email Verified Successfully')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
        <!-- Success Icon -->
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-primary mb-2">Email Verified!</h1>
            <p class="text-secondary">Your account has been successfully verified</p>
        </div>

        <!-- Content Card -->
        <div class="card">
            <div class="text-center space-y-6">
                <p class="text-secondary">
                    You can now access all features of your account and start creating amazing content.
                </p>

                <div class="space-y-3">
                    <a href="{{ route('login') }}" class="btn-primary w-full justify-center inline-flex">
                        Sign In to Your Account
                    </a>
                    <a href="{{ url('/') }}" class="btn-secondary w-full justify-center inline-flex">
                        Explore the Blog
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
