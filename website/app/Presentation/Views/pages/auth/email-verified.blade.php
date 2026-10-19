@extends ('layouts.app')

@section ('title', 'Email Verified Successfully')

@section ('content')
    <x-room-background
        id="verify-bg"
        min-height="clamp(32rem,85vh,52rem)"
        :pan="12"
        :dolly="0.15"
        :seconds="22"
    >
        <div class="w-full max-w-md">
            <!-- Success Icon -->
            <div class="mb-6 flex justify-center">
                <div
                    class="bg-accent-primary flex h-20 w-20 items-center justify-center rounded-full shadow-lg"
                >
                    <i class="fa-solid fa-check h-10 w-10 text-white" aria-hidden="true"></i>
                </div>
            </div>

            <!-- Header (white text, because it sits on the dark 3D scene) -->
            <div class="mb-8 text-center">
                <h1
                    class="mb-2 text-3xl font-bold"
                    style="color: #fff; text-shadow: 0 2px 16px rgba(0, 0, 0, 0.5)"
                >
                    {{ __('auth.verified_title') }}
                </h1>
                <p
                    style="
                        color: rgba(255, 255, 255, 0.9);
                        text-shadow: 0 1px 8px rgba(0, 0, 0, 0.5);
                    "
                >{{ __('auth.verified_help') }}</p>
            </div>

            <!-- Content Card -->
            <div class="card">
                <div class="space-y-6 text-center">
                    <p class="text-secondary">{{ __('auth.verified_next') }}</p>

                    <div class="space-y-3">
                        <a
                            href="{{ route('login') }}"
                            class="btn-primary inline-flex w-full justify-center rounded-3xl p-4"
                        >
                            {{ __('auth.sign_in_account') }}
                        </a>
                        <a
                            href="{{ url('/') }}"
                            class="btn-secondary inline-flex w-full justify-center border p-4"
                        >
                            {{ __('auth.explore_blog') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </x-room-background>
@endsection
