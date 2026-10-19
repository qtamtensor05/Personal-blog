@extends ('layouts.app')

@section ('title', 'Verify Your Email')

@section ('content')
    <div class="flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full max-w-md">
            <!-- Icon -->
            <div class="mb-6 flex justify-center">
                <div
                    class="bg-accent-primary flex h-20 w-20 items-center justify-center rounded-full"
                >
                    <svg class="text-surface h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
            </div>

            <!-- Header -->
            <div class="mb-8 text-center">
                <h1 class="text-primary mb-2 text-3xl font-bold">Check Your Email</h1>
                <p class="text-secondary">We've sent a verification link to your email address</p>
            </div>

            <!-- Content Card -->
            <div class="card">
                @if (session('status'))
                    <x-alert type="success" dismissible class="mb-6">
                        {{ session('status') }}
                    </x-alert>
                @endif

                @if ($errors->any())
                    <x-alert type="error" dismissible class="mb-6">
                        {{ $errors->first() }}
                    </x-alert>
                @endif

                <div class="space-y-4 text-center">
                    <p class="text-secondary text-sm">Please check your inbox and click the verification link to activate your account. The link will expire in 24 hours.</p>

                    <div class="bg-surface-secondary rounded-lg p-4">
                        <p class="text-primary mb-2 text-sm font-medium">Didn't receive the email?</p>
                        <ul class="text-secondary space-y-1 text-left text-xs">
                            <li>• Check your spam or junk folder</li>
                            <li>• Make sure the email address is correct</li>
                            <li>• Wait a few minutes for the email to arrive</li>
                        </ul>
                    </div>

                    <!-- Resend Form -->
                    <form method="POST" action="{{ route('identity.email.resend') }}" class="pt-4">
                        @csrf
                        <x-button type="submit" variant="secondary" class="w-full justify-center">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Resend Verification Email
                        </x-button>
                    </form>

                    <!-- Back to Home -->
                    <div class="border-default border-t pt-4">
                        <a
                            href="{{ url('/') }}"
                            class="text-accent-primary text-sm hover:underline"
                        >
                            ← Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
