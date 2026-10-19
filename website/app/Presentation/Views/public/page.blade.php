@php
    $blog = $page->blog;
    $settings = $page->settings;
    $tokens = $page->designTokens;
    $description = $staticPage->seoDescription ?? $staticPage->content->plainText(160);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>
        {{
            $staticPage->seoTitle ??
                $staticPage->title
        }} — {{ $blog->name }}
    </title>
    @if ($description !== '')
        <meta name="description" content="{{ $description }}" />
    @endif
    @if ($page->noIndex)
        <meta name="robots" content="noindex,nofollow" />
    @endif
    <meta property="og:title" content="{{ $staticPage->seoTitle ?? $staticPage->title }}" />
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

    <phantom-ui loading animation="shimmer" reveal="0.2" aria-label="{{ __('public.loading_content') }}">
        <main class="container-custom max-w-3xl py-10">
            <a
                href="{{ route('public.blog', $blog->slug) }}"
                class="text-sm"
                style="color: var(--d-primary)"
                >← {{ $blog->name }}</a
            >

            <article class="mt-6">
                <h1
                    class="text-hero mb-8"
                    style="font-family: var(--d-heading-font), sans-serif; color: var(--d-text)"
                >
                    {{ $staticPage->title }}
                </h1>
                {{-- $html comes from TiptapHtmlRenderer, which escapes all text and attributes. --}}
                <div class="post-content">{!! $html !!}</div>
            </article>
        </main>
    </phantom-ui>
</body>
</html>
