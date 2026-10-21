@extends ('layouts.app')

@section ('title', $profile->name)

@section ('content')
    <div class="container-custom section-spacing max-w-4xl">
        <section class="card flex flex-wrap items-center gap-5 p-6 sm:p-8">
            @if ($profile->avatarUrl)
                <img src="{{ $profile->avatarUrl }}" alt="" class="h-20 w-20 rounded-full object-cover" />
            @else
                <span class="bg-accent-primary text-surface flex h-20 w-20 items-center justify-center rounded-full text-2xl font-semibold" aria-hidden="true">{{ mb_strtoupper(mb_substr($profile->name, 0, 1)) }}</span>
            @endif
            <div>
                <p class="text-secondary text-sm">{{ __('profile.public_title') }}</p>
                <h1 class="text-hero">{{ $profile->name }}</h1>
            </div>
        </section>

        <section class="mt-8" aria-labelledby="public-profile-blogs">
            <h2 id="public-profile-blogs" class="text-primary mb-4 text-xl font-semibold">{{ __('profile.public_blogs') }}</h2>
            @if ($blogs === [])
                <p class="text-secondary">{{ __('profile.no_public_blogs') }}</p>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($blogs as $blog)
                        <a href="{{ route('public.blog', $blog->slug) }}" class="card hover:border-accent-primary block p-5 transition-colors">
                            <h3 class="text-primary font-semibold">{{ $blog->name }}</h3>
                            @if ($blog->description)
                                <p class="text-secondary mt-2 line-clamp-3 text-sm">{{ $blog->description }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
