@extends('layouts.app')

@section('title', 'Home | Personal Blog')

@section('content')
    <section style="position:relative;isolation:isolate;overflow:hidden;background:#0a0a0a;min-height:clamp(34rem,80vh,48rem)">
        @once
            <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/4.0.0/model-viewer.min.js"></script>
        @endonce

        <model-viewer
            id="hero-model"
            src="{{ asset('models/smoking-room-web.glb') }}"
            alt="3D scene of an abandoned room reclaimed by nature"
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
            style="position:absolute;inset:0;z-index:0;width:100%;height:100%;--poster-color:transparent;background:transparent;"
        ></model-viewer>

        <div aria-hidden="true"
             style="position:absolute;inset:0;z-index:1;pointer-events:none;background:linear-gradient(to top,rgba(0,0,0,.78),rgba(0,0,0,.25) 50%,rgba(0,0,0,0) 80%)"></div>

        <div style="position:relative;z-index:2;display:flex;align-items:center;justify-content:center;min-height:inherit;padding:7rem 1rem 4rem;text-align:center;pointer-events:none">
            <div style="max-width:42rem;margin:0 auto;text-align:center">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-1.5 text-sm font-medium backdrop-blur"
                      style="color:#fff">
                    <span class="h-2 w-2 rounded-full bg-accent-primary"></span>
                    Ideas worth sharing
                </span>

                <h1 class="mt-6 text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl"
                    style="color:#fff;text-shadow:0 2px 16px rgba(0,0,0,.5)">
                    Discover ideas.
                    <span class="block text-accent-primary">Share knowledge.</span>
                </h1>

                <p class="mx-auto mt-6 max-w-xl text-base leading-8 sm:text-lg"
                   style="color:rgba(255,255,255,.9);text-shadow:0 1px 8px rgba(0,0,0,.5)">
                    Read articles from writers across the platform, find new perspectives,
                    and publish what you have learned.
                </p>

                <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:.75rem;margin-top:2.5rem;pointer-events:auto">
                    <x-button :href="route('posts.index')" size="lg">
                        Explore articles
                    </x-button>

                    <a href="{{ route('register') }}"
                       style="display:inline-flex;align-items:center;justify-content:center;border-radius:.75rem;border:1px solid rgba(255,255,255,.45);background:rgba(255,255,255,.12);padding:.75rem 1.5rem;font-weight:600;color:#fff;-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px)">
                        Start writing
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
                        seconds: 16
                    },

                    // ---- Optional extras (0 = off) ----
                    follow: 0,
                    scrollDolly: 0
                };

                var debug = location.search.indexOf('cam') !== -1;
                var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (reduce) { cfg.loop = null; cfg.follow = 0; cfg.scrollDolly = 0; }

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
                        mv.minCameraOrbit = (base.theta - 40) + 'deg ' + (base.phi - 20) + 'deg ' + (base.r * 0.4) + 'm';
                        mv.maxCameraOrbit = (base.theta + 40) + 'deg ' + (base.phi + 20) + 'deg ' + base.r + 'm';
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
                        var deg = function (rad) { return (rad * 180 / Math.PI).toFixed(1); };
                        console.log(
                            "orbit: '" + deg(o.theta) + 'deg ' + deg(o.phi) + 'deg ' + o.radius.toFixed(3) + "m'",
                            " target: '" + t.x.toFixed(3) + 'm ' + t.y.toFixed(3) + 'm ' + t.z.toFixed(3) + "m'"
                        );
                    });
                    return;
                }

                if (!cfg.loop && !cfg.follow && !cfg.scrollDolly) return;

                // ---- Scripted camera motion ----
                var mx = 0, my = 0, cx = 0, cy = 0, visible = true;

                window.addEventListener('pointermove', function (e) {
                    var b = hero.getBoundingClientRect();
                    if (e.clientY < b.top || e.clientY > b.bottom) return;
                    mx = (e.clientX / window.innerWidth) * 2 - 1;
                    my = ((e.clientY - b.top) / b.height) * 2 - 1;
                }, { passive: true });

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
                    var pan   = L ? Math.sin(angle) * L.pan : 0;
                    var tilt  = L ? Math.sin(angle * 2) * L.tilt : 0;
                    var dolly = L ? (1 - Math.cos(angle)) / 2 * L.dolly : 0;

                    var b = hero.getBoundingClientRect();
                    var progress = Math.min(1, Math.max(0, -b.top / b.height));

                    var theta = base.theta + pan + cx * cfg.follow;
                    var phi   = base.phi + tilt + cy * cfg.follow * 0.4;
                    var r     = base.r * (1 - dolly) * (1 - cfg.scrollDolly * progress);

                    mv.cameraOrbit = theta.toFixed(2) + 'deg ' + phi.toFixed(2) + 'deg ' + r.toFixed(4) + 'm';
                }
                requestAnimationFrame(tick);
            })();
        </script>
    </section>

    @if (count($featuredPosts) > 0)
        <section class="border-y border-default bg-surface-secondary/60" aria-labelledby="featured-heading">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <div class="mb-10 flex items-center gap-5">
                    <x-illustration
                        name="featured-deco"
                        ratio="aspect-square"
                        class="hidden h-20 w-20 shrink-0 sm:block"
                    />

                    <div>
                        <h2 id="featured-heading" class="text-3xl font-bold tracking-tight text-primary sm:text-4xl">
                            Featured articles
                        </h2>
                        <p class="mt-2 text-secondary">
                            Hand-picked stories worth your time.
                        </p>
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

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8" aria-labelledby="recent-heading">
        <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end justify-between">
            <div>
                <h2 id="recent-heading" class="text-2xl font-bold tracking-tight text-primary sm:text-4xl">
                    Latest articles
                </h2>
                <p class="mt-2 text-secondary">
                    The newest stories published across the platform.
                </p>
            </div>

            <x-button :href="route('posts.index')" variant="outline">
                View all articles
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17 17 7M7 7h10v10"/>
                </svg>
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
                    class="mx-auto w-10 sm:w-72"
                />

                <h3 class="mt-6 text-xl font-semibold text-primary">
                    No articles yet
                </h3>
                <p class="mx-auto mt-2 max-w-md text-secondary">
                    No public articles to show right now. Be the first to publish one.
                </p>

                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <x-button :href="route('register')">
                        Write an article
                    </x-button>
                    <x-button :href="route('posts.index')" variant="outline">
                        Explore articles
                    </x-button>
                </div>
            </div>
        @endif
    </section>

    <section class="border-y border-default bg-surface-secondary/60" aria-labelledby="steps-heading">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 id="steps-heading" class="text-3xl font-bold tracking-tight text-primary sm:text-4xl">
                    From idea to published in three steps
                </h2>
                <p class="mt-3 text-secondary">
                    No setup. Create an account and start writing.
                </p>
            </div>

            <ol class="mt-12 grid gap-6 md:grid-cols-3">
                @php
                    $steps = [
                        ['name' => 'step-write',   'title' => 'Write',   'text' => 'Draft your article in a clean editor and add a cover image.'],
                        ['name' => 'step-publish', 'title' => 'Publish', 'text' => 'Review your draft, then publish it to your blog when it is ready.'],
                        ['name' => 'step-share',   'title' => 'Share',   'text' => 'Send the link to readers and see your article in the community feed.'],
                    ];
                @endphp

                @foreach ($steps as $i => $step)
                    <li class="flex flex-col rounded-3xl border border-default bg-surface p-6 sm:p-8">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-primary text-sm font-semibold text-white">
                                {{ $i + 1 }}
                            </span>
                            <h3 class="text-lg font-semibold text-primary">{{ $step['title'] }}</h3>
                        </div>
                        <p class="mt-3 leading-7 text-secondary">{{ $step['text'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ============================================================
         CALL TO ACTION
         SVG slot: cta  ->  public/images/illustrations/cta.svg
         Suggested size: about 480x400
         ============================================================ --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        <div class="relative isolate overflow-hidden border-accent-primary/20">
            <div class="pointer-events-none absolute -right-20 -top-24 -z-10 h-72 w-72 rounded-full bg-accent-primary/10 blur-3xl" aria-hidden="true"></div>

            <div class="grid items-center gap-8 px-6 sm:px-12 sm:py-16 lg:grid-cols-5 lg:gap-12">
                <div class="text-center lg:col-span-3 lg:text-left">
                    <h2 class="text-3xl font-bold tracking-tight text-primary sm:text-4xl">
                        Have something worth sharing?
                    </h2>

                    <p class="mx-auto mt-4 max-w-xl leading-7 text-secondary lg:mx-0">
                        Create a free account and publish your knowledge, experiences, and ideas for the community.
                    </p>

                    <div class="mt-8">
                        <x-button :href="route('register')" size="lg">
                            Create an account
                            <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0-5 5m5-5H6"/>
                            </svg>
                        </x-button>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection