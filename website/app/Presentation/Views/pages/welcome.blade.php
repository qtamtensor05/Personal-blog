@extends('layouts.app')

@section('title', 'Welcome to Personal Blog')

@section('content')
<!-- Hero Section -->
<section class="relative py-20 bg-gradient-to-br from-surface via-surface-secondary to-surface overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-3xl mx-auto">
            <h1 class="text-5xl md:text-6xl font-bold text-primary mb-6 leading-tight">
                Welcome to <span class="text-accent-primary">Personal Blog</span>
            </h1>
            <p class="text-xl text-secondary mb-8 leading-relaxed">
                Explore articles on software development, design principles, and technology insights.
                Learn, share, and grow with our community.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <x-button :href="route('posts.index')" size="lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                    </svg>
                    Explore Articles
                </x-button>
                <x-button :href="route('register')" variant="outline" size="lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Join Community
                </x-button>
            </div>
        </div>
    </div>

    <!-- Background decoration -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute top-20 left-10 w-72 h-72 bg-accent-primary rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-accent-warm rounded-full blur-3xl"></div>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    <!-- Featured Posts -->
    @if(!empty($featuredPosts))
        <section class="mb-16">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-primary mb-2">Featured Articles</h2>
                    <p class="text-secondary">Handpicked content you shouldn't miss</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($featuredPosts as $post)
                    <x-post-card
                        :title="$post['title']"
                        :excerpt="$post['excerpt']"
                        :url="route('posts.show', $post['slug'])"
                        :image="$post['cover_image']"
                        :date="$post['published_at']"
                        :author="$post['author']"
                        :category="$post['category']"
                        :isFeatured="$post['is_featured'] ?? false"
                    />
                @endforeach
            </div>
        </section>
    @endif

    <!-- Recent Posts -->
    @if(!empty($recentPosts))
        <section>
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-primary mb-2">Recent Articles</h2>
                    <p class="text-secondary">Latest updates from our contributors</p>
                </div>
                <x-button :href="route('posts.index')" variant="outline">
                    View All
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </x-button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($recentPosts as $post)
                    <x-post-card
                        :title="$post['title']"
                        :excerpt="$post['excerpt']"
                        :url="route('posts.show', $post['slug'])"
                        :image="$post['cover_image']"
                        :date="$post['published_at']"
                        :author="$post['author']"
                        :category="$post['category']"
                    />
                @endforeach
            </div>
        </section>
    @endif

    <!-- CTA Section -->
    <section class="mt-20 card bg-gradient-to-br from-accent-primary/10 to-accent-warm/10 border-accent-primary/20">
        <div class="text-center py-12 px-6">
            <h2 class="text-3xl font-bold text-primary mb-4">Ready to Start Writing?</h2>
            <p class="text-secondary mb-8 max-w-2xl mx-auto">
                Join our community of writers and share your knowledge with thousands of readers.
                Create your account and publish your first article today.
            </p>
            <x-button :href="route('register')" size="lg">
                Get Started for Free
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </x-button>
        </div>
    </section>

    <!-- Backend Notice -->
    <x-alert type="info" class="mt-12">
        <strong>Development Note:</strong> This homepage displays mock data.
        To show real posts, implement the Blog domain layer with PostRepository and use cases (ListFeaturedPosts, ListRecentPosts).
    </x-alert>

</div>
@endsection
