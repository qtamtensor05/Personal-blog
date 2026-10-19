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
            {!! Illuminate\Support\Js::encode($editorConfig) !!}
        </script>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a onclick="window.history.back(); return false;" href="{{ route('blogs.posts.index', $blog->id) }}" class="btn-ghost" aria-label="{{ __('post.editor_back') }}"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> {{ __('post.editor_back') }}</a>
                <span class="text-secondary text-sm" data-save-status aria-live="polite">{{ __('post.saved') }}</span>
            </div>
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">
                <p class="font-semibold">Không thể xuất bản bài viết. Kiểm tra các mục sau:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('blogs.posts.publish', [$blog->id, $post->id]) }}" data-publish-form>
            @csrf
            <input type="hidden" name="title" data-publish-title value="{{ $editable->title }}" />
            <input type="hidden" name="excerpt" data-publish-excerpt value="{{ $editable->excerpt ?? $post->excerpt }}" />
            <input type="hidden" name="slug" data-publish-slug value="{{ $post->slug }}" />
            <input type="hidden" name="cover_media_id" data-publish-cover value="{{ $post->coverMediaId }}" />
            <input type="hidden" name="og_media_id" value="{{ $post->ogMediaId }}" />
            <input type="hidden" name="seo_title" value="{{ $post->seoTitle }}" />
            <input type="hidden" name="seo_description" value="{{ $post->seoDescription }}" />
            <input type="hidden" name="is_featured" value="{{ $post->isFeatured ? '1' : '0' }}" data-publish-featured />
            @foreach ($post->categoryIds as $categoryId)<input type="hidden" name="category_ids[]" value="{{ $categoryId }}" data-publish-category="{{ $categoryId }}" />@endforeach
            @foreach ($post->tagIds as $tagId)<input type="hidden" name="tag_ids[]" value="{{ $tagId }}" data-publish-tag="{{ $tagId }}" />@endforeach
        <div class="grid gap-6">
            {{-- Writing area --}}
            <div class="card space-y-4">
                <label for="title" class="text-secondary block text-sm font-medium">{{ __('post.title') }}</label>
                <input id="title" value="{{ $editable->title }}" maxlength="160" required class="input text-xl font-semibold" placeholder="{{ __('post.title_placeholder') }}" data-publish-title-source />
                @if ($errors->has('title')) <p class="text-sm text-red-600" role="alert">{{ $errors->first('title') }}</p> @endif
                <input type="hidden" value="{{ $editable->excerpt ?? $post->excerpt }}" data-publish-excerpt-source />

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="post-content-editor" class="text-secondary block text-sm font-medium">{{ __('post.content') }}</label>
                        <button type="button" class="btn-secondary text-sm" data-preview-toggle data-preview-label="{{ __('post.preview') }}" data-edit-label="{{ __('post.preview_edit') }}" aria-pressed="false">{{ __('post.preview') }}</button>
                    </div>
                    <div class="border-default flex flex-wrap gap-1 rounded-t-xl border border-b-0 bg-surface-secondary p-2" role="toolbar" aria-label="{{ __('post.toolbar_label') }}">
                        @foreach ([
                            'bold' => [__('post.toolbar.bold'), 'fa-bold'], 'italic' => [__('post.toolbar.italic'), 'fa-italic'],
                            'h2' => [__('post.toolbar.heading2'), 'fa-heading'], 'h3' => [__('post.toolbar.heading3'), 'fa-heading'],
                            'alignLeft' => [__('post.toolbar.align_left'), 'fa-align-left'], 'alignCenter' => [__('post.toolbar.align_center'), 'fa-align-center'],
                            'alignRight' => [__('post.toolbar.align_right'), 'fa-align-right'], 'alignJustify' => [__('post.toolbar.align_justify'), 'fa-align-justify'],
                            'bulletList' => [__('post.toolbar.bullet_list'), 'fa-list-ul'], 'orderedList' => [__('post.toolbar.numbered_list'), 'fa-list-ol'],
                            'blockquote' => [__('post.toolbar.quote'), 'fa-quote-left'], 'codeBlock' => [__('post.toolbar.code'), 'fa-code'],
                            'link' => [__('post.toolbar.link'), 'fa-link'], 'image' => [__('post.toolbar.image'), 'fa-image']
                        ] as $command => [$label, $icon])
                            <button type="button" class="btn-ghost h-9 w-9 justify-center" data-command="{{ $command }}" aria-label="{{ $label }}" title="{{ $label }}">
                                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                            </button>
                        @endforeach
                        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" data-image-input />
                        <span class="text-secondary flex items-center gap-2 px-2 text-xs" data-image-upload-status
                            data-uploading-label="{{ __('post.image_uploading') }}"
                            data-uploaded-label="{{ __('post.image_uploaded') }}"
                            data-failed-label="{{ __('post.image_upload_failed') }}"
                            data-not-inserted-label="{{ __('post.image_not_inserted') }}"
                            role="status" aria-live="polite"></span>
                    </div>
                    <input type="hidden" name="content" data-post-content value="{{ json_encode($editable->content->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}" />
                    <div id="post-content-editor" data-editor tabindex="0" role="textbox" aria-multiline="true" aria-label="{{ __('post.content') }}"></div>
                    <p class="text-secondary text-xs">{{ __('post.content_help') }}</p>
                    @if ($errors->has('content')) <p class="text-sm text-red-600" role="alert">{{ $errors->first('content') }} Nhập nội dung trong khung soạn thảo phía trên; có thể dùng nút Xem trước để kiểm tra.</p> @endif
                </div>


            </div>

            {{-- Post details --}}
            <section class="card grid gap-5 md:grid-cols-2">
                <h2 class="text-primary text-lg font-semibold">{{ __('post.details') }}</h2>

                <x-input name="slug_editor" :label="__('post.slug')" :value="old('slug', $post->slug)" :error="$errors->first('slug')" required maxlength="160" data-publish-slug-source />

                @if (count($categories) > 0)
                    <fieldset class="space-y-1">
                        <legend class="text-primary text-sm font-medium">{{ __('post.categories') }}</legend>
                        @foreach ($categories as $category)
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    value="{{ $category->id }}"
                                    data-publish-category-source="{{ $category->id }}"
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
                                        value="{{ $tag->id }}"
                                        data-publish-tag-source="{{ $tag->id }}"
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
                    <input type="hidden" id="cover_media_id" value="{{ $post->coverMediaId }}" data-cover-media-id>
                    <div data-cover-preview>@if($coverAsset?->secureUrl)<img src="{{ $coverAsset->secureUrl }}" alt="" class="mb-2 aspect-video w-full rounded-md object-cover">@endif</div>
                    <input id="cover_upload" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="input" data-cover-image-input>
                    <p class="text-secondary text-xs" data-cover-status>{{ __('post.cover_help') }}</p>
                </div>
                <x-input
                    name="seo_title_editor"
                    :label="__('post.seo_title')"
                    :value="$post->seoTitle"
                    maxlength="255"
                />
                <x-textarea
                    name="seo_description_editor"
                    :label="__('post.seo_description')"
                    rows="2"
                    maxlength="500"
                    >{{ $post->seoDescription }}</x-textarea
                >
                <p class="text-secondary text-xs md:col-span-2">{{ __('post.seo_description_help') }}</p>
                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        value="1"
                        data-publish-featured-source
                        @checked ($post->isFeatured)
                    />
                    {{ __('post.featured') }}
                </label>

            </section>
        </div>
            <div class="mt-6 flex justify-end">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-paper-plane mr-2" aria-hidden="true"></i>{{ __('post.publish') }}</button>
            </div>
        </form>

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
        (function () {
            const title = document.getElementById('title');
            const slug = document.querySelector('[data-publish-slug-source]');
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
