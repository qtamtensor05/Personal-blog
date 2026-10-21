@props ([
    'title' => '',
    'excerpt' => '',
    'url' => '#',
    'image' => null,
    'date' => null,
    'author' => null,
    'authorId' => null,
    'category' => null,
    'isFeatured' => false,
])

<article data-animate="post-card" class="card group transition-shadow hover:shadow-md">
    @if ($image)
        <a href="{{ $url }}">
            <div data-image-frame class="media-frame aspect-video overflow-hidden rounded-t-lg">
                <img
                    src="{{ $image }}"
                    alt="{{ $title }}"
                    class="media-image h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    data-image-reveal
                    loading="lazy"
                />
            </div>
        </a>
    @endif

    <div class="space-y-3 p-6">
        <!-- Meta -->
        <div class="text-secondary flex items-center gap-3 text-xs">
            @if ($date)
                <time datetime="{{ date('Y-m-d', strtotime($date)) }}">
                    {{
                        \Illuminate\Support\Carbon::parse($date)
                            ->locale(app()->getLocale())
                            ->translatedFormat('M j, Y')
                    }}
                </time>
            @endif

            @if ($category)
                @if ($date)
                    <span class="text-border">•</span>
                @endif
                <x-badge variant="primary" size="sm">{{ $category }}</x-badge>
            @endif
        </div>

        <!-- Title -->
        <h3
            class="text-primary group-hover:text-accent-primary text-xl font-semibold transition-colors"
        >
            <a href="{{ $url }}" class="hover:underline"> {{ $title }} </a>
        </h3>

        <!-- Excerpt -->
        @if ($excerpt)
            <p class="text-secondary line-clamp-2 text-sm leading-relaxed">{{ $excerpt }}</p>
        @endif

        <!-- Author & Featured Badge -->
        <div class="flex items-center justify-between pt-2">
            @if ($author)
                <div class="flex items-center gap-2">
                    @if ($authorId)
                        <a href="{{ route('profiles.show', $authorId) }}" aria-label="{{ __('profile.view_public', ['name' => $author]) }}" class="rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
                            <span class="bg-accent-primary text-surface flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold" aria-hidden="true">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</span>
                        </a>
                    @else
                        <span class="bg-accent-primary text-surface flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold" aria-hidden="true">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</span>
                    @endif
                    <span class="text-secondary text-xs">{{ $author }}</span>
                </div>
            @else
                <div></div>
            @endif

            @if ($isFeatured)
                <x-badge variant="highlight">{{ __('home.featured_badge') }}</x-badge>
            @endif
        </div>
    </div>
</article>
