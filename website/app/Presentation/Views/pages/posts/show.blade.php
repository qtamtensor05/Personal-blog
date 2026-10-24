@extends ('layouts.app')

@section ('title', $post['title'] ?? __('post.not_found'))

@section ('content')
    <article class="py-12">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav
                class="text-secondary mb-8 flex items-center text-sm"
                aria-label="{{ __('ui.breadcrumb') }}"
            >
                <a href="{{ url('/') }}" class="hover:text-accent-primary">{{ __('post.home') }}</a>
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <a
                    href="{{ route('posts.index') }}"
                    class="hover:text-accent-primary"
                    >{{ __('post.posts') }}</a
                >
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                <span class="text-primary">{{
                    $post['title'] ??
                        __('post.not_found')
                }}</span>
            </nav>

            <!-- Article Header -->
            <header class="mb-8">
                @if ($post['category'] ?? false)
                    <x-badge variant="primary" class="mb-4"> {{ $post['category'] }} </x-badge>
                @endif

                <h1 class="text-primary mb-4 text-4xl leading-tight font-bold md:text-5xl">
                    {{ $post['title'] }}
                </h1>

                @if ($post['excerpt'] ?? false)
                    <p class="text-secondary mb-6 text-xl">{{ $post['excerpt'] }}</p>
                @endif

                <!-- Meta Information -->
                <div class="text-secondary flex flex-wrap items-center gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <div
                            class="bg-accent-primary text-surface flex h-10 w-10 items-center justify-center rounded-full font-medium"
                        >
                            {{
                                strtoupper(
                                    substr($post['author'] ?? 'A', 0, 1),
                                )
                            }}
                        </div>
                        <div>
                            <div class="text-primary font-medium">
                                {{
                                    $post['author'] ??
                                        __('post.unknown_author')
                                }}
                            </div>
                            <div class="text-xs">{{ __('post.author') }}</div>
                        </div>
                    </div>
                    <div class="text-default hidden sm:block">•</div>
                    <div>
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                        {{
                            date(
                                'F j, Y',
                                strtotime($post['published_at'] ?? 'now'),
                            )
                        }}
                    </div>
                </div>
            </header>

            <!-- Cover Image -->
            @if ($post['cover_image'] ?? false)
                <div class="mb-8 overflow-hidden rounded-xl">
                    <img
                        src="{{ $post['cover_image'] }}"
                        alt="{{ $post['title'] }}"
                        class="h-auto w-full object-cover"
                        loading="lazy"
                    />
                </div>
            @endif

            <!-- Backend Notice -->
            <x-alert type="warning" class="mb-8">
                <strong>{{ __('post.note') }}</strong> {{ __('post.rendering_notice') }}
            </x-alert>

            <!-- Article Content -->
            <div class="prose prose-lg max-w-none">
                <div class="text-primary space-y-6 leading-relaxed">
                    {!!
                        $post['content'] ??
                            '<p>' . e(__('post.content_unavailable')) . '</p>'
                    !!}
                </div>
            </div>

            <!-- Tags (if available) -->
            @if (!empty($post['tags']))
                <div class="border-default mt-12 border-t pt-8">
                    <h3 class="text-secondary mb-3 text-sm font-medium">
                        {{ __('post.tagged_with') }}
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($post['tags'] as $tag)
                            <a
                                href="#"
                                class="bg-surface-secondary text-secondary hover:bg-accent-primary hover:text-surface rounded-full px-3 py-1.5 text-sm transition-colors"
                            >
                                #{{ $tag }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Share Section -->
            <div class="border-default mt-12 border-t pt-8">
                <h3 class="text-secondary mb-3 text-sm font-medium">
                    {{ __('post.share_article') }}
                </h3>
                <div class="flex gap-3">
                    <button
                        class="bg-surface-secondary hover:bg-accent-primary hover:text-surface text-primary rounded-lg px-4 py-2 transition-colors"
                    >
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </button>
                    <button
                        class="bg-surface-secondary hover:bg-accent-primary hover:text-surface text-primary rounded-lg px-4 py-2 transition-colors"
                    >
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </button>
                    <button
                        class="bg-surface-secondary hover:bg-accent-primary hover:text-surface text-primary rounded-lg px-4 py-2 transition-colors"
                    >
                        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Back to Posts -->
            <div class="mt-12">
                <a
                    href="{{ route('posts.index') }}"
                    class="text-accent-primary inline-flex items-center hover:underline"
                >
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                    {{ __('post.back_to_posts') }}
                </a>
            </div>

            <!-- Related Posts Section (Mock) -->
            @if (!empty($relatedPosts))
                <div class="border-default mt-16 border-t pt-12">
                    <h2 class="text-primary mb-6 text-2xl font-bold">{{ __('post.related') }}</h2>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        @foreach ($relatedPosts as $related)
                            <x-post-card
                                :title="$related['title']"
                                :excerpt="$related['excerpt']"
                                :url="route('posts.show', $related['slug'])"
                                :image="$related['cover_image']"
                                :date="$related['published_at']"
                                :author="$related['author']"
                                :category="$related['category']"
                            />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </article>

    <!-- Custom Prose Styles -->
    <style>
        .prose {
            color: var(--color-text-primary);
        }
        .prose h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--color-text-primary);
            margin-top: 2rem;
            margin-bottom: 1rem;
            line-height: 1.3;
        }
        .prose h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--color-text-primary);
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .prose p {
            margin-bottom: 1.25rem;
            line-height: 1.75;
        }
        .prose ul,
        .prose ol {
            margin: 1.25rem 0;
            padding-left: 1.5rem;
        }
        .prose li {
            margin-bottom: 0.5rem;
        }
        .prose a {
            color: var(--color-accent-primary);
            text-decoration: underline;
        }
        .prose a:hover {
            text-decoration: none;
        }
        .prose code {
            background: var(--color-surface-secondary);
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.875em;
            font-family: 'Courier New', monospace;
        }
        .prose pre {
            background: var(--color-surface-secondary);
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin: 1.5rem 0;
        }
        .prose pre code {
            background: none;
            padding: 0;
        }
        .prose blockquote {
            border-left: 4px solid var(--color-accent-primary);
            padding-left: 1rem;
            font-style: italic;
            color: var(--color-text-secondary);
            margin: 1.5rem 0;
        }
        .prose img {
            border-radius: 0.5rem;
            margin: 1.5rem 0;
        }
    </style>
@endsection
