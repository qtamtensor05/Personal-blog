@extends('layouts.app')

@section('title', 'Edit Template — Admin Console')

@section('content')
<div class="container-custom section-spacing max-w-6xl">
    <div class="mb-8">
        <h1 class="text-hero mb-2">Edit Template: {{ $template->name }}</h1>
        <p class="text-secondary">Modify the draft. Changes won't affect published version until you publish again.</p>
    </div>

    @include('admin.templates._editor')

    <div class="hidden grid lg:grid-cols-[minmax(0,1fr)_minmax(22rem,0.8fr)] gap-8 items-start" style="display:none">
    <div class="card">
        <div id="templateFormError" class="hidden mb-6" role="alert"></div>
        <form id="templateForm" class="space-y-6">
            @csrf
            @method('PUT')

            <x-input
                name="name"
                label="Template Name"
                :value="$template->name"
                required
            />

            <x-input
                name="slug"
                label="Slug"
                :value="$template->slug"
                required
                hint="Generated from the name. Edit it when you need a different URL."
            />

            <x-textarea
                name="description"
                label="Description"
                rows="3">{{ $template->description }}</x-textarea>

            <x-input
                name="demo_url"
                label="Demo URL"
                :value="$template->demoUrl"
                readonly
                hint="Generated automatically from the slug."
            />

            @php
                $tokens = $template->draftData?->designTokens ?? $template->publishedData?->designTokens;
            @endphp

            @if($tokens !== null)
                <div class="border-t border-default pt-6">
                    <h2 class="text-lg font-semibold text-primary mb-4">Design Tokens</h2>
                    <div class="grid md:grid-cols-2 gap-4">
                        <x-input name="primaryColor" label="Primary" type="color" :value="$tokens->primaryColor" required />
                        <x-input name="secondaryColor" label="Secondary" type="color" :value="$tokens->secondaryColor" required />
                        <x-input name="accentColor" label="Accent" type="color" :value="$tokens->accentColor" required />
                        <x-input name="textColor" label="Text" type="color" :value="$tokens->textColor" required />
                        <x-input name="backgroundColor" label="Background" type="color" :value="$tokens->backgroundColor" required />
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
                </div>
            @endif

            @php
                $sections = $template->draftData?->sections ?? $template->publishedData?->sections ?? [];
            @endphp

            <div class="border-t border-default pt-6">
                <h2 class="text-lg font-semibold text-primary mb-1">Sections</h2>
                <p class="text-sm text-secondary mb-4">Add, remove, and reorder the blocks that make up the template.</p>
                <div id="sections" class="space-y-4">
                    @foreach($sections as $s)
                        <div class="card">
                            <div class="flex items-start gap-4">
                                <div class="flex-1 space-y-3">
                                    <input type="text" name="section_keys[]" value="{{ $s->key }}" class="input" required />
                                    <select name="section_types[]" class="input" required>
                                            @foreach(['hero', 'recent_posts', 'featured_post', 'categories', 'about', 'newsletter', 'image', 'footer'] as $type)
                                            <option value="{{ $type }}" {{ $s->section === $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
                                        <div class="{{ $s->section === 'image' ? '' : 'hidden ' }}space-y-3 rounded-md border border-default p-3" data-image-fields>
                                            <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-template-image class="input" />
                                            <input type="hidden" name="section_image_media_ids[]" value="{{ $s->props['mediaId'] ?? '' }}" />
                                            <input type="text" name="section_image_alts[]" value="{{ $s->props['alt'] ?? '' }}" placeholder="Image description" class="input" />
                                            <select name="section_image_positions[]" class="input">
                                                @foreach(['full' => 'Full width', 'left' => 'Align left', 'right' => 'Align right'] as $position => $label)
                                                    <option value="{{ $position }}" @selected(($s->props['position'] ?? 'full') === $position)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <p class="text-xs text-secondary" data-upload-status>{{ isset($s->props['mediaId']) ? 'Cloudinary image selected.' : 'Choose an image to upload to Cloudinary.' }}</p>
                                        </div>
                                </div>
                                <button type="button" onclick="this.closest('.card').remove()" class="text-red-500 hover:text-red-600 text-sm">Remove</button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" onclick="addSection()" class="btn-secondary mt-4">Add Section</button>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-default">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('admin.templates.index') }}" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
    <aside class="lg:sticky lg:top-6">
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-secondary">Live review</h2>
            <span class="text-xs text-secondary">Draft configuration</span>
        </div>
        <div id="templatePreview" class="card overflow-hidden p-0">
            <div id="previewHeader" class="p-6" style="background:{{ $tokens?->backgroundColor ?? '#F7F6F1' }};color:{{ $tokens?->textColor ?? '#616B5E' }};font-family:'{{ $tokens?->headingFont ?? 'Instrument Sans' }}',sans-serif">
                <p class="text-xs uppercase tracking-widest opacity-70">Template preview</p>
                <h2 id="previewTitle" class="mt-2 text-3xl font-semibold">{{ $template->name }}</h2>
                <p id="previewDescription" class="mt-2 text-sm opacity-80">{{ $template->description ?: 'Your template description will appear here.' }}</p>
            </div>
            <div id="previewSections" class="space-y-2 p-4" style="background:#fff;color:{{ $tokens?->textColor ?? '#616B5E' }}"></div>
        </div>
    </aside>
    </div>
