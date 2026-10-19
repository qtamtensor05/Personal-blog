@extends ('layouts.app')

@section ('title', 'All Posts')

@section ('content')
    <div class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-primary mb-2 text-4xl font-bold">All Posts</h1>
                <p class="text-secondary">Explore our collection of articles and stories</p>
            </div>

            <!-- Search and Filters -->
            <div class="card mb-8">
                <form
                    method="GET"
                    action="{{ route('posts.index') }}"
                    class="flex flex-col gap-4 md:flex-row"
                >
                    <!-- Search Input -->
                    <div class="flex-1">
                        <label for="search" class="sr-only">Search posts</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="Search by title or summary..."
                            value="{{ $search }}"
                            class="border-default focus:ring-accent-primary focus:border-accent-primary text-primary placeholder:text-secondary w-full rounded-lg border px-4 py-2.5 focus:ring-2 focus:outline-none"
                        />
                    </div>

                    <!-- Search Button -->
                    <x-button type="submit">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Search
                    </x-button>
                </form>
            </div>

            @if ($search !== '')
                <div class="mb-6">
                    <p class="text-secondary text-sm">
                        Showing results for "<strong class="text-primary">{{ $search }}</strong>"
                        <a
                            href="{{ route('posts.index') }}"
                            class="text-accent-primary ml-2 hover:underline"
                            >Clear search</a
                        >
                    </p>
                </div>
            @endif

            <!-- Posts Grid -->
            <div class="mb-12 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($posts->items as $post)
                    <x-post-card
                        :title="$post->title"
                        :excerpt="$post->excerpt"
                        :url="route('public.post', [$post->blogSlug, $post->slug])"
                        :image="$post->coverUrl"
                        :date="$post->publishedAt"
                        :author="$post->authorName"
                        :is-featured="$post->isFeatured"
                    />
                @empty
                    <div class="col-span-full">
                        <x-empty-state
                            title="No posts found"
                            message="We couldn't find any posts matching your search criteria."
                        >
                            <x-button :href="route('posts.index')"> View All Posts </x-button>
                        </x-empty-state>
                    </div>
                @endforelse
            </div>

            @if ($posts->lastPage() > 1)
                <nav
                    aria-label="Post pagination"
                    class="flex flex-wrap items-center justify-center gap-2"
                >
                    @if ($posts->currentPage > 1)
                        <a
                            href="{{ route('posts.index', array_filter(['search' => $search, 'page' => $posts->currentPage - 1])) }}"
                            rel="prev"
                            class="btn btn-secondary"
                            >Previous</a
                        >
                    @endif

                    @for ($page = 1; $page <= $posts->lastPage(); $page++)
                        <a
                            href="{{ route('posts.index', array_filter(['search' => $search, 'page' => $page])) }}"
                            @if ($page === $posts->currentPage) aria-current="page" @endif
                            class="btn {{ $page === $posts->currentPage ? 'btn-primary' : 'btn-secondary' }} min-w-10 justify-center"
                        >
                            {{ $page }}
                        </a>
                    @endfor

                    @if ($posts->currentPage < $posts->lastPage())
                        <a
                            href="{{ route('posts.index', array_filter(['search' => $search, 'page' => $posts->currentPage + 1])) }}"
                            rel="next"
                            class="btn btn-secondary"
                            >Next</a
                        >
                    @endif
                </nav>
            @endif
        </div>
    </div>
@endsection
