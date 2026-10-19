@extends ('layouts.app')

@section ('title', __('templates.admin_index') . ' - ' . __('admin.console'))

@section ('content')
    <div class="container-custom section-spacing">
        <div class="mb-8">
            <div class="mb-4 flex items-center justify-between rounded-md">
                <h1 class="text-hero">{{ __('templates.admin_index') }}</h1>
                <a
                    href="{{ route('admin.templates.create') }}"
                    class="btn-primary flex items-center justify-center gap-2 rounded-md px-4 py-2"
                >
                    <i class="fa-solid fa-plus h-5 w-5" aria-hidden="true"></i>
                    {{ __('templates.new') }}
                </a>
            </div>
            <p class="text-secondary">{{ __('templates.admin_manage_help') }}</p>
        </div>

        <div id="templateActionError" class="mb-6 hidden" role="alert"></div>

        @if (count($templates) === 0)
            <x-empty-state :message="__('templates.admin_empty')">
                {{ __('templates.admin_empty_help') }}
            </x-empty-state>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-surface-secondary border-default border-b">
                        <tr>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                {{ __('templates.name') }}
                            </th>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                {{ __('templates.slug') }}
                            </th>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                {{ __('templates.status') }}
                            </th>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                {{ __('templates.version') }}
                            </th>
                            <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                {{ __('templates.updated') }}
                            </th>
                            <th class="text-primary px-4 py-3 text-right text-sm font-semibold">
                                {{ __('templates.actions') }}
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
                                            {{ __('templates.view') }}
                                        </a>
                                        <a
                                            href="{{ route('admin.templates.edit', $template->id) }}"
                                            class="text-accent-primary text-sm hover:underline"
                                        >
                                            {{ __('templates.edit') }}
                                        </a>
                                        @if ($template->draftData !== null && $template->status->value === 'DRAFT')
                                            <button
                                                onclick="publishTemplate({{ $template->id }})"
                                                class="text-accent-primary text-sm hover:underline"
                                            >
                                                {{ __('templates.publish') }}
                                            </button>
                                        @endif
                                        <button
                                            onclick="deleteTemplate({{ $template->id }}, '{{ addslashes($template->name) }}')"
                                            class="text-sm text-red-500 hover:underline"
                                        >
                                            {{ __('templates.delete') }}
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
                    <h2 class="text-primary text-xl font-semibold">{{ __('templates.deleted') }}</h2>
                    <p class="text-secondary text-sm">{{ __('templates.deleted_help') }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-surface-secondary border-default border-b">
                            <tr>
                                <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                    {{ __('templates.name') }}
                                </th>
                                <th class="text-primary px-4 py-3 text-left text-sm font-semibold">
                                    {{ __('templates.deleted_at') }}
                                </th>
                                <th class="text-primary px-4 py-3 text-right text-sm font-semibold">
                                    {{ __('templates.actions') }}
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
                                                {{ __('templates.restore') }}
                                            </button>
                                            <button
                                                type="button"
                                                onclick="permanentlyDeleteTemplate({{ $template->id }}, '{{ addslashes($template->name) }}')"
                                                class="text-sm text-red-500 hover:underline"
                                            >
                                                {{ __('templates.delete_forever') }}
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
                    @js(__('templates.publish_confirm_title')),
                    @js(__('templates.publish_confirm')),
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
                                          e.message || @js(__('templates.publish_error')),
                                      ),
                                  ),
                    )
                    .catch(() => showTemplateActionError(@js(__('ui.network_error'))));
            }

            async function deleteTemplate(id, name) {
                const confirmMessage = @js(__('templates.delete_confirm', ['name' => ':name'])).replace(':name', name);
    
                const confirmed = await window.requestModalConfirmation(
                    @js(__('templates.delete')),
                    confirmMessage
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
                                          e.message || @js(__('templates.delete_error')),
                                      ),
                                  ),
                    )
                    .catch(() => showTemplateActionError(@js(__('ui.network_error'))));
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
                                          e.message || @js(__('templates.restore_error')),
                                      ),
                                  ),
                    )
                    .catch(() => showTemplateActionError(@js(__('ui.network_error'))));
            }

            async function permanentlyDeleteTemplate(id, name) {
                const confirmed = await window.requestModalConfirmation(
                    @js(__('templates.delete_forever')),
                    @js(__('templates.delete_forever_confirm', ['name' => ':name'])).replace(':name', name),
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
                                              @js(__('templates.delete_error')),
                                      ),
                                  ),
                    )
                    .catch(() => showTemplateActionError(@js(__('ui.network_error'))));
            }
        </script>
    @endpush
@endsection
