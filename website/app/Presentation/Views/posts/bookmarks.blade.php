@extends ('layouts.app')

@section ('title', __('bookmarks.title'))

@section ('content')
    <div class="container-custom section-spacing">
        <h1 class="text-hero mb-8">{{ __('bookmarks.title') }}</h1>

        @if ($posts === [])
            <p class="text-secondary">{{ __('bookmarks.empty') }}</p>
            <a href="{{ route('posts.index') }}" class="text-accent-primary mt-4 inline-block font-medium hover:underline">
                {{ __('bookmarks.explore') }}
            </a>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $item)
                    <x-post-card
                        :title="$item->post->title"
                        :excerpt="$item->post->excerpt"
                        :url="route('public.post', [$item->blog->slug, $item->post->slug])"
                        :image="$item->coverUrl"
                        :date="$item->post->publishedAt"
                        :category="$item->blog->name"
                        :likes="$item->engagement->likes"
                        :views="$item->engagement->views"
                    />
                @endforeach
            </div>
        @endif
    </div>
@endsection
