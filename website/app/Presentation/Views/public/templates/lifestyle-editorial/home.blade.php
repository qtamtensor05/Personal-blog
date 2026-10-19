@php
    $blog = $page->blog;
    $settings = $page->settings;
    $slots = collect($page->sections)
        ->keyBy('key')
        ->map(fn($section) => array_replace($section->props, $page->slotContent[$section->key] ?? []));
    $header = $slots->get('site-header', []);
    $hero = $slots->get('hero', []);
    $latest = $slots->get('latest-posts', []);
    $about = $slots->get('about', []);
    $gallery = $slots->get('gallery', []);
    $profile = $slots->get('sidebar-profile', []);
    $newsletter = $slots->get('newsletter', []);
    $contact = $slots->get('contact', []);
    $footer = $slots->get('site-footer', []);
    $images = asset('images/templates/lifestyle-editorial');
    $heroImage =
        !empty($hero['mediaId']) && isset($mediaUrls[$hero['mediaId']])
            ? $mediaUrls[$hero['mediaId']]
            : $images . '/hero.svg';
    $aboutImage =
        !empty($about['mediaId']) && isset($mediaUrls[$about['mediaId']])
            ? $mediaUrls[$about['mediaId']]
            : $images . '/portrait.svg';
    $galleryMedia = collect($gallery['mediaIds'] ?? [])
        ->map(fn($id) => $mediaUrls[$id] ?? null)
        ->filter()
        ->values();
    $fallbackCards = ['card-flower.svg', 'card-sky.svg', 'card-field.svg'];
    $screen = $screen ?? 'home';
    $templatePreview = $templatePreview ?? false;
    $homeUrl = $templatePreview
        ? route('templates.preview', $blog->slug)
        : route('public.blog', $blog->slug);
    $screenUrl = fn(string $target) => $templatePreview
        ? route('templates.preview', $blog->slug) . '?screen=' . $target
        : route('public.blog.section', [$blog->slug, $target]);
@endphp
<!DOCTYPE html>
<html lang="{{ $settings->locale }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $settings->seoTitle ?? $blog->name }}</title>
    @if ($settings->seoDescription ?? $blog->description)
        <meta name="description" content="{{ $settings->seoDescription ?? $blog->description }}" />
    @endif
    @if ($page->noIndex)
        <meta name="robots" content="noindex,nofollow" />
    @endif
    @vite (['app/Presentation/Css/app.css', 'app/Presentation/Js/app.js'])
