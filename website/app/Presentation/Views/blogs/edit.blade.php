@extends ('layouts.app')

@section ('title', __('blog.edit_title'))

@section ('content')
    <div class="container-custom section-spacing max-w-4xl">
        <div class="mb-8">
            <div class="mb-5 flex items-center gap-3">
                <a
                    href="{{ route('blogs.show', $blog->id) }}"
                    class="btn-ghost"
                    aria-label="{{ __('blog.back_to_blog') }}"
                    ><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    {{ __('blog.back_to_blog') }}</a
                >
                <h1 class="text-hero">
                    {{
                        __('blog.manage_title', [
                            'name' => $blog->name,
                        ])
                    }}
                </h1>
            </div>
            <p class="text-secondary">{{ __('blog.manage_help') }}</p>
        </div>

        <nav class="mb-6 flex flex-wrap gap-2" aria-label="{{ __('blog.management') }}">
            <span class="btn-primary"
                ><i class="fa-solid fa-gear mr-2" aria-hidden="true"></i
                >{{ __('blog.information') }}</span
            >
            <a
                class="btn-secondary"
                href="{{ route('blogs.design.edit', ['blog' => $blog->id, 'region' => 'body']) }}"
                ><i class="fa-solid fa-palette mr-2" aria-hidden="true"></i
                >{{ __('blog.appearance') }}</a
            >
            <a
                class="btn-secondary"
                href="{{ route('blogs.design.edit', ['blog' => $blog->id, 'region' => 'body']).'#slot-about' }}"
                ><i class="fa-solid fa-user mr-2" aria-hidden="true"></i>{{ __('blog.about') }}</a
            >
            <a
                class="btn-secondary"
                href="{{ route('blogs.design.edit', ['blog' => $blog->id, 'region' => 'body']).'#slot-contact' }}"
                ><i class="fa-solid fa-address-card mr-2" aria-hidden="true"></i
                >{{ __('blog.contact') }}</a
            >
            <a class="btn-secondary" href="{{ route('blogs.posts.index', $blog->id) }}"
                ><i class="fa-solid fa-newspaper mr-2" aria-hidden="true"></i
                >{{ __('post.posts') }}</a
            >
        </nav>
        <div class="card">
            <form method="POST" action="{{ route('blogs.update', $blog->id) }}" class="space-y-5">
                @csrf
                @method ('PUT')

                @if ($errors->has('general'))
                    <x-alert type="error" dismissible>{{ $errors->first('general') }}</x-alert>
                @endif

                <x-input
                    name="name"
                    :label="__('blog.blog_name')"
                    :value="old('name', $blog->name)"
                    :error="$errors->first('name')"
                    required
                />

                <x-input
                    name="slug"
                    :label="__('blog.slug')"
                    :hint="__('blog.slug_help')"
                    :value="old('slug', $blog->slug)"
                    :error="$errors->first('slug')"
                    required
                />

                <x-textarea
                    name="description"
                    :label="__('blog.description')"
                    rows="3"
                    :error="$errors->first('description')"
                    >{{
                        old(
                            'description',
                            $blog->description,
                        )
                    }}</x-textarea
                >

                <div class="border-default border-t pt-5">
                    <h2 class="text-primary mb-4 text-lg font-semibold">{{ __('blog.access') }}</h2>

                    <div class="space-y-4">
                        <div>
                            <label
                                class="text-primary mb-2 block text-sm font-medium"
                                >{{ __('blog.status') }}</label
                            >
                            <select name="status" class="input">
                                @foreach (['DRAFT', 'PRIVATE', 'PUBLIC'] as $status)
                                    <option
                                        value="{{ $status }}"
                                        {{
                                            old(
                                                'status',
                                                $blog->status->value === 'DRAFT'
                                                    ? 'DRAFT'
                                                    : ($blog->visibility->value === 'PRIVATE'
                                                        ? 'PRIVATE'
                                                        : 'PUBLIC'),
                                            ) === $status
                                                ? 'selected'
                                                : ''
                                        }}
                                    >
                                        {{ __('template.status_' . $status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <input
                            type="hidden"
                            name="visibility"
                            value="{{ old('status', $blog->visibility->value === 'PRIVATE' ? 'PRIVATE' : 'PUBLIC') === 'PRIVATE' ? 'PRIVATE' : 'PUBLIC' }}"
                        />
                        <p class="text-secondary text-xs">{{ __('blog.status_help') }}</p>

                        <div class="flex items-center gap-2">
                            <input type="hidden" name="discoverable" value="0" />
                            <input
                                type="checkbox"
                                name="discoverable"
                                value="1"
                                id="discoverable"
                                {{
                                    old('discoverable', $blog->discoverable)
                                        ? 'checked'
                                        : ''
                                }}
                                class="border-default h-4 w-4 rounded"
                            />
                            <label for="discoverable" class="text-secondary text-sm">
                                {{ __('blog.discoverable') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div class="border-default flex items-center gap-3 border-t pt-4">
                    <button type="submit" class="btn-primary rounded-md px-4 py-2">
                        {{ __('blog.save_changes') }}
                    </button>
                    <a
                        href="{{ route('blogs.show', $blog->id) }}"
                        class="btn-secondary"
                        >{{ __('blog.back') }}</a
                    >
                </div>
            </form>
        </div>
    </div>

    @push ('scripts')
        <script>
            (function () {
                const name = document.querySelector('[name=name]');
                const slug = document.querySelector('[name=slug]');
                if (!name || !slug) return;
                let edited =
                    slug.value !==
                    name.value
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-|-$/g, '');
                const slugify = (value) =>
                    value
                        .toLowerCase()
                        .trim()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-|-$/g, '');
                name.addEventListener('input', () => {
                    if (!edited) slug.value = slugify(name.value);
                });
                slug.addEventListener('input', () => {
                    edited = true;
                });
            })();
        </script>
    @endpush
@endsection
