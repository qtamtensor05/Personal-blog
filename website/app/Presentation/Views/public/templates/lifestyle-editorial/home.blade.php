@php
    $blog = $page->blog;
    $settings = $page->settings;
    $slots = collect($page->sections)
        ->keyBy('key')
        ->map(fn($section) => array_replace($section->props, $page->slotContent[$section->key] ?? []));
    $header = $slots->get('site-header', []);
    $heroSection = collect($page->sections)->first(fn($section) => $section->section === 'hero');
    $hero = $heroSection
        ? array_replace($heroSection->props, $page->slotContent[$heroSection->key] ?? [])
        : [];
    $latest = $slots->get('latest-posts', []);
    $about = $slots->get('about', []);
    $gallery = $slots->get('gallery', []);
    $profile = $slots->get('sidebar-profile', []);
    $newsletter = $slots->get('newsletter', []);
    $contact = $slots->get('contact', []);
    $footer = $slots->get('site-footer', []);
    $images = asset('images/templates/lifestyle-editorial');
    $aboutImage = !empty($about['mediaId']) && isset($mediaUrls[$about['mediaId']])
        ? $mediaUrls[$about['mediaId']]
        : $images . '/card-field.svg';
    $heroImage =
        !empty($hero['mediaId']) && isset($mediaUrls[$hero['mediaId']])
            ? $mediaUrls[$hero['mediaId']]
            : $images . '/hero.svg';
    $galleryMedia = collect($gallery['mediaIds'] ?? [])
        ->map(fn($id) => $mediaUrls[$id] ?? null)
        ->filter()
        ->values();
    $fallbackCards = ['card-flower.svg', 'card-sky.svg', 'card-field.svg'];

    $screen = $screen ?? 'home';
    $templatePreview = $templatePreview ?? false;
    $search = $search ?? '';
    $homeUrl = $templatePreview
        ? route('templates.preview', $blog->slug)
        : route('public.blog', $blog->slug);
    $screenUrl = fn(string $target) => $templatePreview
        ? route('templates.preview', $blog->slug) . '?screen=' . $target
        : route('public.blog.section', [$blog->slug, $target]);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $settings->seoTitle ?? $blog->name }}</title>
    @if ($settings->seoDescription ?? $blog->description)
        <meta name="description" content="{{ $settings->seoDescription ?? $blog->description }}" />
    @endif
    @if ($page->noIndex)
        <meta name="robots" content="noindex,nofollow" />
    @endif
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    />
    @vite (['app/Presentation/Css/app.css', 'app/Presentation/Js/app.js'])
