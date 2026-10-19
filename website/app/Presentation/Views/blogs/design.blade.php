@extends ('layouts.app')

@section ('title', 'Nội dung blog - ' . $blog->name)

@php
    $draft = $design?->draftData;
    $live = $design?->publishedData;
    $current = $draft ?? $live;
    $hasUnpublished = $draft !== null && ($live === null || json_encode($draft) !== json_encode($live));
    $regionLabels = [
        'body' => __('template.home'),
        'header' => __('template.header'),
        'sidebar' => __('template.sidebar'),
        'footer' => __('template.footer'),
    ];
@endphp

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <a
                    onclick="
                        window.history.back();
                        return false;
                    "
                    href="{{ route('blogs.show', $blog->id) }}"
                    class="text-secondary hover:text-accent-primary text-sm"
                    >← {{ $blog->name }}</a
                >
                <p class="text-accent-primary mt-3 text-sm font-semibold tracking-wider uppercase">
                    {{ __('template.studio') }}
                </p>
                <h1 class="text-hero mt-2">
                    {{
                        $current
                            ? __('template.edit_content', ['region' => $regionLabels[$activeRegion]])
                            : __('template.choose_template')
                    }}
                </h1>
                @if ($hasUnpublished)
                    <x-badge variant="highlight" class="mt-2">
                        {{ __('template.unpublished_changes') }}
                    </x-badge>
                @endif
            </div>
            @if ($current)
                <a
                    href="{{ route('public.blog', $blog->slug) }}"
                    target="_blank"
                    rel="noopener"
                    class="btn-secondary"
                >
                    {{ __('template.open_blog') }} ↗
                </a>
            @endif
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        @if ($current === null)
            <section class="card">
                <h2 class="text-primary text-lg font-semibold">
                    {{
                        __(
                            'template.choose_admin_template',
                        )
                    }}
                </h2>
                <p class="text-secondary mt-1 mb-4 text-sm">{{
                    __(
                        'template.choose_template_help',
                    )
                }}</p>
                @if (count($templates) === 0)
                    <p class="text-secondary text-sm">{{ __('template.no_template') }}</p>
                @else
                    <form
                        method="POST"
                        action="{{ route('blogs.design.template', $blog->id) }}"
                        class="space-y-3"
                    >
                        @csrf
                        @foreach ($templates as $template)
                            <label
                                class="border-default hover:border-accent-primary flex cursor-pointer items-start gap-3 rounded-md border p-3"
                            >
                                <input
                                    type="radio"
                                    name="template_id"
                                    value="{{ $template->id }}"
                                    class="mt-1"
                                    required
                                />
                                @if ($template->publishedData?->layout === 'lifestyle-editorial')
                                    <img
                                        src="{{ asset('images/templates/lifestyle-editorial/hero.svg') }}"
                                        alt=""
                                        class="h-24 w-36 rounded object-cover"
                                    />
                                @endif
                                <span
                                    ><strong
                                        class="text-primary block"
                                        >{{ $template->name }}</strong
                                    ><span
                                        class="text-secondary text-sm"
                                        >{{ $template->description }}</span
                                    ></span
                                >
                            </label>
                        @endforeach
                        <button type="submit" class="btn-primary">
                            {{ __('template.use_selected') }}
                        </button>
                    </form>
                @endif
            </section>
        @else
            @php
                $sections = collect($current->sections)->filter(
                    fn($section) => $section->visible && $section->region === $activeRegion,
                );
                $fieldLabels = [
                    'eyebrow' => __('template.field.eyebrow'),
                    'title' => __('template.field.title'),
                    'description' => __('template.field.description'),
                    'archiveTitle' => __('template.field.archive_title'),
                    'archiveDescription' => __('template.field.archive_description'),
                    'mediaId' => __('template.media'),
                    'mediaIds' => __('template.gallery_media'),
                    'alt' => __('template.field.alt'),
                    'ctaLabel' => __('template.field.cta_label'),
                    'ctaUrl' => __('template.field.cta_url'),
                    'email' => 'Email',
                    'phone' => __('template.field.phone'),
                    'facebookUrl' => 'Facebook',
                    'youtubeUrl' => 'YouTube',
                    'tiktokUrl' => 'TikTok',
                    'zaloUrl' => 'Zalo',
                    'categoryId' => 'Category ID',
                    'categoryIds' => 'Category IDs',
                    'limit' => __('template.field.post_limit'),
                    'copyright' => __('template.field.copyright'),
                ];
                $sectionLabels = [
                    'hero' => __('template.hero'),
                    'recent_posts' => __('template.post_section'),
                    'about' => __('template.about'),
                    'gallery' => __('template.gallery'),
                    'cta' => __('template.contact'),
                    'header' => __('template.header'),
                    'navigation' => __('template.header'),
                    'profile' => __('template.profile'),
                    'sidebar_posts' => __('template.posts'),
                    'newsletter' => __('template.newsletter'),
                    'footer' => __('template.footer'),
                    'image' => __('template.gallery'),
                    'image_text' => __('template.about'),
                    'categories' => __('template.categories'),
                    'featured_post' => __('template.posts'),
                ];
            @endphp
            <section class="card">
                <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-accent-primary text-xs font-semibold tracking-wider uppercase">{{ $regionLabels[$activeRegion] }}</p>
                        <p class="text-secondary mt-1 text-sm">{{ __('template.component_help') }}</p>
                    </div>
                    <a
                        href="{{ route('blogs.show', $blog->id) }}"
                        class="btn-ghost px-3 py-2 text-sm"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        {{ __('template.choose_other_region') }}
                    </a>
                </div>
                <form
                    method="POST"
                    action="{{ route('blogs.design.content', $blog->id) }}"
                    class="space-y-5"
                >
                    @csrf
                    @method ('PUT')
                    <input type="hidden" name="region" value="{{ $activeRegion }}" />
                    @forelse ($sections as $section)
                        @php
                            $values = array_replace($section->props, $current->slotContent[$section->key] ?? []);
                            $fields = \App\Domain\Templates\TemplateSlotCatalog::fields($section->section);
                            $sectionTitle = $sectionLabels[$section->section] ?? ($section->label ?? $section->key);
                        @endphp
                        @if ($fields !== [])
                            <details
                                id="slot-{{ $section->key }}"
                                class="group border-default bg-surface-secondary scroll-mt-6 rounded-md border"
                            >
                                <summary
                                    class="text-primary flex cursor-pointer list-none items-center justify-between gap-4 p-4 font-semibold"
                                >
                                    <span>{{ $sectionTitle }}</span
                                    ><i
                                        class="fa-solid fa-chevron-down text-accent-primary text-sm transition group-open:rotate-180"
                                        aria-hidden="true"
                                    ></i>
                                </summary>
                                <fieldset class="border-default grid gap-3 border-t p-4">
                                    @foreach ($fields as $field)
                                        @php
                                            $value = $values[$field] ?? '';
                                            $inputName = 'slot_content[' . $section->key . '][' . $field . ']';
                                        @endphp
                                        @if ($field === 'slides' && $section->section === 'hero')
                                            @php ($slides = is_array($value) && $value !== [] ? $value : [ [ 'eyebrow' => $values['eyebrow'] ?? '', 'title' => $values['title'] ?? '', 'description' => $values['description'] ?? '', 'mediaId' => $values['mediaId'] ?? '', 'ctaLabel' => $values['ctaLabel'] ?? '', 'ctaUrl' => $values['ctaUrl'] ?? '' ] ])
                                            <div
                                                class="space-y-4"
                                                data-hero-slides
                                                data-slot-key="{{ $section->key }}"
                                            >
                                                <div
                                                    class="flex flex-wrap items-center justify-between gap-2"
                                                >
                                                    <h3 class="text-primary font-semibold">
                                                        {{ __('template.hero_slides') }}
                                                    </h3>
                                                    <button
                                                        type="button"
                                                        class="btn-secondary"
                                                        data-add-slide
                                                    >
                                                        <i
                                                            class="fa-solid fa-plus mr-2"
                                                            aria-hidden="true"
                                                        ></i
                                                        >{{ __('template.add_slide') }}
                                                    </button>
                                                </div>
                                                <div class="space-y-4" data-slide-list>
                                                    @foreach ($slides as $slideIndex => $slide)
                                                        <details
                                                            class="hero-slide-card border-default rounded-md border"
                                                            data-slide
                                                            @if ($loop->first) open @endif
                                                        >
                                                            <summary class="hero-slide-summary">
                                                                <span data-slide-title
                                                                    >{{ __('template.slide') }} {{ $loop->iteration }}{{
                                                                        !empty($slide['title'])
                                                                            ? ' · ' . $slide['title']
                                                                            : ''
                                                                    }}</span
                                                                ><i
                                                                    class="fa-solid fa-chevron-down"
                                                                    aria-hidden="true"
                                                                ></i>
                                                            </summary>
                                                            <div class="p-4">
                                                                <div class="mb-3 flex justify-end">
                                                                    <button
                                                                        type="button"
                                                                        class="text-sm text-red-500"
                                                                        data-remove-slide
                                                                        aria-label="{{ __('template.remove_slide') }}"
                                                                    >
                                                                        <i
                                                                            class="fa-solid fa-trash"
                                                                            aria-hidden="true"
                                                                        ></i>
                                                                        {{ __('template.remove_slide') }}
                                                                    </button>
                                                                </div>
                                                                <div
                                                                    class="grid gap-3 sm:grid-cols-2"
                                                                >
                                                                    <x-input
                                                                        :name="'slide-' . $slideIndex . '-eyebrow'"
                                                                        data-slide-field="eyebrow"
                                                                        :label="__('template.field.eyebrow')"
                                                                        :value="$slide['eyebrow'] ?? ''"
                                                                    />
                                                                    <x-input
                                                                        :name="'slide-' . $slideIndex . '-title'"
                                                                        data-slide-field="title"
                                                                        :label="__('template.field.title')"
                                                                        :value="$slide['title'] ?? ''"
                                                                        required
                                                                    />
                                                                    <div class="sm:col-span-2">
                                                                        <x-textarea
                                                                            :name="'slide-' . $slideIndex . '-description'"
                                                                            data-slide-field="description"
                                                                            :label="__('template.field.description')"
                                                                            rows="3"
                                                                            >{{ $slide['description'] ?? '' }}</x-textarea
                                                                        >
                                                                    </div>
                                                                    <x-input
                                                                        :name="'slide-' . $slideIndex . '-ctaLabel'"
                                                                        data-slide-field="ctaLabel"
                                                                        :label="__('template.field.cta_label')"
                                                                        :value="$slide['ctaLabel'] ?? ''"
                                                                    />
                                                                    <x-input
                                                                        :name="'slide-' . $slideIndex . '-ctaUrl'"
                                                                        data-slide-field="ctaUrl"
                                                                        :label="__('template.field.cta_url')"
                                                                        :value="$slide['ctaUrl'] ?? ''"
                                                                    />
                                                                </div>
                                                                @php ($slideAsset = collect($mediaAssets)->firstWhere( 'id', (int) ($slide['mediaId'] ?? 0) ))
                                                                <div
                                                                    class="mt-3 space-y-2"
                                                                    data-direct-media="single"
                                                                >
                                                                    <label
                                                                        class="text-primary block text-sm font-medium"
                                                                        >{{ __('template.media') }}</label
                                                                    ><input
                                                                        type="hidden"
                                                                        value="{{ $slide['mediaId'] ?? '' }}"
                                                                        data-slide-field="mediaId"
                                                                        data-media-value
                                                                    />
                                                                    <div data-media-preview>
                                                                        @if ($slideAsset?->secureUrl)
                                                                            <img
                                                                                src="{{ $slideAsset->secureUrl }}"
                                                                                alt=""
                                                                                class="max-h-48 rounded-md object-cover"
                                                                            />
                                                                        @endif
                                                                    </div>
                                                                    <input
                                                                        type="file"
                                                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                                                        class="input"
                                                                        data-media-file
                                                                    />
                                                                    <p class="text-secondary text-xs" data-media-status>{{ __('template.upload_replace') }}</p>
                                                                </div>
                                                            </div>
                                                        </details>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @elseif (in_array($field, ['description', 'archiveDescription'], true))
                                            <x-textarea
                                                :name="$inputName"
                                                :label="$fieldLabels[$field] ?? $field"
                                                rows="3"
                                                >{{ $value }}</x-textarea
                                            >
                                        @elseif ($field === 'mediaId')
                                            @php ($selectedAsset = collect($mediaAssets)->firstWhere('id', (int) $value))
                                            <div class="space-y-2" data-direct-media="single">
                                                <label
                                                    class="text-primary block text-sm font-medium"
                                                    >{{ __('template.media') }}</label
                                                >
                                                <input
                                                    type="hidden"
                                                    name="{{ $inputName }}"
                                                    value="{{ $value }}"
                                                    data-media-value
                                                />
                                                <div data-media-preview>
                                                    @if ($selectedAsset?->secureUrl)
                                                        @if ($selectedAsset->resourceType === 'video')
                                                            <video
                                                                src="{{ $selectedAsset->secureUrl }}"
                                                                controls
                                                                class="max-h-56 rounded-md"
                                                            ></video>
                                                        @else
                                                            <img
                                                                src="{{ $selectedAsset->secureUrl }}"
                                                                alt=""
                                                                class="max-h-56 rounded-md object-cover"
                                                            />
                                                        @endif
                                                    @endif
                                                </div>
                                                <input
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                                                    class="input"
                                                    data-media-file
                                                />
                                                <p class="text-secondary text-xs" data-media-status>{{ __('template.upload_replace') }}</p>
                                            </div>
                                        @elseif (in_array($field, ['categoryId', 'limit'], true))
                                            <x-input
                                                type="number"
                                                min="1"
                                                :name="$inputName"
                                                :label="$fieldLabels[$field] ?? $field"
                                                :value="$value"
                                            />
                                        @elseif ($field === 'mediaIds')
                                            <div class="space-y-2" data-direct-media="multiple">
                                                <label
                                                    class="text-primary block text-sm font-medium"
                                                    >{{ __('template.gallery_media') }}</label
                                                >
                                                <input
                                                    type="hidden"
                                                    name="{{ $inputName }}"
                                                    value="{{ is_array($value) ? implode(',', $value) : $value }}"
                                                    data-media-value
                                                />
                                                <div
                                                    class="grid grid-cols-2 gap-2 sm:grid-cols-3"
                                                    data-media-preview
                                                >
                                                    @foreach ((array) $value as $mediaId)
                                                        @php ($asset = collect($mediaAssets)->firstWhere('id', (int) $mediaId))
                                                        @if ($asset?->secureUrl)
                                                            <div
                                                                class="relative"
                                                                data-uploaded-media="{{ $asset->id }}"
                                                            >
                                                                @if ($asset->resourceType === 'video')
                                                                    <video
                                                                        src="{{ $asset->secureUrl }}"
                                                                        controls
                                                                        class="aspect-video w-full rounded object-cover"
                                                                    ></video>
                                                                @else
                                                                    <img
                                                                        src="{{ $asset->secureUrl }}"
                                                                        alt=""
                                                                        class="aspect-video w-full rounded object-cover"
                                                                    />
                                                                @endif
                                                                <button
                                                                    type="button"
                                                                    class="absolute top-1 right-1 rounded bg-black/70 px-2 py-1 text-xs text-white"
                                                                    data-remove-media
                                                                >
                                                                    ×
                                                                </button>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                                <input
                                                    type="file"
                                                    multiple
                                                    accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                                                    class="input"
                                                    data-media-file
                                                />
                                                <p class="text-secondary text-xs" data-media-status>{{ __('template.gallery_upload_help') }}</p>
                                            </div>
                                        @elseif ($field === 'categoryIds')
                                            <x-input
                                                :name="$inputName"
                                                :label="$fieldLabels[$field] ?? $field"
                                                :value="is_array($value) ? implode(',', $value) : $value"
                                                hint="Nhập các ID, phân cách bằng dấu phẩy."
                                            />
                                        @else
                                            <x-input
                                                :name="$inputName"
                                                :label="$fieldLabels[$field] ?? $field"
                                                :value="$value"
                                            />
                                        @endif
                                    @endforeach
                                </fieldset>
                            </details>
                        @endif
                    @empty
                        <x-empty-state :message="__('template.empty_region')">{{
                            __(
                                'template.admin_config_required',
                            )
                        }}</x-empty-state>
                    @endforelse
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="border-secondary rounded-md border px-4 py-2">
                            {{
                                __('template.save_draft', [
                                    'region' => $regionLabels[$activeRegion],
                                ])
                            }}
                        </button>
                        <button
                            type="submit"
                            name="publish"
                            value="1"
                            class="btn-primary rounded-md px-4 py-2"
                        >
                            {{
                                __('template.save_publish', [
                                    'region' => $regionLabels[$activeRegion],
                                ])
                            }}
                        </button>
                    </div>
                </form>
            </section>
        @endif
    </div>
