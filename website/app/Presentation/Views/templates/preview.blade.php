@php
    $tokens = $data->designTokens;
    $layout = $data->postLayout;
    $postWidthClass = match ($layout->contentWidth) {
        'narrow' => 'max-w-2xl',
        'wide' => 'max-w-5xl',
        default => 'max-w-3xl',
    };
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $template->name }} — Preview</title>
    @vite (['app/Presentation/Css/app.css', 'app/Presentation/Js/app.js'])
</head>
<body
    class="antialiased"
    style="--d-primary: {{ $tokens->primaryColor }}; --d-secondary: {{ $tokens->secondaryColor }}; --d-accent: {{ $tokens->accentColor }}; --d-text: {{ $tokens->textColor }}; --d-bg: {{ $tokens->backgroundColor }}; --d-heading-font: '{{ $tokens->headingFont }}'; --d-body-font: '{{ $tokens->bodyFont }}'; background: var(--d-bg); color: var(--d-text); font-family: var(--d-body-font), sans-serif;"
>
    <div
        class="border-default border-b px-4 py-3 text-center text-sm"
        style="background: var(--d-accent); color: var(--d-text)"
    >
        {{ __('ui.template_preview_banner', ['name' => $template->name]) }}
    </div>
    <phantom-ui loading animation="shimmer" reveal="0.2" aria-label="{{ __('ui.loading_template_preview') }}">
        @php ($regions = collect($data->sections) ->filter(fn($section) => $section->visible) ->groupBy('region'))
        <main class="container-custom max-w-6xl space-y-10 py-10">
            @foreach ($data->sections as $section)
                @includeIf ('blogs.design-sections.' . $section->section,
                    [
                        'blog' => $blog,
                        'posts' => [],
                        'categories' => [],
                        'section' => $section,
                        'content' => $section->props,
                        'mediaUrls' => $mediaUrls,
                        'documentHtml' => $documentHtml
                    ])
            @endforeach

            <section class="border-t pt-10" style="border-color: var(--d-secondary)">
                <p class="mb-5 text-xs font-semibold tracking-widest uppercase opacity-60">{{ __('ui.post_frame_preview') }}</p>
                <article
                    class="mx-auto {{ $postWidthClass }} {{ $layout->variant === 'card' ? 'rounded-2xl border p-6 shadow-sm md:p-10' : '' }}"
                    @if ($layout->variant === 'card')
                        style="border-color: var(--d-secondary)"
                    @endif
                >
                    @if ($layout->showBlogName)
                        <p
                            class="mb-5 text-sm"
                            style="color: var(--d-primary)"
                        >&larr; {{ $blog->name }}</p>
                    @endif
                    <header class="mb-8 {{ $layout->variant === 'card' ? 'text-center' : '' }}">
                        <h1
                            class="text-hero mb-3"
                            style="font-family: var(--d-heading-font), sans-serif"
                        >
                            {{ __('ui.sample_post_title') }}
                        </h1>
                        @if ($layout->showPublishedDate)
                            <time class="text-sm opacity-70">19/10/2026</time>
                        @endif
                    </header>
                    @if ($layout->showCover)
                        <div
                            class="mb-8 aspect-video w-full rounded-md opacity-25"
                            style="background: var(--d-secondary)"
                        ></div>
                    @endif
                    <div class="post-content">
                        <p>{{ __('ui.template_frame_help') }}</p>
                        <p>{{ __('ui.template_frame_settings') }}</p>
                    </div>
                </article>
            </section>
        </main>
    </phantom-ui>
</body>
</html>
