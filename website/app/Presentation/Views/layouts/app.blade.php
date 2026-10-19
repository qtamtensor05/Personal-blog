<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="claude:viewport-fit-sides" content="cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <script>
        (() => {
            let savedTheme = null;

            try {
                savedTheme = localStorage.getItem('theme');
            } catch {
                // Storage may be disabled; the system preference still works.
            }
            const theme =
                savedTheme === 'light' || savedTheme === 'dark'
                    ? savedTheme
                    : window.matchMedia('(prefers-color-scheme: dark)').matches
                      ? 'dark'
                      : 'light';

            document.documentElement.dataset.theme = theme;
        })();
    </script>

    <title>{{ $title ?? config('app.name', 'Personal Blog') }}</title>

    @fonts

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    />

    @vite (['app/Presentation/Css/app.css', 'app/Presentation/Js/app.js'])

    @stack ('styles')
</head>
<body class="antialiased">
    <!-- Header -->
    <header class="bg-surface border-default sticky top-0 z-50 border-b">
        <nav
            class="container-custom py-4"
            x-data="{ mobileMenuOpen: false }"
            @keydown.escape.window="mobileMenuOpen = false"
        >
            <div class="flex items-center justify-between">
                <!-- Logo/Brand -->
                <a
                    href="{{ url('/') }}"
                    class="text-primary hover:text-accent-primary flex items-center gap-2 text-lg font-semibold transition-colors"
                >
                    <svg class="h-8 w-8" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="16" cy="16" r="14" stroke="currentColor" stroke-width="2" />
                        <path d="M12 12h8M10 16h12M12 20h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <span>{{ config('app.name', 'Blog') }}</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden items-center gap-6 md:flex">
                    <a
                        href="{{ url('/') }}"
                        class="text-sm font-medium hover:text-accent-primary transition-colors {{ request()->is('/') ? 'text-accent-primary' : '' }}"
                    >
                        Home
                    </a>
                    <a
                        href="{{ url('/posts') }}"
                        class="text-sm font-medium hover:text-accent-primary transition-colors {{ request()->is('posts*') ? 'text-accent-primary' : '' }}"
                    >
                        Articles
                    </a>
                    @auth
                        <a
                            href="{{ route('blogs.index') }}"
                            class="text-sm font-medium hover:text-accent-primary transition-colors {{ request()->routeIs('blogs.*') ? 'text-accent-primary' : '' }}"
                        >
                            My Blogs
                        </a>
                    @endauth
                </div>

                <!-- Auth Actions -->
                <div class="flex items-center gap-3">
                    @guest
                        @include ('layouts.partials.theme-toggle')
                        <a
                            href="{{ route('login') }}"
                            class="btn-ghost hidden rounded-md border px-4 py-2 text-sm sm:inline-flex"
                        >
                            Sign In
                        </a>
                        <a
                            href="{{ url('/register') }}"
                            class="btn-primary rounded-md px-4 py-2 text-sm"
                        >
                            Get Started
                        </a>
                    @else
                        <a
                            href="{{ route('notifications.index') }}"
                            class="btn-ghost relative p-2"
                            aria-label="Notifications"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 01-6 0m6 0H9" />
                            </svg>
                            @if ($notificationUnreadCount > 0)
                                <span
                                    class="absolute -top-1 -right-1 min-w-5 rounded-full bg-red-600 px-1 text-center text-xs font-semibold text-white"
                                >
                                    {{
                                        $notificationUnreadCount > 99
                                            ? '99+'
                                            : $notificationUnreadCount
                                    }}
                                </span>
                            @endif
                        </a>
                        @include ('layouts.partials.theme-toggle')
                        <div class="relative hidden md:block" x-data="{ open: false }">
                            <button
                                @click="open = !open"
                                @click.away="open = false"
                                class="hover:text-accent-primary flex items-center gap-2 text-sm font-medium transition-colors"
                            >
                                @if ($profileAvatarUrl)
                                    <img
                                        src="{{ $profileAvatarUrl }}"
                                        alt="{{ auth()->user()->name }}"
                                        class="h-8 w-8 rounded-full object-cover"
                                    />
                                @else
                                    <div
                                        class="bg-accent-primary text-surface flex h-8 w-8 items-center justify-center rounded-full font-semibold"
                                    >
                                        {{
                                            mb_strtoupper(
                                                mb_substr(auth()->user()->name, 0, 1),
                                            )
                                        }}
                                    </div>
                                @endif
                                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
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
                                class="bg-surface border-default absolute right-0 mt-2 w-48 rounded-lg border py-1 shadow-lg"
                                style="display: none"
                            >
                                <a
                                    href="{{ route('profile.edit') }}"
                                    class="hover:bg-surface-secondary block px-4 py-2 text-sm"
                                >
                                    Profile
                                </a>
                                @if ($canAccessAdminConsole)
                                    <a
                                        href="{{ route('admin.dashboard') }}"
                                        class="text-accent-primary hover:bg-surface-secondary block px-4 py-2 text-sm font-medium"
                                    >
                                        Admin Console
                                    </a>
                                @endif
                                <a
                                    href="{{ route('blogs.index') }}"
                                    class="hover:bg-surface-secondary block px-4 py-2 text-sm"
                                >
                                    My Blogs
                                </a>
                                <a
                                    href="{{ route('blogs.create') }}"
                                    class="text-accent-primary hover:bg-surface-secondary block px-4 py-2 text-sm font-medium"
                                >
                                    Create Blog
                                </a>
                                <hr class="border-default my-1" />
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="hover:bg-surface-secondary w-full px-4 py-2 text-left text-sm"
                                    >
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endguest

                    <!-- Mobile Menu Toggle -->
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="btn-ghost p-2 md:hidden"
                        aria-label="Toggle menu"
                        :aria-expanded="mobileMenuOpen.toString()"
                        aria-controls="mobile-navigation"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div
                id="mobile-navigation"
                x-show="mobileMenuOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="border-default mt-4 border-t pt-4 md:hidden"
                style="display: none"
            >
                <div
                    class="flex flex-col gap-3"
                    @click="
                        if ($event.target.closest('a, button[type=submit]')) mobileMenuOpen = false;
                    "
                >
                    <a
                        href="{{ url('/') }}"
                        class="hover:text-accent-primary text-sm font-medium transition-colors"
                    >
                        Home
                    </a>
                    <a
                        href="{{ url('/posts') }}"
                        class="hover:text-accent-primary text-sm font-medium transition-colors"
                    >
                        Articles
                    </a>
                    @auth
                        <a
                            href="{{ route('blogs.index') }}"
                            class="hover:text-accent-primary text-sm font-medium transition-colors"
                        >
                            My Blogs
                        </a>
                        <a
                            href="{{ route('blogs.create') }}"
                            class="text-accent-primary hover:text-accent-primary text-sm font-medium transition-colors"
                        >
                            Create Blog
                        </a>
                        <div class="border-default border-t pt-3"></div>
                        <a
                            href="{{ route('profile.edit') }}"
                            class="hover:text-accent-primary text-sm font-medium transition-colors"
                        >
                            Profile
                        </a>
                        @if ($canAccessAdminConsole)
                            <a
                                href="{{ route('admin.dashboard') }}"
                                class="text-accent-primary hover:text-accent-primary text-sm font-medium transition-colors"
                            >
                                Admin Console
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="hover:text-accent-primary text-left text-sm font-medium transition-colors"
                            >
                                Sign Out
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <phantom-ui loading animation="shimmer" reveal="0.2" aria-label="Loading page">
        <main>
            @yield ('content')
        </main>
    </phantom-ui>

    <!-- Footer -->
    <footer class="bg-surface border-default mt-16 border-t">
        <div class="container-custom section-spacing">
            <div class="mb-8 grid grid-cols-1 gap-8 md:grid-cols-4">
                <!-- About -->
                <div class="md:col-span-2">
                    <h3 class="text-primary mb-3 text-lg font-semibold">
                        {{
                            config(
                                'app.name',
                                'Personal Blog',
                            )
                        }}
                    </h3>
                    <p class="text-secondary mb-4 text-sm leading-relaxed">A space for sharing thoughts, ideas, and stories. Built with Laravel and passion for clean code.</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-primary mb-3 text-sm font-semibold">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a
                                href="{{ url('/') }}"
                                class="hover:text-accent-primary transition-colors"
                                >Home</a
                            >
                        </li>
                        <li>
                            <a
                                href="{{ url('/posts') }}"
                                class="hover:text-accent-primary transition-colors"
                                >Articles</a
                            >
                        </li>
                        @auth
                            <li>
                                <a
                                    href="{{ route('blogs.index') }}"
                                    class="hover:text-accent-primary transition-colors"
                                    >My Blogs</a
                                >
                            </li>
                            <li>
                                <a
                                    href="{{ route('blogs.create') }}"
                                    class="hover:text-accent-primary transition-colors"
                                    >Create Blog</a
                                >
                            </li>
                        @endauth
                    </ul>
                </div>

                <!-- Account -->
                <div>
                    <h4 class="text-primary mb-3 text-sm font-semibold">Account</h4>
                    <ul class="space-y-2 text-sm">
                        @guest
                            <li>
                                <a
                                    href="{{ route('login') }}"
                                    class="hover:text-accent-primary transition-colors"
                                    >Sign In</a
                                >
                            </li>
                            <li>
                                <a
                                    href="{{ url('/register') }}"
                                    class="hover:text-accent-primary transition-colors"
                                    >Register</a
                                >
                            </li>
                        @else
                            <li>
                                <a
                                    href="{{ route('profile.edit') }}"
                                    class="hover:text-accent-primary transition-colors"
                                    >Profile</a
                                >
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="hover:text-accent-primary transition-colors"
                                    >
                                        Sign Out
                                    </button>
                                </form>
                            </li>
                        @endauth
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div
                class="border-default flex flex-col items-center justify-between gap-4 border-t pt-6 sm:flex-row"
            >
                <p class="text-secondary text-sm">&copy; {{ date('Y') }} {{ config('app.name', 'Personal Blog') }}. All rights reserved.</p>
                <p class="text-secondary text-sm">
                    Built with
                    <span class="text-accent-primary">Laravel {{ app()->version() }}</span>
                </p>
            </div>
        </div>
    </footer>

    <x-modal id="appErrorModal" title="Something went wrong" />

    <!-- Alpine.js for mobile menu -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    @stack ('scripts')
</body>
</html>
