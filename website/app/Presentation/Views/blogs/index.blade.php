@extends ('layouts.app')

@section ('title', 'My Blogs')

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8">
            <div class="mb-4 flex items-center justify-between">
                <h1 class="text-hero">My Blogs</h1>
                <a
                    href="{{ route('blogs.create') }}"
                    class="btn-primary flex items-center gap-2 rounded-md px-4 py-2"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Blog
                </a>
            </div>
            <p class="text-secondary">Manage your blogs and choose templates to customize.</p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if (count($blogs) === 0)
            <x-empty-state
                message="No blogs yet"
                action="{{ route('blogs.create') }}"
                actionLabel="Create your first blog"
            >
                Start by creating a blog, then choose a template and customize its design.
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
                                    {{ $blog->status->value }}
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

                        <div class="border-default flex items-center justify-between border-t pt-4">
                            <a
                                href="{{ route('blogs.edit', $blog->id) }}"
                                class="text-accent-primary text-sm hover:underline"
                            >
                                Edit
                            </a>
                            <span class="text-secondary font-mono text-xs">{{ $blog->slug }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
