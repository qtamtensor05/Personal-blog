<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="claude:viewport-fit-sides" content="cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Personal Blog') }}</title>

    @fonts

    @vite(['app/Presentation/Css/app.css', 'app/Presentation/Js/app.js'])

    @stack('styles')
</head>
<body class="antialiased">
    <!-- Header -->
    <header class="bg-surface border-b border-default sticky top-0 z-50">
        <nav class="container-custom py-4">
            <div class="flex items-center justify-between">
                <!-- Logo/Brand -->
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-primary font-semibold text-lg hover:text-accent-primary transition-colors">
                    <svg class="w-8 h-8" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="16" cy="16" r="14" stroke="currentColor" stroke-width="2"/>
                        <path d="M12 12h8M10 16h12M12 20h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span>{{ config('app.name', 'Blog') }}</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center gap-6">
                    <a href="{{ url('/') }}" class="text-sm font-medium hover:text-accent-primary transition-colors {{ request()->is('/') ? 'text-accent-primary' : '' }}">
                        Home
                    </a>
                    <a href="{{ url('/posts') }}" class="text-sm font-medium hover:text-accent-primary transition-colors {{ request()->is('posts*') ? 'text-accent-primary' : '' }}">
                        Articles
                    </a>
                    @auth
                        <a href="{{ url('/my-posts') }}" class="text-sm font-medium hover:text-accent-primary transition-colors {{ request()->is('my-posts*') ? 'text-accent-primary' : '' }}">
                            My Posts
                        </a>
                    @endauth
                </div>

                <!-- Auth Actions -->
                <div class="flex items-center gap-3">
                    @guest
                        <a href="{{ route('login') }}" class="btn-ghost text-sm hidden sm:inline-flex">
                            Sign In
                        </a>
                        <a href="{{ url('/register') }}" class="btn-primary text-sm">
                            Get Started
                        </a>
                    @else
                        <div class="relative" x-data="{ open: false }">
                            <button
                                @click="open = !open"
                                @click.away="open = false"
                                class="flex items-center gap-2 text-sm font-medium hover:text-accent-primary transition-colors"
                            >
                                <div class="w-8 h-8 rounded-full bg-accent-primary flex items-center justify-center text-surface font-semibold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown -->
                            <div
                                x-show="open"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-48 bg-surface border border-default rounded-lg shadow-lg py-1"
                                style="display: none;"
                            >
                                <a href="{{ url('/profile') }}" class="block px-4 py-2 text-sm hover:bg-surface-secondary">
                                    Profile
                                </a>
                                <a href="{{ url('/my-posts') }}" class="block px-4 py-2 text-sm hover:bg-surface-secondary">
                                    My Posts
                                </a>
                                <hr class="my-1 border-default">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-surface-secondary">
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endguest

                    <!-- Mobile Menu Toggle -->
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="md:hidden btn-ghost p-2"
                        aria-label="Toggle menu"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div
                x-show="mobileMenuOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="md:hidden mt-4 pt-4 border-t border-default"
                style="display: none;"
            >
                <div class="flex flex-col gap-3">
                    <a href="{{ url('/') }}" class="text-sm font-medium hover:text-accent-primary transition-colors">
                        Home
                    </a>
                    <a href="{{ url('/posts') }}" class="text-sm font-medium hover:text-accent-primary transition-colors">
                        Articles
                    </a>
                    @auth
                        <a href="{{ url('/my-posts') }}" class="text-sm font-medium hover:text-accent-primary transition-colors">
                            My Posts
                        </a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-surface border-t border-default mt-16">
        <div class="container-custom section-spacing">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <!-- About -->
                <div class="md:col-span-2">
                    <h3 class="text-primary font-semibold text-lg mb-3">{{ config('app.name', 'Personal Blog') }}</h3>
                    <p class="text-sm text-secondary leading-relaxed mb-4">
                        A space for sharing thoughts, ideas, and stories. Built with Laravel and passion for clean code.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-primary font-semibold text-sm mb-3">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ url('/') }}" class="hover:text-accent-primary transition-colors">Home</a></li>
                        <li><a href="{{ url('/posts') }}" class="hover:text-accent-primary transition-colors">Articles</a></li>
                        @auth
                            <li><a href="{{ url('/my-posts') }}" class="hover:text-accent-primary transition-colors">My Posts</a></li>
                        @endauth
                    </ul>
                </div>

                <!-- Account -->
                <div>
                    <h4 class="text-primary font-semibold text-sm mb-3">Account</h4>
                    <ul class="space-y-2 text-sm">
                        @guest
                            <li><a href="{{ route('login') }}" class="hover:text-accent-primary transition-colors">Sign In</a></li>
                            <li><a href="{{ url('/register') }}" class="hover:text-accent-primary transition-colors">Register</a></li>
                        @else
                            <li><a href="{{ url('/profile') }}" class="hover:text-accent-primary transition-colors">Profile</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="hover:text-accent-primary transition-colors">Sign Out</button>
                                </form>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="pt-6 border-t border-default flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-sm text-secondary">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Personal Blog') }}. All rights reserved.
                </p>
                <p class="text-sm text-secondary">
                    Built with <span class="text-accent-primary">Laravel {{ app()->version() }}</span>
                </p>
            </div>
        </div>
    </footer>

    <!-- Alpine.js for mobile menu -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    @stack('scripts')
</body>
</html>
