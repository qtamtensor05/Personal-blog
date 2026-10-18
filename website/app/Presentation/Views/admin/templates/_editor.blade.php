@php
    $isEdit = isset($template);
    $tokens = $isEdit ? ($template->draftData?->designTokens ?? $template->publishedData?->designTokens) : \App\Domain\Templates\ValueObjects\TemplateDesignTokens::default();
    $postLayout = $isEdit ? ($template->draftData?->postLayout ?? $template->publishedData?->postLayout) : \App\Domain\Templates\ValueObjects\TemplatePostLayout::default();
    $document = ['type' => 'doc', 'content' => [['type' => 'paragraph']]];
    if ($isEdit) {
        $documentSection = collect($template->draftData?->sections ?? $template->publishedData?->sections ?? [])->firstWhere('section', 'document');
        $document = $documentSection?->props['content'] ?? $document;
    }
    $editorConfig = [
        'content' => $document,
        'mediaUrls' => (object) ($mediaUrls ?? []),
        'mediaUrl' => route('media.store'),
        'saveUrl' => $isEdit ? route('admin.templates.update', $template->id) : route('admin.templates.store'),
        'method' => $isEdit ? 'PUT' : 'POST',
        'successUrl' => route('admin.templates.index'),
    ];
@endphp

<div class="container-custom section-spacing max-w-7xl" data-template-editor>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('admin.templates.index') }}" class="text-sm text-secondary hover:text-accent-primary">← Templates</a>
            <h1 class="text-hero mt-2">{{ $isEdit ? 'Edit template' : 'New template' }}</h1>
            <p class="text-secondary">Compose the full template page with text, images, and responsive columns.</p>
        </div>
        <span class="text-sm text-secondary" data-editor-status>Draft not saved</span>
    </div>

    <script type="application/json" data-editor-config>@json($editorConfig)</script>

    <form method="POST" class="space-y-8" data-template-form>
        @csrf
        <div class="grid lg:grid-cols-[minmax(0,1fr)_20rem] gap-8 items-start">
            <section class="card space-y-5">
                <div class="grid md:grid-cols-2 gap-4">
                    <x-input name="name" label="Template name" :value="$isEdit ? $template->name : ''" required />
                    <x-input name="slug" label="Slug" :value="$isEdit ? $template->slug : ''" hint="Generated from the name; editable." />
                </div>
                <x-textarea name="description" label="Description" rows="2">{{ $isEdit ? $template->description : '' }}</x-textarea>
                <input type="hidden" name="demo_url" value="{{ $isEdit ? $template->demoUrl : '' }}">

                <div class="flex flex-wrap gap-2 border-y border-default py-3" role="toolbar" aria-label="Template formatting">
                    @foreach(['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'bold' => 'Bold', 'italic' => 'Italic', 'bulletList' => 'List', 'orderedList' => 'Numbered', 'quote' => 'Quote', 'divider' => 'Divider', 'columns2' => '2 columns', 'columns3' => '3 columns', 'image' => 'Image'] as $command => $label)
                        <button type="button" class="btn-ghost text-sm px-3 py-1" data-template-command="{{ $command }}">{{ $label }}</button>
                    @endforeach
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" data-template-image-input>
                </div>

                <div data-template-content-editor class="rounded-md border border-default p-5"></div>

                <div class="flex items-center gap-3 border-t border-default pt-5">
                    <button type="submit" class="btn-primary">Save draft</button>
                    <a href="{{ route('admin.templates.index') }}" class="btn-ghost">Cancel</a>
                </div>
            </section>

            <aside class="card space-y-4 lg:sticky lg:top-6">
                <div>
                    <h2 class="text-lg font-semibold text-primary">Design tokens</h2>
                    <p class="text-sm text-secondary">These values style the document when it is rendered in a blog.</p>
                </div>
                @foreach(['primaryColor' => 'Primary', 'secondaryColor' => 'Secondary', 'accentColor' => 'Accent', 'textColor' => 'Text', 'backgroundColor' => 'Background'] as $field => $label)
                    <x-input type="color" name="{{ $field }}" label="{{ $label }}" :value="$tokens->{$field}" required />
                @endforeach
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-primary" for="headingFont">Heading font</label>
                    <select name="headingFont" id="headingFont" class="input" required>
                        @foreach(['Instrument Sans', 'DM Sans', 'Manrope', 'Merriweather', 'Playfair Display', 'Space Grotesk'] as $font)
                            <option value="{{ $font }}" @selected($tokens->headingFont === $font)>{{ $font }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-primary" for="bodyFont">Body font</label>
                    <select name="bodyFont" id="bodyFont" class="input" required>
                        @foreach(['Instrument Sans', 'DM Sans', 'Manrope', 'Source Serif 4', 'Merriweather', 'Space Grotesk'] as $font)
                            <option value="{{ $font }}" @selected($tokens->bodyFont === $font)>{{ $font }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="border-t border-default pt-4 space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-primary">Post frame</h2>
                        <p class="text-sm text-secondary">Every public post created under this template uses this frame.</p>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-primary" for="postLayoutVariant">Header layout</label>
                        <select name="postLayoutVariant" id="postLayoutVariant" class="input" required>
                            @foreach(['editorial' => 'Editorial', 'card' => 'Card', 'minimal' => 'Minimal'] as $value => $label)
                                <option value="{{ $value }}" @selected($postLayout->variant === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-primary" for="postContentWidth">Content width</label>
                        <select name="postContentWidth" id="postContentWidth" class="input" required>
                            @foreach(['narrow' => 'Narrow', 'standard' => 'Standard', 'wide' => 'Wide'] as $value => $label)
                                <option value="{{ $value }}" @selected($postLayout->contentWidth === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    @foreach([
                        'postShowBlogName' => ['Show blog name', $postLayout->showBlogName],
                        'postShowPublishedDate' => ['Show published date', $postLayout->showPublishedDate],
                        'postShowCover' => ['Show cover image', $postLayout->showCover],
                    ] as $field => [$label, $checked])
                        <label class="flex items-center gap-2 text-sm text-primary">
                            <input type="checkbox" name="{{ $field }}" value="1" @checked($checked)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </aside>
        </div>
    </form>
</div>

@push('scripts')
    @vite('app/Presentation/Js/template-editor.js')
@endpush
