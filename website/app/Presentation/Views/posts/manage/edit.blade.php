@extends ('layouts.app')

@section ('title', __('post.edit_title', ['title' => $editable->title]))

@section ('content')
    @php
        $editorConfig = [
            'blogId' => $blog->id,
            'content' => $editable->content->toArray(),
            'mediaUrls' => (object) $mediaUrls,
            'draftUrl' => route('blogs.posts.draft', [$blog->id, $post->id]),
            'mediaUrl' => route('media.store'),
        ];
    @endphp
    <div class="container-custom section-spacing max-w-6xl" data-post-editor>
        <script type="application/json" data-editor-config>
            {{ Illuminate\Support\Js::encode($editorConfig) }}
        </script>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('blogs.posts.index', $blog->id) }}" class="btn-ghost" aria-label="{{ __('post.editor_back') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> {{ __('post.editor_back') }}</a>
                <span class="text-secondary text-sm" data-save-status aria-live="polite">{{ __('post.saved') }}</span>
            </div>
            <form method="POST" action="{{ route('blogs.posts.publish', [$blog->id, $post->id]) }}" data-publish-form>
                @csrf
                <button type="submit" class="btn-primary"><i class="fa-solid fa-paper-plane mr-2" aria-hidden="true"></i>{{ $post->publishedAt === null ? __('post.publish') : __('post.save_changes') }}</button>
            </form>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <div class="grid gap-6">
            {{-- Writing area --}}
            <div class="card space-y-4">
                <label for="title" class="text-secondary block text-sm font-medium">{{ __('post.title') }}</label>
                <input id="title" name="title" value="{{ $editable->title }}" maxlength="160" required class="input text-xl font-semibold" placeholder="{{ __('post.title_placeholder') }}" />
                <input type="hidden" name="excerpt" value="{{ $editable->excerpt ?? $post->excerpt }}" />

                <div>
                    <label for="post-content-fallback" class="text-secondary mb-2 block text-sm font-medium">{{ __('post.content') }}</label>
                    <div class="border-default flex flex-wrap gap-1 rounded-t-xl border border-b-0 bg-surface-secondary p-2" role="toolbar" aria-label="{{ __('post.toolbar_label') }}">
                        @foreach ([
                            'bold' => [__('post.toolbar.bold'), 'fa-bold'], 'italic' => [__('post.toolbar.italic'), 'fa-italic'],
                            'h2' => [__('post.toolbar.heading2'), 'fa-heading'], 'h3' => [__('post.toolbar.heading3'), 'fa-heading'],
                            'bulletList' => [__('post.toolbar.bullet_list'), 'fa-list-ul'], 'orderedList' => [__('post.toolbar.numbered_list'), 'fa-list-ol'],
                            'blockquote' => [__('post.toolbar.quote'), 'fa-quote-left'], 'codeBlock' => [__('post.toolbar.code'), 'fa-code'],
                            'link' => [__('post.toolbar.link'), 'fa-link'], 'image' => [__('post.toolbar.image'), 'fa-image']
                        ] as $command => [$label, $icon])
                            <button type="button" class="btn-ghost h-9 w-9 justify-center" data-command="{{ $command }}" aria-label="{{ $label }}" title="{{ $label }}">
                                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                            </button>
                        @endforeach
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" data-image-input />
                    </div>
                    <textarea id="post-content-fallback" data-editor-fallback class="post-editor-fallback" aria-label="{{ __('post.content') }}" placeholder="{{ __('post.start_writing') }}">{{ $editable->content->plainText(20000) }}</textarea>
                    <div data-editor></div>
                    <p class="text-secondary text-xs">{{ __('post.content_help') }}</p>
                </div>


            </div>

            {{-- Post details --}}
            <form
                method="POST"
                action="{{ route('blogs.posts.meta', [$blog->id, $post->id]) }}"
                class="card grid gap-5 md:grid-cols-2"
            >
                @csrf
                @method ('PUT')
                <h2 class="text-primary text-lg font-semibold">{{ __('post.details') }}</h2>

                <x-input name="slug" :label="__('post.slug')" :value="$post->slug" required maxlength="160" />

                @if (count($categories) > 0)
                    <fieldset class="space-y-1">
                        <legend class="text-primary text-sm font-medium">{{ __('post.categories') }}</legend>
                        @foreach ($categories as $category)
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="category_ids[]"
                                    value="{{ $category->id }}"
                                    @checked (in_array($category->id, $post->categoryIds, true))
                                />
                                {{ $category->name }}
                            </label>
                        @endforeach
                    </fieldset>
                @endif

                @if (count($tags) > 0)
                    <fieldset class="space-y-1">
                        <legend class="text-primary text-sm font-medium">{{ __('post.tags') }}</legend>
                        <div class="flex flex-wrap gap-x-4 gap-y-1">
                            @foreach ($tags as $tag)
                                <label class="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        name="tag_ids[]"
                                        value="{{ $tag->id }}"
                                        @checked (in_array($tag->id, $post->tagIds, true))
                                    />
                                    {{ $tag->name }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <div class="space-y-1">
                    @php($coverAsset = collect($mediaAssets)->firstWhere('id', $post->coverMediaId))
                    <label for="cover_upload" class="text-primary block text-sm font-medium">{{ __('post.cover') }}</label>
                    <input type="hidden" id="cover_media_id" name="cover_media_id" value="{{ $post->coverMediaId }}" data-cover-media-id>
                    <input type="hidden" name="og_media_id" value="{{ $post->ogMediaId }}">
                    <div data-cover-preview>@if($coverAsset?->secureUrl)<img src="{{ $coverAsset->secureUrl }}" alt="" class="mb-2 aspect-video w-full rounded-md object-cover">@endif</div>
                    <input id="cover_upload" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="input" data-cover-image-input>
                    <p class="text-secondary text-xs" data-cover-status>{{ __('post.cover_help') }}</p>
                </div>
                <x-input
                    name="seo_title"
                    :label="__('post.seo_title')"
                    :value="$post->seoTitle"
                    maxlength="255"
                />
                <x-textarea
                    name="seo_description"
                    :label="__('post.seo_description')"
                    rows="2"
                    maxlength="500"
                    >{{ $post->seoDescription }}</x-textarea
                >
                <p class="text-secondary text-xs md:col-span-2">{{ __('post.seo_description_help') }}</p>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_featured" value="0" />
                    <input
                        type="checkbox"
                        name="is_featured"
                        value="1"
                        @checked ($post->isFeatured)
                    />
                    {{ __('post.featured') }}
                </label>

                <button type="submit" class="btn-secondary w-full justify-center">
                    {{ __('post.save_info') }}
                </button>
            </form>
        </div>

        <form
            method="POST"
            action="{{ route('blogs.posts.destroy', [$blog->id, $post->id]) }}"
            class="mt-8"
            data-modal-confirm-form
            data-confirm-title="{{ __('post.delete') }}"
            data-confirm-message="{{ __('post.delete_confirm') }}"
        >
            @csrf
            @method ('DELETE')
            <button type="submit" class="text-sm text-red-500 hover:underline">{{ __('post.delete') }}</button>
        </form>
    </div>
@endsection

@push ('scripts')
    <script>
        (() => {
            const root = document.querySelector('[data-post-editor]');
                    <textarea id="post-content-fallback" data-editor-fallback class="post-editor-fallback" aria-label="{{ __('post.content') }}" placeholder="{{ __('post.start_writing') }}">{{ $editable->content->plainText(20000) }}</textarea>
            if (!root || !fallback) return;
            const config = JSON.parse(root.querySelector('[data-editor-config]').textContent);
            const title = root.querySelector('[name="title"]');
            const status = root.querySelector('[data-save-status]');
            let timer;
            const saveFallback = async () => {
                const paragraphs = fallback.value.split(/\n+/).map((text) => ({ type: 'paragraph', ...(text ? { content: [{ type: 'text', text }] } : {}) }));
                status.textContent = 'Saving.';
                try {
                    const response = await fetch(config.draftUrl, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ title: title.value, content: { type: 'doc', content: paragraphs } }),
                    });
                    if (!response.ok) throw new Error('Could not save the post content.');
                    status.textContent = 'Draft saved';
                } catch (error) { status.textContent = error.message; status.classList.add('text-red-500'); }
            };
            const schedule = () => { status.textContent = 'Unsaved changes'; clearTimeout(timer); timer = setTimeout(saveFallback, 900); };
            fallback.addEventListener('input', schedule);
            title.addEventListener('input', schedule);
        })();
    </script>
    <script>
        (function () {
            const title = document.getElementById('title');
            const slug = document.querySelector('[name=slug]');
            if (!title || !slug) return;
            let edited =
                slug.value !==
                title.value
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
            title.addEventListener('input', () => {
                if (!edited) slug.value = slugify(title.value);
            });
            slug.addEventListener('input', () => {
                edited = true;
            });
        })();
    </script>
    <script>
        document.querySelector('[data-post-visibility]')?.addEventListener('change', async (event) => {
            const value = event.target.value;
            if (value === 'DRAFT') {
                const form = document.createElement('form'); form.method = 'POST'; form.action = {{ Illuminate\Support\Js::from(route('blogs.posts.status', [$blog->id, $post->id])) }};
                form.innerHTML = '<input name="_token" value="{{ csrf_token() }}"><input name="_method" value="PATCH"><input name="status" value="DRAFT">'; document.body.append(form); form.submit(); return;
            }
            if (value === 'PUBLIC') document.querySelector('[data-publish-form]')?.requestSubmit();
        });
    </script>
    @vite ('app/Presentation/Js/post-editor.js')
@endpush
