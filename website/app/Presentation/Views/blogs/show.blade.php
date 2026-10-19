@extends ('layouts.app')

@section ('title', $blog->name)

@section ('content')
    <div class="container-custom section-spacing max-w-4xl">
        <div class="mb-8">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h1 class="text-hero mb-2">{{ $blog->name }}</h1>
                    <p class="text-secondary font-mono text-sm">{{ $blog->slug }}</p>
                </div>
                <x-badge :variant="$blog->status->value === 'PUBLISHED' ? 'primary' : 'default'">
                    {{ $blog->status->value }}
                </x-badge>
            </div>
            @if ($blog->description)
                <p class="text-secondary">{{ $blog->description }}</p>
            @endif
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        <section class="mb-8">
            <div class="mb-4">
                <p class="text-accent-primary text-sm font-semibold tracking-wider uppercase">Website management</p>
                <h2 class="text-primary mt-1 text-2xl font-semibold">Home page and layout</h2>
                <p class="text-secondary mt-1 text-sm">Edit the content in the fixed positions of the template currently in use.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                        'header' => [
                            'Header',
                            'Logo, blog name, menu, and top-of-page CTA.',
                            'fa-solid fa-heading'
                        ],
                        'body' => [
                            'Body / Home page',
                            'Banner, hero, content, latest posts, and categories.',
                            'fa-solid fa-house'
                        ],
                        'sidebar' => [
                            'Sidebar',
                            'Blogger info, latest posts, and newsletter.',
                            'fa-solid fa-table-columns'
                        ],
                        'footer' => [
                            'Footer',
                            'Description, links, social networks, and copyright.',
                            'fa-solid fa-grip-lines'
                        ]
                    ]
                    as $region => [$label, $description, $icon])
                    <a
                        href="{{ route('blogs.design.edit', ['blog' => $blog->id, 'region' => $region]) }}"
                        class="card group hover:border-accent-primary block transition hover:-translate-y-0.5"
                    >
                        <div
                            class="bg-surface-secondary text-accent-primary mb-4 flex h-10 w-10 items-center justify-center rounded-md"
                        >
                            <i class="{{ $icon }}" aria-hidden="true"></i>
                        </div>
                        <h3 class="text-primary group-hover:text-accent-primary font-semibold">
                            {{ $label }}
                        </h3>
                        <p class="text-secondary mt-2 text-sm">{{ $description }}</p>
                        <span
                            class="text-accent-primary mt-4 inline-flex items-center gap-2 text-sm font-medium"
                        >
                            Manage content
                            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </span>
                    </a>
                @endforeach
                <article class="card hover:border-accent-primary transition hover:-translate-y-0.5">
                    <div
                        class="bg-surface-secondary text-accent-primary mb-4 flex h-10 w-10 items-center justify-center rounded-md"
                    >
                        <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-primary font-semibold">Posts</h3>
                    <p class="text-secondary mt-2 text-sm">Create posts, add images, choose categories, and publish content.</p>
                    <div class="mt-4 flex flex-col items-start gap-2 text-sm font-medium">
                        <a
                            href="{{ route('blogs.posts.index', $blog->id) }}"
                            class="text-accent-primary inline-flex items-center gap-2 hover:underline"
                        >
                            Add / manage posts
                            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                        <a
                            href="{{ route('blogs.design.edit', ['blog' => $blog->id, 'region' => 'body']).'#slot-latest-posts' }}"
                            class="text-accent-primary inline-flex items-center gap-2 hover:underline"
                        >
                            Configure post display
                            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                    </div>
                </article>
                <a
                    href="{{ route('blogs.media.index', $blog->id) }}"
                    class="card group hover:border-accent-primary block transition hover:-translate-y-0.5"
                >
                    <div
                        class="bg-surface-secondary text-accent-primary mb-4 flex h-10 w-10 items-center justify-center rounded-md"
                    >
                        <i class="fa-solid fa-photo-film" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-primary group-hover:text-accent-primary font-semibold">
                        Media
                    </h3>
                    <p class="text-secondary mt-2 text-sm">Upload and manage images and videos used in templates and posts.</p>
                    <span
                        class="text-accent-primary mt-4 inline-flex items-center gap-2 text-sm font-medium"
                    >
                        Open Media library
                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </span>
                </a>
            </div>
        </section>

        <div class="mb-8">
            <div class="card">
                <h2 class="text-primary mb-4 text-lg font-semibold">View Public Site</h2>
                <p class="text-secondary mb-4 text-sm">See how your blog looks to visitors.</p>
                <a
                    href="{{ route('public.blog', $blog->slug) }}"
                    target="_blank"
                    rel="noopener"
                    class="btn-secondary text-sm"
                >
                    Preview Blog
                </a>
            </div>
        </div>

        <div class="border-default flex items-center gap-3 border-t pt-6">
            <a href="{{ route('blogs.edit', $blog->id) }}" class="btn-secondary">Edit Blog Info</a>
            <a href="{{ route('blogs.taxonomy', $blog->id) }}" class="btn-secondary"
                >Categories &amp; Tags</a
            >
            <form
                method="POST"
                action="{{ route('blogs.destroy', $blog->id) }}"
                class="inline"
                data-modal-confirm-form
                data-confirm-title="Delete blog"
                data-confirm-message="Delete this blog? This cannot be undone."
            >
                @csrf
                @method ('DELETE')
                <button type="submit" class="text-sm text-red-500 hover:underline">
                    Delete Blog
                </button>
            </form>
        </div>
    </div>
@endsection
