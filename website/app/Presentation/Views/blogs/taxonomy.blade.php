@extends ('layouts.app')

@section ('title', 'Categories & Tags — ' . $blog->name)

@section ('content')
    <div class="container-custom section-spacing max-w-5xl">
        <div class="mb-8">
            <a
                href="{{ route('blogs.show', $blog->id) }}"
                class="text-secondary hover:text-accent-primary text-sm"
                >← {{ $blog->name }}</a
            >
            <h1 class="text-hero mt-2">Categories &amp; Tags</h1>
            <p class="text-secondary">Organize posts. Deleting a category or tag removes it from posts, not the posts themselves.</p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <div class="grid gap-8 lg:grid-cols-2">
            {{-- Categories --}}
            <section class="card space-y-5">
                <h2 class="text-primary text-lg font-semibold">Categories</h2>

                <form
                    method="POST"
                    action="{{ route('blogs.categories.store', $blog->id) }}"
                    class="space-y-3"
                >
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-input name="name" label="Name" required data-slug-source />
                        <x-input
                            name="slug"
                            label="Slug"
                            required
                            hint="e.g. travel-notes"
                            data-slug-target
                        />
                    </div>
                    <div class="space-y-1">
                        <label for="parent_id" class="text-primary block text-sm font-medium"
                            >Parent</label
                        >
                        <select name="parent_id" id="parent_id" class="input">
                            <option value="">None</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn-primary rounded-md px-4 py-2 text-sm">
                        Add category
                    </button>
                </form>

                @if (count($categories) === 0)
                    <p class="text-secondary text-sm">No categories yet.</p>
                @else
                    <ul class="divide-default border-default divide-y border-t">
                        @foreach ($categories as $category)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div>
                                    <span
                                        class="text-primary font-medium"
                                        >{{ $category->name }}</span
                                    >
                                    <span
                                        class="text-secondary ml-1 font-mono text-xs"
                                        >{{ $category->slug }}</span
                                    >
                                    @if (!$category->isActive)
                                        <x-badge class="ml-1">inactive</x-badge>
                                    @endif
                                </div>
                                <form
                                    method="POST"
                                    action="{{ route('blogs.categories.destroy', [$blog->id, $category->id]) }}"
                                    data-modal-confirm-form
                                    data-confirm-title="Delete category"
                                    data-confirm-message="Delete this category?"
                                >
                                    @csrf
                                    @method ('DELETE')
                                    <button
                                        type="submit"
                                        class="text-sm text-red-500 hover:underline"
                                    >
                                        Delete
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Tags --}}
            <section class="card space-y-5">
                <h2 class="text-primary text-lg font-semibold">Tags</h2>

                <form
                    method="POST"
                    action="{{ route('blogs.tags.store', $blog->id) }}"
                    class="space-y-3"
                >
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-input name="name" label="Name" required data-slug-source />
                        <x-input name="slug" label="Slug" required data-slug-target />
                    </div>
                    <button type="submit" class="btn-primary rounded-md px-4 py-2 text-sm">
                        Add tag
                    </button>
                </form>

                @if (count($tags) === 0)
                    <p class="text-secondary text-sm">No tags yet.</p>
                @else
                    <div class="border-default flex flex-wrap gap-2 border-t pt-4">
                        @foreach ($tags as $tag)
                            <form
                                method="POST"
                                action="{{ route('blogs.tags.destroy', [$blog->id, $tag->id]) }}"
                                data-modal-confirm-form
                                data-confirm-title="Delete tag"
                                data-confirm-message="Delete tag {{ $tag->name }}?"
                                class="inline-flex"
                            >
                                @csrf
                                @method ('DELETE')
                                <span class="badge gap-2">
                                    {{ $tag->name }}
                                    <button
                                        type="submit"
                                        class="text-red-500"
                                        aria-label="Delete tag {{ $tag->name }}"
                                    >
                                        ×
                                    </button>
                                </span>
                            </form>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-slug-source]').forEach((source) => {
            const form = source.closest('form');
            const target = form?.querySelector('[data-slug-target]');

            if (!target) {
                return;
            }

            let slugWasEdited = target.value !== '';

            target.addEventListener('input', () => {
                slugWasEdited = true;
            });

            source.addEventListener('input', () => {
                if (slugWasEdited) {
                    return;
                }

                target.value = source.value
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/đ/gi, 'd')
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            });
        });
    </script>
@endsection
