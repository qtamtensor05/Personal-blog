@extends('layouts.app')

@section('title', 'All Posts')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-primary mb-2">All Posts</h1>
            <p class="text-secondary">Explore our collection of articles and stories</p>
        </div>

        <!-- Search and Filters -->
        <div class="card mb-8">
            <form method="GET" action="{{ route('posts.index') }}" class="flex flex-col md:flex-row gap-4">
                <!-- Search Input -->
                <div class="flex-1">
                    <label for="search" class="sr-only">Search posts</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Search by title or content..."
                        value="{{ request('search') }}"
                        class="w-full px-4 py-2.5 border border-default rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-primary focus:border-accent-primary text-primary placeholder:text-secondary"
                    >
                </div>

                <!-- Category Filter (placeholder) -->
                <div class="w-full md:w-48">
                    <label for="category" class="sr-only">Category</label>
                    <select
                        id="category"
                        name="category"
                        class="w-full px-4 py-2.5 border border-default rounded-lg focus:outline-none focus:ring-2 focus:ring-accent-primary focus:border-accent-primary text-primary bg-surface"
                    >
                        <option value="">All Categories</option>
                        <option value="technology">Technology</option>
                        <option value="design">Design</option>
                        <option value="business">Business</option>
                    </select>
                </div>

                <!-- Search Button -->
                <x-button type="submit">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Search
                </x-button>
            </form>
        </div>

        @if(request('search'))
            <div class="mb-6">
                <p class="text-sm text-secondary">
                    Showing results for "<strong class="text-primary">{{ request('search') }}</strong>"
                    <a href="{{ route('posts.index') }}" class="ml-2 text-accent-primary hover:underline">Clear search</a>
                </p>
            </div>
        @endif

        <!-- Backend Notice -->
        <x-alert type="warning" class="mb-8">
            <strong>Note:</strong> Post listing functionality requires backend implementation.
            The Domain/Blog layer, PostRepository, and Use Cases (ListPosts, GetPostBySlug) need to be created.
        </x-alert>

        <!-- Posts Grid (Mock Data) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            @php
            // Mock posts data - Replace with actual data from repository
            $mockPosts = [
                [
                    'id' => 1,
                    'title' => 'Getting Started with Clean Architecture in Laravel',
                    'slug' => 'getting-started-clean-architecture-laravel',
                    'excerpt' => 'Learn how to structure your Laravel application using Clean Architecture principles for better maintainability and testability.',
                    'cover_image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=800&h=600&fit=crop',
                    'published_at' => '2026-10-10',
                    'author' => 'John Doe',
                    'category' => 'Technology',
                    'is_featured' => true,
                ],
                [
                    'id' => 2,
                    'title' => 'Modern UI Design Principles for Web Applications',
                    'slug' => 'modern-ui-design-principles',
                    'excerpt' => 'Explore the fundamental principles of modern UI design that create intuitive and beautiful web applications.',
                    'cover_image' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&h=600&fit=crop',
                    'published_at' => '2026-10-08',
                    'author' => 'Jane Smith',
                    'category' => 'Design',
                    'is_featured' => false,
                ],
                [
                    'id' => 3,
                    'title' => 'Building Scalable REST APIs with Laravel',
                    'slug' => 'building-scalable-rest-apis-laravel',
                    'excerpt' => 'Best practices for designing and implementing RESTful APIs that scale with your application needs.',
                    'cover_image' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800&h=600&fit=crop',
                    'published_at' => '2026-10-05',
                    'author' => 'Mike Johnson',
                    'category' => 'Technology',
                    'is_featured' => false,
                ],
                [
                    'id' => 4,
                    'title' => 'The Future of Web Development in 2027',
                    'slug' => 'future-web-development-2027',
                    'excerpt' => 'Predictions and trends shaping the future of web development, from AI integration to new frameworks.',
                    'cover_image' => 'https://images.unsplash.com/photo-1504639725590-34d0984388bd?w=800&h=600&fit=crop',
                    'published_at' => '2026-10-03',
                    'author' => 'Sarah Williams',
                    'category' => 'Technology',
                    'is_featured' => false,
                ],
                [
                    'id' => 5,
                    'title' => 'Effective Team Collaboration in Remote Work',
                    'slug' => 'effective-team-collaboration-remote',
                    'excerpt' => 'Strategies and tools for maintaining productive collaboration in distributed teams.',
                    'cover_image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&h=600&fit=crop',
                    'published_at' => '2026-10-01',
                    'author' => 'David Brown',
                    'category' => 'Business',
                    'is_featured' => false,
                ],
                [
                    'id' => 6,
                    'title' => 'Database Optimization Techniques for High Traffic',
                    'slug' => 'database-optimization-high-traffic',
                    'excerpt' => 'Proven techniques to optimize database performance and handle millions of requests efficiently.',
                    'cover_image' => 'https://images.unsplash.com/photo-1544383835-bda2bc66a55d?w=800&h=600&fit=crop',
                    'published_at' => '2026-09-28',
                    'author' => 'Emma Davis',
                    'category' => 'Technology',
                    'is_featured' => false,
                ],
            ];
            @endphp

            @forelse($mockPosts as $post)
                <x-post-card
                    :title="$post['title']"
                    :excerpt="$post['excerpt']"
                    :url="route('posts.show', $post['slug'])"
                    :image="$post['cover_image']"
                    :date="$post['published_at']"
                    :author="$post['author']"
                    :category="$post['category']"
                />
            @empty
                <div class="col-span-full">
                    <x-empty-state
                        title="No posts found"
                        message="We couldn't find any posts matching your search criteria."
                    >
                        <x-button :href="route('posts.index')">
                            View All Posts
                        </x-button>
                    </x-empty-state>
                </div>
            @endforelse
        </div>

        <!-- Pagination (Placeholder) -->
        <div class="flex justify-center">
            <nav class="flex items-center gap-2">
                <button disabled class="px-4 py-2 border border-default rounded-lg text-secondary bg-surface-secondary cursor-not-allowed">
                    Previous
                </button>
                <button class="px-4 py-2 bg-accent-primary text-surface rounded-lg font-medium">
                    1
                </button>
                <button class="px-4 py-2 border border-default rounded-lg text-primary hover:bg-surface-secondary">
                    2
                </button>
                <button class="px-4 py-2 border border-default rounded-lg text-primary hover:bg-surface-secondary">
                    3
                </button>
                <button class="px-4 py-2 border border-default rounded-lg text-primary hover:bg-surface-secondary">
                    Next
                </button>
            </nav>
        </div>

    </div>
</div>
@endsection