</div>

@push('scripts')
<script>
const availableSections = [
    ['hero', 'Hero'], ['recent_posts', 'Recent posts'], ['featured_post', 'Featured post'],
    ['categories', 'Categories'], ['about', 'About'], ['newsletter', 'Newsletter'], ['image', 'Image'], ['footer', 'Footer'],
];
const slugify = (value) => value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
const nameInput = document.querySelector('[name=name]');
const slugInput = document.querySelector('[name=slug]');
const demoInput = document.querySelector('[name=demo_url]');
const formError = document.getElementById('templateFormError');
const showFormError = (message) => {
    formError.textContent = message;
    formError.className = 'mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';
    window.showErrorModal?.(message);
};
const updateDemoUrl = () => { demoInput.value = `${window.location.origin}/template-preview/${slugInput.value || 'untitled'}`; };
let slugEdited = slugInput.value !== slugify(nameInput.value);
nameInput.addEventListener('input', () => {
    if (!slugEdited) slugInput.value = slugify(nameInput.value);
    updateDemoUrl();
    updatePreview();
});
slugInput.addEventListener('input', () => { slugEdited = true; demoInput.value = `${window.location.origin}/template-preview/${slugInput.value}`; updatePreview(); });
updateDemoUrl();

function addSection() {
    const container = document.getElementById('sections');
    const div = document.createElement('div');
    div.className = 'card';
    div.innerHTML = `
        <div class="flex items-start gap-4">
            <div class="flex-1 space-y-3">
                <input type="text" name="section_keys[]" placeholder="Section key" class="input" required />
                <select name="section_types[]" class="input" required>
                    <option value="">-- Choose type --</option>
                    ${availableSections.map(([value, label]) => `<option value="${value}">${label}</option>`).join('')}
                </select>
                <div class="hidden space-y-3 rounded-md border border-default p-3" data-image-fields>
                    <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-template-image class="input" />
                    <input type="hidden" name="section_image_media_ids[]" />
                    <input type="text" name="section_image_alts[]" placeholder="Image description" class="input" />
                    <select name="section_image_positions[]" class="input">
                        <option value="full">Full width</option>
                        <option value="left">Align left</option>
                        <option value="right">Align right</option>
                    </select>
                    <p class="text-xs text-secondary" data-upload-status>Choose an image to upload to Cloudinary.</p>
                </div>
            </div>
            <button type="button" onclick="this.closest('.card').remove()" class="text-red-500 hover:text-red-600 text-sm">Remove</button>
        </div>
    `;
    container.appendChild(div);
    const type = div.querySelector('select[name="section_types[]"]');
    const imageFields = div.querySelector('[data-image-fields]');
    type.addEventListener('change', () => { imageFields.classList.toggle('hidden', type.value !== 'image'); updatePreview(); });
    div.querySelector('[data-template-image]').addEventListener('change', (event) => uploadTemplateImage(event.target, div));
    div.querySelectorAll('input, select').forEach((field) => field.addEventListener('input', updatePreview));
    updatePreview();
}

