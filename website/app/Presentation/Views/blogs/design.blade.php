@extends ('layouts.app')

@section ('title', 'Design — ' . $blog->name)

@php
    $draft = $design?->draftData;
    $live = $design?->publishedData;
    $current = $draft ?? $live;
    $hasUnpublished = $draft !== null && ($live === null || json_encode($draft) !== json_encode($live));
    $regionLabels = [
        'body' => 'Trang chủ / Body',
        'header' => 'Header',
        'sidebar' => 'Sidebar',
        'footer' => 'Footer',
    ];
@endphp

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <a
                    href="{{ route('blogs.show', $blog->id) }}"
                    class="text-secondary hover:text-accent-primary text-sm"
                    >← {{ $blog->name }}</a
                >
                <p class="text-accent-primary mt-3 text-sm font-semibold tracking-wider uppercase">Step 2 of 2</p>
                <h1 class="text-hero mt-2">Blog Design</h1>
                <p class="text-secondary">
                    @if ($live)
                        Live design published {{
                            \Carbon\Carbon::parse(
                                $design->publishedAt,
                            )->diffForHumans()
                        }}.
                    @else
                        Not published yet.
                    @endif
                    @if ($hasUnpublished)
                        <x-badge variant="highlight" class="ml-2">Unpublished changes</x-badge>
                    @endif
                </p>
            </div>
            @if ($current !== null)
                <a
                    href="{{ route('public.blog', $blog->slug) }}"
                    target="_blank"
                    rel="noopener"
                    class="btn-secondary"
                >
                    <i class="fa-solid fa-blog"></i>
                    Open the blog
                </a>
            @endif
        </div>

        @if (session('status'))
            <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
        @endif

        <div class="space-y-8">
            {{-- Template is selected once for a new blog, then the layout is locked. --}}
            @if ($current === null)
                <section class="card">
                    <h2 class="text-primary mb-1 text-lg font-semibold">
                        Choose an admin template
                    </h2>
                    <p class="text-secondary mb-4 text-sm">These designs were published by an administrator. Applying one replaces your draft; your live design stays unchanged until you publish.</p>

                    @if (count($templates) === 0)
                        <p class="text-secondary text-sm">No templates are published yet.</p>
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
                                        @checked ($current?->templateId === $template->id)
                                        required
                                    />
                                    @if ($template->publishedData?->layout === 'lifestyle-editorial')
                                        <img
                                            src="{{ asset('images/templates/lifestyle-editorial/hero.svg') }}"
                                            alt=""
                                            class="h-24 w-36 rounded object-cover"
                                        />
                                    @endif
                                    <span class="flex-1">
                                        <span class="text-primary block font-medium">
                                            {{ $template->name }}
                                            <span class="text-secondary text-xs font-normal"
                                                >v{{ $template->versionNumber }}</span
                                            >
                                        </span>
                                        @if ($template->description)
                                            <span
                                                class="text-secondary block text-sm"
                                                >{{ $template->description }}</span
                                            >
                                        @endif
                                        @if ($current?->templateId === $template->id &&
                                            $template->versionNumber > $current->templateVersion)
                                            <span class="text-accent-warm mt-1 block text-xs">
                                                A newer version is available. Re-apply to use it
                                                (resets your customizations).
                                            </span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                            <button type="submit" class="btn-primary rounded-md px-4 py-2">
                                Use selected template
                            </button>
                        </form>
                    @endif
                </section>
            @endif

            @if ($current)
                @php
                    $studioRegions = collect($current->sections)
                        ->filter(fn($section) => $section->visible)
                        ->groupBy('region');
                    $fieldLabels = [
                        'eyebrow' => 'Nhãn nhỏ',
                        'title' => 'Tiêu đề',
                        'description' => 'Mô tả',
                        'archiveTitle' => 'Tiêu đề trang bài viết',
                        'archiveDescription' => 'Mô tả trang bài viết',
                        'mediaId' => 'Media ID',
                        'mediaIds' => 'Media IDs (phân cách dấu phẩy)',
                        'alt' => 'Mô tả ảnh',
                        'ctaLabel' => 'Nhãn CTA',
                        'ctaUrl' => 'Liên kết CTA',
                        'categoryId' => 'Category ID lọc bài mới',
                        'categoryIds' => 'Category IDs ghim',
                        'limit' => 'Số lượng bài mới nhất',
                        'copyright' => 'Copyright',
                    ];
                @endphp
                <section class="card">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-accent-primary text-xs font-semibold tracking-wider uppercase">{{ $regionLabels[$activeRegion] }}</p>
                            <h2 class="text-primary mt-1 text-lg font-semibold">
                                Chỉnh nội dung {{ $regionLabels[$activeRegion] }}
                            </h2>
                            <p class="text-secondary mt-1 text-sm">Chỉ các slot thuộc vùng này được hiển thị. Bố cục và vị trí đã được template khóa.</p>
                        </div>
                        <a
                            href="{{ route('blogs.show', $blog->id) }}"
                            class="btn-ghost px-3 py-2 text-sm"
                            >← Chọn vùng khác</a
                        >
                    </div>
                    <form
                        method="POST"
                        action="{{ route('blogs.design.content', $blog->id) }}"
                        class="space-y-5"
                    >
                        @csrf
                        @method ('PUT')
                        <input type="hidden" name="region" value="{{ $activeRegion }}" />
                        @foreach ([
                                'body' => 'Trang chủ',
                                'header' => 'Header dùng chung',
                                'sidebar' => 'Sidebar',
                                'footer' => 'Footer dùng chung'
                            ]
                            as $region => $regionLabel)
                            @if ($region === $activeRegion && $studioRegions->get($region, collect())->isNotEmpty())
                                <details class="border-default rounded-md border p-4" open>
                                    <summary class="text-primary cursor-pointer font-semibold">
                                        {{ $regionLabel }}
                                    </summary>
                                    <div class="mt-4 space-y-5">
                                        @foreach ($studioRegions->get($region) as $section)
                                            @php
                                                $values = array_replace($section->props, $current->slotContent[$section->key] ?? []);
                                                $fields = \App\Domain\Templates\TemplateSlotCatalog::fields($section->section);
                                            @endphp
                                            @if ($fields !== [])
                                                <fieldset
                                                    id="slot-{{ $section->key }}"
                                                    class="scroll-mt-6 border-l-2 pl-4"
                                                    style="border-color: var(--d-accent)"
                                                >
                                                    <legend class="text-primary font-medium">
                                                        {{ $section->label ?? $section->key }}
                                                    </legend>
                                                    <div class="mt-3 grid gap-3">
                                                        @foreach ($fields as $field)
                                                            @php
                                                                $value = $values[$field] ?? '';
                                                                $inputName = 'slot_content[' . $section->key . '][' . $field . ']';
                                                            @endphp
                                                            @if (in_array($field, ['description', 'archiveDescription'], true))
                                                                <x-textarea
                                                                    :name="$inputName"
                                                                    :label="$fieldLabels[$field]"
                                                                    rows="3"
                                                                    >{{ $value }}</x-textarea
                                                                >
                                                            @elseif ($field === 'mediaId')
                                                                <div class="space-y-1">
                                                                    <label
                                                                        class="text-primary block text-sm font-medium"
                                                                        >{{ $fieldLabels[$field] }}</label
                                                                    >
                                                                    <select
                                                                        class="input"
                                                                        name="slot_content[{{ $section->key }}][{{ $field }}]"
                                                                    >
                                                                        <option value="">
                                                                            Chọn từ Media của blog
                                                                        </option>
                                                                        @foreach ($mediaAssets as $asset)
                                                                            <option
                                                                                value="{{ $asset->id }}"
                                                                                @selected ((string) $value === (string) $asset->id)
                                                                            >
                                                                                #{{ $asset->id }} · {{
                                                                                    $asset->resourceType === 'video'
                                                                                        ? 'Video'
                                                                                        : 'Ảnh'
                                                                                }} · {{ $asset->altText ?: $asset->originalName }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <a
                                                                        href="{{ route('blogs.media.index', $blog->id) }}"
                                                                        class="text-accent-primary inline-block text-xs hover:underline"
                                                                        >Thêm hình ảnh hoặc video
                                                                        vào thư viện →</a
                                                                    >
                                                                </div>
                                                            @elseif (in_array($field, ['categoryId', 'limit'], true))
                                                                <x-input
                                                                    type="number"
                                                                    min="1"
                                                                    :name="$inputName"
                                                                    :label="$fieldLabels[$field]"
                                                                    :value="$value"
                                                                />
                                                            @elseif (in_array($field, ['mediaIds', 'categoryIds'], true))
                                                                <x-input
                                                                    :name="$inputName"
                                                                    :label="$fieldLabels[$field]"
                                                                    :value="is_array($value) ? implode(',', $value) : $value"
                                                                    hint="Nhập các ID cách nhau bằng dấu phẩy."
                                                                />
                                                            @elseif ($field === 'ctaUrl')
                                                                <x-input
                                                                    :name="$inputName"
                                                                    :label="$fieldLabels[$field]"
                                                                    :value="$value"
                                                                    hint="Chấp nhận https://, đường dẫn /..., #section hoặc mailto:."
                                                                />
                                                            @else
                                                                <x-input
                                                                    :name="$inputName"
                                                                    :label="$fieldLabels[$field]"
                                                                    :value="$value"
                                                                />
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            @endif
                                        @endforeach
                                    </div>
                                </details>
                            @elseif ($region === $activeRegion)
                                <x-empty-state message="Template chưa có {{ $regionLabel }}">
                                    Admin cần thêm component vào vùng này trong template trước khi
                                    blogger có thể nhập nội dung.
                                </x-empty-state>
                            @endif
                        @endforeach
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="btn-secondary px-4 py-2">
                                Lưu nháp {{ $regionLabels[$activeRegion] }}
                            </button>
                            <button
                                type="submit"
                                name="publish"
                                value="1"
                                class="btn-primary px-4 py-2"
                            >
                                Lưu và xuất bản {{ $regionLabels[$activeRegion] }}
                            </button>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>

@endsection
