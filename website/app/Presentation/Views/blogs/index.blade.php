@extends ('layouts.app')

@section ('title', __('blog.my_blogs'))

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-hero">{{ __('blog.my_blogs') }}</h1>
                <a
                    href="{{ route('blogs.create') }}"
                    class="btn-primary flex items-center gap-2 rounded-md px-4 py-2"
                >
                    <i class="fa-solid fa-plus h-5 w-5" aria-hidden="true"></i>
                    {{ __('blog.new_blog') }}
                </a>
            </div>
            <p class="text-secondary">{{ __('blog.manage_intro') }}</p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if (count($blogs) === 0)
            <x-empty-state
                :message="__('blog.empty_title')"
                action="{{ route('blogs.create') }}"
                :actionLabel="__('blog.empty_action')"
            >
                {{ __('blog.empty_help') }}
            </x-empty-state>
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($blogs as $blog)
                    <div class="card group">
                        <div class="mb-4">
                            <div class="mb-2 flex items-start justify-between">
                                <h3
                                    class="text-primary group-hover:text-accent-primary text-lg font-semibold transition-colors"
                                >
                                    <a
                                        href="{{ route('blogs.show', $blog->id) }}"
                                        >{{ $blog->name }}</a
                                    >
                                </h3>
                                <x-badge
                                    :variant="$blog->status->value === 'PUBLISHED' ? 'primary' : 'default'"
                                >
                                    {{ __('blog.status_'.$blog->status->value) }}
                                </x-badge>
                            </div>
                            @if ($blog->description)
                                <p class="text-secondary text-sm">{{
                                    Str::limit(
                                        $blog->description,
                                        100,
                                    )
                                }}</p>
                            @endif
                        </div>

                        <div class="border-default border-t pt-4">
                            <span class="text-secondary font-mono text-xs">{{ $blog->slug }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
