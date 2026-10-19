@extends('layouts.app')

@section('title', 'Email Verified Successfully')

@section('content')
<x-room-background id="verify-bg" min-height="clamp(32rem,85vh,52rem)" :pan="12" :dolly="0.15" :seconds="22">
    <div class="w-full max-w-md">
        <!-- Success Icon -->
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 bg-accent-primary rounded-full flex items-center justify-center shadow-lg">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        </div>

        <!-- Header (white text, because it sits on the dark 3D scene) -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold mb-2" style="color:#fff;text-shadow:0 2px 16px rgba(0,0,0,.5)">
                Email Verified!
            </h1>
            <p style="color:rgba(255,255,255,.9);text-shadow:0 1px 8px rgba(0,0,0,.5)">
                Your account has been successfully verified
            </p>
        </div>

        <!-- Content Card -->
        <div class="card">
            <div class="text-center space-y-6">
                <p class="text-secondary">
                    You can now access all features of your account and start creating amazing content.
                </p>

                <div class="space-y-3">
                    <a href="{{ route('login') }}" class="p-4 rounded-3xl btn-primary w-full justify-center inline-flex">
                        Sign In to Your Account
                    </a>
                    <a href="{{ url('/') }}" class="p-4 border btn-secondary w-full justify-center inline-flex">
                        Explore the Blog
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-room-background>
@endsection
