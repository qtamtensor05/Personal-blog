@php
    $blog = $page->blog;
    $settings = $page->settings;
    $tokens = $page->designTokens;
    $layout = $page->postLayout;
    $description = $post->seoDescription ?? ($post->excerpt ?? $post->content->plainText(160));
    $contentWidthClass = match ($layout->contentWidth) {
        'narrow' => 'max-w-2xl',
        'wide' => 'max-w-5xl',
        default => 'max-w-3xl',
    };
    $articleClass = $layout->variant === 'card' ? 'rounded-2xl border p-6 shadow-sm md:p-10' : '';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $post->seoTitle ?? $post->title }} — {{ $blog->name }}</title>
    @if ($description !== '')
        <meta name="description" content="{{ $description }}" />
    @endif
    @if ($page->noIndex)
        <meta name="robots" content="noindex,nofollow" />
    @endif
    <meta property="og:title" content="{{ $post->seoTitle ?? $post->title }}" />
    @if ($cover)
        <meta property="og:image" content="{{ $cover['url'] }}" />
    @endif
    @vite (['app/Presentation/Css/app.css', 'app/Presentation/Js/app.js'])
</head>
<body
    class="antialiased"
    style="--d-primary: {{ $tokens->primaryColor }}; --d-secondary: {{ $tokens->secondaryColor }}; --d-accent: {{ $tokens->accentColor }}; --d-text: {{ $tokens->textColor }}; --d-bg: {{ $tokens->backgroundColor }}; --d-heading-font: '{{ $tokens->headingFont }}'; --d-body-font: '{{ $tokens->bodyFont }}'; background: var(--d-bg); color: var(--d-text); font-family: var(--d-body-font), sans-serif;"
>
    @if ($page->isOwnerPreview)
        <div
            class="px-4 py-2 text-center text-sm"
            style="background: var(--d-accent); color: var(--d-text)"
        >
            {{ __('public.owner_preview') }}
        </div>
    @endif
    <div class="container-custom flex justify-end pt-3">
        @include('layouts.partials.interface-language-switcher', ['id' => 'public'])
    </div>

    <phantom-ui loading animation="shimmer" reveal="0.2" aria-label="{{ __('public.loading_post') }}">
        <main class="container-custom {{ $contentWidthClass }} py-10">
            @if ($layout->showBlogName)
                <a
                    href="{{ route('public.blog', $blog->slug) }}"
                    class="text-sm"
                    style="color: var(--d-primary)"
                    ><i class="fa-solid fa-arrow-left-long"></i> {{ $blog->name }}
                </a>
            @endif

            <article
                class="{{ $layout->showBlogName ? 'mt-6' : '' }} {{ $articleClass }}"
                @if ($layout->variant === 'card') style="border-color: var(--d-secondary)" @endif
            >
                <header class="mb-8 {{ $layout->variant === 'card' ? 'text-center' : '' }}">
                    <h1
                        class="text-hero mb-3"
                        style="font-family: var(--d-heading-font), sans-serif; color: var(--d-text)"
                    >
                        {{ $post->title }}
                    </h1>
                    @if ($layout->showPublishedDate && $post->publishedAt)
                        <time
                            datetime="{{ \Carbon\Carbon::parse($post->publishedAt)->toAtomString() }}"
                            class="text-sm opacity-70"
                        >
                            {{
                                \Carbon\Carbon::parse($post->publishedAt)
                                    ->timezone($settings->timezone)
                                    ->format('d/m/Y')
                            }}
                        </time>
                    @endif
                </header>

                @if ($layout->showCover && $cover)
                    <img
                        src="{{ $cover['url'] }}"
                        alt=""
                        class="mb-8 w-full rounded-md"
                        @if ($cover['width'] && $cover['height']) width="{{ $cover['width'] }}" height="{{ $cover['height'] }}" @endif
                    />
                @endif

                {{-- $html comes from TiptapHtmlRenderer, which escapes all text and attributes. --}}
                <div class="post-content">{!! $html !!}</div>
            </article>

            <div class="mt-12 border-t pt-6" style="border-color: var(--d-secondary)">
                @include ('public.partials.report-form', ['blog' => $blog, 'postSlug' => $post->slug])
            </div>
        </main>
    </phantom-ui>
</body>
</html>
