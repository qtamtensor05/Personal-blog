@props ([
    'title' => '',
    'excerpt' => '',
    'url' => '#',
    'image' => null,
    'date' => null,
    'author' => null,
    'category' => null,
    'isFeatured' => false,
    'likes' => null,
    'views' => null,
])

<article data-animate="post-card" class="card group h-full overflow-hidden transition-shadow hover:shadow-md" style="padding: 0">
    <a href="{{ $url }}" class="flex h-full flex-col focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-accent-primary">
        <div data-image-frame class="media-frame aspect-video overflow-hidden rounded-t-lg">
            <img
                src="{{ $image ?: asset('images/illustrations/post-cover.svg') }}"
                alt=""
                class="media-image h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                data-image-reveal
                loading="lazy"
            />
        </div>
        <div class="flex flex-1 flex-col space-y-3 p-6">
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
            {{ $title }}
        </h3>

        <!-- Excerpt -->
        @if ($excerpt)
            <p class="text-secondary line-clamp-2 text-sm leading-relaxed">{{ $excerpt }}</p>
        @endif

        <!-- Author & Featured Badge -->
        <div class="flex items-center justify-between pt-2">
            @if ($author)
                <div class="flex items-center gap-2">
                    <span class="bg-accent-primary text-surface flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold" aria-hidden="true">{{ mb_strtoupper(mb_substr($author, 0, 1)) }}</span>
                    <span class="text-secondary text-xs">{{ $author }}</span>
                </div>
            @else
                <div></div>
            @endif

            @if ($isFeatured)
                <x-badge variant="highlight">{{ __('home.featured_badge') }}</x-badge>
            @endif
        </div>

        @if ($likes !== null || $views !== null)
            <div class="border-default text-secondary mt-auto flex items-center gap-4 border-t pt-3 text-xs tabular-nums">
                @if ($likes !== null)
                    <span class="inline-flex items-center gap-1.5" aria-label="{{ __('post.likes_count') }}: {{ $likes }}">
                        <i class="fa-regular fa-heart text-rose-500" aria-hidden="true"></i>
                        <span>{{ \App\Presentation\Formatting\CompactCount::format((int) $likes) }}</span>
                    </span>
                @endif
                @if ($views !== null)
                    <span class="inline-flex items-center gap-1.5" aria-label="{{ __('post.views_count') }}: {{ $views }}">
                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        <span>{{ $views }}</span>
                    </span>
                @endif
            </div>
        @endif
        </div>
    </a>
</article>
