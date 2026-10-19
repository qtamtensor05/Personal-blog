@extends ('layouts.app')

@section ('title', __('posts.all'))

@section ('content')
    <div class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-primary mb-2 text-4xl font-bold">{{ __('posts.all') }}</h1>
                <p class="text-secondary">{{ __('posts.explore') }}</p>
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
                        <label for="search" class="sr-only">{{ __('posts.search_label') }}</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="{{ __('posts.search_placeholder') }}"
                            value="{{ $search }}"
                            class="border-default focus:ring-accent-primary focus:border-accent-primary text-primary placeholder:text-secondary w-full rounded-lg border px-4 py-2.5 focus:ring-2 focus:outline-none"
                        />
                    </div>

                    <!-- Search Button -->
                    <x-button type="submit">
                        <i class="fa-solid fa-magnifying-glass mr-2 h-5 w-5" aria-hidden="true"></i>
                        {{ __('posts.search') }}
                    </x-button>
                </form>
            </div>

            @if ($search !== '')
                <div class="mb-6">
                    <p class="text-secondary text-sm">
                        {{ __('posts.results_for') }} "<strong class="text-primary">{{ $search }}</strong>"
                        <a
                            href="{{ route('posts.index') }}"
                            class="text-accent-primary ml-2 hover:underline"
                            >{{ __('posts.clear_search') }}</a
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
                            :title="__('posts.none_found')"
                            :message="__('posts.none_match')"
                        >
                            <x-button :href="route('posts.index')">{{ __('posts.view_all') }}</x-button>
                        </x-empty-state>
                    </div>
                @endforelse
            </div>

            @if ($posts->lastPage() > 1)
                <nav
                    aria-label="{{ __('posts.pagination') }}"
                    class="flex flex-wrap items-center justify-center gap-2"
                >
                    @if ($posts->currentPage > 1)
                        <a
                            href="{{ route('posts.index', array_filter(['search' => $search, 'page' => $posts->currentPage - 1])) }}"
                            rel="prev"
                            class="btn btn-secondary"
                            >{{ __('posts.previous') }}</a
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
                            >{{ __('posts.next') }}</a
                        >
                    @endif
                </nav>
            @endif
        </div>
    </div>
@endsection