document.querySelectorAll('select[name="section_types[]"]').forEach((type) => {
    const imageFields = type.closest('.space-y-3')?.querySelector('[data-image-fields]');
    type.addEventListener('change', () => imageFields?.classList.toggle('hidden', type.value !== 'image'));
    imageFields?.querySelector('[data-template-image]')?.addEventListener('change', (event) => uploadTemplateImage(event.target, type.closest('.card')));
});

async function uploadTemplateImage(input, section) {
    const file = input.files[0];
    if (!file) return;
    const status = section.querySelector('[data-upload-status]');
    const form = new FormData();
    form.append('file', file);
    form.append('scope', 'PLATFORM');
    status.textContent = 'Uploading image to Cloudinary…';
    try {
        const response = await fetch('{{ route('media.store') }}', {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: form,
        });
        const body = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(body.message || 'Image upload failed.');
        section.querySelector('input[name="section_image_media_ids[]"]').value = body.data.id;
        status.textContent = 'Image uploaded to Cloudinary.';
        updatePreview();
    } catch (error) {
        status.textContent = error.message;
        window.showErrorModal?.(error.message);
    }
}

function updatePreview() {
    const data = new FormData(document.getElementById('templateForm'));
    document.getElementById('previewTitle').textContent = data.get('name') || 'Untitled template';
    document.getElementById('previewDescription').textContent = data.get('description') || 'Your template description will appear here.';
    const header = document.getElementById('previewHeader');
    header.style.background = data.get('backgroundColor') || '#F7F6F1';
    header.style.color = data.get('textColor') || '#616B5E';
    header.style.fontFamily = `'${data.get('headingFont') || 'Instrument Sans'}', sans-serif`;
    document.getElementById('previewSections').innerHTML = Array.from(document.querySelectorAll('[name^="section_types[]"]'))
        .map((field, index) => `<div class="rounded-md border border-default px-3 py-3 text-sm" style="border-left:4px solid ${data.get('accentColor') || '#E0BE75'}"><strong>${index + 1}. ${field.options[field.selectedIndex]?.text || 'Section'}</strong><span class="block text-xs opacity-70">${field.value || 'Choose a section type'}</span></div>`)
        .join('');
}

document.getElementById('templateForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const keys = formData.getAll('section_keys[]');
    const types = formData.getAll('section_types[]');
        const imageMediaIds = formData.getAll('section_image_media_ids[]');
        const imageAlts = formData.getAll('section_image_alts[]');
        const imagePositions = formData.getAll('section_image_positions[]');
        const sections = keys.map((k, i) => ({
            key: k,
            section: types[i],
            props: types[i] === 'image' ? { mediaId: imageMediaIds[i] || null, alt: imageAlts[i] || 'Template image', position: imagePositions[i] || 'full' } : {},
        })).filter(s => s.key && s.section);

    if (sections.length === 0) {
        showFormError('Add at least one section before saving the template.');
        return;
    }

    const payload = {
        name: formData.get('name'),
        slug: formData.get('slug'),
        description: formData.get('description') || null,
        demo_url: formData.get('demo_url') || null,
        draft_data: {
            sections,
            designTokens: {
                primaryColor: formData.get('primaryColor'),
                secondaryColor: formData.get('secondaryColor'),
                accentColor: formData.get('accentColor'),
                textColor: formData.get('textColor'),
                backgroundColor: formData.get('backgroundColor'),
                headingFont: formData.get('headingFont'),
                bodyFont: formData.get('bodyFont'),
            }
        }
    };

    const r = await fetch('{{ route('admin.templates.update', $template->id) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
        },
        body: JSON.stringify(payload),
    });

    if (r.ok) {
        location.href = '{{ route('admin.templates.index') }}';
    } else {
        const err = await r.json().catch(() => ({}));
        showFormError(err.message || 'The template could not be saved. Check the form and try again.');
    }
});
updatePreview();
</script>
@endpush
@endsection
