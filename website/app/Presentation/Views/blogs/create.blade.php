@extends ('layouts.app')

@section ('title', __('blog.create_title'))

@section ('content')
    <div class="container-custom section-spacing max-w-2xl">
        <div class="mb-8">
            <p class="text-accent-primary mb-2 text-sm font-semibold tracking-wider uppercase">{{ __('ui.create_blog') }}</p>
            <h1 class="text-hero mb-2">{{ __('blog.create_title') }}</h1>
            <p class="text-secondary">{{ __('blog.create_intro') }}</p>
        </div>

        <div class="card">
            <form method="POST" action="{{ route('blogs.store') }}" class="space-y-5">
                @csrf

                <x-input
                    name="name"
                    :label="__('blog.name')"
                    placeholder="My Awesome Blog"
                    :error="$errors->first('name')"
                    :value="old('name')"
                    required
                />

                <x-input
                    name="slug"
                    :label="__('blog.slug')"
                    placeholder="my-awesome-blog"
                    :hint="__('blog.slug_help')"
                    :error="$errors->first('slug')"
                    :value="old('slug')"
                    required
                />

                <x-textarea
                    name="description"
                    :label="__('blog.description')"
                    :placeholder="__('blog.description_placeholder')"
                    rows="3"
                    :error="$errors->first('description')"
                    >{{ old('description') }}</x-textarea
                >

                <div class="border-default flex items-center gap-3 border-t pt-4">
                    <button type="submit" class="btn-primary rounded-md px-4 py-2">
                        {{ __('blog.create') }}
                    </button>
                    <a
                        href="{{ route('blogs.index') }}"
                        class="btn-ghost rounded-md border px-4 py-2"
                        >{{ __('common.cancel') }}</a
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
                let edited = Boolean(slug.value);
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
