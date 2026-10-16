@extends('layouts.app')

@section('title', $post['title'] ?? 'Post Not Found')

@section('content')
<article class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Breadcrumb -->
        <nav class="flex items-center text-sm text-secondary mb-8" aria-label="Breadcrumb">
            <a href="{{ url('/') }}" class="hover:text-accent-primary">Home</a>
            <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
            </svg>
            <a href="{{ route('posts.index') }}" class="hover:text-accent-primary">Posts</a>
            <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
            </svg>
            <span class="text-primary">{{ $post['title'] ?? 'Post' }}</span>
        </nav>

        <!-- Article Header -->
        <header class="mb-8">
            @if($post['category'] ?? false)
                <x-badge variant="primary" class="mb-4">
                    {{ $post['category'] }}
                </x-badge>
            @endif

            <h1 class="text-4xl md:text-5xl font-bold text-primary mb-4 leading-tight">
                {{ $post['title'] }}
            </h1>

            @if($post['excerpt'] ?? false)
                <p class="text-xl text-secondary mb-6">
                    {{ $post['excerpt'] }}
                </p>
            @endif

            <!-- Meta Information -->
            <div class="flex flex-wrap items-center gap-4 text-sm text-secondary">
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-full bg-accent-primary flex items-center justify-center text-surface font-medium">
                        {{ strtoupper(substr($post['author'] ?? 'A', 0, 1)) }}
                    </div>
                    <div>
                        <div class="text-primary font-medium">{{ $post['author'] ?? 'Unknown Author' }}</div>
                        <div class="text-xs">Author</div>
                    </div>
                </div>
                <div class="hidden sm:block text-default">•</div>
                <div>
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ date('F j, Y', strtotime($post['published_at'] ?? 'now')) }}
                </div>
                <div class="hidden sm:block text-default">•</div>
                <div>
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    5 min read
                </div>
            </div>
        </header>

        <!-- Cover Image -->
        @if($post['cover_image'] ?? false)
            <div class="mb-8 rounded-xl overflow-hidden">
                <img
                    src="{{ $post['cover_image'] }}"
                    alt="{{ $post['title'] }}"
                    class="w-full h-auto object-cover"
                    loading="lazy"
                >
            </div>
        @endif

        <!-- Backend Notice -->
        <x-alert type="warning" class="mb-8">
            <strong>Note:</strong> Post content rendering requires backend implementation.
            Create Domain/Blog/Entities/Post, PostRepository, and GetPostBySlugUseCase.
        </x-alert>

        <!-- Article Content -->
        <div class="prose prose-lg max-w-none">
            <div class="text-primary leading-relaxed space-y-6">
                {!! $post['content'] ?? '<p>No content available.</p>' !!}
            </div>
        </div>

        <!-- Tags (if available) -->
        @if(!empty($post['tags']))
            <div class="mt-12 pt-8 border-t border-default">
                <h3 class="text-sm font-medium text-secondary mb-3">Tagged with:</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($post['tags'] as $tag)
                        <a href="#" class="px-3 py-1.5 bg-surface-secondary text-secondary text-sm rounded-full hover:bg-accent-primary hover:text-surface transition-colors">
                            #{{ $tag }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Share Section -->
        <div class="mt-12 pt-8 border-t border-default">
            <h3 class="text-sm font-medium text-secondary mb-3">Share this article:</h3>
            <div class="flex gap-3">
                <button class="px-4 py-2 bg-surface-secondary hover:bg-accent-primary hover:text-surface text-primary rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </button>
                <button class="px-4 py-2 bg-surface-secondary hover:bg-accent-primary hover:text-surface text-primary rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                </button>
                <button class="px-4 py-2 bg-surface-secondary hover:bg-accent-primary hover:text-surface text-primary rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Back to Posts -->
        <div class="mt-12">
            <a href="{{ route('posts.index') }}" class="inline-flex items-center text-accent-primary hover:underline">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to All Posts
            </a>
        </div>

        <!-- Related Posts Section (Mock) -->
        @if(!empty($relatedPosts))
            <div class="mt-16 pt-12 border-t border-default">
                <h2 class="text-2xl font-bold text-primary mb-6">Related Posts</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($relatedPosts as $related)
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
.prose ul, .prose ol {
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
