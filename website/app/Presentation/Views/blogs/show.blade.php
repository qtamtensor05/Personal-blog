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
                    {{ __('blog.status_'.$blog->status->value) }}
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
                <p class="text-accent-primary text-sm font-semibold tracking-wider uppercase">{{ __('template.management') }}</p>
                <h2 class="text-primary mt-1 text-2xl font-semibold">{{ __('template.home_and_layout') }}</h2>
                <p class="text-secondary mt-1 text-sm">{{ __('template.fixed_positions_help') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                        'header' => [
                            __('template.header'),
                            __('template.header_description'),
                            'fa-solid fa-heading'
                        ],
                        'body' => [
                            __('template.home'),
                            __('template.home_description'),
                            'fa-solid fa-house'
                        ],
                        'sidebar' => [
                            __('template.sidebar'),
                            __('template.sidebar_description'),
                            'fa-solid fa-table-columns'
                        ],
                        'footer' => [
                            __('template.footer'),
                            __('template.footer_description'),
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
                            {{ __('template.manage_content') }}
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
                    <h3 class="text-primary font-semibold">{{ __('template.posts') }}</h3>
                    <p class="text-secondary mt-2 text-sm">{{ __('template.posts_description') }}</p>
                    <div class="mt-4 flex flex-col items-start gap-2 text-sm font-medium">
                        <a
                            href="{{ route('blogs.posts.index', $blog->id) }}"
                            class="text-accent-primary inline-flex items-center gap-2 hover:underline"
                        >
                            {{ __('template.manage_posts') }}
                            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                        </a>
                        <a href="{{ route('public.blog.section', [$blog->slug, 'posts']) }}" class="text-accent-primary inline-flex items-center gap-2 hover:underline">
                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>{{ __('template.view_site') }}
                        </a>
                    </div>
                </article>
                <a
                    href="{{ route('blogs.media.index', $blog->id) }}"
                    class="card group hover:border-accent-primary block transition hover:-translate-y-0.5"
                >
                    <div class="bg-surface-secondary text-accent-primary mb-4 flex h-10 w-10 items-center justify-center rounded-md">
                        <i class="fa-solid fa-photo-film" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-primary group-hover:text-accent-primary font-semibold">{{ __('template.media_library') }}</h3>
                    <p class="text-secondary mt-2 text-sm">{{ __('template.media_library_description') }}</p>
                    <span class="text-accent-primary mt-4 inline-flex items-center gap-2 text-sm font-medium">
                        {{ __('template.open_media_library') }}
                        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
                    </span>
                </a>
            </div>
        </section>

        <div class="mb-8">
            <div class="card">
                <h2 class="text-primary mb-4 text-lg font-semibold">{{ __('template.view_site') }}</h2>
                <p class="text-secondary mb-4 text-sm">{{ __('template.view_site_description') }}</p>
                <a
                    href="{{ route('public.blog', $blog->slug) }}"
                    target="_blank"
                    rel="noopener"
                    class="btn-secondary text-sm"
                >
                    {{ __('template.open_blog') }}
                </a>
            </div>
        </div>

        <section class="border-default border-t pt-6" aria-labelledby="blog-settings-heading">
            <h2 id="blog-settings-heading" class="text-primary mb-4 text-lg font-semibold">{{ __('template.blog_settings') }}</h2>
            <div class="grid gap-6 lg:grid-cols-2">
                <section id="blog-information" class="card space-y-4">
                    <h3 class="text-primary font-semibold"><i class="fa-solid fa-gear mr-2" aria-hidden="true"></i>{{ __('template.edit_blog') }}</h3>
                    <form method="POST" action="{{ route('blogs.update', $blog->id) }}" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <x-input name="name" :label="__('template.blog_name')" :value="old('name', $blog->name)" required />
                        <x-input name="slug" :label="__('template.slug')" :value="old('slug', $blog->slug)" required />
                        <x-textarea name="description" :label="__('template.description')" rows="3">{{ old('description', $blog->description) }}</x-textarea>
                        <div>
                            <label for="blog-status" class="text-primary mb-1 block text-sm font-medium">{{ __('template.status') }}</label>
                            <select id="blog-status" name="status" class="input">
                                @foreach (['DRAFT', 'PRIVATE', 'PUBLIC'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $blog->status->value === 'DRAFT' ? 'DRAFT' : ($blog->visibility->value === 'PRIVATE' ? 'PRIVATE' : 'PUBLIC')) === $status)>{{ __('template.status_'.$status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="visibility" value="{{ old('status', $blog->status->value === 'DRAFT' ? 'DRAFT' : ($blog->visibility->value === 'PRIVATE' ? 'PRIVATE' : 'PUBLIC')) === 'PRIVATE' ? 'PRIVATE' : 'PUBLIC' }}">
                        <input type="hidden" name="discoverable" value="{{ $blog->discoverable ? 1 : 0 }}">
                        @if ($errors->any()) <p class="text-sm text-red-500">{{ $errors->first() }}</p> @endif
                        <button type="submit" class="btn-primary px-4 py-2 rounded-md">{{ __('template.save_changes') }}</button>
                    </form>
                </section>

                <section id="blog-taxonomy" class="card space-y-5">
                    <h3 class="text-primary font-semibold"><i class="fa-solid fa-tags mr-2" aria-hidden="true"></i>{{ __('template.categories') }}</h3>
                    <div>
                        <h4 class="text-primary mb-2 text-sm font-medium">{{ __('template.categories') }}</h4>
                        <form method="POST" action="{{ route('blogs.categories.store', $blog->id) }}" class="mb-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                            @csrf
                            <x-input name="name" :label="__('template.name')" required data-slug-source />
                            <x-input name="slug" :label="__('template.slug')" required data-slug-target />
                            <input type="hidden" name="parent_id" value="">
                            <button class="bg-accent-primary px-4 py-2 rounded-md border !text-white self-end" type="submit">{{ __('template.add') }}</button>
                        </form>
                        <ul class="divide-default divide-y">
                            @forelse ($categories as $category)
                                <li class="flex items-center justify-between gap-3 py-2"><span>{{ $category->name }} <small class="text-secondary">{{ $category->slug }}</small></span><form method="POST" action="{{ route('blogs.categories.destroy', [$blog->id, $category->id]) }}" data-modal-confirm-form data-confirm-title="{{ __('template.delete_category') }}" data-confirm-message="{{ __('template.confirm_delete_category') }}">@csrf @method('DELETE')<button class="text-red-500" type="submit" aria-label="{{ __('template.delete_category') }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form></li>
                            @empty <li class="py-2 text-sm text-secondary">{{ __('template.no_categories') }}</li> @endforelse
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-primary mb-2 text-sm font-medium">{{ __('template.tags') }}</h4>
                        <form method="POST" action="{{ route('blogs.tags.store', $blog->id) }}" class="mb-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                            @csrf
                            <x-input name="name" :label="__('template.name')" required data-slug-source />
                            <x-input name="slug" :label="__('template.slug')" required data-slug-target />
                            <button class="bg-accent-primary px-4 py-2 rounded-md border !text-white self-end" type="submit">{{ __('template.add') }}</button>
                        </form>
                        <ul class="divide-default divide-y">
                            @forelse ($tags as $tag)
                                <li class="flex items-center justify-between gap-3 py-2"><span>{{ $tag->name }} <small class="text-secondary">{{ $tag->slug }}</small></span><form method="POST" action="{{ route('blogs.tags.destroy', [$blog->id, $tag->id]) }}" data-modal-confirm-form data-confirm-title="{{ __('template.delete_tag') }}" data-confirm-message="{{ __('template.confirm_delete_tag') }}">@csrf @method('DELETE')<button class="text-red-500" type="submit" aria-label="{{ __('template.delete_tag') }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button></form></li>
                            @empty <li class="py-2 text-sm text-secondary">{{ __('template.no_tags') }}</li> @endforelse
                        </ul>
                    </div>
                    @if ($errors->any()) <p class="text-sm text-red-500">{{ $errors->first() }}</p> @endif
                </section>
            </div>
        </section>
        <div class="border-default flex items-center gap-3 border-t pt-6">
            <form
                method="POST"
                action="{{ route('blogs.destroy', $blog->id) }}"
                class="inline"
                data-modal-confirm-form
                data-confirm-title="{{ __('template.delete_blog') }}"
                data-confirm-message="{{ __('template.confirm_delete_blog') }}"
            >
                @csrf
                @method ('DELETE')
                <button type="submit" class="text-sm text-red-500 px-4 py-2 border border-red-500 rounded-md hover:underline">
                    {{ __('template.delete_blog') }}
                </button>
            </form>
        </div>
    </div>
@endsection