</head>
<body class="le-page">
    @if ($page->isOwnerPreview)
        <div class="le-owner-preview">
            Bạn đang xem bản nháp. Khách truy cập chỉ thấy nội dung đã xuất bản.
        </div>
    @endif

    <header class="le-header">
        <a class="le-brand" href="{{ $homeUrl }}">
            @if (!empty($header['mediaId']) && isset($mediaUrls[$header['mediaId']]))
                <img src="{{ $mediaUrls[$header['mediaId']] }}" alt="" class="le-brand-image" />
            @else
                <span class="le-brand-mark" aria-hidden="true">TH</span>
            @endif
            <span>{{ $header['title'] ?? $blog->name }}</span>
        </a>
        <nav class="le-nav" aria-label="Điều hướng chính">
            <a href="{{ $homeUrl }}" @if ($screen === 'home') aria-current="page" @endif
                >Trang chủ</a
            >
            <a href="{{ $screenUrl('posts') }}" @if ($screen === 'posts') aria-current="page" @endif
                >Bài viết</a
            >
            <a href="{{ $screenUrl('about') }}" @if ($screen === 'about') aria-current="page" @endif
                >Giới thiệu</a
            >
            <a
                href="{{ $screenUrl('contact') }}"
                @if ($screen === 'contact') aria-current="page" @endif
                >Liên hệ</a
            >
        </nav>
        <div class="le-social" aria-label="Mạng xã hội">
            @foreach (array_slice($settings->socialLinks, 0, 3) as $platform => $url)
                <a
                    href="{{ $url }}"
                    target="_blank"
                    rel="noopener me"
                    aria-label="{{ ucfirst($platform) }}"
                    >{{
                        strtoupper(
                            substr($platform, 0, 1),
                        )
                    }}</a
                >
            @endforeach
        </div>
    </header>

    @if ($screen === 'home')
        <main>
            <section id="home" class="le-hero" style="--le-hero: url('{{ $heroImage }}')">
                <div class="le-hero-copy">
                    <p>{{
                        $hero['eyebrow'] ??
                            'Nhật ký sống chậm'
                    }}</p>
                    <h1>
                        {{
                            $hero['title'] ??
                                'My Life. My Blog.'
                        }}
                    </h1>
                    <span>{{
                        $hero['description'] ??
                            $blog->description
                    }}</span>
                    @if (!empty($hero['ctaLabel']))
                        <a
                            href="{{ $hero['ctaUrl'] ?? '#latest-posts' }}"
                            >{{ $hero['ctaLabel'] }}</a
                        >
                    @endif
                </div>
                <div class="le-hero-strip" aria-hidden="true">
                    @foreach ($fallbackCards as $image)
                        <img src="{{ $images.'/'.$image }}" alt="" />
                    @endforeach
                </div>
            </section>

            <div class="le-content-grid">
                <div>
                    <section id="latest-posts" class="le-posts">
                        <p class="le-kicker">{{
                            $latest['title'] ??
                                'Bài viết mới nhất'
                        }}</p>
                        <div class="le-post-grid">
                            @forelse (array_slice($page->posts, 0, (int) ($latest['limit'] ?? 3)) as $post)
                                <article>
                                    <img
                                        src="{{ $images.'/'.$fallbackCards[$loop->index % 3] }}"
                                        alt=""
                                    />
                                    <time>{{
                                        $post->publishedAt
                                            ? \Illuminate\Support\Carbon::parse($post->publishedAt)->format('d.m.Y')
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
                                        >Xem thêm <span aria-hidden="true">→</span></a
                                    >
                                </article>
                            @empty
                                @foreach ([
                                        [
                                            'Biến ngày bình thường thành nguồn cảm hứng',
                                            'Ghi lại những điều giản dị giúp cuộc sống trở nên nhiều màu sắc hơn.'
                                        ],
                                        [
                                            '5 lý do để bắt đầu ngày mới sớm hơn',
                                            'Một vài thói quen nhỏ để có buổi sáng nhẹ nhàng và trọn vẹn.'
                                        ],
                                        [
                                            'Học cách sắp xếp lại cuộc sống',
                                            'Bắt đầu từ không gian quanh mình và giữ lại điều thực sự có ý nghĩa.'
                                        ]
                                    ]
                                    as [$title, $excerpt])
                                    <article>
                                        <img
                                            src="{{ $images.'/'.$fallbackCards[$loop->index] }}"
                                            alt="Ảnh minh họa bài viết"
                                        />
                                        <time>22.03.2026</time>
                                        <h2>{{ $title }}</h2>
                                        <p>{{ $excerpt }}</p>
                                        <a class="le-read-more" href="{{ $screenUrl('posts') }}"
                                            >Xem thêm <span aria-hidden="true">→</span></a
                                        >
                                    </article>
                                @endforeach
                            @endforelse
                        </div>
                        <a class="le-all-posts" href="{{ $screenUrl('posts') }}"
                            >Xem tất cả bài viết <span aria-hidden="true">→</span></a
                        >
                    </section>

                    <section id="about" class="le-about">
                        <img src="{{ $aboutImage }}" alt="" />
                        <div>
                            <p class="le-kicker">Câu chuyện của tôi</p>
                            <h2>{{ $about['title'] ?? 'About me' }}</h2>
                            <p>{{
                                $about['description'] ??
                                    $blog->description
                            }}</p>
                        </div>
                    </section>

                    <section class="le-gallery">
                        <h2>
                            {{
                                $gallery['title'] ??
                                    'Những khoảnh khắc'
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
                                        alt="Ảnh trong bộ sưu tập"
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
                                    'Góc nhỏ của tôi'
                            }}
                        </h2>
                        <p>{{
                            $profile['description'] ??
                                'Xin chào, mình là người kể chuyện phía sau blog này.'
                        }}</p>
                        <a class="le-sidebar-cta" href="{{ $screenUrl('about') }}"
                            >Xem giới thiệu →</a
                        >
                    </section>
                    <section>
                        <h2>
                            {{
                                $newsletter['title'] ??
                                    'Nhận bài viết mới'
                            }}
                        </h2>
                        <p>{{ $newsletter['description'] ?? '' }}</p>
                        <div class="le-email">email@example.com</div>
                        <a class="le-sidebar-button" href="{{ $screenUrl('contact') }}">{{
                            $newsletter['ctaLabel'] ??
                                'Đăng ký'
                        }}</a>
                    </section>
                    @if (count($page->categories) > 0)
                        <section>
                            <h2>Chuyên mục</h2>
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
                <p class="le-mark">✤</p>
                <h2>
                    {{
                        $contact['title'] ??
                            "Let me know what's on your mind"
                    }}
                </h2>
                <p>{{ $contact['description'] ?? '' }}</p>
                <a href="{{ $screenUrl('contact') }}">{{
                    $contact['ctaLabel'] ??
                        'Xem thông tin liên hệ'
                }}</a>
            </section>
        </main>
        <section id="contact" class="le-contact">
            <p class="le-mark">✤</p>
            <h2>
                {{
                    $contact['title'] ??
                        "Let me know what's on your mind"
                }}
            </h2>
            <p>{{ $contact['description'] ?? '' }}</p>
            @if (!empty($contact['ctaLabel']))
                <a
                    href="{{ $contact['ctaUrl'] ?? 'mailto:hello@example.com' }}"
                    >{{ $contact['ctaLabel'] }}</a
                >
            @endif
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
