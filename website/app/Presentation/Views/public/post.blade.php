@php
    $blog = $page->blog;
    $settings = $page->settings;
    $tokens = $page->designTokens;
    $description = $post->seoDescription ?? $post->excerpt ?? $post->content->plainText(160);
@endphp
<!DOCTYPE html>
<html lang="{{ $settings->locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->seoTitle ?? $post->title }} — {{ $blog->name }}</title>
    @if($description !== '')
        <meta name="description" content="{{ $description }}">
    @endif
    @if($page->noIndex)
        <meta name="robots" content="noindex,nofollow">
    @endif
    <meta property="og:title" content="{{ $post->seoTitle ?? $post->title }}">
    @if($cover)
        <meta property="og:image" content="{{ $cover['url'] }}">
    @endif
    @vite(['app/Presentation/Css/app.css'])
</head>
<body class="antialiased"
      style="--d-primary: {{ $tokens->primaryColor }}; --d-secondary: {{ $tokens->secondaryColor }}; --d-accent: {{ $tokens->accentColor }}; --d-text: {{ $tokens->textColor }}; --d-bg: {{ $tokens->backgroundColor }}; --d-heading-font: '{{ $tokens->headingFont }}'; --d-body-font: '{{ $tokens->bodyFont }}'; background: var(--d-bg); color: var(--d-text); font-family: var(--d-body-font), sans-serif;">
    @if($page->isOwnerPreview)
        <div class="text-center text-sm py-2 px-4" style="background: var(--d-accent); color: var(--d-text);">
            Owner preview: this blog is not visible to visitors.
        </div>
    @endif

    <main class="container-custom max-w-3xl py-10">
        <a href="{{ route('public.blog', $blog->slug) }}" class="text-sm" style="color: var(--d-primary);">← {{ $blog->name }}</a>

        <article class="mt-6">
            <header class="mb-8">
                <h1 class="text-hero mb-3" style="font-family: var(--d-heading-font), sans-serif; color: var(--d-text);">{{ $post->title }}</h1>
                @if($post->publishedAt)
                    <time datetime="{{ \Carbon\Carbon::parse($post->publishedAt)->toAtomString() }}" class="text-sm opacity-70">
                        {{ \Carbon\Carbon::parse($post->publishedAt)->timezone($settings->timezone)->format('d/m/Y') }}
                    </time>
                @endif
            </header>

            @if($cover)
                <img src="{{ $cover['url'] }}" alt="" class="w-full rounded-md mb-8"
                     @if($cover['width'] && $cover['height']) width="{{ $cover['width'] }}" height="{{ $cover['height'] }}" @endif>
            @endif

            {{-- $html comes from TiptapHtmlRenderer, which escapes all text and attributes. --}}
            <div class="post-content">{!! $html !!}</div>
        </article>
    </main>
</body>
</html>
