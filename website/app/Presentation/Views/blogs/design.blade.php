@extends('layouts.app')

@section('title', 'Design — '.$blog->name)

@php
    $draft = $design?->draftData;
    $live = $design?->publishedData;
    $current = $draft ?? $live;
    $tokens = $current?->designTokens;
    $hasUnpublished = $draft !== null && ($live === null || json_encode($draft) !== json_encode($live));
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

        @if($draft)
            <form method="POST" action="{{ route('blogs.design.publish', $blog->id) }}">
                @csrf
                <button type="submit" class="btn-primary" @disabled(! $hasUnpublished)>Publish design</button>
            </form>
        @endif
    </div>

    @if(session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif

    @if($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif

    <div class="grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] gap-8">
        <div class="space-y-8">
            {{-- Template picker --}}
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
                                <span>
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

            {{-- Token customization --}}
            @if($tokens)
                <section class="card">
                    <h2 class="text-lg font-semibold text-primary mb-1">Customize</h2>
                    <p class="text-sm text-secondary mb-4">Colors and fonts for your blog. Saved as a draft.</p>

                    <form method="POST" action="{{ route('blogs.design.draft', $blog->id) }}" id="tokensForm" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach([
                                'primaryColor' => 'Primary',
                                'secondaryColor' => 'Secondary',
                                'accentColor' => 'Accent',
                                'textColor' => 'Text',
                                'backgroundColor' => 'Background',
                            ] as $field => $label)
                                <x-input type="color" :name="$field" :label="$label" :value="$tokens->{$field}" required />
                            @endforeach
                            <div class="space-y-1">
                                <label for="headingFont" class="block text-sm font-medium text-primary">Heading font</label>
                                <select name="headingFont" id="headingFont" class="input" required>
                                    @foreach(['Instrument Sans', 'DM Sans', 'Manrope', 'Merriweather', 'Playfair Display', 'Space Grotesk'] as $font)
                                        <option value="{{ $font }}" @selected($tokens->headingFont === $font)>{{ $font }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label for="bodyFont" class="block text-sm font-medium text-primary">Body font</label>
                                <select name="bodyFont" id="bodyFont" class="input" required>
                                    @foreach(['Instrument Sans', 'DM Sans', 'Manrope', 'Source Serif 4', 'Merriweather', 'Space Grotesk'] as $font)
                                        <option value="{{ $font }}" @selected($tokens->bodyFont === $font)>{{ $font }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary py-2 px-4 rounded-md">Save draft</button>
                    </form>
                </section>
            @endif

            @if($current)
                @php
                    $studioRegions = collect($current->sections)->filter(fn ($section) => $section->visible)->groupBy('region');
                    $fieldLabels = ['eyebrow' => 'Nhãn nhỏ', 'title' => 'Tiêu đề', 'description' => 'Mô tả', 'mediaId' => 'Media ID', 'mediaIds' => 'Media IDs (phân cách dấu phẩy)', 'alt' => 'Mô tả ảnh', 'ctaLabel' => 'Nhãn CTA', 'ctaUrl' => 'Liên kết CTA', 'categoryId' => 'Category ID lọc bài mới', 'categoryIds' => 'Category IDs ghim', 'limit' => 'Số lượng hiển thị', 'copyright' => 'Copyright'];
                @endphp
                <section class="card">
                    <h2 class="text-lg font-semibold text-primary mb-1">Studio nội dung</h2>
                    <p class="text-sm text-secondary mb-4">Chỉ chỉnh dữ liệu trong slot của template. Bố cục và vị trí được khóa.</p>
                    <form method="POST" action="{{ route('blogs.design.content', $blog->id) }}" class="space-y-5">
                        @csrf
                        @method('PUT')
                        @foreach(['body' => 'Trang chủ', 'header' => 'Header dùng chung', 'sidebar' => 'Sidebar', 'footer' => 'Footer dùng chung'] as $region => $regionLabel)
                            @if($studioRegions->get($region, collect())->isNotEmpty())
                                <details class="rounded-md border border-default p-4" @if($region === 'body') open @endif>
                                    <summary class="cursor-pointer font-semibold text-primary">{{ $regionLabel }}</summary>
                                    <div class="mt-4 space-y-5">
                                        @foreach($studioRegions->get($region) as $section)
                                            @php
                                                $values = $current->slotContent[$section->key] ?? [];
                                                $fields = \App\Domain\Templates\TemplateSlotCatalog::fields($section->section);
                                            @endphp
                                            @if($fields !== [])
                                                <fieldset class="border-l-2 pl-4" style="border-color: var(--d-accent);">
                                                    <legend class="font-medium text-primary">{{ $section->label ?? $section->key }}</legend>
                                                    <div class="grid gap-3 mt-3">
                                                        @foreach($fields as $field)
                                                            @php
                                                                $value = $values[$field] ?? '';
                                                                $inputName = 'slot_content['.$section->key.']['.$field.']';
                                                            @endphp
                                                            @if($field === 'description')
                                                                <x-textarea :name="$inputName" :label="$fieldLabels[$field]" rows="3">{{ $value }}</x-textarea>
                                                            @elseif($field === 'mediaId')
                                                                <div class="space-y-1"><label class="block text-sm font-medium text-primary">{{ $fieldLabels[$field] }}</label><select class="input" name="slot_content[{{ $section->key }}][{{ $field }}]"><option value="">Chọn từ Media của blog</option>@foreach($mediaAssets as $asset)<option value="{{ $asset->id }}" @selected((string) $value === (string) $asset->id)>#{{ $asset->id }} — {{ $asset->altText ?: 'Hình ảnh' }}</option>@endforeach</select></div>
                                                            @elseif(in_array($field, ['categoryId', 'limit'], true))
                                                                <x-input type="number" min="1" :name="$inputName" :label="$fieldLabels[$field]" :value="$value" />
                                                            @elseif(in_array($field, ['mediaIds', 'categoryIds'], true))
                                                                <x-input :name="$inputName" :label="$fieldLabels[$field]" :value="is_array($value) ? implode(',', $value) : $value" hint="Nhập các ID cách nhau bằng dấu phẩy." />
                                                            @elseif($field === 'ctaUrl')
                                                                <x-input type="url" :name="$inputName" :label="$fieldLabels[$field]" :value="$value" />
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
                            @endif
                        @endforeach
                        <button type="submit" class="btn-primary py-2 px-4">Lưu nội dung Trang chủ</button>
                    </form>
                </section>
            @endif
        </div>

        {{-- Preview: renders the draft with only controlled sections --}}
        <section aria-label="Preview">
            <h2 class="text-sm font-medium text-secondary mb-2">Preview (draft)</h2>
            @if($current)
                <div id="designPreview" class="rounded-lg border border-default overflow-hidden"
                     style="--d-primary: {{ $tokens->primaryColor }}; --d-secondary: {{ $tokens->secondaryColor }}; --d-accent: {{ $tokens->accentColor }}; --d-text: {{ $tokens->textColor }}; --d-bg: {{ $tokens->backgroundColor }}; --d-heading-font: '{{ $tokens->headingFont }}'; --d-body-font: '{{ $tokens->bodyFont }}';">
                    <div class="p-6 space-y-6" style="background: var(--d-bg); color: var(--d-text); font-family: var(--d-body-font), sans-serif;">
                        @foreach($current->sections as $section)
                            @includeIf('blogs.design-sections.'.$section->section, ['blog' => $blog, 'posts' => [], 'categories' => [], 'section' => $section, 'content' => array_replace($section->props, $current->slotContent[$section->key] ?? []), 'mediaUrls' => $mediaUrls, 'documentHtml' => $documentHtml])
                        @endforeach
                    </div>
                </div>
            @else
                <x-empty-state message="No design yet">
                    Choose a template to start designing your blog.
                </x-empty-state>
            @endif
        </section>
    </div>
</div>

@push('scripts')
<script>
// Live preview of token changes before saving.
(function () {
    const form = document.getElementById('tokensForm');
    const preview = document.getElementById('designPreview');
    if (!form || !preview) return;

    const map = {
        primaryColor: '--d-primary', secondaryColor: '--d-secondary', accentColor: '--d-accent',
        textColor: '--d-text', backgroundColor: '--d-bg',
        headingFont: '--d-heading-font', bodyFont: '--d-body-font',
    };

    form.addEventListener('input', (e) => {
        const prop = map[e.target.name];
        if (!prop) return;
        const value = e.target.name.endsWith('Font')
            ? `'${e.target.value.replace(/[^A-Za-z0-9 \-]/g, '')}'`
            : e.target.value;
        preview.style.setProperty(prop, value);
    });
})();
</script>
@endpush
@endsection
