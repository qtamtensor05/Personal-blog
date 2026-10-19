@extends ('layouts.app')

@section ('title', __('templates.create_title') . ' - ' . __('admin.console'))

@section ('content')
    <div class="container-custom section-spacing max-w-6xl">
        <div class="mb-8">
            <h1 class="text-hero mb-2">{{ __('templates.create_title') }}</h1>
            <p class="text-secondary">{{ __('templates.create_help') }}</p>
        </div>

        @include ('admin.templates._editor')

        <div
            class="grid hidden items-start gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(22rem,0.8fr)]"
            style="display: none"
        >
            <div class="card">
                <div id="templateFormError" class="mb-6 hidden" role="alert"></div>
                <form id="templateForm" class="space-y-6">
                    @csrf

                    <x-input name="name" :label="__('templates.old_name')" :placeholder="__('templates.old_modern')" required />

                    <x-input
                        name="slug"
                        :label="__('templates.slug')"
                        placeholder="modern-blog"
                        required
                        :hint="__('templates.slug_hint')"
                    />

                    <x-textarea
                        name="description"
                        :label="__('templates.description_label')"
                        :placeholder="__('templates.old_description')"
                        rows="3"
                    />

                    <x-input
                        name="demo_url"
                        :label="__('templates.old_demo_url')"
                        readonly
                        :hint="__('templates.old_demo_hint')"
                    />

                    <div class="border-default border-t pt-6">
                        <h2 class="text-primary mb-4 text-lg font-semibold">{{ __('templates.design_tokens') }}</h2>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input
                                name="primaryColor"
                                :label="__('templates.primary_color')"
                                type="color"
                                value="#90B250"
                                required
                            />
                            <x-input
                                name="secondaryColor"
                                :label="__('templates.secondary_color')"
                                type="color"
                                value="#BD927D"
                                required
                            />
                            <x-input
                                name="accentColor"
                                :label="__('templates.accent_color')"
                                type="color"
                                value="#E0BE75"
                                required
                            />
                            <x-input
                                name="textColor"
                                :label="__('templates.text_color')"
                                type="color"
                                value="#616B5E"
                                required
                            />
                            <x-input
                                name="backgroundColor"
                                :label="__('templates.background_color')"
                                type="color"
                                value="#F7F6F1"
                                required
                            />
                            <div class="space-y-1">
                                <label
                                    for="headingFont"
                                    class="text-primary block text-sm font-medium"
                                    >{{ __('templates.heading_font') }}</label
                                >
                                <select name="headingFont" id="headingFont" class="input" required>
                                    @foreach ([
                                            'Instrument Sans',
                                            'DM Sans',
                                            'Manrope',
                                            'Merriweather',
                                            'Playfair Display',
                                            'Space Grotesk'
                                        ]
                                        as $font)
                                        <option value="{{ $font }}">{{ $font }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label for="bodyFont" class="text-primary block text-sm font-medium"
                                    >{{ __('templates.body_font') }}</label
                                >
                                <select name="bodyFont" id="bodyFont" class="input" required>
                                    @foreach ([
                                            'Instrument Sans',
                                            'DM Sans',
                                            'Manrope',
                                            'Source Serif 4',
                                            'Merriweather',
                                            'Space Grotesk'
                                        ]
                                        as $font)
                                        <option value="{{ $font }}">{{ $font }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="border-default border-t pt-6">
                        <h2 class="text-primary mb-1 text-lg font-semibold">{{ __('templates.sections') }}</h2>
                        <p class="text-secondary mb-4 text-sm">{{ __('templates.sections_help') }}</p>
                        <div id="sections" class="space-y-4"></div>
                        <button type="button" onclick="addSection()" class="btn-secondary mt-4">
                            {{ __('templates.add_section') }}
                        </button>
                    </div>

                    <div class="border-default flex items-center gap-3 border-t pt-4">
                        <button type="submit" class="btn-primary rounded-md px-4 py-2">
                            {{ __('templates.save') }}
                        </button>
                        <a href="{{ route('admin.templates.index') }}" class="btn-ghost">{{ __('templates.cancel') }}</a>
                    </div>
                </form>
            </div>
            <aside class="lg:sticky lg:top-6">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-secondary text-sm font-semibold tracking-wide uppercase">
                        {{ __('templates.live_review') }}
                    </h2>
                    <span class="text-secondary text-xs">{{ __('templates.draft_config') }}</span>
                </div>
                <div id="templatePreview" class="card overflow-hidden p-0">
                    <div
                        id="previewHeader"
                        class="p-6"
                        style="
                            background: #f7f6f1;
                            color: #616b5e;
                            font-family: 'Instrument Sans', sans-serif;
                        "
                    >
                        <p id="previewEyebrow" class="text-xs tracking-widest uppercase opacity-70">{{ __('templates.preview') }}</p>
                        <h2 id="previewTitle" class="mt-2 text-3xl font-semibold">{{ __('templates.old_modern') }}</h2>
                        <p id="previewDescription" class="mt-2 text-sm opacity-80">{{ __('templates.description_preview') }}</p>
                    </div>
                    <div
                        id="previewSections"
                        class="space-y-2 p-4"
                        style="background: #fff; color: #616b5e"
                    ></div>
                </div>
            </aside>
        </div>
    </div>

    @push ('scripts')
        <script>
            let sectionCount = 0;
            const availableSections = [
                ['hero', @js(__('templates.old_hero'))],
                ['recent_posts', @js(__('templates.old_recent_posts'))],
                ['featured_post', @js(__('templates.old_featured_post'))],
                ['categories', @js(__('templates.old_categories'))],
                ['about', @js(__('templates.old_about'))],
                ['newsletter', @js(__('templates.old_newsletter'))],
                ['image', @js(__('templates.old_image'))],
                ['footer', @js(__('templates.old_footer'))],
            ];

            const slugify = (value) =>
                value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-|-$/g, '');
            let slugEdited = false;
            const nameInput = document.querySelector('[name=name]');
            const slugInput = document.querySelector('[name=slug]');
            const demoInput = document.querySelector('[name=demo_url]');
            const formError = document.getElementById('templateFormError');
            const showFormError = (message) => {
                formError.textContent = message;
                formError.className =
                    'mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';
                window.showErrorModal?.(message);
            };
            const updateDemoUrl = () => {
                demoInput.value = `${window.location.origin}/template-preview/${slugInput.value || 'untitled'}`;
            };

            nameInput.addEventListener('input', () => {
                if (!slugEdited) slugInput.value = slugify(nameInput.value);
                updateDemoUrl();
                updatePreview();
            });
            slugInput.addEventListener('input', () => {
                slugEdited = true;
                updateDemoUrl();
                updatePreview();
            });
            updateDemoUrl();

            function addSection() {
                const container = document.getElementById('sections');
                const id = ++sectionCount;
                const div = document.createElement('div');
                div.className = 'card';
                div.innerHTML = `
                    <div class="flex items-start gap-4">
                        <div class="flex-1 space-y-3">
                            <input type="text" name="sections[${id}][key]" placeholder="${@js(__('templates.section_key_hint'))}" class="input" required />
                            <select name="sections[${id}][section]" class="input" required>
                                <option value="">${@js(__('templates.choose_section'))}</option>
                                ${availableSections.map(([value, label]) => `<option value="${value}">${label}</option>`).join('')}
                            </select>
                            <div class="hidden space-y-3 rounded-md border border-default p-3" data-image-fields>
                                <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-template-image class="input" />
                                <input type="hidden" name="sections[${id}][props][mediaId]" data-template-media-id />
                                <input type="text" name="sections[${id}][props][alt]" placeholder="${@js(__('templates.image_description'))}" class="input" />
                                <select name="sections[${id}][props][position]" class="input">
                                    <option value="full">${@js(__('templates.full_width'))}</option>
                                    <option value="left">${@js(__('templates.align_left'))}</option>
                                    <option value="right">${@js(__('templates.align_right'))}</option>
                                </select>
                                <p class="text-xs text-secondary" data-upload-status>${@js(__('templates.choose_image'))}</p>
                            </div>
                        </div>
                        <button type="button" onclick="this.closest('.card').remove()" class="text-red-500 hover:text-red-600 text-sm">${@js(__('templates.remove'))}</button>
                    </div>
                `;
                container.appendChild(div);
                const type = div.querySelector('select[name$="[section]"]');
                const imageFields = div.querySelector('[data-image-fields]');
                type.addEventListener('change', () => {
                    imageFields.classList.toggle('hidden', type.value !== 'image');
                    updatePreview();
                });
                div.querySelector('[data-template-image]').addEventListener('change', (event) =>
                    uploadTemplateImage(event.target, div),
                );
                div.querySelectorAll('input, select').forEach((field) =>
                    field.addEventListener('input', updatePreview),
                );
                updatePreview();
            }

            async function uploadTemplateImage(input, section) {
                const file = input.files[0];
                if (!file) return;
                const status = section.querySelector('[data-upload-status]');
                const form = new FormData();
                form.append('file', file);
                form.append('scope', 'PLATFORM');
                status.textContent = @js(__('templates.uploading_image'));
                try {
                    const response = await fetch('{{ route('media.store') }}', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: form,
                    });
                    const body = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(body.message || @js(__('templates.image_upload_failed')));
                    section.querySelector('[data-template-media-id]').value = body.data.id;
                    status.textContent = @js(__('templates.image_uploaded'));
                    updatePreview();
                } catch (error) {
                    status.textContent = error.message;
                    window.showErrorModal?.(error.message);
                }
            }

            function updatePreview() {
                const form = document.getElementById('templateForm');
                const data = new FormData(form);
                const preview = document.getElementById('templatePreview');
                document.getElementById('previewTitle').textContent = data.get('name') || @js(__('templates.untitled'));
                document.getElementById('previewDescription').textContent =
                    data.get('description') || @js(__('templates.description_preview'));
                const header = document.getElementById('previewHeader');
                header.style.background = data.get('backgroundColor') || '#F7F6F1';
                header.style.color = data.get('textColor') || '#616B5E';
                header.style.fontFamily = `'${data.get('headingFont') || 'Instrument Sans'}', sans-serif`;
                document.getElementById('previewSections').innerHTML = Array.from(
                    document.querySelectorAll('[name^="sections["][name$="[section]"]'),
                )
                    .map(
                        (field, index) =>
                            `<div class="rounded-md border border-default px-3 py-3 text-sm" style="border-left:4px solid ${data.get('accentColor') || '#E0BE75'}"><strong>${index + 1}. ${field.options[field.selectedIndex]?.text || @js(__('templates.untitled_section'))}</strong><span class="block text-xs opacity-70">${field.value || @js(__('templates.choose_section_type'))}</span></div>`,
                    )
                    .join('');
            }

            document.getElementById('templateForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                const sections = [];

                for (let i = 1; i <= sectionCount; i++) {
                    const key = formData.get(`sections[${i}][key]`);
                    const section = formData.get(`sections[${i}][section]`);
                    const props = {};
                    if (section === 'image') {
                        props.mediaId = formData.get(`sections[${i}][props][mediaId]`) || null;
                        props.alt = formData.get(`sections[${i}][props][alt]`) || 'Template image';
                        props.position = formData.get(`sections[${i}][props][position]`) || 'full';
                    }
                    if (key && section) sections.push({ key, section, props });
                }

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
                        },
                    },
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
                    const err = await r.json().catch(() => ({}));
                    showFormError(
                        err.message || 'The template could not be saved. Check the form and try again.',
                    );
                }
            });

            // Add one section by default
            addSection();
            updatePreview();
        </script>
    @endpush
@endsection
