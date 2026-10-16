@extends('layouts.app')

@section('title', 'New Template — Admin Console')

@section('content')
<div class="container-custom section-spacing max-w-4xl">
    <div class="mb-8">
        <h1 class="text-hero mb-2">New Template</h1>
        <p class="text-secondary">Create a template draft with sections and design tokens.</p>
    </div>

    <div class="card">
        <form id="templateForm" class="space-y-6">
            @csrf

            <x-input
                name="name"
                label="Template Name"
                placeholder="Modern Blog"
                required
            />

            <x-input
                name="slug"
                label="Slug"
                placeholder="modern-blog"
                required
                hint="Lowercase letters, numbers and hyphens only"
            />

            <x-textarea
                name="description"
                label="Description"
                placeholder="A clean, minimal blog layout with hero and recent posts"
                rows="3"
            />

            <x-input
                name="demo_url"
                label="Demo URL (optional)"
                placeholder="https://demo.example.com/modern-blog"
                type="url"
            />

            <div class="border-t border-default pt-6">
                <h2 class="text-lg font-semibold text-primary mb-4">Design Tokens</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <x-input name="primaryColor" label="Primary Color" type="color" value="#90B250" required />
                    <x-input name="secondaryColor" label="Secondary Color" type="color" value="#BD927D" required />
                    <x-input name="accentColor" label="Accent Color" type="color" value="#E0BE75" required />
                    <x-input name="textColor" label="Text Color" type="color" value="#616B5E" required />
                    <x-input name="backgroundColor" label="Background Color" type="color" value="#F7F6F1" required />
                    <x-input name="headingFont" label="Heading Font" placeholder="Instrument Sans" required />
                    <x-input name="bodyFont" label="Body Font" placeholder="Instrument Sans" required />
                </div>
            </div>

            <div class="border-t border-default pt-6">
                <h2 class="text-lg font-semibold text-primary mb-4">Sections</h2>
                <div id="sections" class="space-y-4"></div>
                <button type="button" onclick="addSection()" class="btn-secondary mt-4">Add Section</button>
            </div>

            <div class="flex items-center gap-3 pt-4 border-t border-default">
                <button type="submit" class="btn-primary">Save Draft</button>
                <a href="{{ route('admin.templates.index') }}" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let sectionCount = 0;
const availableSections = ['hero', 'recent_posts', 'featured_post', 'categories', 'about', 'newsletter', 'footer'];

function addSection() {
    const container = document.getElementById('sections');
    const id = ++sectionCount;
    const div = document.createElement('div');
    div.className = 'card';
    div.innerHTML = `
        <div class="flex items-start gap-4">
            <div class="flex-1 space-y-3">
                <input type="text" name="sections[${id}][key]" placeholder="Section key (e.g. hero-1)" class="input" required />
                <select name="sections[${id}][section]" class="input" required>
                    <option value="">-- Choose section type --</option>
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
    const sections = [];

    for (let i = 1; i <= sectionCount; i++) {
        const key = formData.get(`sections[${i}][key]`);
        const section = formData.get(`sections[${i}][section]`);
        if (key && section) sections.push({ key, section, props: {} });
    }

    if (sections.length === 0) {
        alert('Add at least one section');
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

    const r = await fetch('{{ route('admin.templates.store') }}', {
        method: 'POST',
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

// Add one section by default
addSection();
</script>
@endpush
@endsection
