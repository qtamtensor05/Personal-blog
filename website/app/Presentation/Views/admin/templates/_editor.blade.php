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
        'header' => [__('template.header'), __('template.header')],
        'hero' => [__('template.hero'), __('template.home')],
        'image_text' => [__('template.about'), __('template.home')],
        'gallery' => [__('template.gallery'), __('template.home')],
        'recent_posts' => [__('template.post_section'), __('template.home')],
        'categories' => [__('taxonomy.categories'), __('template.home')],
        'cta' => [__('template.contact'), __('template.home')],
        'profile' => [__('template.profile'), __('template.sidebar')],
        'sidebar_posts' => [__('template.posts'), __('template.sidebar')],
        'newsletter' => [__('template.newsletter'), __('template.home')],
        'footer' => [__('template.footer'), __('template.footer')],
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
                <h2 class="text-primary text-lg font-semibold">{{ __('templates.about_information') }}</h2>
                <p class="text-secondary text-sm">{{ __('templates.admin_help') }}</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <x-input
                    name="name"
                    :label="__('templates.name_label')"
                    :value="$isEdit ? $template->name : old('name')"
                    required
                />
                <x-input
                    name="slug"
                    label="Slug"
                    :value="$isEdit ? $template->slug : old('slug')"
                    :hint="__('blog.slug_help')"
                />
            </div>
            <x-textarea name="description" :label="__('templates.description_label')" rows="2">{{
                $isEdit
                    ? $template->description
                    : old('description')
            }}</x-textarea>
            <div class="space-y-1">
                <label for="layout" class="text-primary block text-sm font-medium"
                    >{{ __('templates.layout') }}</label
                >
                <select name="layout" id="layout" class="input">
                    <option value="default" @selected ($data?->layout === 'default')>
                        {{ __('templates.standard') }}
                    </option>
                    <option
                        value="lifestyle-editorial"
                        @selected ($data?->layout === 'lifestyle-editorial')
                    >
                        Lifestyle Editorial
                    </option>
                </select>
                <p class="text-secondary text-xs">{{ __('templates.layout_help') }}</p>
            </div>
        </section>

        @if($isLifestyle)
        <section class="card space-y-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-primary text-lg font-semibold">{{ __('templates.fallback') }}</h2>
                    <p class="text-secondary text-sm">{{ __('templates.fallback_help') }}</p>
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
                                <i class="fa-solid fa-chevron-down text-sm text-accent-primary transition group-open:rotate-180" aria-hidden="true"></i>
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
                <h2 class="text-primary text-lg font-semibold">{{ __('templates.frame_components') }}</h2>
                <p class="text-secondary text-sm">{{ __('templates.components_help') }}</p>
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
                                {{ __('templates.visible') }}</label
                            >
                            <label class="ml-auto"
                                >{{ __('templates.order') }}
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
                        ? __('templates.save_draft')
                        : __('templates.create_starter')
                }}
            </button>
            <a
                class="btn-ghost rounded-md border px-4 py-2"
                href="{{ route('admin.templates.index') }}"
                >{{ __('templates.back') }}</a
            >
        </div>
    </form>
</div>
