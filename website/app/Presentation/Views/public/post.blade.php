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
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js" defer></script>
    @vite (['app/Presentation/Css/app.css', 'app/Presentation/Js/app.js'])
</head>
<body style="background: #ffffff">
    @if ($page->isOwnerPreview)
        <div class="bg-accent-primary px-4 py-8 text-center text-sm text-white">
            {{ __('public.owner_preview') }}
        </div>
    @endif

    <phantom-ui
        loading
        animation="shimmer"
        reveal="0.2"
        aria-label="{{ __('public.loading_post') }}"
    >
        <main data-animate="page" class="container-custom {{ $contentWidthClass }} bg-white py-10">
            @if ($layout->showBlogName)
                <a
                    href="{{ route('public.blog', $blog->slug) }}"
                    onclick="
                        window.history.back();
                        return false;
                    "
                    class="btn-primary inline-flex items-center gap-2 rounded-lg border px-4 py-2.5 text-base font-medium transition-colors hover:opacity-80"
                >
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    {{ __('post.back_previous') }}
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

                    <div class="text-secondary inline-flex items-center gap-2 text-sm">
                        @if ($author?->avatarUrl)
                            <img
                                src="{{ $author->avatarUrl }}"
                                alt=""
                                class="h-8 w-8 rounded-full object-cover"
                            />
                        @else
                            <span
                                class="bg-accent-primary text-surface flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold"
                                aria-hidden="true"
                                >{{
                                    strtoupper(
                                        substr($author?->name ?? $blog->name, 0, 1),
                                    )
                                }}</span
                            >
                        @endif
                        <span>{{ $author?->name ?? $blog->name }}</span>
                    </div>

                    @if ($layout->showPublishedDate && $post->publishedAt)
                        <time
                            datetime="{{ \Carbon\Carbon::parse($post->publishedAt)->timezone($settings->timezone)->toAtomString() }}"
                            class="text-sm opacity-70"
                        >
                            {{
                                \Carbon\Carbon::parse($post->publishedAt)
                                    ->timezone($settings->timezone)
                                    ->format('H:i d/m/Y')
                            }}
                        </time>
                    @endif
                </header>

                <div
                    class="mb-8 flex flex-wrap items-center gap-3"
                    role="group"
                    aria-label="{{ __('post.actions') }}"
                >
                    @auth
                        <form
                            method="POST"
                            action="{{ $interactionState->liked ? route('public.post.unlike', [$blog->slug, $post->slug]) : route('public.post.like', [$blog->slug, $post->slug]) }}"
                        >
                            @csrf
                            <input type="hidden" name="_method" value="{{ $interactionState->liked ? 'DELETE' : 'PUT' }}" />
                            <button
                                type="submit"
                                aria-pressed="{{ $interactionState->liked ? 'true' : 'false' }}"
                                class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600 {{ $interactionState->liked ? 'border-rose-300 bg-rose-50 text-rose-600' : 'border-slate-300 text-slate-700 hover:border-rose-300 hover:text-rose-600' }}"
                            >
                                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="{{ $interactionState->liked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-8.3 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z" />
                                </svg>
                                {{ $interactionState->liked ? __('post.unlike') : __('post.like') }}
                            </button>
                        </form>
                        <form
                            method="POST"
                            action="{{ $interactionState->bookmarked ? route('public.post.unbookmark', [$blog->slug, $post->slug]) : route('public.post.bookmark', [$blog->slug, $post->slug]) }}"
                        >
                            @csrf
                            <input type="hidden" name="_method" value="{{ $interactionState->bookmarked ? 'DELETE' : 'PUT' }}" />
                            <button
                                type="submit"
                                aria-pressed="{{ $interactionState->bookmarked ? 'true' : 'false' }}"
                                class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-primary {{ $interactionState->bookmarked ? 'border-accent-primary bg-surface-secondary text-accent-primary' : 'border-default text-secondary hover:border-accent-primary hover:text-accent-primary' }}"
                            >
                                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="{{ $interactionState->bookmarked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1Z" />
                                </svg>
                                {{ $interactionState->bookmarked ? __('post.unbookmark') : __('post.bookmark') }}
                            </button>
                        </form>
                        <a href="{{ route('bookmarks.index') }}" class="border-default text-secondary hover:border-accent-primary hover:text-accent-primary inline-flex items-center rounded-lg border px-4 py-2 font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-primary">
                            {{ __('post.view_bookmarks') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-rose-700 underline-offset-2 hover:underline">{{ __('post.sign_in_to_like') }}</a>
                        <a href="{{ route('login') }}" class="text-accent-primary text-sm underline-offset-2 hover:underline">{{ __('post.sign_in_to_bookmark') }}</a>
                    @endauth
                    @if ($canViewLikers)
                        <button
                            type="button"
                            data-likers-open
                            data-likers-url="{{ route('blogs.posts.likers', [$blog->id, $post->id]) }}"
                            data-likers-count="{{ $postEngagement->likes }}"
                            class="border-default bg-surface-secondary text-primary hover:border-accent-primary ml-auto inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-primary"
                            aria-label="{{ __('post.likers_for', ['title' => $post->title]) }}"
                        >
                            <svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" class="text-rose-600" aria-hidden="true">
                                <path d="M12 21 3.5 12.4a5.5 5.5 0 0 1 7.8-7.8L12 5.3l.7-.7a5.5 5.5 0 0 1 7.8 7.8L12 21Z" />
                            </svg>
                            <span data-likers-count-value>{{ \App\Presentation\Formatting\CompactCount::format($postEngagement->likes) }}</span>
                        </button>
                    @else
                        <span class="border-default bg-surface-secondary text-primary ml-auto inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold" aria-label="{{ __('post.likes_count') }}: {{ $postEngagement->likes }}">
                            <svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor" class="text-rose-600" aria-hidden="true"><path d="M12 21 3.5 12.4a5.5 5.5 0 0 1 7.8-7.8L12 5.3l.7-.7a5.5 5.5 0 0 1 7.8 7.8L12 21Z" /></svg>
                            <span>{{ \App\Presentation\Formatting\CompactCount::format($postEngagement->likes) }}</span>
                        </span>
                    @endif
                </div>

                @if ($layout->showCover)
                    <div data-image-frame class="media-frame mb-8 overflow-hidden rounded-md">
                        <img
                            src="{{ $cover['url'] ?? asset('images/illustrations/post-cover.svg') }}"
                            alt=""
                            class="media-image block aspect-video w-full rounded-md object-cover"
                            data-image-reveal
                            @if ($cover && $cover['width'] && $cover['height']) width="{{ $cover['width'] }}" height="{{ $cover['height'] }}" @endif
                        />
                    </div>
                @endif

                {{-- $html comes from TiptapHtmlRenderer, which escapes all text and attributes. --}}
                <div class="post-content">{!! $html !!}</div>

            </article>

            @include ('public.partials.comments', ['comments' => $comments, 'blog' => $blog, 'post' => $post])

            <div class="mt-12 border-t pt-6" style="border-color: var(--d-secondary)">
                @include ('public.partials.report-form', ['blog' => $blog, 'postSlug' => $post->slug])
            </div>
        </main>
    </phantom-ui>
    @if ($canViewLikers)
        @include ('posts.manage.likers-dialog')
    @endif
</body>
</html>
