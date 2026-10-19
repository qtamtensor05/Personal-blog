@extends ('layouts.app')

@section ('title', 'Templates — Admin Console')

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8">
            <div class="mb-4 flex items-center justify-between rounded-md">
                <h1 class="text-hero">Templates</h1>
                <a
                    href="{{ route('admin.templates.create') }}"
                    class="btn-primary flex items-center justify-center gap-2 rounded-md px-4 py-2"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Template
                </a>
            </div>
            <p class="text-secondary">Manage blog templates that users can choose and customize.</p>
        </div>

        <div id="templateActionError" class="mb-6 hidden" role="alert"></div>

        @if (count($templates) === 0)
            <x-empty-state message="No templates yet">
                Create the first template for users to pick when designing their blog.
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-secondary border-default border-b">
                        <tr>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                Name
                            </th>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                Slug
                            </th>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                Status
                            </th>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                Version
                            </th>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                Updated
                            </th>
                            <th class="text-primary px-4 py-3 text-right text-sm font-semibold">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-surface divide-default divide-y">
                        @foreach ($templates as $template)
                            <tr class="hover:bg-surface-secondary transition-colors">
                                <td class="px-4 py-3">
                                    <div class="text-primary font-medium">
                                        {{ $template->name }}
                                    </div>
                                    @if ($template->description)
                                        <div class="text-secondary mt-1 max-w-md text-sm">
                                            {{
                                                Str::limit(
                                                    $template->description,
                                                    80,
                                                )
                                            }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-secondary px-4 py-3 font-mono text-sm">
                                    {{ $template->slug }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusBadge = match ($template->status->value) {
                                            'PUBLISHED' => 'primary',
                                            'DRAFT' => 'default',
                                            'HIDDEN', 'RETIRED' => 'highlight',
                                        };
                                    @endphp
                                    <x-badge
                                        :variant="$statusBadge"
                                        >{{ $template->status->value }}</x-badge
                                    >
                                </td>
                                <td class="text-secondary px-4 py-3 text-sm">
                                    v{{ $template->versionNumber }}
                                </td>
                                <td class="text-secondary px-4 py-3 text-sm">
                                    {{
                                        \Carbon\Carbon::parse(
                                            $template->publishedAt ?? now(),
                                        )->diffForHumans()
                                    }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            href="{{ route('templates.preview', $template->slug) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-accent-primary text-sm hover:underline"
                                        >
                                            Xem template
                                        </a>
                                        <a
                                            href="{{ route('admin.templates.edit', $template->id) }}"
                                            class="text-accent-primary text-sm hover:underline"
                                        >
                                            Edit
                                        </a>
                                        @if ($template->draftData !== null && $template->status->value === 'DRAFT')
                                            <button
                                                onclick="publishTemplate({{ $template->id }})"
                                                class="text-accent-primary text-sm hover:underline"
                                            >
                                                Publish
                                            </button>
                                        @endif
                                        <button
                                            onclick="deleteTemplate({{ $template->id }}, '{{ addslashes($template->name) }}')"
                                            class="text-sm text-red-500 hover:underline"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if (count($deletedTemplates) > 0)
            <section class="mt-10">
                <div class="mb-4">
                    <h2 class="text-primary text-xl font-semibold">Recently deleted</h2>
                    <p class="text-secondary text-sm">Restore a template or permanently remove it and its category assignments.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-surface-secondary border-default border-b">
                            <tr>
                                <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                    Name
                                </th>
                                <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                    Deleted
                                </th>
                                <th class="text-primary px-4 py-3 text-right text-sm font-semibold">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-surface divide-default divide-y">
                            @foreach ($deletedTemplates as $template)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="text-primary font-medium">
                                            {{ $template->name }}
                                        </div>
                                        <div class="text-secondary font-mono text-xs">
                                            {{ $template->slug }}
                                        </div>
                                    </td>
                                    <td class="text-secondary px-4 py-3 text-sm">
                                        {{
                                            \Carbon\Carbon::parse(
                                                $template->deletedAt ?? now(),
                                            )->diffForHumans()
                                        }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex justify-end gap-3">
                                            <button
                                                type="button"
                                                onclick="restoreTemplate({{ $template->id }})"
                                                class="text-accent-primary text-sm hover:underline"
                                            >
                                                Restore
                                            </button>
                                            <button
                                                type="button"
                                                onclick="permanentlyDeleteTemplate({{ $template->id }}, '{{ addslashes($template->name) }}')"
                                                class="text-sm text-red-500 hover:underline"
                                            >
                                                Delete permanently
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    @push ('scripts')
        <script>
            function showTemplateActionError(message) {
                const box = document.getElementById('templateActionError');
                box.textContent = message;
                box.className =
                    'mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700';
                window.showErrorModal?.(message);
            }

            async function publishTemplate(id) {
                const confirmed = await window.requestModalConfirmation(
                    'Publish template',
                    'Publish this template? Users will be able to choose it for their blogs.',
                );
                if (!confirmed) return;

                fetch(`/admin/templates/${id}/publish`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ status: 'PUBLISHED' }),
                })
                    .then((r) =>
                        r.ok
                            ? location.reload()
                            : r
                                  .json()
                                  .then((e) =>
                                      showTemplateActionError(
                                          e.message || 'The template could not be published.',
                                      ),
                                  ),
                    )
                    .catch(() => showTemplateActionError('Network error. Please try again.'));
            }

            async function deleteTemplate(id, name) {
                const confirmed = await window.requestModalConfirmation(
                    'Delete template',
                    `Delete template "${name}"? This cannot be undone.`,
                );
                if (!confirmed) return;

                fetch(`/admin/templates/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                })
                    .then((r) =>
                        r.ok
                            ? location.reload()
                            : r
                                  .json()
                                  .then((e) =>
                                      showTemplateActionError(
                                          e.message || 'The template could not be deleted.',
                                      ),
                                  ),
                    )
                    .catch(() => showTemplateActionError('Network error. Please try again.'));
            }

            function restoreTemplate(id) {
                fetch(`/admin/templates/${id}/restore`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                })
                    .then((r) =>
                        r.ok
                            ? location.reload()
                            : r
                                  .json()
                                  .then((e) =>
                                      showTemplateActionError(
                                          e.message || 'The template could not be restored.',
                                      ),
                                  ),
                    )
                    .catch(() => showTemplateActionError('Network error. Please try again.'));
            }

            async function permanentlyDeleteTemplate(id, name) {
                const confirmed = await window.requestModalConfirmation(
                    'Delete template permanently',
                    `Permanently delete template "${name}"? This cannot be undone.`,
                );
                if (!confirmed) return;

                fetch(`/admin/templates/${id}/permanent`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                })
                    .then((r) =>
                        r.ok
                            ? location.reload()
                            : r
                                  .json()
                                  .then((e) =>
                                      showTemplateActionError(
                                          e.message ||
                                              'The template could not be permanently deleted.',
                                      ),
                                  ),
                    )
                    .catch(() => showTemplateActionError('Network error. Please try again.'));
            }
        </script>
    @endpush
@endsection
