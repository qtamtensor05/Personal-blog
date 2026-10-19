@php
    $isEdit = isset($template);
    $data = $isEdit ? ($template->draftData ?? $template->publishedData) : \App\Domain\Templates\TemplateStarters::blog();
    $slots = collect($data?->sections ?? [])->keyBy('section');
    $selected = $slots->keys()->all();
    $action = $isEdit ? route('admin.templates.update', $template->id) : route('admin.templates.store');
    $components = [
        'header' => ['Header', 'Header'], 'hero' => ['Banner / Hero', 'Body'],
        'image_text' => ['Image and content', 'Body'], 'gallery' => ['Image gallery', 'Body'],
        'recent_posts' => ['Latest posts', 'Body'], 'categories' => ['Categories', 'Body'],
        'cta' => ['CTA', 'Body'], 'profile' => ['Blogger profile', 'Sidebar'],
        'sidebar_posts' => ['Sidebar latest posts', 'Sidebar'], 'newsletter' => ['Newsletter', 'Body or sidebar'],
        'footer' => ['Footer', 'Footer'],
    ];
@endphp

<div class="container-custom max-w-5xl">
    @if(session('status'))<x-alert type="success" dismissible class="mb-6">{{ session('status') }}</x-alert>@endif
    @if($errors->any())<x-alert type="error" dismissible class="mb-6">{{ $errors->first() }}</x-alert>@endif
    <form method="POST" action="{{ $action }}" class="space-y-8">
        @csrf
        @if($isEdit) @method('PUT') @endif
        <section class="card space-y-4">
            <div>
                <h2 class="text-lg font-semibold text-primary">Template information</h2>
                <p class="text-sm text-secondary">The template defines the layout frame; bloggers fill in the content for each slot below.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <x-input name="name" label="Template name" :value="$isEdit ? $template->name : old('name')" required />
                <x-input name="slug" label="Slug" :value="$isEdit ? $template->slug : old('slug')" hint="Leave blank to generate from the name." />
            </div>
            <x-textarea name="description" label="Description" rows="2">{{ $isEdit ? $template->description : old('description') }}</x-textarea>
            <div class="space-y-1">
                <label for="layout" class="block text-sm font-medium text-primary">Page layout</label>
                <select name="layout" id="layout" class="input">
                    <option value="default" @selected($data?->layout === 'default')>Standard blog</option>
                    <option value="lifestyle-editorial" @selected($data?->layout === 'lifestyle-editorial')>Lifestyle Editorial</option>
                </select>
                <p class="text-xs text-secondary">The layout locks display positions; bloggers can only replace the content of each slot.</p>
            </div>
        </section>

        <section class="card space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-primary">Frame and components</h2>
                <p class="text-sm text-secondary">Choose from the available components. Positions are locked to Header, Body, Sidebar, and Footer; no custom HTML or JavaScript is allowed.</p>
            </div>
            <div class="grid md:grid-cols-2 gap-3">
                @foreach($components as $value => [$label, $region])
                    <div class="rounded-md border border-default p-4">
                        <label class="flex gap-3 cursor-pointer hover:text-accent-primary">
                            <input type="checkbox" name="sections[]" value="{{ $value }}" @checked(in_array($value, $selected, true))>
                            <span><span class="block font-medium text-primary">{{ $label }}</span><span class="text-xs text-secondary">{{ $region }}</span></span>
                        </label>
                        <div class="mt-3 flex items-center gap-3 text-sm">
                            <label class="flex items-center gap-1"><input type="hidden" name="slot_visibility[{{ $value }}]" value="0"><input type="checkbox" name="slot_visibility[{{ $value }}]" value="1" @checked($slots->get($value)?->visible ?? true)> Visible</label>
                            <label class="ml-auto">Order <input class="input inline w-16 py-1" type="number" min="1" max="99" name="slot_order[{{ $value }}]" value="{{ $loop->iteration }}"></label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex gap-3">
            <button class="btn-primary px-4 py-2 rounded-md" type="submit">{{ $isEdit ? 'Save draft' : 'Create template from starter' }}</button>
            <a class="btn-ghost border px-4 py-2 rounded-md" href="{{ route('admin.templates.index') }}">Cancel</a>
        </div>
    </form>
</div>