@endsection

@push ('scripts')
    <script>
        (() => {
            const target = window.location.hash ? document.querySelector(window.location.hash) : null;
            if (target?.tagName === 'DETAILS') target.open = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const uploadUrl = {{
                Illuminate\Support\Js::from(
                    route('media.store'),
                )
            }};
            const blogId = {{
                Illuminate\Support\Js::from(
                    $blog->id,
                )
            }};
            const uploadText = {{
                Illuminate\Support\Js::from(
                    __('template.uploading', ['file' => '__FILE__']),
                )
            }};
            const uploadedText = {{
                Illuminate\Support\Js::from(
                    __('template.uploaded_save'),
                )
            }};

            async function upload(file, status) {
                const body = new FormData();
                body.append('file', file);
                body.append('scope', 'BLOG');
                body.append('blog_id', blogId);
                status.textContent = uploadText.replace('__FILE__', file.name);
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body,
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok)
                    throw new Error(
                        result.message ??
                            Object.values(result.errors ?? {})[0]?.[0] ??
                            'Không thể tải Media.',
                    );
                return result.data;
            }

            document.querySelectorAll('[data-direct-media]').forEach((root) => {
                const input = root.querySelector('[data-media-file]');
                const hidden = root.querySelector('[data-media-value]');
                const preview = root.querySelector('[data-media-preview]');
                const status = root.querySelector('[data-media-status]');
                const syncMultiple = () => {
                    hidden.value = [...preview.querySelectorAll('[data-uploaded-media]')]
                        .map((item) => item.dataset.uploadedMedia)
                        .join(',');
                };
                preview.addEventListener('click', (event) => {
                    if (event.target.matches('[data-remove-media]')) {
                        event.target.closest('[data-uploaded-media]').remove();
                        syncMultiple();
                    }
                });
                input.addEventListener('change', async () => {
                    try {
                        const files = [...input.files];
                        if (!files.length) return;
                        if (root.dataset.directMedia === 'single') preview.innerHTML = '';
                        for (const file of files) {
                            const asset = await upload(file, status);
                            const media =
                                asset.resource_type === 'video'
                                    ? `<video src="${asset.url}" controls class="max-h-56 rounded-md"></video>`
                                    : `<img src="${asset.url}" alt="" class="max-h-56 rounded-md object-cover">`;
                            if (root.dataset.directMedia === 'single') {
                                hidden.value = asset.id;
                                preview.innerHTML = media;
                            } else
                                preview.insertAdjacentHTML(
                                    'beforeend',
                                    `<div class="relative" data-uploaded-media="${asset.id}">${media}<button type="button" class="absolute right-1 top-1 rounded bg-black/70 px-2 py-1 text-xs text-white" data-remove-media>×</button></div>`,
                                );
                        }
                        if (root.dataset.directMedia === 'multiple') syncMultiple();
                        status.textContent = uploadedText;
                    } catch (error) {
                        status.textContent = error.message;
                        status.classList.add('text-red-500');
                    }
                    input.value = '';
                });
            });

            document.querySelectorAll('[data-hero-slides]').forEach((editor) => {
                const list = editor.querySelector('[data-slide-list]');
                const reindex = () =>
                    [...list.querySelectorAll('[data-slide]')].forEach((slide, index) => {
                        const title = slide.querySelector('[data-slide-field="title"]')?.value.trim();
                        slide.querySelector('[data-slide-title]').textContent =
                            `{{ __('template.slide') }} ${index + 1}${title ? ` · ${title}` : ''}`;
                        slide.querySelectorAll('[data-slide-field]').forEach((field) => {
                            field.name = `slot_content[${editor.dataset.slotKey}][slides][${index}][${field.dataset.slideField}]`;
                        });
                    });
                editor.addEventListener('input', (event) => {
                    if (event.target.matches('[data-slide-field="title"]')) reindex();
                });
                editor.addEventListener('click', (event) => {
                    if (event.target.closest('[data-add-slide]')) {
                        if (list.children.length >= 10) return;
                        const clone = list.firstElementChild.cloneNode(true);
                        list.querySelectorAll('[data-slide]').forEach((slide) => {
                            slide.open = false;
                        });
                        clone.open = true;
                        clone.classList.add('hero-slide-enter');
                        clone.querySelectorAll('[data-slide-field]').forEach((field) => {
                            field.value = '';
                        });
                        clone.querySelector('[data-media-preview]').innerHTML = '';
                        clone.querySelector('[data-media-status]').textContent =
                            '{{ __('template.upload_replace') }}';
                        const mediaRoot = clone.querySelector('[data-direct-media]');
                        mediaRoot
                            .querySelector('[data-media-file]')
                            .addEventListener('change', async (uploadEvent) => {
                                const file = uploadEvent.target.files[0];
                                uploadEvent.target.value = '';
                                if (!file) return;
                                const status = mediaRoot.querySelector('[data-media-status]');
                                try {
                                    const asset = await upload(file, status);
                                    mediaRoot.querySelector('[data-media-value]').value = asset.id;
                                    mediaRoot.querySelector('[data-media-preview]').innerHTML =
                                        `<img src="${asset.url}" alt="" class="max-h-48 rounded-md object-cover">`;
                                    status.textContent = uploadedText;
                                } catch (error) {
                                    status.textContent = error.message;
                                    status.classList.add('text-red-500');
                                }
                            });
                        list.append(clone);
                        reindex();
                        window.setTimeout(() => clone.classList.remove('hero-slide-enter'), 350);
                    }
                    if (event.target.closest('[data-remove-slide]')) {
                        const slide = event.target.closest('[data-slide]');
                        if (list.children.length === 1) {
                            slide.classList.remove('hero-slide-shake');
                            requestAnimationFrame(() => slide.classList.add('hero-slide-shake'));
                            return;
                        }
                        slide.classList.add('hero-slide-leave');
                        window.setTimeout(() => {
                            slide.remove();
                            reindex();
                        }, 220);
                    }
                });
                reindex();
            });
        })();
    </script>
@endpush
