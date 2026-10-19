@php
    $blog = $page->blog;
    $settings = $page->settings;
    $tokens = $page->designTokens;
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
    <meta property="og:title" content="{{ $settings->seoTitle ?? $blog->name }}" />
    @vite (['app/Presentation/Css/app.css', 'app/Presentation/Js/app.js'])
</head>
{{-- Tokens are validated hex colors / plain font names, so they are safe inside the style attribute. --}}
<body
    class="antialiased"
    style="--d-primary: {{ $tokens->primaryColor }}; --d-secondary: {{ $tokens->secondaryColor }}; --d-accent: {{ $tokens->accentColor }}; --d-text: {{ $tokens->textColor }}; --d-bg: {{ $tokens->backgroundColor }}; --d-heading-font: '{{ $tokens->headingFont }}'; --d-body-font: '{{ $tokens->bodyFont }}'; background: var(--d-bg); color: var(--d-text); font-family: var(--d-body-font), sans-serif;"
>
    @if ($page->isOwnerPreview)
        <div
            class="px-4 py-2 text-center text-sm"
            style="background: var(--d-accent); color: var(--d-text)"
        >
            Bạn đang xem bản nháp với tư cách chủ blog. Khách truy cập chỉ thấy nội dung đã xuất
            bản.
        </div>
    @endif

    @if ($page->pages !== [])
        <nav
            aria-label="Pages"
            class="container-custom flex max-w-4xl flex-wrap gap-4 pt-6 text-sm"
        >
            @foreach ($page->pages as $staticPage)
                <a
                    href="{{ route('public.page', [$blog->slug, $staticPage->slug]) }}"
                    style="color: var(--d-primary)"
                    >{{ $staticPage->title }}</a
                >
            @endforeach
        </nav>
    @endif

    <phantom-ui loading animation="shimmer" reveal="0.2" aria-label="Loading content">
        @php ($regions = collect($page->sections) ->filter(fn($section) => $section->visible) ->groupBy('region'))
        <header class="container-custom max-w-6xl space-y-4 pt-6">
            @foreach ($regions->get('header', []) as $section)
                @includeIf ('blogs.design-sections.' . $section->section,
                    [
                        'blog' => $blog,
                        'posts' => $page->posts,
                        'categories' => $page->categories,
                        'section' => $section,
                        'content' => array_replace($section->props, $page->slotContent[$section->key] ?? []),
                        'mediaUrls' => $mediaUrls
                    ])
            @endforeach
        </header>
        <main
            class="container-custom grid max-w-6xl gap-10 py-10 lg:grid-cols-[minmax(0,1fr)_18rem]"
        >
            <div class="space-y-10">
                @foreach ($regions->get('body', []) as $section)
                    @includeIf ('blogs.design-sections.' . $section->section,
                        [
                            'blog' => $blog,
                            'posts' => $page->posts,
                            'categories' => $page->categories,
                            'section' => $section,
                            'content' => array_replace($section->props, $page->slotContent[$section->key] ?? []),
                            'mediaUrls' => $mediaUrls
                        ])
                @endforeach
            </div>
            @if ($regions->get('sidebar', collect())->isNotEmpty())
                <aside class="space-y-6">
                    @foreach ($regions->get('sidebar', []) as $section)
                        @includeIf ('blogs.design-sections.' . $section->section,
                            [
                                'blog' => $blog,
                                'posts' => $page->posts,
                                'categories' => $page->categories,
                                'section' => $section,
                                'content' => array_replace($section->props, $page->slotContent[$section->key] ?? []),
                                'mediaUrls' => $mediaUrls
                            ])
                    @endforeach
                </aside>
            @endif

            @include ('public.partials.report-form', ['blog' => $blog])

            @if ($settings->socialLinks !== [])
                <nav aria-label="Social links" class="flex flex-wrap gap-4 text-sm">
                    @foreach ($settings->socialLinks as $platform => $url)
                        <a
                            href="{{ $url }}"
                            rel="noopener me"
                            target="_blank"
                            style="color: var(--d-primary)"
                            >{{ ucfirst($platform) }}</a
                        >
                    @endforeach
                </nav>
            @endif
        </main>
        <footer class="container-custom max-w-6xl space-y-4 pb-10">
            @foreach ($regions->get('footer', []) as $section)
                @includeIf ('blogs.design-sections.' . $section->section,
                    [
                        'blog' => $blog,
                        'posts' => $page->posts,
                        'categories' => $page->categories,
                        'section' => $section,
                        'content' => array_replace($section->props, $page->slotContent[$section->key] ?? []),
                        'mediaUrls' => $mediaUrls
                    ])
            @endforeach
        </footer>
    </phantom-ui>
</body>
</html>
