@php
    $isEdit = isset($template);
    $data = $isEdit ? ($template->draftData ?? $template->publishedData) : \App\Domain\Templates\TemplateStarters::blog();
    $slots = collect($data?->sections ?? [])->keyBy('section');
    $selected = $slots->keys()->all();
    $action = $isEdit ? route('admin.templates.update', $template->id) : route('admin.templates.store');
    $components = [
        'header' => ['Header', 'Header'], 'hero' => ['Banner / Hero', 'Body'],
        'image_text' => ['Ảnh và nội dung', 'Body'], 'gallery' => ['Gallery ảnh', 'Body'],
        'recent_posts' => ['Latest posts', 'Body'], 'categories' => ['Categories', 'Body'],
        'cta' => ['CTA', 'Body'], 'profile' => ['Blogger profile', 'Sidebar'],
        'sidebar_posts' => ['Bài viết mới sidebar', 'Sidebar'], 'newsletter' => ['Newsletter', 'Body hoặc sidebar'],
        'footer' => ['Footer', 'Footer'],
    ];
@endphp

<div class="container-custom section-spacing max-w-5xl">
    @if(session('status'))<x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>@endif
    @if($errors->any())<x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>@endif
    <form method="POST" action="{{ $action }}" class="space-y-8">
        @csrf
        @if($isEdit) @method('PUT') @endif
        <section class="card space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-primary">Thông tin template</h2>
                <p class="text-sm text-secondary">Template chỉ định khung giao diện; blogger sẽ điền nội dung theo các slot bên dưới.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <x-input name="name" label="Tên template" :value="$isEdit ? $template->name : old('name')" required />
                <x-input name="slug" label="Slug" :value="$isEdit ? $template->slug : old('slug')" hint="Để trống để sinh từ tên." />
            </div>
            <x-textarea name="description" label="Mô tả" rows="2">{{ $isEdit ? $template->description : old('description') }}</x-textarea>
            <div class="space-y-1">
                <label for="layout" class="block text-sm font-medium text-primary">Bố cục trang</label>
                <select name="layout" id="layout" class="input">
                    <option value="default" @selected($data?->layout === 'default')>Blog tiêu chuẩn</option>
                    <option value="lifestyle-editorial" @selected($data?->layout === 'lifestyle-editorial')>Lifestyle Editorial</option>
                </select>
                <p class="text-xs text-secondary">Bố cục khóa vị trí hiển thị; blogger chỉ thay nội dung từng slot.</p>
            </div>
        </section>

        <section class="card space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-primary">Khung và component</h2>
                <p class="text-sm text-secondary">Chọn component có sẵn. Vị trí được khóa theo Header, Body, Sidebar và Footer; không có HTML hoặc JavaScript tùy ý.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-3">
                @foreach($components as $value => [$label, $region])
                    <div class="rounded-md border border-default p-4">
                        <label class="flex gap-3 cursor-pointer hover:text-accent-primary">
                            <input type="checkbox" name="sections[]" value="{{ $value }}" @checked(in_array($value, $selected, true))>
                            <span><span class="block font-medium text-primary">{{ $label }}</span><span class="text-xs text-secondary">{{ $region }}</span></span>
                        </label>
                        <div class="mt-3 flex items-center gap-3 text-sm">
                            <label class="flex items-center gap-1"><input type="hidden" name="slot_visibility[{{ $value }}]" value="0"><input type="checkbox" name="slot_visibility[{{ $value }}]" value="1" @checked($slots->get($value)?->visible ?? true)> Hiển thị</label>
                            <label class="ml-auto">Thứ tự <input class="input inline w-16 py-1" type="number" min="1" max="99" name="slot_order[{{ $value }}]" value="{{ $loop->iteration }}"></label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex gap-3">
            <button class="btn-primary" type="submit">{{ $isEdit ? 'Lưu bản nháp' : 'Tạo template từ starter' }}</button>
            <a class="btn-ghost" href="{{ route('admin.templates.index') }}">Hủy</a>
        </div>
    </form>
</div>
