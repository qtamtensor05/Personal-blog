@php
    $blog = $page->blog;
    $settings = $page->settings;
    $tokens = $page->designTokens;
@endphp
<!DOCTYPE html>
<html lang="{{ $settings->locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $settings->seoTitle ?? $blog->name }}</title>
    @if($settings->seoDescription ?? $blog->description)
        <meta name="description" content="{{ $settings->seoDescription ?? $blog->description }}">
    @endif
    @if($page->noIndex)
        <meta name="robots" content="noindex,nofollow">
    @endif
    <meta property="og:title" content="{{ $settings->seoTitle ?? $blog->name }}">
    @vite(['app/Presentation/Css/app.css'])
</head>
{{-- Tokens are validated hex colors / plain font names, so they are safe inside the style attribute. --}}
<body class="antialiased"
      style="--d-primary: {{ $tokens->primaryColor }}; --d-secondary: {{ $tokens->secondaryColor }}; --d-accent: {{ $tokens->accentColor }}; --d-text: {{ $tokens->textColor }}; --d-bg: {{ $tokens->backgroundColor }}; --d-heading-font: '{{ $tokens->headingFont }}'; --d-body-font: '{{ $tokens->bodyFont }}'; background: var(--d-bg); color: var(--d-text); font-family: var(--d-body-font), sans-serif;">
    @if($page->isOwnerPreview)
        <div class="text-center text-sm py-2 px-4" style="background: var(--d-accent); color: var(--d-text);">
            Owner preview: this blog is {{ strtolower($blog->status->value) }} / {{ strtolower($blog->visibility->value) }} and not visible to visitors.
        </div>
    @endif

    @if($page->pages !== [])
        <nav aria-label="Pages" class="container-custom max-w-4xl pt-6 flex flex-wrap gap-4 text-sm">
            @foreach($page->pages as $staticPage)
                <a href="{{ route('public.page', [$blog->slug, $staticPage->slug]) }}" style="color: var(--d-primary);">{{ $staticPage->title }}</a>
            @endforeach
        </nav>
    @endif

    <main class="container-custom max-w-4xl py-10 space-y-10">
        @foreach($page->sections as $section)
            @includeIf('blogs.design-sections.'.$section->section, ['blog' => $blog, 'posts' => $page->posts])
        @endforeach

        @include('public.partials.report-form', ['blog' => $blog])

        @if($settings->socialLinks !== [])
            <nav aria-label="Social links" class="flex flex-wrap gap-4 text-sm">
                @foreach($settings->socialLinks as $platform => $url)
                    <a href="{{ $url }}" rel="noopener me" target="_blank" style="color: var(--d-primary);">{{ ucfirst($platform) }}</a>
                @endforeach
            </nav>
        @endif
    </main>
</body>
</html>