</head>
<body class="le-page">
    @if ($page->isOwnerPreview)
        <div class="le-owner-preview">{{ __('public.owner_preview') }}</div>
    @endif

    <div class="container-custom flex pt-3">
        @include('public.partials.platform-home-link', ['blog' => $blog])
    </div>

    <header class="le-header">
        <a class="le-brand" href="{{ $homeUrl }}">
            @if (!empty($header['mediaId']) && isset($mediaUrls[$header['mediaId']]))
                <img src="{{ $mediaUrls[$header['mediaId']] }}" alt="" class="le-brand-image" />
            @else
                <i class="fa-solid fa-book-open le-brand-mark" aria-hidden="true"></i>
            @endif
            <span>{{ $header['title'] ?? $blog->name }}</span>
        </a>
        <nav class="le-nav" aria-label="{{ __('public.navigation') }}">
            <a
                href="{{ $homeUrl }}"
                @if ($screen === 'home') aria-current="page" @endif
                >{{ __('ui.home') }}</a
            >
            <a
                href="{{ $screenUrl('posts') }}"
                @if ($screen === 'posts') aria-current="page" @endif
                >{{ __('ui.articles') }}</a
            >
            <a
                href="{{ $screenUrl('about') }}"
                @if ($screen === 'about') aria-current="page" @endif
                >{{ __('template.about') }}</a
            >
            <a
                href="{{ $screenUrl('contact') }}"
                @if ($screen === 'contact') aria-current="page" @endif
                >{{ __('public.contact') }}</a
            >
        </nav>
        <div class="le-social" aria-label="{{ __('public.social') }}">
            @foreach (array_slice($settings->socialLinks, 0, 3) as $platform => $url)
                <a
                    href="{{ $url }}"
                    target="_blank"
                    rel="noopener me"
                    aria-label="{{ ucfirst($platform) }}"
                    ><i
                        class="fa-brands {{ match ($platform) { 'facebook' => 'fa-facebook', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'tiktok' => 'fa-tiktok', default => 'fa-link' } }}"
                        aria-hidden="true"
                    ></i
                ></a>
            @endforeach
            <form
                method="POST"
                action="{{ route('interface-locale.update') }}"
                class="le-locale-picker"
            >
                @csrf
                <label
                    class="sr-only"
                    for="public-interface-locale"
                    >{{ __('template.language') }}</label
                >
                <select
                    id="public-interface-locale"
                    name="locale"
                    aria-label="{{ __('template.language') }}"
                    onchange="this.form.submit()"
                >
                    <option value="vi" @selected (app()->getLocale() === 'vi')>VI</option>
                    <option value="en" @selected (app()->getLocale() === 'en')>EN</option>
                </select>
            </form>
        </div>
    </header>

    @if ($screen === 'home')
        <main>
            @php ($heroSlides = is_array($hero['slides'] ?? null) && count($hero['slides']) ? $hero['slides'] : [$hero])
            <section id="home" class="le-hero" data-hero-carousel>
                @foreach ($heroSlides as $slide)
                    @php ($slideImage = !empty($slide['mediaId']) && isset($mediaUrls[$slide['mediaId']]) ? $mediaUrls[$slide['mediaId']] : $heroImage)
                    <div
                        class="le-hero-panel{{ $loop->first ? ' is-active' : '' }}"
                        data-hero-slide
                        aria-hidden="{{ $loop->first ? 'false' : 'true' }}"
                        style="--le-hero: url('{{ $slideImage }}')"
                    >
                        <div class="le-hero-copy">
                            <p>{{
                                $slide['eyebrow'] ??
                                    __('public.slow_living')
                            }}</p>
                            <h1>
                                {{
                                    $slide['title'] ??
                                        __('public.hero_title')
                                }}
                            </h1>
                            <span>{{
                                $slide['description'] ??
                                    $blog->description
                            }}</span>
                            @if (!empty($slide['ctaLabel']))
                                <a
                                    href="{{ $slide['ctaUrl'] ?? '#latest-posts' }}"
                                    >{{ $slide['ctaLabel'] }}</a
                                >
                            @endif
                        </div>
                    </div>
                @endforeach
                @if (count($heroSlides) > 1)
                    <div
                        class="le-hero-strip"
                        data-hero-thumbnails
                        aria-label="{{ __('template.hero_slides') }}"
                    >
                        @foreach ($heroSlides as $slide)
                            @php ($thumbImage = !empty($slide['mediaId']) && isset($mediaUrls[$slide['mediaId']]) ? $mediaUrls[$slide['mediaId']] : $images . '/' . $fallbackCards[$loop->index % count($fallbackCards)])
                            <button
                                type="button"
                                data-hero-thumb="{{ $loop->index }}"
                                class="le-hero-thumb{{ $loop->first ? ' is-active' : '' }}"
                                aria-label="{{ __('template.slide') }} {{ $loop->iteration }}: {{ $slide['title'] ?? '' }}"
                                aria-current="{{ $loop->first ? 'true' : 'false' }}"
                            >
                                <img src="{{ $thumbImage }}" alt="" loading="lazy" />
                                <span>{{
                                    $slide['title'] ??
                                        __('template.slide') . ' ' . $loop->iteration
                                }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="le-hero-controls" aria-label="{{ __('template.hero_slides') }}">
                        <button
                            type="button"
                            data-hero-prev
                            aria-label="{{ __('template.previous_slide') }}"
                        >
                            <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                        </button>
                        <span data-hero-count>1 / {{ count($heroSlides) }}</span>
                        <button
                            type="button"
                            data-hero-next
                            aria-label="{{ __('template.next_slide') }}"
                        >
                            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                @endif
            </section>

            <div class="le-content-grid">
                <div>
                    <section id="latest-posts" class="le-posts">
                        <p class="le-kicker">{{
                            $latest['title'] ??
                                __('public.latest_posts')
                        }}</p>
                        <div class="le-post-grid">
                            @forelse (array_slice($publicPosts ?? [], 0, (int) ($latest['limit'] ?? 3)) as $post)
                                <article>
                                    <img
                                        src="{{ $post->coverMediaId && isset($mediaUrls[$post->coverMediaId]) ? $mediaUrls[$post->coverMediaId] : $images.'/'.$fallbackCards[$loop->index % 3] }}"
                                        alt=""
                                    />
                                    <time>{{
                                        $post->publishedAt
                                            ? \\Illuminate\\Support\\Carbon::parse($post->publishedAt)->format('d.m.Y')
                                            : ''
                                    }}</time>
                                    <h2>
                                        <a
                                            href="{{ route('public.post', [$blog->slug, $post->slug]) }}"
                                            >{{ $post->title }}</a
                                        >
                                    </h2>
                                    <p>{{
                                        $post->excerpt ??
                                            $post->content->plainText(150)
                                    }}</p>
                                    <a
                                        class="le-read-more"
                                        href="{{ route('public.post', [$blog->slug, $post->slug]) }}"
                                        >{{ __('public.read_post') }}
                                        <i
                                            class="fa-solid fa-arrow-right-long"
                                            aria-hidden="true"
                                        ></i
                                    ></a>
                                </article>
                            @empty
                                <p class="le-kicker">{{ __('public.no_posts') }}</p>
                            @endforelse
                        </div>
                        <a class="le-all-posts" href="{{ $screenUrl('posts') }}"
                            >{{ __('public.view_all_posts') }}
                            <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i
                        ></a>
                    </section>

                    <section id="about" class="le-about">
                        <img src="{{ $aboutImage }}" alt="" />
                        <div>
                            <p class="le-kicker">{{ __('public.about_story') }}</p>
                            <h2>
                                {{
                                    $about['title'] ??
                                        __('public.about_me')
                                }}
                            </h2>
                            <p>{{
                                $about['description'] ??
                                    $blog->description
                            }}</p>
                            <a class="le-page-cta" href="{{ $screenUrl('about') }}"
                                >{{ __('public.about_me') }}
                                <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i
                            ></a>
                        </div>
                    </section>
                    <section class="le-gallery">
                        <h2>
                            {{
                                $gallery['title'] ??
                                    __('public.gallery_title')
                            }}
                        </h2>
                        <div>
                            @if ($galleryMedia->isNotEmpty())
                                @foreach ($galleryMedia->take(3) as $url)
                                    <img src="{{ $url }}" alt="" />
                                @endforeach
                            @else
                                @foreach ($fallbackCards as $image)
                                    <img
                                        src="{{ $images.'/'.$image }}"
                                        alt="{{ __('public.gallery_image') }}"
                                    />
                                @endforeach
                            @endif
                        </div>
                    </section>
                </div>

                <aside class="le-sidebar">
                    <section>
                        <img
                            class="le-avatar"
                            src="{{ !empty($profile['mediaId']) && isset($mediaUrls[$profile['mediaId']]) ? $mediaUrls[$profile['mediaId']] : $images.'/avatar.svg' }}"
                            alt=""
                        />
                        <h2>
                            {{
                                $profile['title'] ??
                                    __('public.my_corner')
                            }}
                        </h2>
                        <p>{{
                            $profile['description'] ??
                                __('public.blogger_intro')
                        }}</p>
                        <a class="le-sidebar-cta" href="{{ $screenUrl('about') }}"
                            >{{ __('public.about_me') }}
                            <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i
                        ></a>
                    </section>
                    <section>
                        <h2>
                            {{
                                $newsletter['title'] ??
                                    __('public.subscribe_title')
                            }}
                        </h2>
                        <p>{{ $newsletter['description'] ?? '' }}</p>
                        <div class="le-email">email@example.com</div>
                        <a class="le-sidebar-button" href="{{ $screenUrl('contact') }}">{{
                            $newsletter['ctaLabel'] ??
                                __('public.subscribe')
                        }}</a>
                    </section>
                    @if (count($page->categories) > 0)
                        <section>
                            <h2>{{ __('template.categories') }}</h2>
                            <ul>
                                @foreach ($page->categories as $category)
                                    <li>{{ $category->name }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </aside>
            </div>
            <section id="contact" class="le-contact">
                <i class="fa-solid fa-envelope le-mark" aria-hidden="true"></i>
                <h2>
                    {{
                        $contact['title'] ??
                            __('public.contact_title')
                    }}
                </h2>
                <p>{{ $contact['description'] ?? '' }}</p>
                <a href="{{ $contact['ctaUrl'] ?? $screenUrl('contact') }}">{{
                    $contact['ctaLabel'] ??
                        __('public.contact')
                }}</a>
            </section>
        </main>
    @else
        @include ('public.templates.lifestyle-editorial.screens.' . $screen,
            compact(
                'blog',
                'page',
                'about',
                'contact',
                'profile',
                'images',
                'screenUrl',
                'homeUrl',
                'fallbackCards'
            ))
    @endif

    <footer class="le-footer">
        <a href="{{ $homeUrl }}" class="le-brand"
            ><span class="le-brand-mark" aria-hidden="true">TH</span
            ><span>{{ $header['title'] ?? $blog->name }}</span></a
        >
        <p>{{ $footer['description'] ?? '' }}</p>
        <p>{{
            $footer['copyright'] ??
                '© ' . now()->year . ' ' . $blog->name
        }}</p>
    </footer>
</body>
</html>
