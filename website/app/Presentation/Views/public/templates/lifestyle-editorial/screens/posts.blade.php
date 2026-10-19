<main class="le-inner-page">
    <header class="le-inner-header">
        <p class="le-kicker">{{ __('public.discover') }}</p>
        <h1>{{ $latest['archiveTitle'] ?? __('public.all_posts') }}</h1>
        <p>{{ $latest['archiveDescription'] ?? __('public.archive_description', ['name' => $blog->name]) }}</p>
    </header>
    <form method="GET" action="{{ $screenUrl('posts') }}" class="le-archive-search">
        <label for="blog-post-search" class="sr-only">{{ __('posts.search_label') }}</label>
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
            <a href="{{ $screenUrl('posts') }}">{{ __('posts.clear_search') }}</a>
        @endif
    </form>
    <div class="le-archive-grid">
        @forelse ($publicPosts as $post)
            <article>
                @if ($post->coverMediaId && isset($mediaUrls[$post->coverMediaId]))
                    <img src="{{ $mediaUrls[$post->coverMediaId] }}" alt="">
                @else
                    <div class="le-archive-placeholder" aria-hidden="true"><i class="fa-solid fa-book-open"></i></div>
                @endif
                <div>
                    <time>{{ $post->publishedAt ? \Illuminate\Support\Carbon::parse($post->publishedAt)->format('d.m.Y') : '' }}</time>
                    <h2><a href="{{ route('public.post', [$blog->slug, $post->slug]) }}">{{ $post->title }}</a></h2>
                    <p>{{ $post->excerpt ?? '' }}</p>
                </div>
            </article>
        @empty
            <div class="le-archive-empty">
                <p>{{ $search !== '' ? __('posts.none_match') : __('public.no_posts') }}</p>
            </div>
        @endforelse
    </div>
</main>
