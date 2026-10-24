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

    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js" defer></script>
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
                    class="hover:text-accent-primary flex items-center justify-center gap-2 text-lg font-semibold transition-colors"
                >
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center">
                        <i
                            class="fa-solid fa-book-open text-xl leading-none"
                            aria-hidden="true"
                        ></i>
                    </span>
                    <span>{{ config('app.name', 'Blog') }}</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden items-center gap-6 md:flex">
                    <a
                        href="{{ url('/') }}"
                        class="text-sm font-medium hover:text-accent-primary transition-colors {{ request()->is('/') ? 'text-accent-primary' : '' }}"
                    >
                        {{ __('ui.home') }}
                    </a>
                    <a
                        href="{{ url('/posts') }}"
                        class="text-sm font-medium hover:text-accent-primary transition-colors {{ request()->is('posts*') ? 'text-accent-primary' : '' }}"
                    >
                        {{ __('ui.community_articles') }}
                    </a>
                    @auth
                        <a
                            href="{{ route('blogs.index') }}"
                            class="text-sm font-medium hover:text-accent-primary transition-colors {{ request()->routeIs('blogs.*') ? 'text-accent-primary' : '' }}"
                        >
                            {{ __('ui.my_blogs') }}
                        </a>
                    @endauth
                </div>

                <!-- Auth Actions -->
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('interface-locale.update') }}">
                        @csrf
                        <label
                            class="sr-only"
                            for="interface-locale"
                            >{{ __('template.language') }}</label
                        >
                        <select
                            id="interface-locale"
                            name="locale"
                            class="input w-auto appearance-none py-1 text-sm"
                            onchange="this.form.submit()"
                        >
                            <option value="vi" @selected (app()->getLocale() === 'vi')>VI</option>
                            <option value="en" @selected (app()->getLocale() === 'en')>EN</option>
                        </select>
                    </form>
                    @guest
                        @include ('layouts.partials.theme-toggle')
                        <a
                            href="{{ route('login') }}"
                            class="btn-ghost hidden rounded-md border px-4 py-2 text-sm sm:inline-flex"
                        >
                            {{ __('ui.sign_in') }}
                        </a>
                        <a
                            href="{{ url('/register') }}"
                            class="btn-primary rounded-md px-4 py-2 text-sm"
                        >
                            {{ __('ui.get_started') }}
                        </a>
                    @else
                        <a
                            href="{{ route('notifications.index') }}"
                            class="border-default bg-surface text-secondary hover:border-accent-primary hover:text-accent-primary relative inline-flex h-10 w-10 items-center justify-center rounded-md border transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-primary"
                            aria-label="{{ __('ui.notifications') }}"
                        >
                            <i class="fa-solid fa-bell h-5 w-5" aria-hidden="true"></i>
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
                        <div class="relative hidden items-center md:flex" x-data="{ open: false }" @click.away="open = false" @keydown.escape.window="open = false">
                            <button
                                type="button"
                                @click="open = !open"
                                :aria-expanded="open.toString()"
                                aria-haspopup="true"
                                aria-controls="account-dropdown"
                                class="hover:text-accent-primary flex items-center gap-2 rounded-md text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-primary"
                            >
                                @if ($profileAvatarUrl)
                                    <img
                                        src="{{ $profileAvatarUrl }}"
                                        alt=""
                                        class="h-8 w-8 rounded-full object-cover"
                                    />
                                @else
                                    <span
                                        class="bg-accent-primary text-surface flex h-8 w-8 items-center justify-center rounded-full font-semibold"
                                    >
                                        {{
                                            mb_strtoupper(
                                                mb_substr(auth()->user()->name, 0, 1),
                                            )
                                        }}
                                    </span>
                                @endif
                                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                                <i class="fa-solid fa-chevron-down h-4 w-4" aria-hidden="true"></i>
                            </button>

                            <!-- Dropdown -->
                            <div
                                id="account-dropdown"
                                x-show="open"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                class="bg-surface border-default absolute top-full right-0 z-[60] mt-2 w-52 origin-top-right rounded-lg border py-1 shadow-lg"
                                style="display: none"
                            >
                                <a
                                    href="{{ route('profile.edit') }}"
                                    class="hover:bg-surface-secondary block px-4 py-2 text-sm"
                                >
                                    {{ __('ui.profile') }}
                                </a>
                                @if ($canAccessAdminConsole)
                                    <a
                                        href="{{ route('admin.dashboard') }}"
                                        class="text-accent-primary hover:bg-surface-secondary block px-4 py-2 text-sm font-medium"
                                    >
                                        {{ __('ui.admin_console') }}
                                    </a>
                                @endif
                                <a
                                    href="{{ route('blogs.index') }}"
                                    class="hover:bg-surface-secondary block px-4 py-2 text-sm"
                                >
                                    {{ __('ui.my_blogs') }}
                                </a>
                                <a
                                    href="{{ route('bookmarks.index') }}"
                                    class="hover:bg-surface-secondary block px-4 py-2 text-sm"
                                >
                                    {{ __('bookmarks.title') }}
                                </a>
                                <hr class="border-default my-1" />
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="hover:bg-surface-secondary w-full px-4 py-2 text-left text-sm"
                                    >
                                        {{ __('ui.sign_out') }}
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
                        aria-label="{{ __('ui.toggle_menu') }}"
                        :aria-expanded="mobileMenuOpen.toString()"
                        aria-controls="mobile-navigation"
                    >
                        <i class="fa-solid fa-bars h-6 w-6" aria-hidden="true"></i>
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
                        {{ __('ui.home') }}
                    </a>
                    <a
                        href="{{ url('/posts') }}"
                        class="hover:text-accent-primary text-sm font-medium transition-colors"
                    >
                        {{ __('ui.community_articles') }}
                    </a>
                    @auth
                        <a
                            href="{{ route('blogs.index') }}"
                            class="hover:text-accent-primary text-sm font-medium transition-colors"
                        >
                            {{ __('ui.my_blogs') }}
                        </a>
                        <div class="border-default border-t pt-3"></div>
                        <a
                            href="{{ route('profile.edit') }}"
                            class="hover:text-accent-primary text-sm font-medium transition-colors"
                        >
                            {{ __('ui.profile') }}
                        </a>
                        <a
                            href="{{ route('bookmarks.index') }}"
                            class="hover:text-accent-primary text-sm font-medium transition-colors"
                        >
                            {{ __('bookmarks.title') }}
                        </a>
                        @if ($canAccessAdminConsole)
                            <a
                                href="{{ route('admin.dashboard') }}"
                                class="text-accent-primary hover:text-accent-primary text-sm font-medium transition-colors"
                            >
                                {{ __('ui.admin_console') }}
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="hover:text-accent-primary text-left text-sm font-medium transition-colors"
                            >
                                {{ __('ui.sign_out') }}
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main data-animate="page">
        @yield ('content')
    </main>

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
                    <p class="text-secondary mb-4 text-sm leading-relaxed">{{ __('ui.footer_description') }}</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-primary mb-3 text-sm font-semibold">
                        {{ __('ui.quick_links') }}
                    </h4>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a
                                href="{{ url('/') }}"
                                class="hover:text-accent-primary transition-colors"
                                >{{ __('ui.home') }}</a
                            >
                        </li>
                        <li>
                            <a
                                href="{{ url('/posts') }}"
                                class="hover:text-accent-primary transition-colors"
                                >{{ __('ui.community_articles') }}</a
                            >
                        </li>
                        @auth
                            <li>
                                <a
                                    href="{{ route('blogs.index') }}"
                                    class="hover:text-accent-primary transition-colors"
                                    >{{ __('ui.my_blogs') }}</a
                                >
                            </li>
                        @endauth
                    </ul>
                </div>

                <!-- Account -->
                <div>
                    <h4 class="text-primary mb-3 text-sm font-semibold">{{ __('ui.account') }}</h4>
                    <ul class="space-y-2 text-sm">
                        @guest
                            <li>
                                <a
                                    href="{{ route('login') }}"
                                    class="hover:text-accent-primary transition-colors"
                                    >{{ __('ui.sign_in') }}</a
                                >
                            </li>
                            <li>
                                <a
                                    href="{{ url('/register') }}"
                                    class="hover:text-accent-primary transition-colors"
                                    >{{ __('ui.register') }}</a
                                >
                            </li>
                        @else
                            <li>
                                <a
                                    href="{{ route('profile.edit') }}"
                                    class="hover:text-accent-primary transition-colors"
                                    >{{ __('ui.profile') }}</a
                                >
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="hover:text-accent-primary transition-colors"
                                    >
                                        {{ __('ui.sign_out') }}
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
                <p class="text-secondary text-sm">&copy; {{ date('Y') }} {{ config('app.name', 'Personal Blog') }}. {{ __('ui.all_rights_reserved') }}</p>
                <p class="text-secondary text-sm">
                    {{ __('ui.built_with') }}
                    <span class="text-accent-primary">Laravel {{ app()->version() }}</span>
                </p>
            </div>
        </div>
    </footer>

    <x-modal id="appErrorModal" :title="__('ui.something_wrong')" />

    <!-- Alpine.js for mobile menu -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    @stack ('scripts')
</body>
</html>
