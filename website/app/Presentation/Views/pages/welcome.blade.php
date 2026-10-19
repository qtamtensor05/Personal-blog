@extends ('layouts.app')

@section ('title', __('home.page_title'))

@section ('content')
    <section
        style="
            position: relative;
            isolation: isolate;
            overflow: hidden;
            background: #0a0a0a;
            min-height: clamp(34rem, 80vh, 48rem);
        "
    >
        @once
            <script
                type="module"
                src="https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js"
            ></script>
        @endonce

        <model-viewer
            id="hero-model"
            src="{{ asset('models/smoking-room-web.glb') }}"
            alt="{{ __('home.hero_image_alt') }}"
            disable-zoom
            interaction-prompt="none"
            interpolation-decay="120"
            camera-orbit="0deg 85deg 1m"
            field-of-view="70deg"
            environment-image="neutral"
            exposure="1.6"
            shadow-intensity="0"
            loading="eager"
            reveal="auto"
            style="
                position: absolute;
                inset: 0;
                z-index: 0;
                width: 100%;
                height: 100%;
                --poster-color: transparent;
                background: transparent;
            "
        ></model-viewer>

        <div
            aria-hidden="true"
            style="
                position: absolute;
                inset: 0;
                z-index: 1;
                pointer-events: none;
                background: linear-gradient(
                    to top,
                    rgba(0, 0, 0, 0.78),
                    rgba(0, 0, 0, 0.25) 50%,
                    rgba(0, 0, 0, 0) 80%
                );
            "
        ></div>

        <div
            style="
                position: relative;
                z-index: 2;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: inherit;
                padding: 7rem 1rem 4rem;
                text-align: center;
                pointer-events: none;
            "
        >
            <div style="max-width: 42rem; margin: 0 auto; text-align: center">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-1.5 text-sm font-medium backdrop-blur"
                    style="color: #fff"
                >
                    <span class="bg-accent-primary h-2 w-2 rounded-full"></span>
                    {{ __('home.tagline') }}
                </span>

                <h1
                    class="mt-6 text-4xl leading-[1.1] font-bold tracking-tight sm:text-5xl lg:text-6xl"
                    style="color: #fff; text-shadow: 0 2px 16px rgba(0, 0, 0, 0.5)"
                >
                    {{ __('home.title') }}
                    <span class="text-accent-primary block">{{ __('home.title_accent') }}</span>
                </h1>

                <p
                    class="mx-auto mt-6 max-w-xl text-base leading-8 sm:text-lg"
                    style="
                        color: rgba(255, 255, 255, 0.9);
                        text-shadow: 0 1px 8px rgba(0, 0, 0, 0.5);
                    "
                >{{ __('home.description') }}</p>

                <div
                    style="
                        display: flex;
                        flex-wrap: wrap;
                        justify-content: center;
                        gap: 0.75rem;
                        margin-top: 2.5rem;
                        pointer-events: auto;
                    "
                >
                    <x-button
                        :href="route('posts.index')"
                        size="lg"
                        >{{ __('home.explore') }}</x-button
                    >

                    <a
                        href="{{ auth()->check() ? route('blogs.create') : route('register') }}"
                        style="
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 0.75rem;
                            border: 1px solid rgba(255, 255, 255, 0.45);
                            background: rgba(255, 255, 255, 0.12);
                            padding: 0.75rem 1.5rem;
                            font-weight: 600;
                            color: #fff;
                            -webkit-backdrop-filter: blur(6px);
                            backdrop-filter: blur(6px);
                        "
                    >
                        {{ __('home.start_writing') }}
                    </a>
                </div>
            </div>
        </div>

        <script>
            (function () {
                var mv = document.getElementById('hero-model');
                if (!mv) return;
                var hero = mv.parentElement;

                // ---- Camera settings: places the camera INSIDE the room ----
                var cfg = {
                    theta: 0,
                    phi: 85,
                    radius: 0.25,
                    fov: 90,
                    target: [0.5, 0.35, 0.5],
                    manual: null,

                    // ---- Looping camera motion: repeats forever, smooth at both ends ----
                    loop: {
                        pan: 20,
                        tilt: 3,
                        dolly: 0.25,
                        seconds: 16,
                    },

                    // ---- Optional extras (0 = off) ----
                    follow: 0,
                    scrollDolly: 0,
                };

                var debug = location.search.indexOf('cam') !== -1;
                var reduce =
                    window.matchMedia &&
                    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (reduce) {
                    cfg.loop = null;
                    cfg.follow = 0;
                    cfg.scrollDolly = 0;
                }

                var base = { theta: cfg.theta, phi: cfg.phi, r: 1 };
                var ready = false;

                function place() {
                    if (cfg.manual) {
                        var parts = cfg.manual.orbit.split(' ').map(parseFloat);
                        base = { theta: parts[0], phi: parts[1], r: parts[2] };
                        mv.cameraTarget = cfg.manual.target;
                    } else {
                        var c = mv.getBoundingBoxCenter();
                        var d = mv.getDimensions();
                        var size = Math.max(d.x, d.y, d.z);
                        base = { theta: cfg.theta, phi: cfg.phi, r: size * cfg.radius };

                        var tx = c.x + (cfg.target[0] - 0.5) * d.x;
                        var ty = c.y + (cfg.target[1] - 0.5) * d.y;
                        var tz = c.z + (cfg.target[2] - 0.5) * d.z;
                        mv.cameraTarget = tx + 'm ' + ty + 'm ' + tz + 'm';
                    }

                    mv.fieldOfView = cfg.fov + 'deg';
                    mv.cameraOrbit = base.theta + 'deg ' + base.phi + 'deg ' + base.r + 'm';

                    if (!debug) {
                        // Wide enough for the motion below, but the camera never leaves the room.
                        mv.minCameraOrbit =
                            base.theta -
                            40 +
                            'deg ' +
                            (base.phi - 20) +
                            'deg ' +
                            base.r * 0.4 +
                            'm';
                        mv.maxCameraOrbit =
                            base.theta + 40 + 'deg ' + (base.phi + 20) + 'deg ' + base.r + 'm';
                    }
                    mv.jumpCameraToGoal();
                    ready = true;
                }

                mv.addEventListener('load', place);

                if (debug) {
                    mv.setAttribute('camera-controls', '');
                    mv.removeAttribute('disable-zoom');
                    mv.addEventListener('camera-change', function () {
                        var o = mv.getCameraOrbit();
                        var t = mv.getCameraTarget();
                        var deg = function (rad) {
                            return ((rad * 180) / Math.PI).toFixed(1);
                        };
                        console.log(
                            "orbit: '" +
                                deg(o.theta) +
                                'deg ' +
                                deg(o.phi) +
                                'deg ' +
                                o.radius.toFixed(3) +
                                "m'",
                            " target: '" +
                                t.x.toFixed(3) +
                                'm ' +
                                t.y.toFixed(3) +
                                'm ' +
                                t.z.toFixed(3) +
                                "m'",
                        );
                    });
                    return;
                }

                if (!cfg.loop && !cfg.follow && !cfg.scrollDolly) return;

                // ---- Scripted camera motion ----
                var mx = 0,
                    my = 0,
                    cx = 0,
                    cy = 0,
                    visible = true;

                window.addEventListener(
                    'pointermove',
                    function (e) {
                        var b = hero.getBoundingClientRect();
                        if (e.clientY < b.top || e.clientY > b.bottom) return;
                        mx = (e.clientX / window.innerWidth) * 2 - 1;
                        my = ((e.clientY - b.top) / b.height) * 2 - 1;
                    },
                    { passive: true },
                );

                if ('IntersectionObserver' in window) {
                    new IntersectionObserver(function (entries) {
                        visible = entries[0].isIntersecting;
                    }).observe(hero);
                }

                function tick(now) {
                    requestAnimationFrame(tick);
                    if (!ready || !visible) return;

                    cx += (mx - cx) * 0.06;
                    cy += (my - cy) * 0.06;

                    var L = cfg.loop;
                    var angle = L ? (now / 1000 / L.seconds) * Math.PI * 2 : 0;
                    var pan = L ? Math.sin(angle) * L.pan : 0;
                    var tilt = L ? Math.sin(angle * 2) * L.tilt : 0;
                    var dolly = L ? ((1 - Math.cos(angle)) / 2) * L.dolly : 0;

                    var b = hero.getBoundingClientRect();
                    var progress = Math.min(1, Math.max(0, -b.top / b.height));

                    var theta = base.theta + pan + cx * cfg.follow;
                    var phi = base.phi + tilt + cy * cfg.follow * 0.4;
                    var r = base.r * (1 - dolly) * (1 - cfg.scrollDolly * progress);

                    mv.cameraOrbit =
                        theta.toFixed(2) + 'deg ' + phi.toFixed(2) + 'deg ' + r.toFixed(4) + 'm';
                }
                requestAnimationFrame(tick);
            })();
        </script>
    </section>

    @if (count($featuredPosts) > 0)
        <section
            class="border-default bg-surface-secondary/60 border-y"
            aria-labelledby="featured-heading"
        >
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <div class="mb-10 flex items-center gap-5">
                    <x-illustration
                        name="featured-deco"
                        ratio="aspect-square"
                        class="hidden h-20 w-20 shrink-0 sm:block"
                    />

                    <div>
                        <h2
                            id="featured-heading"
                            class="text-primary text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            {{ __('home.featured_title') }}
                        </h2>
                        <p class="text-secondary mt-2">{{ __('home.featured_description') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($featuredPosts as $post)
                        <x-post-card
                            :title="$post->title"
                            :excerpt="$post->excerpt ?? ''"
                            :url="route('public.post', [$post->blogSlug, $post->slug])"
                            :image="$post->coverUrl"
                            :date="$post->publishedAt"
                            :author="$post->authorName"
                            :isFeatured="$post->isFeatured"
                        />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section
        class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8"
        aria-labelledby="recent-heading"
    >
        <div class="mb-10 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <h2
                    id="recent-heading"
                    class="text-primary text-2xl font-bold tracking-tight sm:text-4xl"
                >
                    {{ __('home.latest_title') }}
                </h2>
                <p class="text-secondary mt-2">{{ __('home.latest_description') }}</p>
            </div>

            <x-button :href="route('posts.index')" variant="outline">
                {{ __('home.view_all') }}
                <i class="fa-solid fa-arrow-right ml-2 h-4 w-4" aria-hidden="true"></i>
            </x-button>
        </div>

        @if (count($recentPosts) > 0)
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($recentPosts as $post)
                    <x-post-card
                        :title="$post->title"
                        :excerpt="$post->excerpt ?? ''"
                        :url="route('public.post', [$post->blogSlug, $post->slug])"
                        :image="$post->coverUrl"
                        :date="$post->publishedAt"
                        :author="$post->authorName"
                    />
                @endforeach
            </div>
        @else
            <div class="mt-4 rounded-2xl px-6 py-14 text-center sm:py-16">
                <x-illustration
                    name="empty"
                    alt=""
                    ratio="aspect-[2/3]"
                    class="mx-auto w-5 sm:w-72"
                />

                <h3 class="text-primary mt-6 text-xl font-semibold">
                    {{ __('home.empty_title') }}
                </h3>
                <p class="text-secondary mx-auto mt-2 max-w-md">{{ __('home.empty_description') }}</p>

                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <x-button :href="auth()->check() ? route('blogs.create') : route('register')">
                        {{ __('home.write_article') }}
                    </x-button>
                    <x-button
                        class="!border-accent-primary !border"
                        :href="route('posts.index')"
                        variant="ghost"
                    >
                        {{ __('home.explore') }}
                    </x-button>
                </div>
            </div>
        @endif
    </section>

    <section
        class="border-default bg-surface-secondary/60 border-y"
        aria-labelledby="steps-heading"
    >
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2
                    id="steps-heading"
                    class="text-primary text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    {{ __('home.steps_title') }}
                </h2>
                <p class="text-secondary mt-3">{{ __('home.steps_description') }}</p>
            </div>

            <ol class="mt-12 grid gap-6 md:grid-cols-3">
                @php
                    $steps = [
                        [
                            'name' => 'step-write',
                            'title' => __('home.step_write_title'),
                            'text' => __('home.step_write_text'),
                        ],
                        [
                            'name' => 'step-publish',
                            'title' => __('home.step_publish_title'),
                            'text' => __('home.step_publish_text'),
                        ],
                        [
                            'name' => 'step-share',
                            'title' => __('home.step_share_title'),
                            'text' => __('home.step_share_text'),
                        ],
                    ];
                @endphp

                @foreach ($steps as $i => $step)
                    <li
                        class="border-default bg-surface flex flex-col rounded-3xl border p-6 sm:p-8"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="bg-accent-primary flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold text-white"
                            >
                                {{ $i + 1 }}
                            </span>
                            <h3 class="text-primary text-lg font-semibold">{{ $step['title'] }}</h3>
                        </div>
                        <p class="text-secondary mt-3 leading-7">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        <div class="border-accent-primary/20 relative isolate overflow-hidden">
            <div
                class="bg-accent-primary/10 pointer-events-none absolute -top-24 -right-20 -z-10 h-72 w-72 rounded-full blur-3xl"
                aria-hidden="true"
            ></div>

            <div class="grid items-center gap-8 px-6 sm:px-12 sm:py-16 lg:grid-cols-5 lg:gap-12">
                <div class="text-center lg:col-span-3 lg:text-left">
                    <h2 class="text-primary text-3xl font-bold tracking-tight sm:text-4xl">
                        {{ __('home.cta_title') }}
                    </h2>

                    <p class="text-secondary mx-auto mt-4 max-w-xl leading-7 lg:mx-0">{{ __('home.cta_description') }}</p>

                    <div class="mt-8">
                        @if (auth()->check())
                            <x-button :href="route('blogs.create')" size="lg">
                                {{ __('home.create_blog') }}
                                <i
                                    class="fa-solid fa-arrow-right ml-2 h-4 w-4"
                                    aria-hidden="true"
                                ></i>
                            </x-button>
                        @else
                            <x-button :href="route('register')" size="lg">
                                {{ __('home.create_account') }}
                                <i
                                    class="fa-solid fa-arrow-right ml-2 h-4 w-4"
                                    aria-hidden="true"
                                ></i>
                            </x-button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
