@props(['id' => 'appModal', 'title' => 'Notice'])

<div id="{{ $id }}" class="fixed inset-0 z-100 hidden items-center justify-center bg-black/50 px-4" data-modal aria-hidden="true">
    <div class="w-full max-w-md rounded-lg border border-default bg-surface p-6 shadow-2xl" role="alertdialog" aria-modal="true" aria-labelledby="{{ $id }}-title" tabindex="-1">
        <div class="flex items-start justify-between gap-4">
            <h2 id="{{ $id }}-title" class="text-lg font-semibold text-primary">{{ $title }}</h2>
            <button type="button" class="btn-ghost -mr-2 -mt-2 p-2" data-modal-close aria-label="Close">&times;</button>
        </div>
        <p class="mt-3 text-sm text-secondary" data-modal-message></p>
        <input type="text" class="input mt-4 hidden" data-modal-input>
        <div class="mt-6 flex justify-end">
            <button type="button" class="btn-ghost px-4 py-2 hidden" data-modal-cancel>Cancel</button>
            <button type="button" class="btn-primary px-4 py-2" data-modal-confirm>Close</button>
        </div>
    </div>
</div>
