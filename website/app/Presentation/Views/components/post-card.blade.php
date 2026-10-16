@props([
    'title' => '',
    'excerpt' => '',
    'url' => '#',
    'image' => null,
    'date' => null,
    'author' => null,
    'category' => null,
    'readTime' => 5,
    'isFeatured' => false,
])

<article class="card group hover:shadow-md transition-shadow">
    @if($image)
        <a href="{{ $url }}">
            <div class="aspect-video rounded-t-lg overflow-hidden">
                <img
                    src="{{ $image }}"
                    alt="{{ $title }}"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                >
            </div>
        </a>
    @endif

    <div class="p-6 space-y-3">
        <!-- Meta -->
        <div class="flex items-center gap-3 text-xs text-secondary">
            @if($date)
                <time datetime="{{ date('Y-m-d', strtotime($date)) }}">
                    {{ date('M j, Y', strtotime($date)) }}
                </time>
            @endif

            @if($category)
                <span class="text-border">•</span>
                <x-badge variant="primary" size="sm">{{ $category }}</x-badge>
            @endif

            <span class="text-border">•</span>
            <span>{{ $readTime }} min read</span>
        </div>

        <!-- Title -->
        <h3 class="text-xl font-semibold text-primary group-hover:text-accent-primary transition-colors">
            <a href="{{ $url }}" class="hover:underline">
                {{ $title }}
            </a>
        </h3>

        <!-- Excerpt -->
        @if($excerpt)
            <p class="text-sm text-secondary line-clamp-2 leading-relaxed">
                {{ $excerpt }}
            </p>
        @endif

        <!-- Author & Featured Badge -->
        <div class="flex items-center justify-between pt-2">
            @if($author)
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full bg-accent-primary flex items-center justify-center text-surface text-xs font-semibold">
                        {{ strtoupper(substr($author, 0, 1)) }}
                    </div>
                    <span class="text-xs text-secondary">{{ $author }}</span>
                </div>
            @else
                <div></div>
            @endif

            @if($isFeatured)
                <x-badge variant="highlight">Featured</x-badge>
            @endif
        </div>
    </div>
</article>
