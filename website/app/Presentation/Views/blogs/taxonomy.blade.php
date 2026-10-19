@extends ('layouts.app')

@section ('title', __('taxonomy.title') . ' - ' . $blog->name)

@section ('content')
    <div class="container-custom section-spacing max-w-5xl">
        <div class="mb-8">
            <a
                onclick="window.history.back(); return false;"
                href="{{ route('blogs.show', $blog->id) }}"
                class="text-secondary hover:text-accent-primary text-sm"
                >← {{ $blog->name }}</a
            >
            <h1 class="text-hero mt-2">{{ __('taxonomy.title') }}</h1>
            <p class="text-secondary">{{ __('taxonomy.help') }}</p>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->has('general'))
            <x-alert type="error" dismissible class="mb-6">
                {{ $errors->first('general') }} Kiểm tra slug bị trùng hoặc quan hệ chuyên mục cha rồi thử lại.
            </x-alert>
        @endif

        <div class="grid gap-8 lg:grid-cols-2">
            {{-- {{ __('taxonomy.categories') }} --}}
            <section class="card space-y-5">
                <h2 class="text-primary text-lg font-semibold">{{ __('taxonomy.categories') }}</h2>

                <form
                    method="POST"
                    action="{{ route('blogs.categories.store', $blog->id) }}"
                    class="space-y-3"
                >
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-input name="name" :label="__('taxonomy.name')" :error="$errors->first('name')" required data-slug-source />
                        <x-input
                            name="slug"
                             :label="__('taxonomy.slug')"
                            required
                             :hint="__('taxonomy.slug_hint')"
                            :error="$errors->first('slug')"
                            data-slug-target
                        />
                    </div>
                    <div class="space-y-1">
                        <label for="parent_id" class="text-primary block text-sm font-medium"
                            >{{ __('taxonomy.parent') }}</label
                        >
                        <select name="parent_id" id="parent_id" class="input">
                            <option value="">{{ __('taxonomy.none') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @if ($errors->has('parent_id')) <p class="text-sm text-red-500">{{ $errors->first('parent_id') }} Chọn chuyên mục cha thuộc blog này.</p> @endif
                    </div>
                    <button type="submit" class="btn-primary rounded-md px-4 py-2 text-sm">
                        {{ __('taxonomy.add_category') }}
                    </button>
                </form>

                @if (count($categories) === 0)
                    <p class="text-secondary text-sm">{{ __('taxonomy.no_categories') }}</p>
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
                                        <x-badge class="ml-1">{{ __('taxonomy.inactive') }}</x-badge>
                                    @endif
                                </div>
                                <form
                                    method="POST"
                                    action="{{ route('blogs.categories.destroy', [$blog->id, $category->id]) }}"
                                    data-modal-confirm-form
                                    data-confirm-title="{{ __('taxonomy.delete_category') }}"
                                    data-confirm-message="{{ __('taxonomy.confirm_delete_category') }}"
                                >
                                    @csrf
                                    @method ('DELETE')
                                    <button
                                        type="submit"
                                        class="text-sm text-red-500 hover:underline"
                                    >
                                        {{ __('ui.remove') }}
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- {{ __('taxonomy.tags') }} --}}
            <section class="card space-y-5">
                <h2 class="text-primary text-lg font-semibold">{{ __('taxonomy.tags') }}</h2>

                <form
                    method="POST"
                    action="{{ route('blogs.tags.store', $blog->id) }}"
                    class="space-y-3"
                >
                    @csrf
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-input name="name" :label="__('taxonomy.name')" :error="$errors->first('name')" required data-slug-source />
                        <x-input name="slug" :label="__('taxonomy.slug')" :hint="__('taxonomy.slug_hint')" :error="$errors->first('slug')" required data-slug-target />
                    </div>
                    <button type="submit" class="btn-primary rounded-md px-4 py-2 text-sm">
                        {{ __('taxonomy.add_tag') }}
                    </button>
                </form>

                @if (count($tags) === 0)
                    <p class="text-secondary text-sm">{{ __('taxonomy.no_tags') }}</p>
                @else
                    <div class="border-default flex flex-wrap gap-2 border-t pt-4">
                        @foreach ($tags as $tag)
                            <form
                                method="POST"
                                action="{{ route('blogs.tags.destroy', [$blog->id, $tag->id]) }}"
                                data-modal-confirm-form
                                data-confirm-title="{{ __('taxonomy.delete_tag') }}"
                                data-confirm-message="{{ __('taxonomy.confirm_delete_tag') }}"
                                class="inline-flex"
                            >
                                @csrf
                                @method ('DELETE')
                                <span class="badge gap-2">
                                    {{ $tag->name }}
                                    <button
                                        type="submit"
                                        class="text-red-500"
                                        aria-label="{{ __('taxonomy.delete_tag') }}"
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
