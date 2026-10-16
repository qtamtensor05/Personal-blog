@extends('layouts.app')

@section('title', 'Templates — Admin Console')

@section('content')
<div class="container-custom section-spacing">
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-hero">Templates</h1>
            <a href="{{ route('admin.templates.create') }}" class="btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Template
            </a>
        </div>
        <p class="text-secondary">Manage blog templates that users can choose and customize.</p>
    </div>

    @if(count($templates) === 0)
        <x-empty-state message="No templates yet">
            Create the first template for users to pick when designing their blog.
        </x-empty-state>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-surface-secondary border-b border-default">
                    <tr>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-primary">Name</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-primary">Slug</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-primary">Status</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-primary">Version</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-primary">Updated</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-primary">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-surface divide-y divide-default">
                    @foreach($templates as $template)
                        <tr class="hover:bg-surface-secondary transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-medium text-primary">{{ $template->name }}</div>
                                @if($template->description)
                                    <div class="text-sm text-secondary mt-1 max-w-md">{{ Str::limit($template->description, 80) }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-sm text-secondary font-mono">{{ $template->slug }}</td>
                            <td class="py-3 px-4">
                                @php
                                    $statusBadge = match($template->status->value) {
                                        'PUBLISHED' => 'primary',
                                        'DRAFT' => 'default',
                                        'HIDDEN', 'RETIRED' => 'highlight',
                                    };
                                @endphp
                                <x-badge :variant="$statusBadge">{{ $template->status->value }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-sm text-secondary">v{{ $template->versionNumber }}</td>
                            <td class="py-3 px-4 text-sm text-secondary">
                                {{ \Carbon\Carbon::parse($template->publishedAt ?? now())->diffForHumans() }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.templates.edit', $template->id) }}"
                                       class="text-sm text-accent-primary hover:underline">
                                        Edit
                                    </a>
                                    @if($template->draftData !== null && $template->status->value === 'DRAFT')
                                        <button
                                            onclick="publishTemplate({{ $template->id }})"
                                            class="text-sm text-accent-primary hover:underline">
                                            Publish
                                        </button>
                                    @endif
                                    <button
                                        onclick="deleteTemplate({{ $template->id }}, '{{ addslashes($template->name) }}')"
                                        class="text-sm text-red-500 hover:underline">
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
</div>

@push('scripts')
<script>
function publishTemplate(id) {
    if (!confirm('Publish this template? Users will be able to choose it for their blogs.')) return;

    fetch(`/admin/templates/${id}/publish`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ status: 'PUBLISHED' }),
    })
    .then(r => r.ok ? location.reload() : r.json().then(e => alert(e.message || 'Failed')))
    .catch(() => alert('Network error'));
}

function deleteTemplate(id, name) {
    if (!confirm(`Delete template "${name}"? This cannot be undone.`)) return;

    fetch(`/admin/templates/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    })
    .then(r => r.ok ? location.reload() : r.json().then(e => alert(e.message || 'Failed')))
    .catch(() => alert('Network error'));
}
</script>
@endpush
@endsection
