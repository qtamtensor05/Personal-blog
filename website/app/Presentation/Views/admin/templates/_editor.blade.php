@php
    $isEdit = isset($template);
    $data = $isEdit
        ? $template->draftData ?? $template->publishedData
        : \App\Domain\Templates\TemplateStarters::blog();
    $slots = collect($data?->sections ?? [])->keyBy('section');
    $selected = $slots->keys()->all();
    $action = $isEdit ? route('admin.templates.update', $template->id) : route('admin.templates.store');
    $isLifestyle = $data?->layout === 'lifestyle-editorial';
    $fieldLabels = ['eyebrow' => 'Nhãn nhỏ', 'title' => 'Tiêu đề', 'description' => 'Mô tả', 'archiveTitle' => 'Tiêu đề trang bài viết', 'archiveDescription' => 'Mô tả trang bài viết', 'ctaLabel' => 'Nhãn nút', 'ctaUrl' => 'Liên kết CTA dự phòng', 'email' => 'Email', 'phone' => 'Số điện thoại', 'facebookUrl' => 'Facebook', 'youtubeUrl' => 'YouTube', 'tiktokUrl' => 'TikTok', 'zaloUrl' => 'Zalo', 'limit' => 'Số lượng bài hiển thị', 'copyright' => 'Copyright'];
    $components = [
        'header' => ['Header', 'Header'],
        'hero' => ['Banner / Hero', 'Body'],
        'image_text' => ['Image and content', 'Body'],
        'gallery' => ['Image gallery', 'Body'],
        'recent_posts' => ['Latest posts', 'Body'],
        'categories' => ['Categories', 'Body'],
        'cta' => ['CTA', 'Body'],
        'profile' => ['Blogger profile', 'Sidebar'],
        'sidebar_posts' => ['Sidebar latest posts', 'Sidebar'],
        'newsletter' => ['Newsletter', 'Body or sidebar'],
        'footer' => ['Footer', 'Footer'],
    ];
@endphp

