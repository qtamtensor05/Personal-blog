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
                    <article class="card flex h-full flex-col p-6">
                        <p class="text-secondary mb-2 text-sm">{{ $item['blog']->name }}</p>
                        <h2 class="text-primary text-xl font-semibold">
                            <a
                                href="{{ route('public.post', [$item['blog']->slug, $item['post']->slug]) }}"
                                class="hover:text-accent-primary hover:underline"
                            >{{ $item['post']->title }}</a>
                        </h2>
                        @if ($item['post']->excerpt)
                            <p class="text-secondary mt-3 line-clamp-3 text-sm">{{ $item['post']->excerpt }}</p>
                        @endif
                        <div class="border-default mt-auto flex justify-end border-t pt-4">
                            <span class="text-secondary inline-flex items-center gap-1.5 text-xs">
                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                {{ __('post.views_count') }}: {{ $item['views'] }}
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection
