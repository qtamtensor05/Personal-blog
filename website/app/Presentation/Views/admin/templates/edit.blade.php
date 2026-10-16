@extends('layouts.app')

@section('title', 'Edit Template — Admin Console')

@section('content')
<div class="container-custom section-spacing max-w-4xl">
    <div class="mb-8">
        <h1 class="text-hero mb-2">Edit Template: {{ $template->name }}</h1>
        <p class="text-secondary">Modify the draft. Changes won't affect published version until you publish again.</p>
    </div>

    <div class="card">
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
                hint="Lowercase letters, numbers and hyphens only"
            />

            <x-textarea
                name="description"
                label="Description"
                rows="3">{{ $template->description }}</x-textarea>

            <x-input
                name="demo_url"
                label="Demo URL (optional)"
                :value="$template->demoUrl"
                type="url"
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
                        <x-input name="headingFont" label="Heading Font" :value="$tokens->headingFont" required />
                        <x-input name="bodyFont" label="Body Font" :value="$tokens->bodyFont" required />
                    </div>
                </div>
            @endif

            @php
                $sections = $template->draftData?->sections ?? $template->publishedData?->sections ?? [];
            @endphp

            <div class="border-t border-default pt-6">
                <h2 class="text-lg font-semibold text-primary mb-4">Sections</h2>
                <div id="sections" class="space-y-4">
                    @foreach($sections as $s)
                        <div class="card">
                            <div class="flex items-start gap-4">
                                <div class="flex-1 space-y-3">
                                    <input type="text" name="section_keys[]" value="{{ $s->key }}" class="input" required />
                                    <select name="section_types[]" class="input" required>
                                        @foreach(['hero', 'recent_posts', 'featured_post', 'categories', 'about', 'newsletter', 'footer'] as $type)
                                            <option value="{{ $type }}" {{ $s->section === $type ? 'selected' : '' }}>{{ $type }}</option>
                                        @endforeach
                                    </select>
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
</div>

@push('scripts')
<script>
const availableSections = ['hero', 'recent_posts', 'featured_post', 'categories', 'about', 'newsletter', 'footer'];

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
                    ${availableSections.map(s => `<option value="${s}">${s}</option>`).join('')}
                </select>
            </div>
            <button type="button" onclick="this.closest('.card').remove()" class="text-red-500 hover:text-red-600 text-sm">Remove</button>
        </div>
    `;
    container.appendChild(div);
}

document.getElementById('templateForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const keys = formData.getAll('section_keys[]');
    const types = formData.getAll('section_types[]');
    const sections = keys.map((k, i) => ({ key: k, section: types[i], props: {} })).filter(s => s.key && s.section);

    if (sections.length === 0) {
        alert('At least one section is required');
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
        const err = await r.json();
        alert(err.message || 'Save failed');
    }
});
</script>
@endpush
@endsection