<div class="container-custom max-w-5xl">
    @if (session('status'))
        <x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>
    @endif
    <form method="POST" action="{{ $action }}" class="space-y-8">
        @csrf
        @if ($isEdit) @method ('PUT') @endif
        <section class="card space-y-4">
            <div>
                <h2 class="text-primary text-lg font-semibold">Thông tin template</h2>
                <p class="text-secondary text-sm">Admin quản lý giao diện và nội dung fallback; blogger chỉ thay nội dung tại các vị trí đã định sẵn.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <x-input
                    name="name"
                    label="Tên template"
                    :value="$isEdit ? $template->name : old('name')"
                    required
                />
                <x-input
                    name="slug"
                    label="Slug"
                    :value="$isEdit ? $template->slug : old('slug')"
                    hint="Leave blank to generate from the name."
                />
            </div>
            <x-textarea name="description" label="Mô tả template" rows="2">{{
                $isEdit
                    ? $template->description
                    : old('description')
            }}</x-textarea>
            <div class="space-y-1">
                <label for="layout" class="text-primary block text-sm font-medium"
                    >Bố cục trang</label
                >
                <select name="layout" id="layout" class="input">
                    <option value="default" @selected ($data?->layout === 'default')>
                        Standard blog
                    </option>
                    <option
                        value="lifestyle-editorial"
                        @selected ($data?->layout === 'lifestyle-editorial')
                    >
                        Lifestyle Editorial
                    </option>
                </select>
                <p class="text-secondary text-xs">Bố cục quyết định vị trí Header, Body, Sidebar, Footer và không cho blogger kéo thả.</p>
            </div>
        </section>

        @if($isLifestyle)
        <section class="card space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-primary text-lg font-semibold">Nội dung fallback của template</h2>
                    <p class="text-secondary text-sm">Đây là nội dung hiển thị khi blogger chưa cấu hình. Cấu trúc và vị trí component đã được khóa theo giao diện Lifestyle Editorial.</p>
                </div>
                @if($isEdit)
                    <a href="{{ route('templates.preview', $template->slug) }}" target="_blank" rel="noopener" class="btn-secondary px-4 py-2 text-sm">Xem trang template ↗</a>
                @endif
            </div>
            <div class="space-y-3">
                @foreach($data->sections as $section)
                    @php
                        $fields = array_values(array_filter(
                            \App\Domain\Templates\TemplateSlotCatalog::fields($section->section),
                            fn ($field) => !in_array($field, ['mediaId', 'mediaIds', 'categoryId', 'categoryIds'], true),
                        ));
                    @endphp
                    @if($fields !== [])
                        <details class="group rounded-md border border-default bg-surface-secondary">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 p-4 font-semibold text-primary">
                                <span>{{ $section->label ?? $section->key }} <small class="ml-2 font-normal text-secondary">{{ ucfirst($section->region) }}</small></span>
                                <span class="text-xl text-accent-primary transition group-open:rotate-45">+</span>
                            </summary>
                            <div class="grid gap-4 border-t border-default p-4 md:grid-cols-2">
                                @foreach($fields as $field)
                                    @php($value = $section->props[$field] ?? '')
                                    @if(in_array($field, ['description', 'archiveDescription'], true))
                                        <x-textarea :name="'slot_props['.$section->key.']['.$field.']'" :label="$fieldLabels[$field] ?? $field" rows="3">{{ $value }}</x-textarea>
                                    @elseif($field === 'limit')
                                        <x-input type="number" min="1" max="12" :name="'slot_props['.$section->key.']['.$field.']'" :label="$fieldLabels[$field]" :value="$value" />
                                    @else
                                        <x-input :name="'slot_props['.$section->key.']['.$field.']'" :label="$fieldLabels[$field] ?? $field" :value="$value" />
                                    @endif
                                @endforeach
                            </div>
                        </details>
                    @endif
                @endforeach
            </div>
        </section>
        @else
        <section class="card space-y-5">
            <div>
                <h2 class="text-primary text-lg font-semibold">Frame and components</h2>
                <p class="text-secondary text-sm">Choose from the available components. Positions are locked to Header, Body, Sidebar, and Footer; no custom HTML or JavaScript is allowed.</p>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($components as $value => [$label, $region])
                    <div class="border-default rounded-md border p-4">
                        <label class="hover:text-accent-primary flex cursor-pointer gap-3">
                            <input
                                type="checkbox"
                                name="sections[]"
                                value="{{ $value }}"
                                @checked (in_array($value, $selected, true))
                            />
                            <span
                                ><span class="text-primary block font-medium">{{ $label }}</span
                                ><span class="text-secondary text-xs">{{ $region }}</span></span
                            >
                        </label>
                        <div class="mt-3 flex items-center gap-3 text-sm">
                            <label class="flex items-center gap-1"
                                ><input
                                    type="hidden"
                                    name="slot_visibility[{{ $value }}]"
                                    value="0"
                                /><input
                                    type="checkbox"
                                    name="slot_visibility[{{ $value }}]"
                                    value="1"
                                    @checked ($slots->get($value)?->visible ?? true)
                                />
                                Visible</label
                            >
                            <label class="ml-auto"
                                >Order
                                <input
                                    class="input inline w-16 py-1"
                                    type="number"
                                    min="1"
                                    max="99"
                                    name="slot_order[{{ $value }}]"
                                    value="{{ $loop->iteration }}"
                            /></label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        <div class="flex gap-3">
            <button class="btn-primary rounded-md px-4 py-2" type="submit">
                {{
                    $isEdit
                        ? 'Lưu bản nháp'
                        : 'Tạo template từ starter'
                }}
            </button>
            <a
                class="btn-ghost rounded-md border px-4 py-2"
                href="{{ route('admin.templates.index') }}"
                >Quay lại</a
            >
        </div>
    </form>
</div>
