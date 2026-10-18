@extends('layouts.app')

@section('title', 'Design — '.$blog->name)

@php
    $draft = $design?->draftData;
    $live = $design?->publishedData;
    $current = $draft ?? $live;
    $hasUnpublished = $draft !== null && ($live === null || json_encode($draft) !== json_encode($live));
    $regionLabels = ['body' => 'Trang chủ / Body', 'header' => 'Header', 'sidebar' => 'Sidebar', 'footer' => 'Footer'];
@endphp

@section('content')
<div class="container-custom section-spacing">
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('blogs.show', $blog->id) }}" class="text-sm text-secondary hover:text-accent-primary">← {{ $blog->name }}</a>
            <p class="mt-3 text-sm font-semibold uppercase tracking-wider text-accent-primary">Step 2 of 2</p>
            <h1 class="text-hero mt-2">Blog Design</h1>
            <p class="text-secondary">
                @if($live)
                    Live design published {{ \Carbon\Carbon::parse($design->publishedAt)->diffForHumans() }}.
                @else
                    Not published yet.
                @endif
                @if($hasUnpublished)
                    <x-badge variant="highlight" class="ml-2">Unpublished changes</x-badge>
                @endif
            </p>
        </div>

        <a href="{{ route('public.blog', $blog->slug) }}" target="_blank" rel="noopener" class="btn-secondary">
            Mở trang blog ↗
        </a>

    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="space-y-8">
            {{-- Template is selected once for a new blog, then the layout is locked. --}}
            @if($current === null)
            <section class="card">
                <h2 class="text-lg font-semibold text-primary mb-1">Choose an admin template</h2>
                <p class="text-sm text-secondary mb-4">
                    These designs were published by an administrator. Applying one replaces your draft; your live design stays unchanged until you publish.
                </p>

                @if(count($templates) === 0)
                    <p class="text-sm text-secondary">No templates are published yet.</p>
                @else
                    <form method="POST" action="{{ route('blogs.design.template', $blog->id) }}" class="space-y-3">
                        @csrf
                        @foreach($templates as $template)
                            <label class="flex items-start gap-3 p-3 rounded-md border border-default cursor-pointer hover:border-accent-primary">
                                <input type="radio" name="template_id" value="{{ $template->id }}" class="mt-1"
                                       @checked($current?->templateId === $template->id) required>
                                @if($template->publishedData?->layout === 'lifestyle-editorial')
                                    <img src="{{ asset('images/templates/lifestyle-editorial/hero.svg') }}" alt="" class="h-24 w-36 rounded object-cover">
                                @endif
                                <span class="flex-1">
                                    <span class="block font-medium text-primary">
                                        {{ $template->name }}
                                        <span class="text-xs text-secondary font-normal">v{{ $template->versionNumber }}</span>
                                    </span>
                                    @if($template->description)
                                        <span class="block text-sm text-secondary">{{ $template->description }}</span>
                                    @endif
                                    @if($current?->templateId === $template->id && $template->versionNumber > $current->templateVersion)
                                        <span class="block text-xs text-accent-warm mt-1">
                                            A newer version is available. Re-apply to use it (resets your customizations).
                                        </span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                        <button type="submit" class="btn-primary">Use selected template</button>
                    </form>
                @endif
            </section>
            @endif

            @if($current)
                @php
                    $studioRegions = collect($current->sections)->filter(fn ($section) => $section->visible)->groupBy('region');
                    $fieldLabels = ['eyebrow' => 'Nhãn nhỏ', 'title' => 'Tiêu đề', 'description' => 'Mô tả', 'archiveTitle' => 'Tiêu đề trang bài viết', 'archiveDescription' => 'Mô tả trang bài viết', 'mediaId' => 'Media ID', 'mediaIds' => 'Media IDs (phân cách dấu phẩy)', 'alt' => 'Mô tả ảnh', 'ctaLabel' => 'Nhãn CTA', 'ctaUrl' => 'Liên kết CTA', 'categoryId' => 'Category ID lọc bài mới', 'categoryIds' => 'Category IDs ghim', 'limit' => 'Số lượng bài mới nhất', 'copyright' => 'Copyright'];
                @endphp
                <section class="card">
                    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-accent-primary">{{ $regionLabels[$activeRegion] }}</p>
                            <h2 class="mt-1 text-lg font-semibold text-primary">Chỉnh nội dung {{ $regionLabels[$activeRegion] }}</h2>
                            <p class="mt-1 text-sm text-secondary">Chỉ các slot thuộc vùng này được hiển thị. Bố cục và vị trí đã được template khóa.</p>
                        </div>
                        <a href="{{ route('blogs.show', $blog->id) }}" class="btn-ghost px-3 py-2 text-sm">← Chọn vùng khác</a>
                    </div>
                    <form method="POST" action="{{ route('blogs.design.content', $blog->id) }}" class="space-y-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="region" value="{{ $activeRegion }}">
                        @foreach(['body' => 'Trang chủ', 'header' => 'Header dùng chung', 'sidebar' => 'Sidebar', 'footer' => 'Footer dùng chung'] as $region => $regionLabel)
                            @if($region === $activeRegion && $studioRegions->get($region, collect())->isNotEmpty())
                                <details class="rounded-md border border-default p-4" open>
                                    <summary class="cursor-pointer font-semibold text-primary">{{ $regionLabel }}</summary>
                                    <div class="mt-4 space-y-5">
                                        @foreach($studioRegions->get($region) as $section)
                                            @php
                                                $values = array_replace($section->props, $current->slotContent[$section->key] ?? []);
                                                $fields = \App\Domain\Templates\TemplateSlotCatalog::fields($section->section);
                                            @endphp
                                            @if($fields !== [])
                                                <fieldset id="slot-{{ $section->key }}" class="scroll-mt-6 border-l-2 pl-4" style="border-color: var(--d-accent);">
                                                    <legend class="font-medium text-primary">{{ $section->label ?? $section->key }}</legend>
                                                    <div class="grid gap-3 mt-3">
                                                        @foreach($fields as $field)
                                                            @php
                                                                $value = $values[$field] ?? '';
                                                                $inputName = 'slot_content['.$section->key.']['.$field.']';
                                                            @endphp
                                                            @if(in_array($field, ['description', 'archiveDescription'], true))
                                                                <x-textarea :name="$inputName" :label="$fieldLabels[$field]" rows="3">{{ $value }}</x-textarea>
                                                            @elseif($field === 'mediaId')
                                                                <div class="space-y-1">
                                                                    <label class="block text-sm font-medium text-primary">{{ $fieldLabels[$field] }}</label>
                                                                    <select class="input" name="slot_content[{{ $section->key }}][{{ $field }}]">
                                                                        <option value="">Chọn từ Media của blog</option>
                                                                        @foreach($mediaAssets as $asset)
                                                                            <option value="{{ $asset->id }}" @selected((string) $value === (string) $asset->id)>#{{ $asset->id }} · {{ $asset->resourceType === 'video' ? 'Video' : 'Ảnh' }} · {{ $asset->altText ?: $asset->originalName }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <a href="{{ route('blogs.media.index', $blog->id) }}" class="inline-block text-xs text-accent-primary hover:underline">Thêm hình ảnh hoặc video vào thư viện →</a>
                                                                </div>
                                                            @elseif(in_array($field, ['categoryId', 'limit'], true))
                                                                <x-input type="number" min="1" :name="$inputName" :label="$fieldLabels[$field]" :value="$value" />
                                                            @elseif(in_array($field, ['mediaIds', 'categoryIds'], true))
                                                                <x-input :name="$inputName" :label="$fieldLabels[$field]" :value="is_array($value) ? implode(',', $value) : $value" hint="Nhập các ID cách nhau bằng dấu phẩy." />
                                                            @elseif($field === 'ctaUrl')
                                                                <x-input :name="$inputName" :label="$fieldLabels[$field]" :value="$value" hint="Chấp nhận https://, đường dẫn /..., #section hoặc mailto:." />
                                                            @else
                                                                <x-input :name="$inputName" :label="$fieldLabels[$field]" :value="$value" />
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </fieldset>
                                            @endif
                                        @endforeach
                                    </div>
                                </details>
                            @elseif($region === $activeRegion)
                                <x-empty-state message="Template chưa có {{ $regionLabel }}">
                                    Admin cần thêm component vào vùng này trong template trước khi blogger có thể nhập nội dung.
                                </x-empty-state>
                            @endif
                        @endforeach
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="btn-secondary py-2 px-4">Lưu nháp {{ $regionLabels[$activeRegion] }}</button>
                            <button type="submit" name="publish" value="1" class="btn-primary py-2 px-4">Lưu và xuất bản {{ $regionLabels[$activeRegion] }}</button>
                        </div>
                    </form>
                </section>
            @endif
    </div>
</div>

@endsection
