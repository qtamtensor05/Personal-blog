<main class="le-inner-page">
    <header class="le-inner-header">
        <p class="le-kicker">{{ __('public.discover') }}</p>
        <h1>
            {{
                $latest['archiveTitle'] ??
                    __('public.all_posts')
            }}
        </h1>
        <p>{{
            $latest['archiveDescription'] ??
                __('public.archive_description', ['name' => $blog->name])
        }}</p>
    </header>

    <form method="GET" action="{{ $screenUrl('posts') }}" class="le-archive-search">
        <label for="blog-post-search" class="sr-only"> {{ __('posts.search_label') }} </label>

        <input
            id="blog-post-search"
            name="search"
            type="search"
            value="{{ $search }}"
            placeholder="{{ __('posts.search_placeholder') }}"
            maxlength="100"
        />

        <button type="submit">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            {{ __('posts.search') }}
        </button>

        @if ($search !== '')
            <a href="{{ $screenUrl('posts') }}"> {{ __('posts.clear_search') }} </a>
        @endif
    </form>

    <div class="le-archive-grid">
        @forelse ($publicPosts as $post)
            <article class="relative">
                <a href="{{ route('public.post', [$blog->slug, $post->slug]) }}" class="absolute inset-0 z-10 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-accent-primary" aria-label="{{ $post->title }}"></a>
                <img src="{{ $post->coverMediaId && isset($mediaUrls[$post->coverMediaId]) ? $mediaUrls[$post->coverMediaId] : asset('images/illustrations/post-cover.svg') }}" alt="" loading="lazy" />

                <div>
                    <time>
                        {{
                            $post->publishedAt
                                ? \Illuminate\Support\Carbon::parse($post->publishedAt)->format('d.m.Y')
                                : ''
                        }}
                    </time>

                    <h2>{{ $post->title }}</h2>

                    <p>{{ $post->excerpt ?? '' }}</p>

                    <div class="text-secondary mb-3 flex items-center gap-4 text-sm">
                        <span class="inline-flex items-center gap-1.5" aria-label="{{ __('post.likes_count') }}: {{ $engagement[$post->id]->likes }}"><i class="fa-regular fa-heart" aria-hidden="true"></i>{{ \App\Presentation\Formatting\CompactCount::format($engagement[$post->id]->likes) }}</span>
                        <span class="inline-flex items-center gap-1.5" aria-label="{{ __('post.views_count') }}: {{ $engagement[$post->id]->views }}"><i class="fa-regular fa-eye" aria-hidden="true"></i>{{ $engagement[$post->id]->views }}</span>
                    </div>

                    <span class="le-read-more">
                        {{ __('public.read_post') }}
                        <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
                    </span>
                </div>
            </article>
        @empty
            <div class="le-archive-empty">
                <p>
                    {{
                        $search !== ''
                            ? __('posts.none_match')
                            : __('public.no_posts')
                    }}
                </p>
            </div>
        @endforelse
    </div>
</main>
