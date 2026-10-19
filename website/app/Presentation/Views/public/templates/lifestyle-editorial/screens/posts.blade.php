<main class="le-inner-page">
    <header class="le-inner-header">
        <p class="le-kicker">{{ __('public.discover') }}</p>
        <h1>{{ $latest['archiveTitle'] ?? __('public.all_posts') }}</h1>
        <p>{{ $latest['archiveDescription'] ?? __('public.archive_description', ['name' => $blog->name]) }}</p>
    </header>
    <div class="le-archive-grid">
        @forelse ($publicPosts as $post)
            <article>
                @if ($post->coverMediaId && isset($mediaUrls[$post->coverMediaId]))<img src="{{ $mediaUrls[$post->coverMediaId] }}" alt="">@endif
                <div>
                    <time>{{ $post->publishedAt ? \Illuminate\Support\Carbon::parse($post->publishedAt)->format('d.m.Y') : '' }}</time>
                    <h2>{{ $post->title }}</h2>
                    <p>{{ $post->excerpt ?? '' }}</p>
                    <a class="le-read-more" href="{{ route('public.post', [$blog->slug, $post->slug]) }}">{{ __('public.read_post') }} <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i></a>
                </div>
            </article>
        @empty
            <p class="le-kicker">{{ __('public.no_posts') }}</p>
        @endforelse
    </div>
</main>
