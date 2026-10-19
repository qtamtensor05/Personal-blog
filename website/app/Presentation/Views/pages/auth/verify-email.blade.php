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
                    <i class="fa-solid fa-envelope-circle-check text-surface h-10 w-10" aria-hidden="true"></i>
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
                    <p class="text-secondary text-sm">{{ __('auth.check_inbox') }}</p>

                    <div class="bg-surface-secondary rounded-lg p-4">
                        <p class="text-primary mb-2 text-sm font-medium">{{ __('auth.no_verification_email') }}</p>
                        <ul class="text-secondary space-y-1 text-left text-xs">
                            <li>• {{ __('auth.check_spam') }}</li>
                            <li>• {{ __('auth.check_email_address') }}</li>
                            <li>• {{ __('auth.wait_email') }}</li>
                        </ul>
                    </div>

                    <!-- Resend Form -->
                    <form method="POST" action="{{ route('identity.email.resend') }}" class="pt-4">
                        @csrf
                        <x-button type="submit" variant="secondary" class="w-full justify-center">
                            <i class="fa-solid fa-arrow-rotate-right mr-2 h-4 w-4" aria-hidden="true"></i>
                            {{ __('auth.resend_verification') }}
                        </x-button>
                    </form>

                    <!-- Back to Home -->
                    <div class="border-default border-t pt-4">
                        <a
                            href="{{ url('/') }}"
                            class="text-accent-primary text-sm hover:underline"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
